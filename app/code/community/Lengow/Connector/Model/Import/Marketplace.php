<?php

/**
 *
 * @category    Lengow
 * @package     Lengow_Connector
 * @author      Team module <team-module@lengow.com>
 * @copyright   2016 Lengow SAS
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

class Lengow_Connector_Model_Import_Marketplace extends Varien_Object
{
     /**
     * @var Lengow_Connector_Helper_Data
     */
    protected $_helper = null;

    /**
     * @var Lengow_Connector_Helper_Config
     */
    protected $_config = null;

    /**
     * @var array all valid actions
     */
    public static $VALID_ACTIONS = array(
        'ship' ,
        'cancel'
    );

    /**
     * @var mixed all markeplaces allowed for an account ID
     */
    public static $MARKETPLACES = array();
    
    /**
     * @var mixed the current marketplace
     */
    public $marketplace;
    
    /**
     * @var string the name of the marketplace
     */
    public $name;

    /**
     * @var integer ID Store
     */
    public $id_store;
    
    /**
     * @var boolean if the marketplace is loaded
     */
    public $is_loaded = false;
    
    /**
     * @var array Lengow states => marketplace states
     */
    public $states_lengow = array();
    
    /**
     * @var array marketplace states => Lengow states
     */
    public $states = array();
    
    /**
     * @var array all possible actions of the marketplace
     */
    public $actions = array();
   
    /**
     * @var array all carriers of the marketplace
     */
    public $carriers = array();

    /**
     * Construct a new Markerplace instance
     *
     * @param array params options
     *
     * integer  id_store  Id store for current order
     * string   name      Marketplace name
     */
    public function __construct($params = array())
    {
        $this->_helper = Mage::helper('lengow_connector/data');
        $this->_config = Mage::helper('lengow_connector/config');
        $this->id_store = $params['id_store'];
        $this->loadApiMarketplace();
        $this->name = strtolower($params['name']);
        if (!isset(self::$MARKETPLACES[$this->id_store]->{$this->name})) {
            throw new Lengow_Connector_Model_Exception(
                $this->_helper->setLogMessage('lengow_log.exception.marketplace_not_present', array(
                    'markeplace_name' => $this->name
                ))
            );
        }
        $this->marketplace = self::$MARKETPLACES[$this->id_store]->{$this->name};
        if (!empty($this->marketplace)) {
            $this->label_name = $this->marketplace->name;
            foreach ($this->marketplace->orders->status as $key => $state) {
                foreach ($state as $value) {
                    $this->states_lengow[(string)$value] = (string)$key;
                    $this->states[(string)$key][(string)$value] = (string)$value;
                }
            }
            foreach ($this->marketplace->orders->actions as $key => $action) {
                foreach ($action->status as $state) {
                    $this->actions[(string)$key]['status'][(string)$state] = (string)$state;
                }
                foreach ($action->args as $arg) {
                    $this->actions[(string)$key]['args'][(string)$arg] = (string)$arg;
                }
                foreach ($action->optional_args as $optional_arg) {
                    $this->actions[(string)$key]['optional_args'][(string)$optional_arg] = $optional_arg;
                }
            }
            if (isset($this->marketplace->orders->carriers)) {
                foreach ($this->marketplace->orders->carriers as $key => $carrier) {
                    $this->carriers[(string)$key] = (string)$carrier->label;
                }
            }
            $this->is_loaded = true;
        }
    }

    /**
     * Load the json configuration of all marketplaces
     */
    public function loadApiMarketplace()
    {
        if (!array_key_exists($this->id_store, self::$MARKETPLACES)) {
            $connector = Mage::getModel('lengow/connector');
            $result = $connector->queryApi('get', '/v3.0/marketplaces', $this->id_store);
            self::$MARKETPLACES[$this->id_store] = $result;
        }
    }

    /**
    * If marketplace exist in xml configuration file
    *
    * @return boolean
    */
    public function isLoaded()
    {
        return $this->is_loaded;
    }

    /**
    * Get the real lengow's state
    *
    * @param string $name The marketplace state
    *
    * @return string The lengow state
    */
    public function getStateLengow($name)
    {
        if (array_key_exists($name, $this->states_lengow)) {
            return $this->states_lengow[$name];
        }
    }
}
