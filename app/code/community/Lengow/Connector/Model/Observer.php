<?php

/**
 *
 * @category    Lengow
 * @package     Lengow_Connector
 * @author      Team module <team-module@lengow.com>
 * @copyright   2016 Lengow SAS
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

class Lengow_Connector_Model_Observer
{
    /**
     * Path for Lengow options
     */
    protected $_lengow_options = array(
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
    );

    /**
     * Display Lengow Menu on demand
     */
    public function updateAdminMenu()
    {
        $menu = Mage::getSingleton('admin/config')->getAdminhtmlConfig()->getNode('menu/lengowtab/children');
        foreach ($menu->children() as $childName => $child) {
            $menu->setNode($childName.'/disabled', '0');
        }
    }

    /**
     * Save Change on lengow data
     *
     * @param $obj
     */
    public function onAfterSave($obj)
    {
        $object = $obj->getEvent()->getObject();
        if (is_a($object, 'Mage_Core_Model_Config_Data')) {
            $pathExplode = explode("/", $object['path']);
            if (isset($pathExplode[0]) && in_array($pathExplode[0], $this->_lengow_options)) {
                if ($object['scope'] == 'stores' || $object['scope'] == 'default') {
                    $oldValue = Mage::getStoreConfig($object['path'], $object['scope_id']);
                    if ($oldValue != $object['value'] && !in_array($pathExplode[2], $this->_exclude_options)) {
                        if ($object['scope'] == 'stores') {
                            $message = Mage::helper('lengow_connector/translation')->t(
                                'log.setting.setting_change_for_shop',
                                array(
                                    'key'       => $object['path'],
                                    'old_value' => $oldValue,
                                    'value'     => $object['value'],
                                    'shop_id'   => $object['scope_id']
                                )
                            );
                        } else {
                            $message = Mage::helper('lengow_connector/translation')->t(
                                'log.setting.setting_change',
                                array(
                                    'key'       => $object['path'],
                                    'old_value' => $oldValue,
                                    'value'     => $object['value'],
                                )
                            );
                        }
                        Mage::helper('lengow_connector')->log('Config', $message);
                    }
                }
            }
        }
    }
}
