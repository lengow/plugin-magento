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
            'path'   => 'lengow_global_options/store_credential/token',
            'store'  => true,
        ),
        'store_enable' => array(
            'path'   => 'lengow_global_options/store_credential/global_store_enable',
            'store'  => true,
        ),
        'account_id' => array(
            'path'   => 'lengow_global_options/store_credential/global_account_id',
            'store'  => true,
        ),
        'access_token' => array(
            'path'   => 'lengow_global_options/store_credential/global_access_token',
            'store'  => true,
        ),
        'secret_token' => array(
            'path'   => 'lengow_global_options/store_credential/global_secret_token',
            'store'  => true,
        ),
        'tracking_id' => array(
            'path'   => 'lengow_global_options/advanced/global_tracking_id',
        ),
        'authorized_ip' => array(
            'path'   => 'lengow_global_options/advanced/global_authorized_ip',
        ),
        'last_statistic_update' => array(
            'path'   => 'lengow_import_options/advanced/last_statistic_update',
            'export' => false,
        ),
        'order_statistic' => array(
            'path'   => 'lengow_import_options/advanced/order_statistic',
            'export' => false,
        ),
        'selection_enable' => array(
            'path'   => 'lengow_export_options/simple/export_selection_enable',
            'store'  => true,
        ),
        'out_stock' => array(
            'path'   => 'lengow_export_options/simple/export_out_stock',
            'store'  => true,
        ),
        'product_type' => array(
            'path'   => 'lengow_export_options/simple/export_product_type',
            'store'  => true,
        ),
        'product_status' => array(
            'path'   => 'lengow_export_options/simple/export_product_status',
            'store'  => true,
        ),
        'export_attribute' => array(
            'path'   => 'lengow_export_options/advanced/export_attribute',
            'export' => false,
        ),
        'shipping_country' => array(
            'path'   => 'lengow_export_options/advanced/export_default_shipping_country',
            'store'  => true,
        ),
        'shipping_method' => array(
            'path'   => 'lengow_export_options/advanced/export_default_shipping_method',
            'store'  => true,
        ),
        'shipping_price' => array(
            'path'   => 'lengow_export_options/advanced/export_default_shipping_price',
            'store'  => true,
        ),
        'parent_image' => array(
            'path'   => 'lengow_export_options/advanced/export_parent_image',
            'store'  => true,
        ),
        'legacy_enable' => array(
            'path'   => 'lengow_export_options/advanced/export_legacy_enable',
        ),
        'file_enable' => array(
            'path'   => 'lengow_export_options/advanced/export_file_enable',
        ),
        'export_cron_enable' => array(
            'path'   => 'lengow_export_options/advanced/export_cron_enable',
        ),
        'last_export' => array(
            'path'   => 'lengow_export_options/advanced/export_last_export',
        ),
        'days' => array(
            'path'   => 'lengow_import_options/simple/import_days',
            'store'  => true,
        ),
        'customer_group' => array(
            'path'   => 'lengow_import_options/simple/import_customer_group',
            'store'  => true,
        ),
        'import_shipping_method' => array(
            'path'   => 'lengow_import_options/simple/import_default_shipping_method',
            'store'  => true,
        ),
        'report_mail_enable' => array(
            'path'   => 'lengow_import_options/advanced/import_report_mail_enable',
        ),
        'report_mail_address' => array(
            'path'   => 'lengow_import_options/advanced/import_report_mail_address',
        ),
        'import_ship_mp_enabled' => array(
            'path'   =>  'lengow_import_options/advanced/import_ship_mp_enabled',
        ),
        'import_stock_ship_mp' => array(
            'path'   =>  'lengow_import_options/advanced/import_stock_ship_mp',
        ),
        'preprod_mode_enable' => array(
            'path'   => 'lengow_import_options/advanced/import_preprod_mode_enable',
        ),
        'import_cron_enable' => array(
            'path'   => 'lengow_import_options/advanced/import_cron_enable',
        ),
        'import_in_progress' => array(
            'path'   => 'lengow_import_options/advanced/import_in_progress',
        ),
        'last_import_manual' => array(
            'path'   => 'lengow_import_options/advanced/last_import_manual',
        ),
        'last_import_cron' => array(
            'path'   => 'lengow_import_options/advanced/last_import_cron',
        ),
        'see_migrate_block' => array(
            'path'   => 'lengow_import_options/advanced/see_migrate_block',
            'export' => false,
        ),
        'last_status_update' => array(
            'path'   => 'lengow_import_options/advanced/last_status_update',
            'export' => false,
        ),
        'account_status' => array(
            'path'   => 'lengow_import_options/advanced/account_status',
            'export' => false,
        ),
    );

    /**
     * Get Value
     *
     * @param string  $key      Lengow setting key
     * @param integer $store_id Store id
     *
     * @return null
     */
    public function get($key, $store_id = 0)
    {
        if (!array_key_exists($key, $this->options)) {
            return null;
        }
        $value = Mage::getStoreConfig($this->options[$key]['path'], $store_id);
        return $value;
    }

    /**
     * Set Value
     *
     * @param string  $key      Lengow setting key
     * @param mixed   $value    Lengow setting value
     * @param integer $store_id Store id
     *
     * @return null
     */
    public function set($key, $value, $store_id = 0)
    {
        if ($store_id == 0) {
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
                $store_id
            );
        }
    }

    /**
     * Get Selected attributes
     *
     * @param integer $store_id store id
     *
     * @return array
     */
    public function getSelectedAttributes($store_id = null)
    {
        $tab = array();
        $attributeSelected = array();
        $val = $this->get('export_attribute', $store_id);
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
     * @param integer $store_id store id
     *
     * @return array
     */
    public function getToken($store_id = null)
    {
        $token = $this->get('token', $store_id);
        if ($token && strlen($token) > 0) {
            return $token;
        } else {
            $token = bin2hex(openssl_random_pseudo_bytes(16));
            $this->set('token', $token, $store_id);
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

    /**
     * Get Values by store or global
     *
     * @param integer $store_id store id
     *
     * @return array
     */
    public function getAllValues($store_id = null)
    {
        $rows = array();
        foreach ($this->options as $key => $value) {
            if (isset($value['export']) && !$value['export']) {
                continue;
            }
            if ($store_id) {
                if (isset($value['store']) && $value['store'] == 1) {
                    $rows[$key] = $this->get($key, $store_id);
                }
            } else {
                $rows[$key] = $this->get($key);
            }
        }
        return $rows;
    }
}
