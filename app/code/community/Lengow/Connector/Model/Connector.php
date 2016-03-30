<?php

/**
 *
 * @category    Lengow
 * @package     Lengow_Sync
 * @author      Team Connector <team-connector@lengow.com>
 * @copyright   2016 Lengow SAS
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

class Lengow_Connector_Model_Connector
{
    /**
     * @var string connector version
     */
    const VERSION = '1.0';

    /**
     * @var mixed error returned by the API
     */
    public $error;

    /**
     * @var string the access token to connect
     */
    protected $_access_token;

    /**
     * @var string the secret to connect
     */
    protected $_secret;

    /**
     * @var string temporary token for the authorization
     */
    protected $_token;

    /**
     * @var integer ID account
     */
    protected $_account_id;

    /**
     * @var integer the user Id
     */
    protected $_user_id;

    /**
     * @var string
     */
    protected $_request;

    // /**
    //  * @var string Lengow API url
    //  */
    // const LENGOW_API_URL = 'http://api.lengow.io:80';

    // /**
    //  * @var string Lengow SANDBOX url
    //  */
    // const LENGOW_API_SANDBOX_URL = 'http://api.lengow.net:80';

    /**
     * @var string URL of the API Lengow
     */
    const LENGOW_API_URL = 'http://10.100.1.242:8081';

    /**
     * @var string URL of the SANDBOX Lengow
     */
    const LENGOW_API_SANDBOX_URL = 'http://10.100.1.242:8081';

    
    
    /**
     * Default options for curl.
     */
    public static $CURL_OPTS = array(
        CURLOPT_CONNECTTIMEOUT => 10,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 300,
        CURLOPT_USERAGENT => 'lengow-php-sdk',
    );

    /**
     * Make a new Lengow API Connector.
     *
     * @param varchar $access_token Your access token.
     * @param varchar $secret Your secret.
     */
    public function init($access_token, $secret)
    {
        $this->_access_token = $access_token;
        $this->_secret = $secret;
    }

    public function getRequest()
    {
        return $this->_request;
    }

    /**
     * Connectection to the API
     *
     * @param varchar $user_token The user token if is connected
     *
     * @return mixed array [authorized token + account_id + user_id] or false
     */
    public function connect($user_token = '')
    {
        $data = $this->_callAction(
            '/access/get_token',
            array(
                'access_token' => $this->_access_token,
                'secret' => $this->_secret,
                'user_token' => $user_token
            ),
            'POST'
        );
        if (isset($data['token'])) {
            $this->_token = $data['token'];
            $this->_account_id = $data['account_id'];
            $this->_user_id = $data['user_id'];
            return $data;
        } else {
            return false;
        }
    }

    /**
     * The API method.
     *
     * @param varchar $method Lengow method API call.
     * @param varchar $array Lengow method API parameters
     * @param varchar $type type of request GET|POST|PUT|HEAD|DELETE|PATCH
     * @param varchar $format return format of API
     *
     * @return array The formated data response
     */
    public function call($method, $array = array(), $type = 'GET', $format = 'json')
    {
        $this->connect();
        try {
            if (!array_key_exists('account_id', $array)) {
                $array['account_id'] = $this->_account_id;
            }
            $data = $this->_callAction($method, $array, $type, $format);
        } catch (Lengow_Connector_Model_Exception $e) {
            return $e->getMessage();
        }
        return $data;
    }

    public function get($method, $array = array(), $format = 'json')
    {
        return $this->call($method, $array, 'GET', $format);
    }

    public function post($method, $array = array(), $format = 'json')
    {
        return $this->call($method, $array, 'POST', $format);
    }

    public function head($method, $array = array(), $format = 'json')
    {
        return $this->call($method, $array, 'HEAD', $format);
    }

    public function put($method, $array = array(), $format = 'json')
    {
        return $this->call($method, $array, 'PUT', $format);
    }

    public function delete($method, $array = array(), $format = 'json')
    {
        return $this->call($method, $array, 'DELETE', $format);
    }

    public function patch($method, $array = array(), $format = 'json')
    {
        return $this->call($method, $array, 'PATCH', $format);
    }

    private function _callAction($api, $args, $type, $format = 'json')
    {
        $result = $this->_makeRequest($type, self::LENGOW_API_URL . $api, $args, $this->_token);
        return $this->_format($result, $format);
    }

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

    protected function _makeRequest($type, $url, $args, $token)
    {
        $helper = Mage::helper('lengow_connector/data');
        $ch = curl_init();
        // Options
        $opts = self::$CURL_OPTS;
        $opts[CURLOPT_CUSTOMREQUEST] = strtoupper($type);
        $url = parse_url($url);
        $opts[CURLOPT_PORT] = $url['port'];
        $opts[CURLOPT_HEADER] = true;
        $opts[CURLOPT_RETURNTRANSFER] = true;
        $opts[CURLOPT_VERBOSE] = false;
        if (isset($token)) {
            $opts[CURLOPT_HTTPHEADER] = array(
                'Authorization: ' . $token
            );
        }
        $url = $url['scheme'] . '://' . $url['host'] . $url['path'];
        if ($type == 'GET') {
            $opts[CURLOPT_URL] = $url . '?' . http_build_query($args);
            $helper->log(
                'Connector',
                $helper->setLogMessage('log.connector.call_api', array('curl_url' => $opts[CURLOPT_URL]))
            );
        } else {
            $opts[CURLOPT_URL] = $url;
            $opts[CURLOPT_POST] = count($args);
            $opts[CURLOPT_POSTFIELDS] = http_build_query($args);
        }
        // Exectute url request
        curl_setopt_array($ch, $opts);
        $result = curl_exec($ch);
        $error = curl_errno($ch);
        if (in_array($error, array(CURLE_OPERATION_TIMEDOUT, CURLE_OPERATION_TIMEOUTED))) {
            $timeout = $helper->setLogMessage('lengow_log.exception.timeout_api');
            $error_message = $helper->setLogMessage('log.connector.error_api', array(
                'error_code' => $helper->decodeLogMessage($timeout, 'en_GB')
            ));
            $helper->log('Connector', $error_message);
            throw new Lengow_Connector_Model_Exception($timeout);
        }
        list($header, $data) = explode("\r\n\r\n", $result, 2);
        $information = curl_getinfo($ch, CURLINFO_HEADER_OUT);
        curl_close($ch);
        if ($data === false) {
            $error_message = $helper->setLogMessage('log.connector.error_api', array(
                'error_code' => $error
            ));
            $helper->log('Connector', $error_message);
            throw new Lengow_Connector_Model_Exception($error);
        }
        return $data;
    }

    public function getAccountId()
    {
        return $this->_account_id;
    }

    /**
     * Check API Authentification
     *
     * @param integer $account_id Account id
     *
     * @return boolean
     */
    public function isValidAuth($account_id)
    {
        $result = $this->connect();
        if (isset($result['token']) && $account_id != 0 && is_integer($account_id)) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Get Valid Account / Access / Secret
     *
     * @param integer $store_id Store Id
     *
     * @return array
     */
    public function getAccessId($store_id = null)
    {
        $config = Mage::helper('lengow_connector/config');
        if ($store_id) {
            $account_id = $config->get('account_id', $store_id);
            $access_token = $config->get('access_token', $store_id);
            $secret_token = $config->get('secret_token', $store_id);
        } else {
            $store_collection = Mage::getResourceModel('core/store_collection')->addFieldToFilter('is_active', 1);
            foreach ($store_collection as $store) {
                $account_id = $config->get('account_id', $store->getId());
                $access_token = $config->get('access_token', $store->getId());
                $secret_token = $config->get('secret_token', $store->getId());
                if (strlen($account_id) > 0 && strlen($access_token) > 0 && strlen($secret_token) > 0) {
                    break;
                }
            }
        }
        if (strlen($account_id) > 0 && strlen($access_token) > 0 && strlen($secret_token) > 0) {
            return array($account_id, $access_token, $secret_token);
        } else {
            return array(null, null, null);
        }
    }

    /**
     * Query Api
     *
     * @param string    $type       (get/post)
     * @param string    $url        to query
     * @param integer   $store_id   Store Id
     * @param array     $params
     *
     * @return api result as array
     */
    public function queryApi($type, $url, $store_id = null, $params = array())
    {
        if (!in_array($type, array('get', 'post'))) {
            return false;
        }
        try {
            list($account_id, $access_token, $secret_token) = $this->getAccessId($store_id);
            $this->init($access_token, $secret_token);
            $results = $this->$type(
                $url,
                array_merge(array('account_id' => $account_id), $params),
                'stream'
            );
        } catch (Lengow_Connector_Model_Exception $e) {
            return false;
        }
        return json_decode($results);
    }
}
