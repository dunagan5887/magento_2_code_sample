<?php
/**
 * Author: Sean Dunagan (github: dunagan5887)
 * Date: 10/31/16
 */

namespace Dunagan\MagentoFixes\Observer\Customer;

use Magento\Framework\Event\ObserverInterface;

/**
 * Class AddressFormatObserver
 * @package Dunagan\MagentoFixes\Observer\Customer
 */
class AddressFormatObserver implements ObserverInterface
{
    /**
     * @var \Dunagan\MagentoFixes\Helper\Customer\AddressFormatPopulaterInterface
     */
    protected $_addressFormatPopulater;

    /**
     * @param \Dunagan\MagentoFixes\Helper\Customer\AddressFormatPopulaterInterface $addressFormatPopulater
     */
    public function __construct(\Dunagan\MagentoFixes\Helper\Customer\AddressFormatPopulaterInterface $addressFormatPopulater)
    {
        $this->_addressFormatPopulater = $addressFormatPopulater;
    }

    /**
     * If the order is virtual, there is a chance that there is no billing address data depending on the payment method
     *  which was used. As such, if this address is the address for a virtual order, we will need to replace all data
     *  values which are null with an empty string
     *
     * @param \Magento\Framework\Event\Observer $observer
     * @return $this
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        // Detect if this is a virtual order being rendered
        $address = $observer->getData('address');
        // Check to see if this address has an order set on it
        $order = $address->getOrder();
        /* @var \Magento\Sales\Model\Order $order */
        if (is_object($order) && $order->getId())
        {
            // Check to see if the order is virtual
            $order_is_virtual = $order->getIsVirtual();
            if ($order_is_virtual)
            {
                // Need to update the address, replacing all null values with empty string values
                // However, we only want to update the values which are set as var's in the format
                $default_format = $observer->getData('type')->getData('default_format');
                $this->_addressFormatPopulater->repopulateNullAddressDataPoints($address, $default_format);
            }
        }
    }
}
