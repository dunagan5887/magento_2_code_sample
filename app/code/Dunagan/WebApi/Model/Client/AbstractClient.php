<?php
/**
 * Author: Sean Dunagan (github: dunagan5887)
 * Date: 9/18/16
 */

namespace Dunagan\WebApi\Model\Client;

use Dunagan\WebApi\Model\ClientInterface;

/**
 * This is an abstract class implementing functionality common to API Clients of any kind
 *
 * Class AbstractClient
 * @package Dunagan\WebApi\Model\Client
 */
abstract class AbstractClient implements ClientInterface
{
    const EXCEPTION_EXECUTING_SOAP_API_CALL = "An exception occurred while executing API call %1 with API client model %2 and parameters %3:\nDetail: %5\nError Message: %4";
    const EXCEPTION_EXECUTING_API_CALL = "An exception occurred while executing API call %1 with API client model %2 and parameters %3: %4";
    const DEBUG_TIME_TO_EXECUTE_MESSAGE = 'Method %1 took %2 seconds to execute';
    const DEBUG_API_CALL_RESPONSE = "Method: %1 was successfully called\nParameters: %2\nResponse: %3";
    const DEBUG_API_EXCEPTION_RESPONSE = "Method: %1 threw an exception when calling\nParameters: %2\nResponse: %3";

    /**
     * @var \Magento\Framework\ObjectManagerInterface
     */
    protected $_objectManager;

    /**
     * @var string
     */
    protected $_configuration_section_id = 'dunagan_web_api_configuration';

    /**
     * Config helper used to access API credential and connection settings
     *
     * @var \Dunagan\WebApi\Helper\Config
     */
    protected $_webApiConfigHelper;

    /**
     * The object which is used to log errors.
     * This will be an object of the type defined by this class's $_logger_di_classname instance field
     *
     * @var \Psr\Log\LoggerInterface
     */
    protected $_logger;

    /**
     * The classname of the object to be used to log errors.
     * This is defined to allow subclasses to define their own logger classes
     *
     * @var string
     */
    protected $_logger_di_classname = '\Dunagan\WebApi\Model\LoggerInterface';

    /**
     * Classname of the object to store the api call result on and return to the calling block
     *
     * @var string
     */
    protected $_api_call_result_object_classname = '\Dunagan\WebApi\Model\ResultInterface';

    /**
     * Executes the API call
     *
     * @param string $method
     * @param array $parameters
     * @return mixed
     */
    abstract public function executeApiCall($method, $parameters);

    /**
     * AbstractClient constructor.
     * @param \Magento\Framework\ObjectManagerInterface $objectManager
     * @param \Dunagan\WebApi\Helper\Config $webApiConfigHelper
     */
    public function __construct(\Magento\Framework\ObjectManagerInterface $objectManager,
                                \Dunagan\WebApi\Helper\Config $webApiConfigHelper)
    {
        $this->_objectManager = $objectManager;
        $this->_webApiConfigHelper = $webApiConfigHelper;
        $this->_webApiConfigHelper->setConfigurationSectionId($this->_configuration_section_id);
        $this->_logger = $this->_objectManager->get($this->_logger_di_classname);
    }

    /**
     * Method to implement a call to the Web API. It will catch any exceptions which are thrown by the attempted
     *  execution and log the exception
     *
     * @param string $method
     * @param array $parameters
     * @return \Dunagan\WebApi\Model\ResultInterface
     */
    public function callApiMethod($method, $parameters)
    {
        $api_call_result_object_classname = $this->_getApiCallResultObjectClassname($method);
        $apiCallResultObject = $this->_objectManager->create($api_call_result_object_classname);
        /* @var $apiCallResultObject \Scc\WebApi\Model\ResultInterface */
        $apiCallResultObject->setApiMethod($method);
        $apiCallResultObject->setApiMethodParameters($parameters);

        $is_debug_mode = $this->_webApiConfigHelper->getConfigValue('debug/is_enabled');

        try
        {
            if ($is_debug_mode)
            {
                $before_call_timestamp = microtime(true);
            }

            $response = $this->executeApiCall($method, $parameters);

            if ($is_debug_mode)
            {
                $after_call_timestamp = microtime(true);
                $time_to_execute_in_ms = $after_call_timestamp - $before_call_timestamp;
                $time_to_execute_in_sec = $time_to_execute_in_ms / 1000.0;
                $time_to_execute_message = __(self::DEBUG_TIME_TO_EXECUTE_MESSAGE, $method, $time_to_execute_in_sec);
                $this->_logger->debug($time_to_execute_message);

                $api_log_message = __(self::DEBUG_API_CALL_RESPONSE, $method, print_r($parameters, true), print_r($response, true));
                $this->_logger->debug($api_log_message);
            }

            $apiCallResultObject->setWasSuccessful(true);
            $apiCallResultObject->setResultMessage($response);
        }
        catch(\SoapFault $e)
        {
            // In the event that this Client is a SOAP Client
            $detail = isset($e->detail) ? $e->detail : 'No details were provided regarding this error';
            $errorMessagePhrase = __(self::EXCEPTION_EXECUTING_SOAP_API_CALL, $method, get_class($this),
                                     print_r($parameters, true), $e->__toString(), $detail);
            $error_message = $errorMessagePhrase->render();
            $apiCallResultObject->setWasSuccessful(false);
            $apiCallResultObject->setResultMessage($error_message);
        }
        catch(\Exception $e)
        {
            $errorMessagePhrase = __(self::EXCEPTION_EXECUTING_API_CALL, $method, get_class($this),
                                     print_r($parameters, true), $e->getMessage());
            $error_message = $errorMessagePhrase->render();
            $apiCallResultObject->setWasSuccessful(false);
            $apiCallResultObject->setResultMessage($error_message);
        }

        if ((!$apiCallResultObject->getWasSuccessful()) && $is_debug_mode)
        {
            $after_call_timestamp = microtime(true);
            $time_to_execute_in_ms = $after_call_timestamp - $before_call_timestamp;
            $time_to_execute_in_sec = $time_to_execute_in_ms / 1000.0;
            $time_to_execute_message = __(self::DEBUG_TIME_TO_EXECUTE_MESSAGE, $method, $time_to_execute_in_sec);
            $this->_logger->debug($time_to_execute_message);

            $api_log_message = __(self::DEBUG_API_EXCEPTION_RESPONSE, $method, print_r($parameters, true), print_r($apiCallResultObject->getResultMessage(), true));
            $this->_logger->debug($api_log_message);
        }

        return $apiCallResultObject;
    }

    /**
     * This method built out to allow subclasses to override with custom logic
     *
     * @param string $method - Passed in in case subclass needs to customize functionality based on the method
     * @return string
     */
    protected function _getApiCallResultObjectClassname($method)
    {
        return $this->_api_call_result_object_classname;
    }
}