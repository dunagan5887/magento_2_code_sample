<?php
/**
 * Author: Sean Dunagan (github: dunagan5887)
 * Date: 10/31/16
 */

namespace Dunagan\MagentoFixes\Helper\Customer;

/**
 * Built out to allow for di.xml overrides
 *
 * Interface AddressFormatPopulaterInterface
 * @package Dunagan\MagentoFixes\Helper\Customer
 */
interface AddressFormatPopulaterInterface
{
    /**
     * Repopulate the address with data based on the format passed in. Will replace null values with the empty string
     *  based on the format passed in
     *
     * @param \Magento\Sales\Model\Order\Address $address
     * @param string $format
     * @return void
     */
    public function repopulateNullAddressDataPoints($address, $format);
}
