<?php
/**
 * Author: Sean Dunagan (github: dunagan5887)
 * Date: 9/18/16
 */

namespace Dunagan\WebApi\Model\Client;

use Dunagan\WebApi\Model\ClientInterface;

/**
 * This class represents an API Client interface implementation
 *
 * Class Soap
 * @package Dunagan\WebApi\Model\Client
 */
class Soap extends AbstractClient implements ClientInterface
{
    const HTTP_PROTOCOL_WSDL_PATH_REGEX = '#^http://#';

    /**
     * The name of this class's module. Potentially used when accessing the wsdl file.
     * Built out to allow subclasses to override the value
     *
     * @var string
     */
    protected $_module_name = 'Dunagan_WebApi';

    /**
     * Factory to produce the SoapClient object
     *
     * @var \Magento\Framework\Webapi\Soap\ClientFactory
     */
    protected $_soapClientFactory;

    /**
     * @var \Magento\Framework\Module\Dir\Reader
     */
    protected $_moduleDirReader;

    /**
     * @var array|null
     */
    protected $_soap_input_headers = null;

    /**
     * The object used to make the actual SOAP call
     *
     * @var \SoapClient
     */
    protected $_soapClient = null;

    /**
     * Soap constructor.
     * @param \Magento\Framework\ObjectManagerInterface $objectManager
     * @param \Dunagan\WebApi\Helper\Config $webApiConfigHelper
     * @param \Magento\Framework\Webapi\Soap\ClientFactory $soapClientFactory
     * @param \Magento\Framework\Module\Dir\Reader $moduleDirReader
     */
    public function __construct(\Magento\Framework\ObjectManagerInterface $objectManager,
                                \Dunagan\WebApi\Helper\Config $webApiConfigHelper,
                                \Magento\Framework\Webapi\Soap\ClientFactory $soapClientFactory,
                                \Magento\Framework\Module\Dir\Reader $moduleDirReader)
    {
        parent::__construct($objectManager, $webApiConfigHelper);

        $this->_soapClientFactory = $soapClientFactory;
        $this->_moduleDirReader = $moduleDirReader;

        $this->_setSoapHeaders();

        $soap_client_options = array(
            'exceptions' => true,
            'cache_wsdl' => WSDL_CACHE_NONE,
            'connection_timeout' => 5
        );
        $location = $this->_webApiConfigHelper->getConfigValue('connection/location');
        if ($location)
        {
            $soap_client_options['location'] = $location;
        }

        $wsdl = $this->getWsdlFile();
        $this->_soapClient = $this->_soapClientFactory->create($wsdl, $soap_client_options);
    }

    /**
     * Should execute an API call to the SOAP Client
     *
     * @param string $method
     * @param array $parameters
     *
     * @return mixed
     */
    public function executeApiCall($method, $parameters)
    {
        //return $this->_soapClient->__soapCall($method, $parameters, null, $this->_soap_input_headers);
        return $this->_soapClient->__soapCall($method, $parameters, null);
    }

    /**
     * Returns the wsdl file to be used for the SOAP calls
     * This class built out to allow subclasses to override the functionality
     *
     * @return string
     */
    public function getWsdlFile()
    {
        $connection_wsdl_path = $this->_webApiConfigHelper->getConfigValue('connection/wsdl');
        if (preg_match(self::HTTP_PROTOCOL_WSDL_PATH_REGEX, $connection_wsdl_path))
        {
            // If the wsdl path defined is an http link, return it as is
            return $connection_wsdl_path;
        }
        // Otherwise, assume that the path is relative to this module
        $module_etc_path = $this->_moduleDirReader->getModuleDir(\Magento\Framework\Module\Dir::MODULE_ETC_DIR,
                                                                 $this->_module_name);
        $full_wsdl_path = $module_etc_path . '/' . $connection_wsdl_path;
        return $full_wsdl_path;
    }

    /**
     * Method constructing SOAP headers. This is built out to allow subclasses to modify the functionality
     */
    protected function _setSoapHeaders()
    {
        $is_sandbox_mode = $this->_webApiConfigHelper->getConfigValue('connection/sandbox');

        $endpoint = ($is_sandbox_mode) ? $this->_webApiConfigHelper->getConfigValue('connection/endpoint')
            : $this->_webApiConfigHelper->getConfigValue('connection/sandbox_endpoint');

        $this->_soap_input_headers = array(
            new \SoapHeader(
                $endpoint,
                'Authentication',
                array(
                    'username' => $this->_webApiConfigHelper->getConfigValue('credentials/username'),
                    'password' => $this->_webApiConfigHelper->getObscuredConfigValue('credentials/password'),
                    'Content-Type' => 'text/xml;charset=UTF-8'
                )
            )
        );
    }
}
