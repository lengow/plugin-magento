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

    /**
     * All Lengow Options Path
     */
    public $options = array(
        'token' => array(
            'path' => 'lengow_global_options/store_credential/token'
        ),
        'store_enable' => array(
            'path' => 'lengow_global_options/store_credential/global_store_enable'
        ),
        'account_id' => array(
            'path' => 'lengow_global_options/store_credential/global_account_id'
        ),
        'access_token' => array(
            'path' => 'lengow_global_options/store_credential/global_access_token'
        ),
        'secret_token' => array(
            'path' => 'lengow_global_options/store_credential/global_secret_token'
        ),
        'tracking_id' => array(
            'path' => 'lengow_global_options/advanced/global_tracking_id'
        ),
        'authorized_ip' => array(
            'path' => 'lengow_global_options/advanced/global_authorized_ip'
        ),
        'last_statistic_update' => array(
            'path' => 'lengow_import_options/advanced/last_statistic_update'
        ),
        'order_statistic' => array(
            'path' => 'lengow_import_options/advanced/order_statistic'
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
        'export_attribute' => array(
            'path' => 'lengow_export_options/advanced/export_attribute'
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
        'file_enable' => array(
            'path' => 'lengow_export_options/advanced/export_file_enable'
        ),
        'export_cron_enable' => array(
            'path' => 'lengow_export_options/advanced/export_cron_enable'
        ),
        'last_export' => array(
            'path' => 'lengow_export_options/advanced/export_last_export'
        ),
        'days' => array(
            'path' => 'lengow_import_options/simple/import_days'
        ),
        'customer_group' => array(
            'path' => 'lengow_import_options/simple/import_customer_group'
        ),
        'import_shipping_method' => array(
            'path' => 'lengow_import_options/simple/import_default_shipping_method'
        ),
        'report_mail_enable' => array(
            'path' => 'lengow_import_options/advanced/import_report_mail_enable'
        ),
        'report_mail_address' => array(
            'path' => 'lengow_import_options/advanced/import_report_mail_address'
        ),
        'preprod_mode_enable' => array(
            'path' => 'lengow_import_options/advanced/import_preprod_mode_enable'
        ),
        'import_cron_enable' => array(
            'path' => 'lengow_import_options/advanced/import_cron_enable'
        ),
        'import_in_progress' => array(
            'path' => 'lengow_import_options/advanced/import_in_progress'
        ),
        'last_import_manual' => array(
            'path' => 'lengow_import_options/advanced/last_import_manual'
        ),
        'last_import_cron' => array(
            'path' => 'lengow_import_options/advanced/last_import_cron'
        ),
    );

    /**
     * Get Value
     *
     * @param $key
     * @param $storeId
     *
     * @return null
     */
    public function get($key, $storeId = 0)
    {
        if (!array_key_exists($key, $this->options)) {
            return null;
        }
        $value = Mage::getStoreConfig($this->options[$key]['path'], $storeId);
        return $value;
    }

    /**
     * Set Value
     *
     * @param $key
     * @param $value
     * @param $storeId
     *
     * @return null
     */
    public function set($key, $value, $storeId = 0)
    {
        if ($storeId == 0) {
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
                'stores',
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
        $val = $this->get('export_attribute', $id_store);
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
     *
     * @param integer $storeId
     *
     * @return array
     */
    public function getToken($storeId = null)
    {
        $token = $this->get('token', $storeId);
        if ($token && strlen($token) > 0) {
            return $token;
        } else {
            $token = bin2hex(openssl_random_pseudo_bytes(16));
            $this->set('token', $token, $storeId);
        }
        return $token;
    }

    /**
     * Get Store by token
     *
     * @param string $token
     *
     * @return mixed
     */
    public function getStoreByToken($token)
    {
        if (strlen($token) <= 0) {
            return false;
        }
        foreach (Mage::app()->getWebsites() as $website) {
            foreach ($website->getGroups() as $group) {
                $stores = $group->getStores();
                foreach ($stores as $store) {
                    if ($token == $this->get('token', $store->getId())) {
                        return $store;
                    }
                }
            }
        }
    }

    /**
     * Get all report mails
     *
     * @return array
     */
    public function getReportEmailAddress()
    {
        $emails = explode(';', $this->get('report_mail_address'));
        if ($emails[0] == '') {
            $emails[0] = Mage::getStoreConfig('trans_email/ident_general/email');
        }
        return $emails;
    }
}
