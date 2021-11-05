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
 * Model config
 */
class Lengow_Connector_Model_Config extends Mage_Core_Model_Config
{
    /**
     * @var array path for Lengow options
     */
    protected $_lengowOptions = array(
        'lengow_global_options',
        'lengow_export_options',
        'lengow_import_options',
    );

    /**
     * Override Save config to store lengow changes
     *
     * @param string $path configuration path
     * @param string $value configuration value
     * @param string $scope Magento scope
     * @param integer $scopeId Magento store id
     *
     * @return Lengow_Connector_Model_Config
     */
    public function saveConfig($path, $value, $scope = 'default', $scopeId = 0)
    {
        $this->checkAndLog($path, $value, $scope, $scopeId);
        parent::saveConfig($path, $value, $scope, $scopeId);
        return $this;
    }

    /**
     * Override Save config to store lengow changes
     *
     * @param string $path configuration path
     * @param string $value configuration value
     * @param string $scope Magento scope
     * @param integer $scopeId Magento store id
     */
    public function checkAndLog($path, $value, $scope = 'default', $scopeId = 0)
    {
        $pathExplode = explode('/', $path);
        if (!isset($pathExplode[0]) || !in_array($pathExplode[0], $this->_lengowOptions, true)) {
            return;
        }
        $key = $pathExplode[2];
        if (!array_key_exists($key, Lengow_Connector_Helper_Config::$lengowSettings)
            || !in_array($scope, array('default', 'stores'))
        ) {
            return;
        }
        $keyParams = Lengow_Connector_Helper_Config::$lengowSettings[$key];
        if (isset($keyParams[Lengow_Connector_Helper_Config::PARAM_LOG])
            && !$keyParams[Lengow_Connector_Helper_Config::PARAM_LOG]
        ) {
            return;
        }
        $configHelper = Mage::helper('lengow_connector/config');
        $oldValue = $configHelper->get($key, $scopeId);
        if ($oldValue != $value) {
            if (isset($keyParams[Lengow_Connector_Helper_Config::PARAM_SECRET])
                && $keyParams[Lengow_Connector_Helper_Config::PARAM_SECRET]
            ) {
                $value = preg_replace("/[a-zA-Z0-9]/", '*', $value);
                $oldValue = preg_replace("/[a-zA-Z0-9]/", '*', $oldValue);
            }
            if ($scope === 'stores') {
                $message = Mage::helper('lengow_connector/translation')->t(
                    'log.setting.setting_change_for_store',
                    array(
                        'key' => Lengow_Connector_Helper_Config::$genericParamKeys[$key],
                        'old_value' => $oldValue,
                        'value' => $value,
                        'store_id' => $scopeId,
                    )
                );
            } else {
                $message = Mage::helper('lengow_connector/translation')->t(
                    'log.setting.setting_change',
                    array(
                        'key' => Lengow_Connector_Helper_Config::$genericParamKeys[$key],
                        'old_value' => $oldValue,
                        'value' => $value,
                    )
                );
            }
            Mage::helper('lengow_connector')->log(Lengow_Connector_Helper_Data::CODE_SETTING, $message);
            // save last update date for a specific settings (change synchronisation interval time)
            if (isset($keyParams[Lengow_Connector_Helper_Config::PARAM_UPDATE])
                && $keyParams[Lengow_Connector_Helper_Config::PARAM_UPDATE]
            ) {
                $configHelper->set(Lengow_Connector_Helper_Config::LAST_UPDATE_SETTING, time());
            }
            // reset the authorization token when a configuration parameter is changed
            if (isset($keyParams[Lengow_Connector_Helper_Config::PARAM_RESET_TOKEN])
                && $keyParams[Lengow_Connector_Helper_Config::PARAM_RESET_TOKEN]
            ) {
                $configHelper->resetAuthorizationToken();
            }
        }
    }
}
