<?php
/**
 * Author: Sean Dunagan (github: dunagan5887)
 * Date: 1/26/17
 */

namespace Dunagan\WebApi\Model\Client;

/**
 * Class Curl
 * @package Dunagan\WebApi\Model\Client
 */
class Curl extends AbstractClient
{
    const ERROR_STATUS_NOT_OK = 'Request returned a status of %1';
    const ERROR_NO_URL_DEFINED = 'No url was defined for a %1 cURL request';

    /**
     * @var string
     */
    protected $_curl_client_classname = 'Dunagan\WebApi\Framework\HTTP\Client\Curl';

    /**
     * {@inheritdoc}
     *
     * In terms of CURL requests, the $method field here will be HTTP method being called e.g. GET, POST, PUT, DELETE
     * $parameters['url'] needs to contain the url to be called
     * TODO Need to set curl cookies/headers/credentials/options based on the data passed in in $parameters
     */
    public function executeApiCall($method, $parameters)
    {
        $curlClient = $this->_objectManager->create($this->_curl_client_classname);
        /* @var \Dunagan\WebApi\Framework\HTTP\Client\Curl $curlClient */
        $url_to_call = isset($parameters['url']) ? $parameters['url'] : null;
        if (empty($url_to_call))
        {
            $error_message = __(self::ERROR_NO_URL_DEFINED, $method);
            throw new \Dunagan\WebApi\Model\Exception\Service\Transmission\RequiredArguments($error_message);
        }
        $curl_request_parameters = isset($parameters['curl_params']) ? $parameters['curl_params'] : [];
        $curlClient->makeMethodRequest($method, $url_to_call, $curl_request_parameters);
        $status = $curlClient->getStatus();
        if ($status != '200')
        {
            $error_message = __(self::ERROR_STATUS_NOT_OK, $status);
            throw new \Dunagan\WebApi\Model\Exception\Http($error_message);
        }

        $response = $curlClient->getBody();
        return $response;
    }
}
