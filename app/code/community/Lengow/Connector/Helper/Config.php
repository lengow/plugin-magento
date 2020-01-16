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
 * @subpackage  Helper
 * @author      Team module <team-module@lengow.com>
 * @copyright   2017 Lengow SAS
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

/**
 * Helper config
 */
class Lengow_Connector_Helper_Config extends Mage_Core_Helper_Abstract
{
    /**
     * @var array all Lengow options path
     */
    protected $_options = array(
        'token' => array(
            'path' => 'lengow_global_options/store_credential/token',
            'store' => true,
            'no_cache' => true,
        ),
        'account_id' => array(
            'path' => 'lengow_global_options/store_credential/global_account_id',
            'global' => true,
            'no_cache' => true,
        ),
        'access_token' => array(
            'path' => 'lengow_global_options/store_credential/global_access_token',
            'global' => true,
            'no_cache' => true,
        ),
        'secret_token' => array(
            'path' => 'lengow_global_options/store_credential/global_secret_token',
            'global' => true,
            'no_cache' => true,
        ),
        'authorization_token' => [
            'path' => 'lengow_global_options/store_credential/authorization_token',
            'global' => true,
            'export' => false,
            'no_cache' => true,
        ],
        'last_authorization_token_update' => [
            'path' => 'lengow_global_options/store_credential/last_authorization_token_update',
            'global' => true,
            'export' => false,
            'no_cache' => true,
        ],
        'store_enable' => array(
            'path' => 'lengow_global_options/store_credential/global_store_enable',
            'store' => true,
            'no_cache' => true,
        ),
        'catalog_id' => array(
            'path' => 'lengow_global_options/store_credential/global_catalog_id',
            'store' => true,
            'no_cache' => true,
        ),
        'tracking_enable' => array(
            'path' => 'lengow_global_options/advanced/global_tracking_enable',
            'global' => true,
            'no_cache' => false,
        ),
        'tracking_id' => array(
            'path' => 'lengow_global_options/advanced/global_tracking_id',
            'global' => true,
            'no_cache' => false,
        ),
        'ip_enable' => array(
            'path' => 'lengow_global_options/advanced/global_authorized_ip_enable',
            'global' => true,
            'no_cache' => false,
        ),
        'authorized_ip' => array(
            'path' => 'lengow_global_options/advanced/global_authorized_ip',
            'global' => true,
            'no_cache' => false,
        ),
        'last_statistic_update' => array(
            'path' => 'lengow_global_options/advanced/last_statistic_update',
            'global' => true,
            'no_cache' => true,
        ),
        'order_statistic' => array(
            'path' => 'lengow_global_options/advanced/order_statistic',
            'export' => false,
            'no_cache' => true,
        ),
        'last_status_update' => array(
            'path' => 'lengow_global_options/advanced/last_status_update',
            'global' => true,
            'no_cache' => true,
        ),
        'account_status' => array(
            'path' => 'lengow_global_options/advanced/account_status',
            'export' => false,
            'no_cache' => true,
        ),
        'last_option_cms_update' => array(
            'path' => 'lengow_global_options/advanced/last_option_cms_update',
            'global' => true,
            'no_cache' => true,
        ),
        'installed_version' => array(
            'path' => 'lengow_global_options/advanced/installed_version',
            'export' => false,
            'no_cache' => true,
        ),
        'last_marketplace_update' => array(
            'path' => 'lengow_global_options/advanced/last_marketplace_update',
            'global' => true,
            'no_cache' => true,
        ),
        'last_catalog_update' => array(
            'path' => 'lengow_global_options/advanced/last_catalog_update',
            'global' => true,
            'no_cache' => true,
        ),
        'last_setting_update' => array(
            'path' => 'lengow_global_options/advanced/last_setting_update',
            'global' => true,
            'no_cache' => true,
        ),
        'selection_enable' => array(
            'path' => 'lengow_export_options/simple/export_selection_enable',
            'store' => true,
            'no_cache' => false,
        ),
        'out_stock' => array(
            'path' => 'lengow_export_options/simple/export_out_stock',
            'store' => true,
            'no_cache' => false,
        ),
        'product_type' => array(
            'path' => 'lengow_export_options/simple/export_product_type',
            'store' => true,
            'no_cache' => false,
        ),
        'product_status' => array(
            'path' => 'lengow_export_options/simple/export_product_status',
            'store' => true,
            'no_cache' => false,
        ),
        'export_attribute' => array(
            'path' => 'lengow_export_options/advanced/export_attribute',
            'export' => false,
            'no_cache' => false,
        ),
        'shipping_country' => array(
            'path' => 'lengow_export_options/advanced/export_default_shipping_country',
            'store' => true,
            'no_cache' => false,
        ),
        'shipping_method' => array(
            'path' => 'lengow_export_options/advanced/export_default_shipping_method',
            'store' => true,
            'no_cache' => false,
        ),
        'shipping_price' => array(
            'path' => 'lengow_export_options/advanced/export_default_shipping_price',
            'store' => true,
            'no_cache' => false,
        ),
        'parent_image' => array(
            'path' => 'lengow_export_options/advanced/export_parent_image',
            'store' => true,
            'no_cache' => false,
        ),
        'file_enable' => array(
            'path' => 'lengow_export_options/advanced/export_file_enable',
            'global' => true,
            'no_cache' => false,
        ),
        'export_cron_enable' => array(
            'path' => 'lengow_export_options/advanced/export_cron_enable',
            'store' => true,
            'no_cache' => false,
        ),
        'last_export' => array(
            'path' => 'lengow_export_options/advanced/export_last_export',
            'store' => true,
            'no_cache' => true,
        ),
        'days' => array(
            'path' => 'lengow_import_options/simple/import_days',
            'store' => true,
            'no_cache' => false,
        ),
        'customer_group' => array(
            'path' => 'lengow_import_options/simple/import_customer_group',
            'store' => true,
            'no_cache' => false,
        ),
        'import_shipping_method' => array(
            'path' => 'lengow_import_options/simple/import_default_shipping_method',
            'store' => true,
            'no_cache' => false,
        ),
        'currency_conversion_enabled' => array(
            'path' => 'lengow_import_options/simple/import_currency_conversion_enable',
            'store' => true,
            'no_cache' => false,
        ),
        'report_mail_enable' => array(
            'path' => 'lengow_import_options/advanced/import_report_mail_enable',
            'global' => true,
            'no_cache' => false,
        ),
        'report_mail_address' => array(
            'path' => 'lengow_import_options/advanced/import_report_mail_address',
            'global' => true,
            'no_cache' => false,
        ),
        'import_ship_mp_enabled' => array(
            'path' => 'lengow_import_options/advanced/import_ship_mp_enabled',
            'global' => true,
            'no_cache' => false,
        ),
        'import_stock_ship_mp' => array(
            'path' => 'lengow_import_options/advanced/import_stock_ship_mp',
            'global' => true,
            'no_cache' => false,
        ),
        'preprod_mode_enable' => array(
            'path' => 'lengow_import_options/advanced/import_preprod_mode_enable',
            'global' => true,
            'no_cache' => false,
        ),
        'import_cron_enable' => array(
            'path' => 'lengow_import_options/advanced/import_cron_enable',
            'global' => true,
            'no_cache' => false,
        ),
        'import_in_progress' => array(
            'path' => 'lengow_import_options/advanced/import_in_progress',
            'global' => true,
            'no_cache' => true,
        ),
        'last_import_manual' => array(
            'path' => 'lengow_import_options/advanced/last_import_manual',
            'global' => true,
            'no_cache' => true,
        ),
        'last_import_cron' => array(
            'path' => 'lengow_import_options/advanced/last_import_cron',
            'global' => true,
            'no_cache' => true,
        ),
        'see_migrate_block' => array(
            'path' => 'lengow_import_options/advanced/see_migrate_block',
            'export' => false,
            'no_cache' => true,
        ),
    );

    /**
     * Get Value
     *
     * @param string $key Lengow setting key
     * @param integer $storeId Magento store id
     *
     * @return mixed
     */
    public function get($key, $storeId = 0)
    {
        if (!array_key_exists($key, $this->_options)) {
            return null;
        }
        if ($this->_options[$key]['no_cache']) {
            $collections = Mage::getModel('core/config_data')->getCollection()
                ->addFieldToFilter('path', $this->_options[$key]['path'])
                ->addFieldToFilter('scope_id', $storeId)
                ->getData();
            $value = count($collections) > 0 ? $collections[0]['value'] : '';
        } else {
            $value = Mage::getStoreConfig($this->_options[$key]['path'], $storeId);
        }
        return $value;
    }

    /**
     * Set Value
     *
     * @param string $key Lengow setting key
     * @param mixed $value Lengow setting value
     * @param integer $storeId Magento store id
     */
    public function set($key, $value, $storeId = 0)
    {
        if ($storeId == 0) {
            Mage::getModel('core/config')->saveConfig(
                $this->_options[$key]['path'],
                $value,
                'default',
                0
            );
        } else {
            Mage::getModel('core/config')->saveConfig(
                $this->_options[$key]['path'],
                $value,
                'stores',
                $storeId
            );
        }
    }

    /**
     * Get Valid Account / Access token / Secret token
     *
     * @return array
     */
    public function getAccessIds()
    {
        $accountId = $this->get('account_id');
        $accessToken = $this->get('access_token');
        $secretToken = $this->get('secret_token');
        if (strlen($accountId) > 0 && strlen($accessToken) > 0 && strlen($secretToken) > 0) {
            return array($accountId, $accessToken, $secretToken);
        } else {
            return array(null, null, null);
        }
    }

    /**
     * Set Valid Account id / Access token / Secret token
     *
     * @param array $accessIds Account id / Access token / Secret token
     */
    public function setAccessIds($accessIds)
    {
        $listKey = array('account_id', 'access_token', 'secret_token');
        foreach ($accessIds as $key => $value) {
            if (!in_array($key, array_keys($listKey))) {
                continue;
            }
            if (strlen($value) > 0) {
                $this->set($key, $value);
            }
        }
    }

    /**
     * Get catalog ids for a specific store
     *
     * @param integer $storeId Magento store id
     *
     * @return array
     */
    public function getCatalogIds($storeId)
    {
        $catalogIds = array();
        $storeCatalogIds = $this->get('catalog_id', $storeId);
        if (strlen($storeCatalogIds) > 0 && $storeCatalogIds != 0) {
            $ids = trim(str_replace(array("\r\n", ',', '-', '|', ' ', '/'), ';', $storeCatalogIds), ';');
            $ids = array_filter(explode(';', $ids));
            foreach ($ids as $id) {
                if (is_numeric($id) && $id > 0) {
                    $catalogIds[] = (int)$id;
                }
            }
        }
        return $catalogIds;
    }

    /**
     * Set catalog ids for a specific shop
     *
     * @param array $catalogIds Lengow catalog ids
     * @param integer $storeId Magento store id
     *
     * @return boolean
     */
    public function setCatalogIds($catalogIds, $storeId)
    {
        $valueChange = false;
        $storeCatalogIds = self::getCatalogIds($storeId);
        foreach ($catalogIds as $catalogId) {
            if (!in_array($catalogId, $storeCatalogIds) && is_numeric($catalogId) && $catalogId > 0) {
                $storeCatalogIds[] = (int)$catalogId;
                $valueChange = true;
            }
        }
        $this->set('catalog_id', implode(';', $storeCatalogIds), $storeId);
        return $valueChange;
    }

    /**
     * Recovers if a store is active or not
     *
     * @param integer $storeId Magento store id
     *
     * @return boolean
     */
    public function storeIsActive($storeId)
    {
        return (bool)$this->get('store_enable', $storeId);
    }

    /**
     * Set active store or not
     *
     * @param integer $storeId Magento store id
     *
     * @return boolean
     */
    public function setActiveStore($storeId)
    {
        $storeIsActive = $this->storeIsActive($storeId);
        $storeHasCatalog = count(self::getCatalogIds($storeId)) > 0;
        $this->set('store_enable', $storeHasCatalog, $storeId);
        return $storeIsActive !== $storeHasCatalog ? true : false;
    }

    /**
     * Get Selected attributes
     *
     * @param integer $storeId Magento store id
     *
     * @return array
     */
    public function getSelectedAttributes($storeId = 0)
    {
        $tab = array();
        $attributeSelected = array();
        $attributes = $this->get('export_attribute', $storeId);
        if (!empty($attributes)) {
            $tab = explode(',', $attributes);
        }
        if (!empty($tab)) {
            foreach ($tab as $value) {
                $attributeSelected[$value] = $value;
            }
        }
        return $attributeSelected;
    }

    /**
     * Generate token
     *
     * @param integer $storeId Magento store id
     *
     * @return string
     */
    public function getToken($storeId = 0)
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
     * @param string $token Lengow store token
     *
     * @return Mage_Core_Model_Store|false
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
                    if ($token === $this->get('token', $store->getId())) {
                        return $store;
                    }
                }
            }
        }
        return false;
    }

    /**
     * Get all report mails
     *
     * @return array
     */
    public function getReportEmailAddress()
    {
        $reportEmailAddress = array();
        $emails = $this->get('report_mail_address');
        $emails = trim(str_replace(array("\r\n", ',', ' '), ';', $emails), ';');
        $emails = explode(';', $emails);
        foreach ($emails as $email) {
            try {
                if (strlen($email) > 0 && Zend_Validate::is($email, 'EmailAddress')) {
                    $reportEmailAddress[] = $email;
                }
            } catch (\Exception $e) {
                continue;
            }
        }
        if (empty($reportEmailAddress)) {
            $reportEmailAddress[] = Mage::getStoreConfig('trans_email/ident_general/email');
        }
        return $reportEmailAddress;
    }

    /**
     * Get all available currency codes
     *
     * @return array
     */
    public function getAllAvailableCurrencyCodes()
    {
        $allCurrencies = array();
        $storeCollection = Mage::getResourceModel('core/store_collection')->addFieldToFilter('is_active', 1);
        foreach ($storeCollection as $store) {
            // get store currencies
            try {
                $storeCurrencies = Mage::app()->getStore($store->getId())->getAvailableCurrencyCodes();
                if (is_array($storeCurrencies)) {
                    foreach ($storeCurrencies as $currency) {
                        if (!in_array($currency, $allCurrencies)) {
                            $allCurrencies[] = $currency;
                        }
                    }
                }
            } catch (\Exception $e) {
                continue;
            }
        }
        return $allCurrencies;
    }

    /**
     * Check if is a new merchant
     *
     * @return boolean
     */
    public function isNewMerchant()
    {
        list($accountId, $accessToken, $secretToken) = $this->getAccessIds();
        if (!is_null($accountId) && !is_null($accessToken) && !is_null($secretToken)) {
            return false;
        }
        return true;
    }

    /**
     * Get Values by store or global
     *
     * @param integer|null $storeId Magento store id
     *
     * @return array
     */
    public function getAllValues($storeId = null)
    {
        $rows = array();
        foreach ($this->_options as $key => $value) {
            if (isset($value['export']) && !$value['export']) {
                continue;
            }
            if ($storeId) {
                if (isset($value['store']) && $value['store']) {
                    $rows[$key] = $this->get($key, $storeId);
                }
            } else {
                if (isset($value['global']) && $value['global']) {
                    $rows[$key] = $this->get($key);
                }
            }
        }
        return $rows;
    }
}
