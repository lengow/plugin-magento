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
     * @var array excludes attributes for export
     */
    protected $_excludeOptions = array(
        'import_in_progress',
        'last_import_manual',
        'last_import_cron',
        'export_last_export',
        'last_statistic_update',
        'order_statistic',
        'see_migrate_block',
        'last_status_update',
        'account_status',
        'last_option_cms_update',
        'installed_version',
        'last_marketplace_update',
        'last_setting_update',
    );

    /**
     * @var array Secret settings list to hide
     */
    protected $_secretSettings = array(
        'global_access_token',
        'global_secret_token',
    );

    /**
     * @var array list of settings for the date of the last update
     */
    protected $_updatedSettings = array(
        'global_catalog_id',
        'import_days',
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
        $pathExplode = explode("/", $path);
        if (isset($pathExplode[0]) && in_array($pathExplode[0], $this->_lengowOptions)) {
            if ($scope === 'default' || $scope === 'stores') {
                $oldValue = Mage::getStoreConfig($path, $scopeId);
                if ($oldValue != $value && !in_array($pathExplode[2], $this->_excludeOptions)) {
                    if (in_array($pathExplode[2], $this->_secretSettings)) {
                        $newValue = preg_replace("/[a-zA-Z0-9]/", '*', $value);
                        $oldValue = preg_replace("/[a-zA-Z0-9]/", '*', $oldValue);
                    } else {
                        $newValue = $value;
                    }
                    if ($scope === 'stores') {
                        $message = Mage::helper('lengow_connector/translation')->t(
                            'log.setting.setting_change_for_store',
                            array(
                                'key' => $path,
                                'old_value' => $oldValue,
                                'value' => $newValue,
                                'store_id' => $scopeId,
                            )
                        );
                    } else {
                        $message = Mage::helper('lengow_connector/translation')->t(
                            'log.setting.setting_change',
                            array(
                                'key' => $path,
                                'old_value' => $oldValue,
                                'value' => $newValue,
                            )
                        );
                    }
                    Mage::helper('lengow_connector')->log('Config', $message);
                    // Save last update date for a specific settings (change synchronisation interval time)
                    if (in_array($pathExplode[2], $this->_updatedSettings)) {
                        Mage::helper('lengow_connector/config')->set('last_setting_update', date('Y-m-d H:i:s'));
                    }
                }
            }
        }
    }
}
