<?php
/**
 * Author: Sean Dunagan (github: dunagan5887)
 * Date: 1/26/17
 */

namespace Dunagan\WebApi\Framework\HTTP\Client;

/**
 * Class Curl
 * @package Dunagan\WebApi\Framework\HTTP\Client
 */
class Curl extends \Magento\Framework\HTTP\Client\Curl
{
    /**
     * Make request
     * This method is publicly scoped to allow for making PUT/DELETE calls
     *
     * @param string $method
     * @param string $uri
     * @param array $params
     * @return void
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    public function makeMethodRequest($method, $uri, $params = [])
    {
        $this->makeRequest($method, $uri, $params);
    }
}
