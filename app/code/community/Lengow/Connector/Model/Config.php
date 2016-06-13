<?php

/**
 *
 * @category    Lengow
 * @package     Lengow_Connector
 * @author      Team module <team-module@lengow.com>
 * @copyright   2016 Lengow SAS
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

class Lengow_Connector_Model_Config extends Mage_Core_Model_Config
{
    /**
     * Path for Lengow options
     */
    private $_lengow_option = array(
        'lengow_global_options',
        'lengow_export_options',
        'lengow_import_options'
    );

    /**
     * Excludes attributes for export
     */
    protected $_exclude_options = array(
        'import_in_progress',
        'last_import_manual',
        'last_import_cron',
        'export_last_export',
        'last_statistic_update',
        'order_statistic',
    );

    /**
     * Override Save config to store lengow changes
     *
     * @param string  $path
     * @param string  $value
     * @param string  $scope
     * @param integer $scope_id
     *
     * @return Mage_Core_Store_Config
     */
    public function saveConfig($path, $value, $scope = 'default', $scope_id = 0)
    {
        $path_explode = explode("/", $path);
        if (isset($path_explode[0]) && in_array($path_explode[0], $this->_lengow_option)) {
            if ($scope == 'default' || $scope == 'stores') {
                $old_value = Mage::getStoreConfig($path, $scope_id);
                if ($old_value!= $value && !in_array($path_explode[2], $this->_exclude_options)) {
                    if ($scope == 'stores') {
                        $message = Mage::helper('lengow_connector/translation')
                            ->t(
                                'log.setting.setting_change_for_store',
                                array(
                                    'key'       => $path,
                                    'old_value' => $old_value,
                                    'value'     => $value,
                                    'store_id'  => $scope_id
                                )
                            );
                    } else {
                        $message = Mage::helper('lengow_connector/translation')->t('log.setting.setting_change', array(
                            'key'       => $path,
                            'old_value' => $old_value,
                            'value'     => $value,
                        ));
                    }
                    Mage::helper('lengow_connector')->log('Config', $message);
                }
            }
        }
        parent::saveConfig($path, $value, $scope, $scope_id);
        return $this;
    }
}
