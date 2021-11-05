<?php
/**
 * Copyright 2017 Lengow SAS
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 *
 * @category    Lengow
 * @package     Lengow_Connector
 * @subpackage  Model
 * @author      Team module <team-module@lengow.com>
 * @copyright   2017 Lengow SAS
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

/**
 * Model connector
 */
class Lengow_Connector_Model_Connector
{
    /**
     * @var string url of Lengow solution
     */
    // const LENGOW_URL = 'lengow.io';
    const LENGOW_URL = 'lengow.net';

    /**
     * @var string url of the Lengow API
     */
    // const LENGOW_API_URL = 'https://api.lengow.io';
    const LENGOW_API_URL = 'https://api.lengow.net';

    /* Lengow API routes */
    const API_ACCESS_TOKEN = '/access/get_token';
    const API_ORDER = '/v3.0/orders';
    const API_ORDER_MOI = '/v3.0/orders/moi/';
    const API_ORDER_ACTION = '/v3.0/orders/actions/';
    const API_MARKETPLACE = '/v3.0/marketplaces';
    const API_PLAN = '/v3.0/plans';
    const API_CMS = '/v3.1/cms';
    const API_CMS_CATALOG = '/v3.1/cms/catalogs/';
    const API_CMS_MAPPING = '/v3.1/cms/mapping/';
    const API_PLUGIN = '/v3.0/plugins';

    /* Request actions */
    const GET = 'GET';
    const POST = 'POST';
    const PUT = 'PUT';
    const PATCH = 'PATCH';

    /* Return formats */
    const FORMAT_JSON = 'json';
    const FORMAT_STREAM = 'stream';

    /* Http codes */
    const CODE_200 = 200;
    const CODE_201 = 201;
    const CODE_401 = 401;
    const CODE_403 = 403;
    const CODE_404 = 404;
    const CODE_500 = 500;
    const CODE_504 = 504;

    /**
     * @var array success HTTP codes for request
     */
    protected $_successCodes = array(
        self::CODE_200,
        self::CODE_201,
    );

    /**
     * @var array authorization HTTP codes for request
     */
    protected $_authorizationCodes = array(
        self::CODE_401,
        self::CODE_403,
    );

    /**
     * @var integer Authorization token lifetime
     */
    protected $_tokenLifetime = 3000;

    /**
     * @var array Default options for curl
     */
    protected $_curlOpts = array(
        CURLOPT_CONNECTTIMEOUT => 10,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 10,
        CURLOPT_USERAGENT => 'lengow-cms-magento',
    );

    /**
     * @var string the access token to connect
     */
    protected $_accessToken;

    /**
     * @var string the secret to connect
     */
    protected $_secret;

    /**
     * @var string temporary token for the authorization
     */
    protected $_token;

    /**
     * @var array lengow url for curl timeout
     */
    protected $_lengowUrls = array(
        self::API_ORDER => 20,
        self::API_ORDER_MOI => 10,
        self::API_ORDER_ACTION => 15,
        self::API_MARKETPLACE => 15,
        self::API_PLAN => 5,
        self::API_CMS => 5,
        self::API_CMS_CATALOG => 10,
        self::API_CMS_MAPPING => 10,
        self::API_PLUGIN => 5,
    );

    /**
     * @var array API requiring no arguments in the call url
     */
    protected $_apiWithoutUrlArgs = array(
        self::API_ACCESS_TOKEN,
        self::API_ORDER_ACTION,
        self::API_ORDER_MOI,
    );

    /**
     * @var array API requiring no authorization for the call url
     */
    protected $_apiWithoutAuthorizations = array(
        self::API_PLUGIN,
    );

    /**
     * @var Lengow_Connector_Helper_Data Lengow helper instance
     */
    protected $_helper;

    /**
     * @var Lengow_Connector_Helper_Config Lengow config helper instance
     */
    protected $_configHelper;

    /**
     * Construct
     */
    public function __construct()
    {
        $this->_helper = Mage::helper('lengow_connector');
        $this->_configHelper = Mage::helper('lengow_connector/config');
    }

    /**
     * Make a new Lengow API Connector.
     *
     * @param string $accessToken your access token
     * @param string $secret your secret
     */
    public function init($accessToken, $secret)
    {
        $this->_accessToken = $accessToken;
        $this->_secret = $secret;
    }

    /**
     * Check if PHP Curl is activated
     *
     * @return boolean
     */
    public function isCurlActivated()
    {
        return function_exists('curl_version');
    }

    /**
     * Check API Authentication
     *
     * @param boolean $logOutput see log or not
     *
     * @return boolean
     */
    public function isValidAuth($logOutput = false)
    {
        if (!$this->isCurlActivated()) {
            return false;
        }
        list($accountId, $accessToken, $secret) = $this->_configHelper->getAccessIds();
        if ($accountId === null) {
            return false;
        }
        try {
            $this->init($accessToken, $secret);
            $this->connect();
        } catch (Lengow_Connector_Model_Exception $e) {
            $message = $this->_helper->decodeLogMessage(
                $e->getMessage(),
                Lengow_Connector_Helper_Translation::DEFAULT_ISO_CODE
            );
            $error = $this->_helper->setLogMessage(
                'log.connector.error_api',
                array(
                    'error_code' => $e->getCode(),
                    'error_message' => $message,
                )
            );
            $this->_helper->log(Lengow_Connector_Helper_Data::CODE_CONNECTOR, $error, $logOutput);
            return false;
        }
        return true;
    }

    /**
     * Get result for a query Api
     *
     * @param string $type request type (GET / POST / PUT / PATCH)
     * @param string $url request url
     * @param array $params request params
     * @param string $body body data for request
     * @param boolean $logOutput see log or not
     *
     * @return mixed
     */
    public function queryApi($type, $url, $params = array(), $body = '', $logOutput = false)
    {
        if (!in_array($type, array(self::GET, self::POST, self::PUT, self::PATCH))) {
            return false;
        }
        try {
            $authorizationRequired = !in_array($url, $this->_apiWithoutAuthorizations, true);
            list($accountId, $accessToken, $secret) = $this->_configHelper->getAccessIds();
            if ($accountId === null && $authorizationRequired) {
                return false;
            }
            $this->init($accessToken, $secret);
            $type = strtolower($type);
            $params = $authorizationRequired
                ? array_merge(array(Lengow_Connector_Model_Import::ARG_ACCOUNT_ID => $accountId), $params)
                : $params;
            $results = $this->$type($url, $params, self::FORMAT_STREAM, $body, $logOutput);
        } catch (Lengow_Connector_Model_Exception $e) {
            $message = $this->_helper->decodeLogMessage(
                $e->getMessage(),
                Lengow_Connector_Helper_Translation::DEFAULT_ISO_CODE
            );
            $error = $this->_helper->setLogMessage(
                'log.connector.error_api',
                array(
                    'error_code' => $e->getCode(),
                    'error_message' => $message,
                )
            );
            $this->_helper->log(Lengow_Connector_Helper_Data::CODE_CONNECTOR, $error, $logOutput);
            return false;
        }
        return json_decode($results);
    }

    /**
     * Get account id by credentials from Middleware
     *
     * @param string $accessToken access token for api
     * @param string $secret secret for api
     * @param boolean $logOutput see log or not
     *
     * @return int|null
     */
    public function getAccountIdByCredentials($accessToken, $secret, $logOutput = false)
    {
        $this->init($accessToken, $secret);
        try {
            $data = $this->_callAction(
                self::API_ACCESS_TOKEN,
                array(
                    'access_token' => $accessToken,
                    'secret' => $secret,
                ),
                self::POST,
                self::FORMAT_JSON,
                '',
                $logOutput
            );
        } catch (Lengow_Connector_Model_Exception $e) {
            $message = $this->_helper->decodeLogMessage(
                $e->getMessage(),
                Lengow_Connector_Helper_Translation::DEFAULT_ISO_CODE
            );
            $error = $this->_helper->setLogMessage(
                'log.connector.error_api',
                array(
                    'error_code' => $e->getCode(),
                    'error_message' => $message,
                )
            );
            $this->_helper->log(Lengow_Connector_Helper_Data::CODE_CONNECTOR, $error, $logOutput);
            return null;
        }
        return $data['account_id'] ? (int) $data['account_id'] : null;
    }

    /**
     * Connection to the API
     *
     * @param boolean $force Force cache Update
     * @param boolean $logOutput see log or not
     *
     * @throws Lengow_Connector_Model_Exception
     */
    public function connect($force = false, $logOutput = false)
    {
        $token = $this->_configHelper->get(Lengow_Connector_Helper_Config::AUTHORIZATION_TOKEN);
        $updatedAt = $this->_configHelper->get(Lengow_Connector_Helper_Config::LAST_UPDATE_AUTHORIZATION_TOKEN);
        if (!$force
            && $token !== null
            && $updatedAt !== null
            && $token !== ''
            && (time() - $updatedAt) < $this->_tokenLifetime
        ) {
            $authorizationToken = $token;
        } else {
            $authorizationToken = $this->_getAuthorizationToken($logOutput);
            $this->_configHelper->set(Lengow_Connector_Helper_Config::AUTHORIZATION_TOKEN, $authorizationToken);
            $this->_configHelper->set(Lengow_Connector_Helper_Config::LAST_UPDATE_AUTHORIZATION_TOKEN, time());
        }
        $this->_token = $authorizationToken;
    }

    /**
     * Get API call
     *
     * @param string $api Lengow method API call
     * @param array $args Lengow method API parameters
     * @param string $format return format of API
     * @param string $body body data for request
     * @param boolean $logOutput see log or not
     *
     * @throws Lengow_Connector_Model_Exception
     *
     * @return mixed
     */
    public function get($api, $args = array(), $format = self::FORMAT_JSON, $body = '', $logOutput = false)
    {
        return $this->_call($api, $args, self::GET, $format, $body, $logOutput);
    }

    /**
     * Post API call
     *
     * @param string $api Lengow method API call
     * @param array $args Lengow method API parameters
     * @param string $format return format of API
     * @param string $body body data for request
     * @param boolean $logOutput see log or not
     *
     * @throws Lengow_Connector_Model_Exception
     *
     * @return mixed
     */
    public function post($api, $args = array(), $format = self::FORMAT_JSON, $body = '', $logOutput = false)
    {
        return $this->_call($api, $args, self::POST, $format, $body, $logOutput);
    }

    /**
     * Put API call
     *
     * @param string $api Lengow method API call
     * @param array $args Lengow method API parameters
     * @param string $format return format of API
     * @param string $body body data for request
     * @param boolean $logOutput see log or not
     *
     * @throws Lengow_Connector_Model_Exception
     *
     * @return mixed
     */
    public function put($api, $args = array(), $format = self::FORMAT_JSON, $body = '', $logOutput = false)
    {
        return $this->_call($api, $args, self::PUT, $format, $body, $logOutput);
    }

    /**
     * Patch API call
     *
     * @param string $api Lengow method API call
     * @param array $args Lengow method API parameters
     * @param string $format return format of API
     * @param string $body body data for request
     * @param boolean $logOutput see log or not
     *
     * @throws Lengow_Connector_Model_Exception
     *
     * @return mixed
     */
    public function patch($api, $args = array(), $format = self::FORMAT_JSON, $body = '', $logOutput = false)
    {
        return $this->_call($api, $args, self::PATCH, $format, $body, $logOutput);
    }

    /**
     * The API method
     *
     * @param string $api Lengow method API call
     * @param array $args Lengow method API parameters
     * @param string $type type of request GET|POST|PUT|PATCH
     * @param string $format return format of API
     * @param string $body body data for request
     * @param boolean $logOutput see log or not
     *
     * @throws Lengow_Connector_Model_Exception
     *
     * @return mixed
     */
    private function _call($api, $args, $type, $format, $body, $logOutput)
    {
        try {
            if (!in_array($api, $this->_apiWithoutAuthorizations, true)) {
                $this->connect(false, $logOutput);
            }
            $data = $this->_callAction($api, $args, $type, $format, $body, $logOutput);
        } catch (Lengow_Connector_Model_Exception $e) {
            if (in_array($e->getCode(), $this->_authorizationCodes, true)) {
                $this->_helper->log(
                    Lengow_Connector_Helper_Data::CODE_CONNECTOR,
                    $this->_helper->setLogMessage('log.connector.retry_get_token'),
                    $logOutput
                );
                if (!in_array($api, $this->_apiWithoutAuthorizations, true)) {
                    $this->connect(true, $logOutput);
                }
                $data = $this->_callAction($api, $args, $type, $format, $body, $logOutput);
            } else {
                throw new Lengow_Connector_Model_Exception($e->getMessage(), $e->getCode());
            }
        }
        return $data;
    }

    /**
     * Call API action
     *
     * @param string $api Lengow method API call
     * @param array $args Lengow method API parameters
     * @param string $type type of request GET|POST|PUT|PATCH
     * @param string $format return format of API
     * @param string $body body data for request
     * @param boolean $logOutput see log or not
     *
     * @throws Lengow_Connector_Model_Exception
     *
     * @return mixed
     */
    private function _callAction($api, $args, $type, $format, $body, $logOutput)
    {
        $result = $this->_makeRequest($type, $api, $args, $this->_token, $body, $logOutput);
        return $this->_format($result, $format);
    }

    /**
     * Get authorization token from Middleware
     *
     * @param boolean $logOutput see log or not
     *
     * @throws Lengow_Connector_Model_Exception
     *
     * @return string
     */
    private function _getAuthorizationToken($logOutput)
    {
        // reset temporary token for the new authorization
        $this->_token = null;
        $data = $this->_callAction(
            self::API_ACCESS_TOKEN,
            array(
                'access_token' => $this->_accessToken,
                'secret' => $this->_secret,
            ),
            self::POST,
            self::FORMAT_JSON,
            '',
            $logOutput
        );
        // return a specific error for get_token
        if (!isset($data['token'])) {
            throw new Lengow_Connector_Model_Exception(
                $this->_helper->setLogMessage('log.connector.token_not_return'),
                self::CODE_500
            );
        }
        if ($data['token'] === '') {
            throw new Lengow_Connector_Model_Exception(
                $this->_helper->setLogMessage('log.connector.token_is_empty'),
                self::CODE_500
            );
        }
        return $data['token'];
    }

    /**
     * Make Curl request
     *
     * @param string $type Lengow method API call
     * @param string $api Lengow API url
     * @param array $args Lengow method API parameters
     * @param string $token temporary access token
     * @param string $body body data for request
     * @param boolean $logOutput see log or not
     *
     * @throws Lengow_Connector_Model_Exception
     *
     * @return mixed
     */
    private function _makeRequest($type, $api, $args, $token, $body, $logOutput)
    {
        // define CURLE_OPERATION_TIMEDOUT for old php versions
        defined('CURLE_OPERATION_TIMEDOUT') || define('CURLE_OPERATION_TIMEDOUT', CURLE_OPERATION_TIMEOUTED);
        $ch = curl_init();
        // get default curl options
        $opts = $this->_curlOpts;
        // get special timeout for specific Lengow API
        if (array_key_exists($api, $this->_lengowUrls)) {
            $opts[CURLOPT_TIMEOUT] = $this->_lengowUrls[$api];
        }
        // get base url for a specific environment
        $url = self::LENGOW_API_URL . $api;
        $opts[CURLOPT_CUSTOMREQUEST] = strtoupper($type);
        $url = parse_url($url);
        if (isset($url['port'])) {
            $opts[CURLOPT_PORT] = $url['port'];
        }
        $opts[CURLOPT_HEADER] = false;
        $opts[CURLOPT_VERBOSE] = false;
        if (!empty($token)) {
            $opts[CURLOPT_HTTPHEADER] = array('Authorization: ' . $token);
        }
        // get call url with the mandatory parameters
        $opts[CURLOPT_URL] = $url['scheme'] . '://' . $url['host'] . $url['path'];
        if (!empty($args) && ($type === self::GET || !in_array($api, $this->_apiWithoutUrlArgs, true))) {
            $opts[CURLOPT_URL] .= '?' . http_build_query($args);
        }
        if ($type !== self::GET) {
            if (!empty($body)) {
                // sending data in json format for new APIs
                $opts[CURLOPT_HTTPHEADER] = array_merge(
                    $opts[CURLOPT_HTTPHEADER],
                    array(
                        'Content-Type: application/json',
                        'Content-Length: ' . strlen($body),
                    )
                );
                $opts[CURLOPT_POSTFIELDS] = $body;
            } else {
                // sending data in string format for legacy APIs
                $opts[CURLOPT_POST] = count($args);
                $opts[CURLOPT_POSTFIELDS] = http_build_query($args);
            }
        }
        $this->_helper->log(
            Lengow_Connector_Helper_Data::CODE_CONNECTOR,
            $this->_helper->setLogMessage(
                'log.connector.call_api',
                array(
                    'call_type' => $type,
                    'curl_url' => $opts[CURLOPT_URL],
                )
            ),
            $logOutput
        );
        curl_setopt_array($ch, $opts);
        $result = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        $curlErrorNumber = curl_errno($ch);
        curl_close($ch);
        $this->_checkReturnRequest($result, $httpCode, $curlError, $curlErrorNumber);
        return $result;
    }

    /**
     * Check return request and generate exception if needed
     *
     * @param string $result Curl return call
     * @param integer $httpCode request http code
     * @param string $curlError Curl error
     * @param string $curlErrorNumber Curl error number
     *
     * @throws Lengow_Connector_Model_Exception
     *
     */
    private function _checkReturnRequest($result, $httpCode, $curlError, $curlErrorNumber)
    {
        if ($result === false) {
            // recovery of Curl errors
            if (in_array($curlErrorNumber, array(CURLE_OPERATION_TIMEDOUT, CURLE_OPERATION_TIMEOUTED), true)) {
                throw new Lengow_Connector_Model_Exception(
                    $this->_helper->setLogMessage('log.connector.timeout_api'),
                    self::CODE_504
                );
            }
            throw new Lengow_Connector_Model_Exception(
                $this->_helper->setLogMessage(
                    'log.connector.error_curl',
                    array(
                        'error_code' => $curlErrorNumber,
                        'error_message' => $curlError,
                    )
                ),
                self::CODE_500
            );
        }
        if (!in_array($httpCode, $this->_successCodes, true)) {
            $result = $this->_format($result);
            // recovery of Lengow Api errors
            if (isset($result['error'], $result['error']['message'])) {
                throw new Lengow_Connector_Model_Exception($result['error']['message'], $httpCode);
            }
            throw new Lengow_Connector_Model_Exception(
                $this->_helper->setLogMessage('log.connector.api_not_available'),
                $httpCode
            );
        }
    }

    /**
     * Get data in specific format
     *
     * @param mixed $data Curl response data
     * @param string $format return format of API
     *
     * @return mixed
     */
    private function _format($data, $format = self::FORMAT_JSON)
    {
        switch ($format) {
            case self::FORMAT_STREAM:
                return $data;
            default:
            case self::FORMAT_JSON:
                return json_decode($data, true);
        }
    }
}
