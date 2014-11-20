<?php

class KBariotis_CouponOnSubscription_Model_Subscriber extends Mage_Newsletter_Model_Subscriber
{

    /**
     * Sends out confirmation success email
     *
     * @return Mage_Newsletter_Model_Subscriber
     */
    public function sendConfirmationSuccessEmail()
    {

        $enabled = Mage::getStoreConfig('coupononsubscription/general/enabled');
        if (!$enabled)
            return parent::sendConfirmationSuccessEmail();

        if ($this->getImportMode())
            return $this;

        $translate = Mage::getSingleton('core/translate');
        /* @var $translate Mage_Core_Model_Translate */
        $translate->setTranslateInline(false);

        $email = Mage::getModel('core/email_template');

        if ($rule = $this->createCoupon($this->getEmail()))
            $email->sendTransactional(
                  'coupon_code_on_subscription',
                  Mage::getStoreConfig(self::XML_PATH_SUCCESS_EMAIL_IDENTITY),
                  $this->getEmail(),
                  $this->getName(),
                  array(
                      'subscriber' => $this,
                      'discount'   => $rule->getDiscountAmount(),
                      'couponCode' => $rule->getCouponCode()
                  )
            );
        else
            $email->sendTransactional(
                  Mage::getStoreConfig(self::XML_PATH_SUCCESS_EMAIL_TEMPLATE),
                  Mage::getStoreConfig(self::XML_PATH_SUCCESS_EMAIL_IDENTITY),
                  $this->getEmail(),
                  $this->getName(),
                  array('subscriber' => $this)
            );

        $translate->setTranslateInline(true);

        return $this;
    }

    public function createCoupon()
    {
        $email = $this->getEmail();

        $rule = Mage::getModel('salesrule/rule')
                    ->getCollection()
                    ->addFieldToFilter('name', $email);

        if ($rule->getSize() > 0)
            return false;

        $discount           = Mage::getStoreConfig('coupononsubscription/general/coupon_value');
        $subscriberGroupIds = Mage::getModel('customer/group')
                                  ->getCollection()
                                  ->getAllIds();
        $currency           = Mage::app()
                                  ->getLocale()
                                  ->currency(Mage::app()
                                                 ->getStore()
                                                 ->getCurrentCurrencyCode())
                                  ->getSymbol();
        $labels             = array(
            0 => Mage::helper('coupononsubscription')
                     ->__($discount . $currency . ' Gift Voucher')
        );
        $couponCode         = Mage::helper('core')
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

        return $rule;

    }

}
