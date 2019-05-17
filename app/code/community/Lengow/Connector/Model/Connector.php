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
     * @var string url of the SANDBOX Lengow
     */
    const LENGOW_API_SANDBOX_URL = 'https://api.lengow.net';

    /**
     * @var array Default options for curl
     */
    public static $curlOpts = array(
        CURLOPT_CONNECTTIMEOUT => 10,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 10,
        CURLOPT_USERAGENT => 'lengow-php-sdk',
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
        '/v3.0/orders' => 15,
        '/v3.0/orders/moi/' => 5,
        '/v3.0/orders/actions/' => 10,
        '/v3.0/marketplaces' => 10,
        '/v3.0/plans' => 3,
        '/v3.0/stats' => 3,
        '/v3.1/cms' => 3,
    );

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
     * Connection to the API
     *
     * @throws Lengow_Connector_Model_Exception get Curl error
     *
     * @return array|false
     */
    public function connect()
    {
        $data = $this->_callAction(
            '/access/get_token',
            array(
                'access_token' => $this->_accessToken,
                'secret' => $this->_secret
            ),
            'POST'
        );
        if (isset($data['token'])) {
            $this->_token = $data['token'];
            return $data;
        } else {
            return false;
        }
    }

    /**
     * The API method
     *
     * @param string $method Lengow method API call
     * @param array $array Lengow method API parameters
     * @param string $type type of request GET|POST|PUT|HEAD|DELETE|PATCH
     * @param string $format return format of API
     * @param string $body body datas for request
     *
     * @return mixed
     */
    public function call($method, $array = array(), $type = 'GET', $format = 'json', $body = '')
    {
        try {
            $this->connect();
            $data = $this->_callAction($method, $array, $type, $format, $body);
        } catch (Lengow_Connector_Model_Exception $e) {
            return $e->getMessage();
        }
        return $data;
    }

    /**
     * Get API call
     *
     * @param string $method Lengow method API call
     * @param array $array Lengow method API parameters
     * @param string $format return format of API
     * @param string $body body datas for request
     *
     * @return array
     */
    public function get($method, $array = array(), $format = 'json', $body = '')
    {
        return $this->call($method, $array, 'GET', $format, $body);
    }

    /**
     * Post API call
     *
     * @param string $method Lengow method API call
     * @param array $array Lengow method API parameters
     * @param string $format return format of API
     * @param string $body body datas for request
     *
     * @return array
     */
    public function post($method, $array = array(), $format = 'json', $body = '')
    {
        return $this->call($method, $array, 'POST', $format, $body);
    }

    /**
     * Head API call
     *
     * @param string $method Lengow method API call
     * @param array $array Lengow method API parameters
     * @param string $format return format of API
     * @param string $body body datas for request
     *
     * @return array
     */
    public function head($method, $array = array(), $format = 'json', $body = '')
    {
        return $this->call($method, $array, 'HEAD', $format, $body);
    }

    /**
     * Put API call
     *
     * @param string $method Lengow method API call
     * @param array $array Lengow method API parameters
     * @param string $format return format of API
     * @param string $body body datas for request
     *
     * @return array
     */
    public function put($method, $array = array(), $format = 'json', $body = '')
    {
        return $this->call($method, $array, 'PUT', $format, $body);
    }

    /**
     * Delete API call
     *
     * @param string $method Lengow method API call
     * @param array $array Lengow method API parameters
     * @param string $format return format of API
     * @param string $body body datas for request
     *
     * @return array
     */
    public function delete($method, $array = array(), $format = 'json', $body = '')
    {
        return $this->call($method, $array, 'DELETE', $format, $body);
    }

    /**
     * Patch API call
     *
     * @param string $method Lengow method API call
     * @param array $array Lengow method API parameters
     * @param string $format return format of API
     * @param string $body body datas for request
     *
     * @return array
     */
    public function patch($method, $array = array(), $format = 'json', $body = '')
    {
        return $this->call($method, $array, 'PATCH', $format, $body);
    }

    /**
     * Call API action
     *
     * @param string $api Lengow method API call
     * @param array $args Lengow method API parameters
     * @param string $type type of request GET|POST|PUT|HEAD|DELETE|PATCH
     * @param string $format return format of API
     * @param string $body body datas for request
     *
     * @throws Lengow_Connector_Model_Exception get Curl error
     *
     * @return array
     */
    private function _callAction($api, $args, $type, $format = 'json', $body = '')
    {
        $result = $this->_makeRequest($type, $api, $args, $this->_token, $body);
        return $this->_format($result, $format);
    }

    /**
     * Get data in specific format
     *
     * @param mixed $data Curl response data
     * @param string $format return format of API
     *
     * @return mixed
     */
    private function _format($data, $format)
    {
        switch ($format) {
            case 'json':
                return json_decode($data, true);
            case 'csv':
                return $data;
            case 'xml':
                return simplexml_load_string($data);
            case 'stream':
                return $data;
        }
    }

    /**
     * Make Curl request
     *
     * @param string $type Lengow method API call
     * @param string $url Lengow API url
     * @param array $args Lengow method API parameters
     * @param string $token temporary access token
     * @param string $body body datas for request
     *
     * @throws Lengow_Connector_Model_Exception get Curl error
     *
     * @return array
     */
    protected function _makeRequest($type, $url, $args, $token, $body = '')
    {
        // Define CURLE_OPERATION_TIMEDOUT for old php versions
        defined('CURLE_OPERATION_TIMEDOUT') || define('CURLE_OPERATION_TIMEDOUT', CURLE_OPERATION_TIMEOUTED);
        $helper = Mage::helper('lengow_connector/data');
        $ch = curl_init();
        // Options
        $opts = self::$curlOpts;
        // get special timeout for specific Lengow API
        if (array_key_exists($url, $this->_lengowUrls)) {
            $opts[CURLOPT_TIMEOUT] = $this->_lengowUrls[$url];
        }
        // get url for a specific environment
        $url = self::LENGOW_API_URL . $url;
        $opts[CURLOPT_CUSTOMREQUEST] = strtoupper($type);
        $url = parse_url($url);
        if (isset($url['port'])) {
            $opts[CURLOPT_PORT] = $url['port'];
        }
        $opts[CURLOPT_HEADER] = false;
        $opts[CURLOPT_RETURNTRANSFER] = true;
        $opts[CURLOPT_VERBOSE] = false;
        if (isset($token)) {
            $opts[CURLOPT_HTTPHEADER] = array(
                'Authorization: ' . $token
            );
        }
        $url = $url['scheme'] . '://' . $url['host'] . $url['path'];
        switch ($type) {
            case 'GET':
                $opts[CURLOPT_URL] = $url . (!empty($args) ? '?' . http_build_query($args) : '');
                $helper->log(
                    'Connector',
                    $helper->setLogMessage('log.connector.call_api', array('curl_url' => $opts[CURLOPT_URL]))
                );
                break;
            case 'PUT':
                if (isset($token)) {
                    $opts[CURLOPT_HTTPHEADER] = array_merge(
                        $opts[CURLOPT_HTTPHEADER],
                        array(
                            'Content-Type: application/json',
                            'Content-Length: ' . strlen($body)
                        )
                    );
                }
                $opts[CURLOPT_URL] = $url . '?' . http_build_query($args);
                $opts[CURLOPT_POSTFIELDS] = $body;
                break;
            case 'PATCH':
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
        // Execute url request
        curl_setopt_array($ch, $opts);
        $result = curl_exec($ch);
        $errorNumber = curl_errno($ch);
        $errorText = curl_error($ch);
        if (in_array($errorNumber, array(CURLE_OPERATION_TIMEDOUT, CURLE_OPERATION_TIMEOUTED))) {
            $timeout = $helper->setLogMessage('lengow_log.exception.timeout_api');
            $errorMessage = $helper->setLogMessage(
                'log.connector.error_api',
                array('error_code' => $helper->decodeLogMessage($timeout, 'en_GB'))
            );
            $helper->log('Connector', $errorMessage);
            throw new Lengow_Connector_Model_Exception($timeout);
        }
        curl_close($ch);
        if ($result === false) {
            $errorCurl = $helper->setLogMessage(
                'lengow_log.exception.error_curl',
                array(
                    'error_code' => $errorNumber,
                    'error_message' => $errorText
                )
            );
            $errorMessage = $helper->setLogMessage(
                'log.connector.error_api',
                array('error_code' => $helper->decodeLogMessage($errorCurl, 'en_GB'))
            );
            $helper->log('Connector', $errorMessage);
            throw new Lengow_Connector_Model_Exception($errorCurl);
        }
        return $result;
    }

    /**
     * Get result for a query Api
     *
     * @param string $type request type (GET / POST / PUT / PATCH)
     * @param string $url request url
     * @param array $params request params
     * @param string $body body datas for request
     *
     * @return mixed
     */
    public function queryApi($type, $url, $params = array(), $body = '')
    {
        if (!in_array($type, array('get', 'post', 'put', 'patch'))) {
            return false;
        }
        try {
            list($accountId, $accessToken, $secretToken) = Mage::helper('lengow_connector/config')->getAccessIds();
            $this->init($accessToken, $secretToken);
            $results = $this->$type(
                $url,
                array_merge(array('account_id' => $accountId), $params),
                'stream',
                $body
            );
        } catch (Lengow_Connector_Model_Exception $e) {
            return false;
        }
        return json_decode($results);
    }
}
