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
     * @var string url of the API Lengow
     */
    // const LENGOW_API_URL = 'https://api.lengow.io';
    // const LENGOW_API_URL = 'https://api.lengow.net';
    const LENGOW_API_URL = 'http://api.lengow.rec';
    // const LENGOW_API_URL = 'http://10.100.1.82:8081';

    /**
     * @var string url of access token API
     */
    const API_ACCESS_TOKEN = '/access/get_token';

    /**
     * @var string url of order API
     */
    const API_ORDER = '/v3.0/orders';

    /**
     * @var string url of order merchant order id API
     */
    const API_ORDER_MOI = '/v3.0/orders/moi/';

    /**
     * @var string url of order action API
     */
    const API_ORDER_ACTION = '/v3.0/orders/actions/';

    /**
     * @var string url of marketplace API
     */
    const API_MARKETPLACE = '/v3.0/marketplaces';

    /**
     * @var string url of plan API
     */
    const API_PLAN = '/v3.0/plans';

    /**
     * @var string url of cms API
     */
    const API_CMS = '/v3.1/cms';

    /**
     * @var string request GET
     */
    const GET = 'GET';

    /**
     * @var string request POST
     */
    const POST = 'POST';

    /**
     * @var string request PUT
     */
    const PUT = 'PUT';

    /**
     * @var string request PATCH
     */
    const PATCH = 'PATCH';

    /**
     * @var string json format return
     */
    const FORMAT_JSON = 'json';

    /**
     * @var string stream format return
     */
    const FORMAT_STREAM = 'stream';

    /**
     * @var string success code
     */
    const CODE_200 = 200;

    /**
     * @var string forbidden access code
     */
    const CODE_403 = 403;

    /**
     * @var string error server code
     */
    const CODE_500 = 500;

    /**
     * @var string timeout server code
     */
    const CODE_504 = 504;

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
        list($accountId, $accessToken, $secretToken) = $this->_configHelper->getAccessIds();
        if ($accountId === null || (int)$accountId === 0 || !is_numeric($accountId)) {
            return false;
        }
        try {
            $this->init($accessToken, $secretToken);
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
            list($accountId, $accessToken, $secretToken) = $this->_configHelper->getAccessIds();
            if ($accountId === null) {
                return false;
            }
            $this->init($accessToken, $secretToken);
            $type = strtolower($type);
            $results = $this->$type(
                $url,
                array_merge(array('account_id' => $accountId), $params),
                self::FORMAT_STREAM,
                $body,
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
            return false;
        }
        return json_decode($results);
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
        $token = $this->_configHelper->get('authorization_token');
        $updatedAt = $this->_configHelper->get('last_authorization_token_update');
        if (!$force
            && $token !== null
            && strlen($token) > 0
            && $updatedAt !== null
            && (time() - $updatedAt) < $this->_tokenLifetime
        ) {
            $authorizationToken = $token;
        } else {
            $authorizationToken = $this->_getAuthorizationToken($logOutput);
            $this->_configHelper->set('authorization_token', $authorizationToken);
            $this->_configHelper->set('last_authorization_token_update', time());
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
            $this->connect();
            $data = $this->_callAction($api, $args, $type, $format, $body, $logOutput);
        } catch (Lengow_Connector_Model_Exception $e) {
            if ($e->getCode() === self::CODE_403) {
                $this->_helper->log(
                    Lengow_Connector_Helper_Data::CODE_CONNECTOR,
                    $this->_helper->setLogMessage('log.connector.retry_get_token'),
                    $logOutput
                );
                $this->connect(true, $logOutput);
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
        } elseif (strlen($data['token']) === 0) {
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
        // define generic Curl options
        $opts = $this->_curlOpts;
        // get special timeout for specific Lengow API
        if (array_key_exists($api, $this->_lengowUrls)) {
            $opts[CURLOPT_TIMEOUT] = $this->_lengowUrls[$api];
        }
        // get url for a specific environment
        $url = self::LENGOW_API_URL . $api;
        $opts[CURLOPT_CUSTOMREQUEST] = strtoupper($type);
        $url = parse_url($url);
        if (isset($url['port'])) {
            $opts[CURLOPT_PORT] = $url['port'];
        }
        $opts[CURLOPT_HEADER] = false;
        $opts[CURLOPT_VERBOSE] = false;
        if (isset($token)) {
            $opts[CURLOPT_HTTPHEADER] = array('Authorization: ' . $token);
        }
        $url = $url['scheme'] . '://' . $url['host'] . $url['path'];
        switch ($type) {
            case self::GET:
                $opts[CURLOPT_URL] = $url . (!empty($args) ? '?' . http_build_query($args) : '');
                break;
            case self::PUT:
                if (isset($token)) {
                    $opts[CURLOPT_HTTPHEADER] = array_merge(
                        $opts[CURLOPT_HTTPHEADER],
                        array(
                            'Content-Type: application/json',
                            'Content-Length: ' . strlen($body),
                        )
                    );
                }
                $opts[CURLOPT_URL] = $url . '?' . http_build_query($args);
                $opts[CURLOPT_POSTFIELDS] = $body;
                break;
            case self::PATCH:
                if (isset($token)) {
                    $opts[CURLOPT_HTTPHEADER] = array_merge(
                        $opts[CURLOPT_HTTPHEADER],
                        array('Content-Type: application/json')
                    );
                }
                $opts[CURLOPT_URL] = $url;
                $opts[CURLOPT_POST] = count($args);
                $opts[CURLOPT_POSTFIELDS] = json_encode($args);
                break;
            default:
                $opts[CURLOPT_URL] = $url;
                $opts[CURLOPT_POST] = count($args);
                $opts[CURLOPT_POSTFIELDS] = http_build_query($args);
                break;
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
            if (in_array($curlErrorNumber, array(CURLE_OPERATION_TIMEDOUT, CURLE_OPERATION_TIMEOUTED))) {
                throw new Lengow_Connector_Model_Exception(
                    $this->_helper->setLogMessage('log.connector.timeout_api'),
                    self::CODE_504
                );
            } else {
                $error = $this->_helper->setLogMessage(
                    'log.connector.error_curl',
                    array(
                        'error_code' => $curlErrorNumber,
                        'error_message' => $curlError,
                    )
                );
                throw new Lengow_Connector_Model_Exception($error, self::CODE_500);
            }
        } else {
            if ($httpCode !== self::CODE_200) {
                $result = $this->_format($result);
                // recovery of Lengow Api errors
                if (isset($result['error'])) {
                    throw new Lengow_Connector_Model_Exception($result['error']['message'], $httpCode);
                } else {
                    throw new Lengow_Connector_Model_Exception(
                        $this->_helper->setLogMessage('Lengow APIs are not available'),
                        $httpCode
                    );
                }
            }
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
