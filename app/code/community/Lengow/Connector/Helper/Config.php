<?php

/**
 *
 * @category    Lengow
 * @package     Lengow_Connector
 * @author      Team module <team-module@lengow.com>
 * @copyright   2016 Lengow SAS
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

class Lengow_Connector_Helper_Config extends Mage_Core_Helper_Abstract
{

    var $options = array(
        'store_enable' => array(
            'path' => 'lengow_export_options/simple/export_store_enable'
        ),
        'selection_enable' => array(
            'path' => 'lengow_export_options/simple/export_selection_enable'
        ),
        'out_stock' => array(
            'path' => 'lengow_export_options/simple/export_out_stock'
        ),
        'product_type' => array(
            'path' => 'lengow_export_options/simple/export_product_type'
        ),
        'product_status' => array(
            'path' => 'lengow_export_options/simple/export_product_status'
        ),
        'shipping_country' => array(
            'path' => 'lengow_export_options/advanced/export_default_shipping_country'
        ),
        'shipping_method' => array(
            'path' => 'lengow_export_options/advanced/export_default_shipping_method'
        ),
        'shipping_price' => array(
            'path' => 'lengow_export_options/advanced/export_default_shipping_price'
        ),
        'parent_image' => array(
            'path' => 'lengow_export_options/advanced/export_parent_image'
        ),
        'file_enabled' => array(
            'path' => 'lengow_export_options/advanced/export_file_enabled'
        ),
        'cron_enabled' => array(
            'path' => 'lengow_export_options/advanced/export_cron_enabled'
        ),
        'token' => array(
            'path' => 'lengow_global_options/token'
        ),
    );

    /**
     * Get Value
     * @param $key
     * @param $storeId
     * @return null
     */
    public function get($key, $storeId)
    {
        if (!array_key_exists($key, $this->options)) {
            return null;
        }
        $value = Mage::getStoreConfig($this->options[$key]['path'], $storeId);
        return $value;
    }

    /**
     * Set Value
     * @param $key
     * @param $value
     * @param $storeId
     * @return null
     */
    public function set($key, $value, $storeId)
    {
        if ($storeId==0) {
            Mage::getModel('core/config')->saveConfig(
                $this->options[$key]['path'],
                $value,
                'default',
                0
            );
        } else {
            Mage::getModel('core/config')->saveConfig(
                $this->options[$key]['path'],
                $value,
                'store',
                $storeId
            );
        }
    }

    /**
     * Get Selected attributes
     * @param int $id_store
     * @return array
     */
    public function getSelectedAttributes($id_store = null)
    {
        $tab = array();
        $attributeSelected = array();
        $val = Mage::getStoreConfig('lenexport/attributelist/attributes', $id_store);
        if (!empty($val)) {
            $tab = explode(',', $val);
            $attributeSelected = array_flip($tab);
        }
        if (!empty($tab)) {
            foreach ($attributeSelected as $key => $value) {
                $attributeSelected[$key] = $key;
            }
        }
        return $attributeSelected;
    }

    /**
     * Generate token
     * @param integer $storeId
     * @return array
     */
    public function getToken($storeId = null)
    {
        $token = $this->get('token', $storeId);
        if ($token && strlen($token)>0) {
            return $token;
        } else {
            $token =  bin2hex(openssl_random_pseudo_bytes(16));
            $this->set('token', $token, $storeId);
        }
        return $token;
    }
}
