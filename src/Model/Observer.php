<?php

class KBariotis_CouponOnSubscription_Model_Observer
{

    public function newsletterSubscriberChange(Varien_Event_Observer $observer)
    {
        $subscriber = $observer->getEvent()
                               ->getSubscriber();

        if ($subscriber->getStatus() == Mage_Newsletter_Model_Subscriber::STATUS_SUBSCRIBED &&
            !$subscriber->isSubscribed()
        ) {
            $enabled = Mage::getStoreConfig('coupononsubscription/general/coupon_value');
            if ($enabled)
                $this->createCoupon($subscriber);
        }

    }

    public function createCoupon(Mage_Newsletter_Model_Subscriber $subscriber)
    {
        $discount           = Mage::getStoreConfig('coupononsubscription/general/coupon_value');
        $subscriberGroupIds = Mage::getModel('customer/group')
                                  ->getCollection()
                                  ->getAllIds();

        $labels = array(
            0 => Mage::helper('coupononsubscription')
                     ->__('Gift Voucher ' . $discount)
        );

        $email      = $subscriber->getEmail();
        $couponCode = Mage::helper('core')
                          ->getRandomString(9);

        $rule = Mage::getModel('salesrule/rule');
        $rule->setName($email)
             ->setDescription($rule->getName())
             ->setFromDate(date('Y-m-d'))
             ->setCouponCode($couponCode)
             ->setCustomerGroupIds($subscriberGroupIds)
             ->setIsActive(1)
             ->setSimpleAction(Mage_SalesRule_Model_Rule::BY_FIXED_ACTION)
             ->setDiscountAmount($discount)
             ->setDiscountQty(1)
             ->setStopRulesProcessing(0)
             ->setIsRss(0)
             ->setWebsiteIds(array(1))
             ->setCouponType(Mage_SalesRule_Model_Rule::COUPON_TYPE_SPECIFIC)
             ->setStoreLabels($labels)
             ->setUsesPerCustomer(1)
             ->setUsesPerCoupon(1)
             ->save();

        $this->sendCouponMail($rule, $email);

    }

    public function sendCouponMail(Mage_SalesRule_Model_Rule $rule, $email)
    {
        $emailTemplate = Mage::getModel('core/email_template')
                             ->loadDefault('coupon_code_on_subscription');

        $emailTemplateVariables = [
            'discount'   => $rule->getDiscountAmount(),
            'couponCode' => $rule->getCouponCode()
        ];

        $emailTemplate->getProcessedTemplate($emailTemplateVariables);

        $emailTemplate->setSenderName(Mage::getStoreConfig('trans_email/ident_general/email'));
        $emailTemplate->setSenderEmail(Mage::getStoreConfig('trans_email/ident_general/email'));
        $emailTemplate->setType('html');
        $emailTemplate->setTemplateSubject('Congratulations');

        $emailTemplate->send(
                      $email,
                      $emailTemplateVariables
        );
    }
}
