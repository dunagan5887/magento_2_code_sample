<?php
/**
 * Author: Sean Dunagan (github: dunagan5887)
 * Date: 10/31/16
 */

namespace Dunagan\MagentoFixes\Helper\Customer;

/**
 * Class AddressFormatPopulater
 * @package Dunagan\MagentoFixes\Helper\Customer
 */
class AddressFormatPopulater implements AddressFormatPopulaterInterface
{
    const VAR_DIRECTIVE_REGEX_TEMPLATE = '#{{var ([0-9a-zA-Z_]*)}}#';

    /**
     * {@inheritdoc}
     */
    public function repopulateNullAddressDataPoints($address, $format)
    {
        // Need to get the "var" directives from the $format string
        $preg_match_return = array();
        $number_of_matches = preg_match_all(self::VAR_DIRECTIVE_REGEX_TEMPLATE, $format, $preg_match_return);
        if (!$number_of_matches)
        {
            // Either there were no matches returned, or an error occurred
            return;
        }

        $var_directives = reset($preg_match_return);
        // Index 1 of the array should be names of the variables
        $variables_array = next($preg_match_return);

        foreach($variables_array as $variable_to_repopulate)
        {
            $address_data_value = $address->getData($variable_to_repopulate);
            if (is_null($address_data_value))
            {
                $address->setData($variable_to_repopulate, '');
            }
        }
    }
}
