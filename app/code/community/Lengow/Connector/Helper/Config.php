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
    /* Settings database key */
    const ACCOUNT_ID = 'global_account_id';
    const ACCESS_TOKEN = 'global_access_token';
    const SECRET = 'global_secret_token';
    const CMS_TOKEN = 'token';
    const AUTHORIZED_IP_ENABLED = 'global_authorized_ip_enable';
    const AUTHORIZED_IPS = 'global_authorized_ip';
    const TRACKING_ENABLED = 'global_tracking_enable';
    const TRACKING_ID = 'global_tracking_id';
    const DEBUG_MODE_ENABLED = 'import_debug_mode_enable';
    const REPORT_MAIL_ENABLED = 'import_report_mail_enable';
    const REPORT_MAILS = 'import_report_mail_address';
    const MIGRATE_BLOCK_ENABLED = 'see_migrate_block';
    const PLUGIN_VERSION = 'installed_version';
    const AUTHORIZATION_TOKEN = 'authorization_token';
    const PLUGIN_DATA = 'plugin_data';
    const ACCOUNT_STATUS_DATA = 'account_status';
    const SHOP_TOKEN = 'global_shop_token';
    const SHOP_ACTIVE = 'global_store_enable';
    const CATALOG_IDS = 'global_catalog_id';
    const SELECTION_ENABLED = 'export_selection_enable';
    const INACTIVE_ENABLED = 'export_inactive_enable';
    const OUT_OF_STOCK_ENABLED = 'export_out_stock';
    const EXPORT_PRODUCT_TYPES = 'export_product_type';
    const EXPORT_ATTRIBUTES = 'export_attribute';
    const EXPORT_PARENT_ATTRIBUTES = 'export_parent_attribute';
    const EXPORT_PARENT_IMAGE_ENABLED = 'export_parent_image';
    const EXPORT_FILE_ENABLED = 'export_file_enable';
    const EXPORT_MAGENTO_CRON_ENABLED = 'export_cron_enable';
    const DEFAULT_EXPORT_SHIPPING_COUNTRY = 'export_default_shipping_country';
    const DEFAULT_EXPORT_CARRIER_ID = 'export_default_shipping_method';
    const DEFAULT_EXPORT_SHIPPING_PRICE = 'export_default_shipping_price';
    const SYNCHRONIZATION_DAY_INTERVAL = 'import_days';
    const DEFAULT_IMPORT_CARRIER_ID = 'import_default_shipping_method';
    const CURRENCY_CONVERSION_ENABLED = 'import_currency_conversion_enable';
    const B2B_WITHOUT_TAX_ENABLED = 'import_b2b_without_tax';
    const SHIPPED_BY_MARKETPLACE_ENABLED = 'import_ship_mp_enabled';
    const SHIPPED_BY_MARKETPLACE_STOCK_ENABLED = 'import_stock_ship_mp';
    const SYNCHRONISATION_MAGENTO_CRON_ENABLED = 'import_cron_enable';
    const SYNCHRONISATION_CUSTOMER_GROUP = 'import_customer_group';
    const SYNCHRONIZATION_IN_PROGRESS = 'import_in_progress';
    const LAST_UPDATE_EXPORT = 'export_last_export';
    const LAST_UPDATE_CRON_SYNCHRONIZATION = 'last_import_cron';
    const LAST_UPDATE_MANUAL_SYNCHRONIZATION = 'last_import_manual';
    const LAST_UPDATE_ACTION_SYNCHRONIZATION = 'last_action_sync';
    const LAST_UPDATE_CATALOG = 'last_catalog_update';
    const LAST_UPDATE_MARKETPLACE = 'last_marketplace_update';
    const LAST_UPDATE_ACCOUNT_STATUS_DATA = 'last_status_update';
    const LAST_UPDATE_OPTION_CMS = 'last_option_cms_update';
    const LAST_UPDATE_SETTING = 'last_setting_update';
    const LAST_UPDATE_PLUGIN_DATA = 'last_plugin_data_update';
    const LAST_UPDATE_AUTHORIZATION_TOKEN = 'last_authorization_token_update';
    const LAST_UPDATE_PLUGIN_MODAL = 'last_plugin_modal_update';

    /* Configuration parameters */
    const PARAM_EXPORT = 'export';
    const PARAM_EXPORT_TOOLBOX = 'export_toolbox';
    const PARAM_GLOBAL = 'global';
    const PARAM_LOG = 'log';
    const PARAM_NO_CACHE = 'no_cache';
    const PARAM_RESET_TOKEN = 'reset_token';
    const PARAM_RETURN = 'return';
    const PARAM_SECRET = 'secret';
    const PARAM_SHOP = 'store';
    const PARAM_PATH = 'path';
    const PARAM_UPDATE = 'update';

    /* Configuration value return type */
    const RETURN_TYPE_BOOLEAN = 'boolean';
    const RETURN_TYPE_INTEGER = 'integer';
    const RETURN_TYPE_ARRAY = 'array';
    const RETURN_TYPE_FLOAT = 'float';

    /**
     * @var array params correspondence keys for toolbox
     */
    public static $genericParamKeys = array(
        self::ACCOUNT_ID => 'account_id',
        self::ACCESS_TOKEN => 'access_token',
        self::SECRET => 'secret',
        self::CMS_TOKEN => 'cms_token',
        self::AUTHORIZED_IP_ENABLED => 'authorized_ip_enabled',
        self::AUTHORIZED_IPS => 'authorized_ips',
        self::TRACKING_ENABLED => 'tracking_enabled',
        self::TRACKING_ID => 'tracking_id',
        self::DEBUG_MODE_ENABLED => 'debug_mode_enabled',
        self::REPORT_MAIL_ENABLED => 'report_mail_enabled',
        self::REPORT_MAILS => 'report_mails',
        self::MIGRATE_BLOCK_ENABLED => 'migrate_block_enabled',
        self::PLUGIN_VERSION => 'plugin_version',
        self::AUTHORIZATION_TOKEN => 'authorization_token',
        self::PLUGIN_DATA => 'plugin_data',
        self::ACCOUNT_STATUS_DATA => 'account_status_data',
        self::SHOP_TOKEN => 'shop_token',
        self::SHOP_ACTIVE => 'shop_active',
        self::CATALOG_IDS => 'catalog_ids',
        self::SELECTION_ENABLED => 'selection_enabled',
        self::INACTIVE_ENABLED => 'inactive_enabled',
        self::OUT_OF_STOCK_ENABLED => 'out_of_stock_enabled',
        self::EXPORT_PRODUCT_TYPES => 'export_product_types',
        self::EXPORT_ATTRIBUTES => 'export_attributes',
        self::EXPORT_PARENT_ATTRIBUTES => 'export_parent_attributes',
        self::EXPORT_PARENT_IMAGE_ENABLED => 'export_parent_image_enabled',
        self::EXPORT_FILE_ENABLED => 'export_file_enabled',
        self::EXPORT_MAGENTO_CRON_ENABLED => 'export_magento_cron_enable',
        self::DEFAULT_EXPORT_SHIPPING_COUNTRY => 'default_export_shipping_country',
        self::DEFAULT_EXPORT_CARRIER_ID => 'default_export_carrier_id',
        self::DEFAULT_EXPORT_SHIPPING_PRICE => 'default_export_shipping_price',
        self::SYNCHRONIZATION_DAY_INTERVAL => 'synchronization_day_interval',
        self::DEFAULT_IMPORT_CARRIER_ID => 'default_import_carrier_id',
        self::CURRENCY_CONVERSION_ENABLED => 'currency_conversion_enabled',
        self::B2B_WITHOUT_TAX_ENABLED => 'b2b_without_tax_enabled',
        self::SHIPPED_BY_MARKETPLACE_ENABLED => 'shipped_by_marketplace_enabled',
        self::SHIPPED_BY_MARKETPLACE_STOCK_ENABLED => 'shipped_by_marketplace_stock_enabled',
        self::SYNCHRONISATION_MAGENTO_CRON_ENABLED => 'synchronization_magento_cron_enabled',
        self::SYNCHRONISATION_CUSTOMER_GROUP => 'synchronization_customer_group',
        self::SYNCHRONIZATION_IN_PROGRESS => 'synchronization_in_progress',
        self::LAST_UPDATE_EXPORT => 'last_update_export',
        self::LAST_UPDATE_CRON_SYNCHRONIZATION => 'last_update_cron_synchronization',
        self::LAST_UPDATE_MANUAL_SYNCHRONIZATION => 'last_update_manual_synchronization',
        self::LAST_UPDATE_ACTION_SYNCHRONIZATION => 'last_update_action_synchronization',
        self::LAST_UPDATE_CATALOG => 'last_update_catalog',
        self::LAST_UPDATE_MARKETPLACE => 'last_update_marketplace',
        self::LAST_UPDATE_ACCOUNT_STATUS_DATA => 'last_update_account_status_data',
        self::LAST_UPDATE_OPTION_CMS => 'last_update_option_cms',
        self::LAST_UPDATE_SETTING => 'last_update_setting',
        self::LAST_UPDATE_PLUGIN_DATA => 'last_update_plugin_data',
        self::LAST_UPDATE_AUTHORIZATION_TOKEN => 'last_update_authorization_token',
        self::LAST_UPDATE_PLUGIN_MODAL => 'last_update_plugin_modal',
    );

    /**
     * @var array all Lengow options path
     */
    public static $lengowSettings = array(
        self::ACCOUNT_ID => array(
            self::PARAM_PATH => 'lengow_global_options/store_credential/global_account_id',
            self::PARAM_GLOBAL => true,
            self::PARAM_NO_CACHE => true,
            self::PARAM_EXPORT => false,
        ),
        self::ACCESS_TOKEN => array(
            self::PARAM_PATH => 'lengow_global_options/store_credential/global_access_token',
            self::PARAM_GLOBAL => true,
            self::PARAM_NO_CACHE => true,
            self::PARAM_EXPORT => false,
            self::PARAM_SECRET => true,
            self::PARAM_RESET_TOKEN => true,
        ),
        self::SECRET => array(
            self::PARAM_PATH => 'lengow_global_options/store_credential/global_secret_token',
            self::PARAM_GLOBAL => true,
            self::PARAM_NO_CACHE => true,
            self::PARAM_EXPORT => false,
            self::PARAM_SECRET => true,
            self::PARAM_RESET_TOKEN => true,
        ),
        self::CMS_TOKEN => array(
            self::PARAM_PATH => 'lengow_global_options/store_credential/token',
            self::PARAM_GLOBAL => true,
            self::PARAM_SHOP => true,
            self::PARAM_NO_CACHE => true,
            self::PARAM_EXPORT_TOOLBOX => false,
        ),
        self::AUTHORIZED_IP_ENABLED => array(
            self::PARAM_PATH => 'lengow_global_options/advanced/global_authorized_ip_enable',
            self::PARAM_GLOBAL => true,
            self::PARAM_NO_CACHE => false,
            self::PARAM_EXPORT_TOOLBOX => false,
            self::PARAM_RETURN => self::RETURN_TYPE_BOOLEAN,
        ),
        self::AUTHORIZED_IPS => array(
            self::PARAM_PATH => 'lengow_global_options/advanced/global_authorized_ip',
            self::PARAM_GLOBAL => true,
            self::PARAM_NO_CACHE => false,
            self::PARAM_EXPORT_TOOLBOX => false,
            self::PARAM_RETURN => self::RETURN_TYPE_ARRAY,
        ),
        self::TRACKING_ENABLED => array(
            self::PARAM_PATH => 'lengow_global_options/advanced/global_tracking_enable',
            self::PARAM_GLOBAL => true,
            self::PARAM_NO_CACHE => false,
            self::PARAM_RETURN => self::RETURN_TYPE_BOOLEAN,
        ),
        self::TRACKING_ID => array(
            self::PARAM_PATH => 'lengow_global_options/advanced/global_tracking_id',
            self::PARAM_GLOBAL => true,
            self::PARAM_NO_CACHE => false,
        ),
        self::DEBUG_MODE_ENABLED => array(
            self::PARAM_PATH => 'lengow_import_options/advanced/import_debug_mode_enable',
            self::PARAM_GLOBAL => true,
            self::PARAM_NO_CACHE => false,
            self::PARAM_EXPORT_TOOLBOX => false,
            self::PARAM_RETURN => self::RETURN_TYPE_BOOLEAN,
        ),
        self::REPORT_MAIL_ENABLED => array(
            self::PARAM_PATH => 'lengow_import_options/advanced/import_report_mail_enable',
            self::PARAM_GLOBAL => true,
            self::PARAM_NO_CACHE => false,
            self::PARAM_RETURN => self::RETURN_TYPE_BOOLEAN,
        ),
        self::REPORT_MAILS => array(
            self::PARAM_PATH => 'lengow_import_options/advanced/import_report_mail_address',
            self::PARAM_GLOBAL => true,
            self::PARAM_NO_CACHE => false,
            self::PARAM_RETURN => self::RETURN_TYPE_ARRAY,
        ),
        self::MIGRATE_BLOCK_ENABLED => array(
            self::PARAM_PATH => 'lengow_import_options/advanced/see_migrate_block',
            self::PARAM_NO_CACHE => true,
            self::PARAM_RETURN => self::RETURN_TYPE_BOOLEAN,
            self::PARAM_LOG => false,
        ),
        self::PLUGIN_VERSION => array(
            self::PARAM_PATH => 'lengow_global_options/advanced/installed_version',
            self::PARAM_NO_CACHE => true,
            self::PARAM_EXPORT => false,
            self::PARAM_LOG => false,
        ),
        self::AUTHORIZATION_TOKEN => array(
            self::PARAM_PATH => 'lengow_global_options/store_credential/authorization_token',
            self::PARAM_GLOBAL => true,
            self::PARAM_NO_CACHE => true,
            self::PARAM_EXPORT => false,
            self::PARAM_LOG => false,
        ),
        self::PLUGIN_DATA => array(
            self::PARAM_PATH => 'lengow_global_options/advanced/plugin_data',
            self::PARAM_GLOBAL => true,
            self::PARAM_NO_CACHE => true,
            self::PARAM_EXPORT => false,
            self::PARAM_LOG => false,
        ),
        self::ACCOUNT_STATUS_DATA => array(
            self::PARAM_PATH => 'lengow_global_options/advanced/account_status',
            self::PARAM_NO_CACHE => true,
            self::PARAM_EXPORT => false,
            self::PARAM_LOG => false,
        ),
        self::SHOP_ACTIVE => array(
            self::PARAM_PATH => 'lengow_global_options/store_credential/global_store_enable',
            self::PARAM_SHOP => true,
            self::PARAM_NO_CACHE => true,
            self::PARAM_EXPORT_TOOLBOX => false,
            self::PARAM_RETURN => self::RETURN_TYPE_BOOLEAN,
        ),
        self::CATALOG_IDS => array(
            self::PARAM_PATH => 'lengow_global_options/store_credential/global_catalog_id',
            self::PARAM_SHOP => true,
            self::PARAM_NO_CACHE => true,
            self::PARAM_EXPORT_TOOLBOX => false,
            self::PARAM_UPDATE => true,
            self::PARAM_RETURN => self::RETURN_TYPE_ARRAY,
        ),
        self::SELECTION_ENABLED => array(
            self::PARAM_PATH => 'lengow_export_options/simple/export_selection_enable',
            self::PARAM_SHOP => true,
            self::PARAM_NO_CACHE => false,
            self::PARAM_RETURN => self::RETURN_TYPE_BOOLEAN,
        ),
        self::INACTIVE_ENABLED => array(
            self::PARAM_PATH => 'lengow_export_options/simple/export_inactive_enable',
            self::PARAM_SHOP => true,
            self::PARAM_NO_CACHE => false,
            self::PARAM_RETURN => self::RETURN_TYPE_BOOLEAN,
        ),
        self::OUT_OF_STOCK_ENABLED => array(
            self::PARAM_PATH => 'lengow_export_options/simple/export_out_stock',
            self::PARAM_SHOP => true,
            self::PARAM_NO_CACHE => false,
            self::PARAM_RETURN => self::RETURN_TYPE_BOOLEAN,
        ),
        self::EXPORT_PRODUCT_TYPES => array(
            self::PARAM_PATH => 'lengow_export_options/simple/export_product_type',
            self::PARAM_SHOP => true,
            self::PARAM_NO_CACHE => false,
            self::PARAM_RETURN => self::RETURN_TYPE_ARRAY,
        ),
        self::EXPORT_ATTRIBUTES => array(
            self::PARAM_PATH => 'lengow_export_options/advanced/export_attribute',
            self::PARAM_SHOP => true,
            self::PARAM_NO_CACHE => false,
            self::PARAM_RETURN => self::RETURN_TYPE_ARRAY,
        ),
        self::EXPORT_PARENT_ATTRIBUTES => array(
            self::PARAM_PATH => 'lengow_export_options/advanced/export_parent_attribute',
            self::PARAM_SHOP => true,
            self::PARAM_NO_CACHE => false,
            self::PARAM_RETURN => self::RETURN_TYPE_ARRAY,
        ),
        self::EXPORT_PARENT_IMAGE_ENABLED => array(
            self::PARAM_PATH => 'lengow_export_options/advanced/export_parent_image',
            self::PARAM_SHOP => true,
            self::PARAM_NO_CACHE => false,
            self::PARAM_RETURN => self::RETURN_TYPE_BOOLEAN,
        ),
        self::EXPORT_FILE_ENABLED => array(
            self::PARAM_PATH => 'lengow_export_options/advanced/export_file_enable',
            self::PARAM_GLOBAL => true,
            self::PARAM_NO_CACHE => false,
            self::PARAM_RETURN => self::RETURN_TYPE_BOOLEAN,
        ),
        self::EXPORT_MAGENTO_CRON_ENABLED => array(
            self::PARAM_PATH => 'lengow_export_options/advanced/export_cron_enable',
            self::PARAM_SHOP => true,
            self::PARAM_NO_CACHE => false,
            self::PARAM_RETURN => self::RETURN_TYPE_BOOLEAN,
        ),
        self::DEFAULT_EXPORT_SHIPPING_COUNTRY => array(
            self::PARAM_PATH => 'lengow_export_options/advanced/export_default_shipping_country',
            self::PARAM_SHOP => true,
            self::PARAM_NO_CACHE => false,
        ),
        self::DEFAULT_EXPORT_CARRIER_ID => array(
            self::PARAM_PATH => 'lengow_export_options/advanced/export_default_shipping_method',
            self::PARAM_SHOP => true,
            self::PARAM_NO_CACHE => false,
        ),
        self::DEFAULT_EXPORT_SHIPPING_PRICE => array(
            self::PARAM_PATH => 'lengow_export_options/advanced/export_default_shipping_price',
            self::PARAM_SHOP => true,
            self::PARAM_NO_CACHE => false,
            self::PARAM_RETURN => self::RETURN_TYPE_FLOAT,
        ),
        self::SYNCHRONIZATION_DAY_INTERVAL => array(
            self::PARAM_PATH => 'lengow_import_options/simple/import_days',
            self::PARAM_SHOP => true,
            self::PARAM_NO_CACHE => false,
            self::PARAM_UPDATE => true,
            self::PARAM_RETURN => self::RETURN_TYPE_INTEGER,
        ),
        self::DEFAULT_IMPORT_CARRIER_ID => array(
            self::PARAM_PATH => 'lengow_import_options/simple/import_default_shipping_method',
            self::PARAM_SHOP => true,
            self::PARAM_NO_CACHE => false,
        ),
        self::CURRENCY_CONVERSION_ENABLED => array(
            self::PARAM_PATH => 'lengow_import_options/simple/import_currency_conversion_enable',
            self::PARAM_SHOP => true,
            self::PARAM_NO_CACHE => false,
            self::PARAM_RETURN => self::RETURN_TYPE_BOOLEAN,
        ),
        self::B2B_WITHOUT_TAX_ENABLED => array(
            self::PARAM_PATH => 'lengow_import_options/advanced/import_b2b_without_tax',
            self::PARAM_GLOBAL => true,
            self::PARAM_NO_CACHE => false,
            self::PARAM_RETURN => self::RETURN_TYPE_BOOLEAN,
        ),
        self::SHIPPED_BY_MARKETPLACE_ENABLED => array(
            self::PARAM_PATH => 'lengow_import_options/advanced/import_ship_mp_enabled',
            self::PARAM_GLOBAL => true,
            self::PARAM_NO_CACHE => false,
            self::PARAM_RETURN => self::RETURN_TYPE_BOOLEAN,
        ),
        self::SHIPPED_BY_MARKETPLACE_STOCK_ENABLED => array(
            self::PARAM_PATH => 'lengow_import_options/advanced/import_stock_ship_mp',
            self::PARAM_GLOBAL => true,
            self::PARAM_NO_CACHE => false,
            self::PARAM_RETURN => self::RETURN_TYPE_BOOLEAN,
        ),
        self::SYNCHRONISATION_MAGENTO_CRON_ENABLED => array(
            self::PARAM_PATH => 'lengow_import_options/advanced/import_cron_enable',
            self::PARAM_GLOBAL => true,
            self::PARAM_NO_CACHE => false,
            self::PARAM_RETURN => self::RETURN_TYPE_BOOLEAN,
        ),
        self::SYNCHRONISATION_CUSTOMER_GROUP => array(
            self::PARAM_PATH => 'lengow_import_options/simple/import_customer_group',
            self::PARAM_SHOP => true,
            self::PARAM_NO_CACHE => false,
            self::PARAM_RETURN => self::RETURN_TYPE_INTEGER,
        ),
        self::SYNCHRONIZATION_IN_PROGRESS => array(
            self::PARAM_PATH => 'lengow_import_options/advanced/import_in_progress',
            self::PARAM_GLOBAL => true,
            self::PARAM_NO_CACHE => true,
            self::PARAM_EXPORT => false,
            self::PARAM_LOG => false,
        ),
        self::LAST_UPDATE_EXPORT => array(
            self::PARAM_PATH => 'lengow_export_options/advanced/export_last_export',
            self::PARAM_SHOP => true,
            self::PARAM_NO_CACHE => true,
            self::PARAM_EXPORT_TOOLBOX => false,
            self::PARAM_RETURN => self::RETURN_TYPE_INTEGER,
            self::PARAM_LOG => false,
        ),
        self::LAST_UPDATE_CRON_SYNCHRONIZATION => array(
            self::PARAM_PATH => 'lengow_import_options/advanced/last_import_cron',
            self::PARAM_GLOBAL => true,
            self::PARAM_NO_CACHE => true,
            self::PARAM_EXPORT_TOOLBOX => false,
            self::PARAM_RETURN => self::RETURN_TYPE_INTEGER,
            self::PARAM_LOG => false,
        ),
        self::LAST_UPDATE_MANUAL_SYNCHRONIZATION => array(
            self::PARAM_PATH => 'lengow_import_options/advanced/last_import_manual',
            self::PARAM_GLOBAL => true,
            self::PARAM_NO_CACHE => true,
            self::PARAM_EXPORT_TOOLBOX => false,
            self::PARAM_RETURN => self::RETURN_TYPE_INTEGER,
            self::PARAM_LOG => false,
        ),
        self::LAST_UPDATE_ACTION_SYNCHRONIZATION => array(
            self::PARAM_PATH => 'lengow_import_options/advanced/last_action_sync',
            self::PARAM_GLOBAL => true,
            self::PARAM_NO_CACHE => true,
            self::PARAM_RETURN => self::RETURN_TYPE_INTEGER,
            self::PARAM_LOG => false,
        ),
        self::LAST_UPDATE_CATALOG => array(
            self::PARAM_PATH => 'lengow_global_options/advanced/last_catalog_update',
            self::PARAM_GLOBAL => true,
            self::PARAM_NO_CACHE => true,
            self::PARAM_RETURN => self::RETURN_TYPE_INTEGER,
            self::PARAM_LOG => false,
        ),
        self::LAST_UPDATE_MARKETPLACE => array(
            self::PARAM_PATH => 'lengow_global_options/advanced/last_marketplace_update',
            self::PARAM_GLOBAL => true,
            self::PARAM_NO_CACHE => true,
            self::PARAM_RETURN => self::RETURN_TYPE_INTEGER,
            self::PARAM_LOG => false,
        ),
        self::LAST_UPDATE_ACCOUNT_STATUS_DATA => array(
            self::PARAM_PATH => 'lengow_global_options/advanced/last_status_update',
            self::PARAM_GLOBAL => true,
            self::PARAM_NO_CACHE => true,
            self::PARAM_RETURN => self::RETURN_TYPE_INTEGER,
            self::PARAM_LOG => false,
        ),
        self::LAST_UPDATE_OPTION_CMS => array(
            self::PARAM_PATH => 'lengow_global_options/advanced/last_option_cms_update',
            self::PARAM_GLOBAL => true,
            self::PARAM_NO_CACHE => true,
            self::PARAM_RETURN => self::RETURN_TYPE_INTEGER,
            self::PARAM_LOG => false,
        ),
        self::LAST_UPDATE_SETTING => array(
            self::PARAM_PATH => 'lengow_global_options/advanced/last_setting_update',
            self::PARAM_GLOBAL => true,
            self::PARAM_NO_CACHE => true,
            self::PARAM_RETURN => self::RETURN_TYPE_INTEGER,
            self::PARAM_LOG => false,
        ),
        self::LAST_UPDATE_PLUGIN_DATA => array(
            self::PARAM_PATH => 'lengow_global_options/advanced/last_plugin_data_update',
            self::PARAM_GLOBAL => true,
            self::PARAM_NO_CACHE => true,
            self::PARAM_RETURN => self::RETURN_TYPE_INTEGER,
            self::PARAM_LOG => false,
        ),
        self::LAST_UPDATE_AUTHORIZATION_TOKEN => array(
            self::PARAM_PATH => 'lengow_global_options/store_credential/last_authorization_token_update',
            self::PARAM_GLOBAL => true,
            self::PARAM_NO_CACHE => true,
            self::PARAM_RETURN => self::RETURN_TYPE_INTEGER,
            self::PARAM_LOG => false,
        ),
        self::LAST_UPDATE_PLUGIN_MODAL => array(
            self::PARAM_PATH => 'lengow_global_options/store_credential/last_plugin_modal_update',
            self::PARAM_GLOBAL => true,
            self::PARAM_NO_CACHE => true,
            self::PARAM_RETURN => self::RETURN_TYPE_INTEGER,
            self::PARAM_LOG => false,
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
        if (!array_key_exists($key, self::$lengowSettings)) {
            return null;
        }
        if (self::$lengowSettings[$key][self::PARAM_NO_CACHE]) {
            $collections = Mage::getModel('core/config_data')->getCollection()
                ->addFieldToFilter('path', self::$lengowSettings[$key][self::PARAM_PATH])
                ->addFieldToFilter('scope_id', $storeId)
                ->getData();
            $value = !empty($collections) ? $collections[0]['value'] : '';
        } else {
            $value = Mage::getStoreConfig(self::$lengowSettings[$key][self::PARAM_PATH], $storeId);
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
        if ($storeId === 0) {
            Mage::getModel('core/config')->saveConfig(
                self::$lengowSettings[$key][self::PARAM_PATH],
                $value,
                'default',
                0
            );
        } else {
            Mage::getModel('core/config')->saveConfig(
                self::$lengowSettings[$key][self::PARAM_PATH],
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
        $accountId = $this->get(self::ACCOUNT_ID);
        $accessToken = $this->get(self::ACCESS_TOKEN);
        $secretToken = $this->get(self::SECRET);
        if ($accountId !== '' && $accessToken !== '' && $secretToken !== '') {
            return array($accountId, $accessToken, $secretToken);
        }
        return array(null, null, null);
    }

    /**
     * Set Valid Account id / Access token / Secret token
     *
     * @param array $accessIds Account id / Access token / Secret token
     *
     * @return boolean
     */
    public function setAccessIds($accessIds)
    {
        $count = 0;
        $listKey = array(self::ACCOUNT_ID, self::ACCESS_TOKEN, self::SECRET);
        foreach ($accessIds as $key => $value) {
            if (!in_array($key, $listKey, true)) {
                continue;
            }
            if ($value !== '') {
                $count++;
                $this->set($key, $value);
            }
        }
        return $count === count($listKey);
    }

    /**
     * Reset access ids for old customer
     */
    public function resetAccessIds()
    {
        $accessIds = array(self::ACCOUNT_ID, self::ACCESS_TOKEN, self::SECRET);
        foreach ($accessIds as $accessId) {
            $value = $this->get($accessId);
            if ($value !== '') {
                $this->set($accessId, '');
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
        $storeCatalogIds = $this->get(self::CATALOG_IDS, $storeId);
        if (!empty($storeCatalogIds)) {
            $ids = trim(str_replace(array("\r\n", ',', '-', '|', ' ', '/'), ';', $storeCatalogIds), ';');
            $ids = array_filter(explode(';', $ids));
            foreach ($ids as $id) {
                if (is_numeric($id) && $id > 0) {
                    $catalogIds[] = (int) $id;
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
        $storeCatalogIds = $this->getCatalogIds($storeId);
        foreach ($catalogIds as $catalogId) {
            if ($catalogId > 0 && is_numeric($catalogId) && !in_array($catalogId, $storeCatalogIds, true)) {
                $storeCatalogIds[] = (int) $catalogId;
                $valueChange = true;
            }
        }
        $this->set(self::CATALOG_IDS, implode(';', $storeCatalogIds), $storeId);
        return $valueChange;
    }

    /**
     * Reset all catalog ids
     */
    public function resetCatalogIds()
    {
        $lengowActiveStores = $this->getLengowActiveStores();
        foreach ($lengowActiveStores as $store) {
            $this->set(self::CATALOG_IDS, '', $store->getId());
            $this->set(self::SHOP_ACTIVE, false, $store->getId());
        }
    }

    /**
     * Reset authorization token
     */
    public function resetAuthorizationToken()
    {
        $this->set(self::AUTHORIZATION_TOKEN, '');
        $this->set(self::LAST_UPDATE_AUTHORIZATION_TOKEN, '');
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
        return (bool) $this->get(self::SHOP_ACTIVE, $storeId);
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
        $catalogIds = $this->getCatalogIds($storeId);
        $storeHasCatalog = !empty($catalogIds);
        $this->set(self::SHOP_ACTIVE, $storeHasCatalog, $storeId);
        return $storeIsActive !== $storeHasCatalog;
    }

    /**
     * Recovers if debug mode is active or not
     *
     * @return boolean
     */
    public function debugModeIsActive()
    {
        return (bool) $this->get(self::DEBUG_MODE_ENABLED);
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
        $attributes = $this->get(self::EXPORT_ATTRIBUTES, $storeId);
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
     * Get parent selected attributes to export instead of child data
     *
     * @param integer $storeId Magento store id
     *
     * @return array
     */
    public function getParentSelectedAttributes($storeId = 0)
    {
        $selectedAttributes = array();
        $attributes = $this->get(self::EXPORT_PARENT_ATTRIBUTES, $storeId);
        if ($attributes !== null) {
            $attributes = explode(',', $attributes);
            foreach ($attributes as $attribute) {
                $selectedAttributes[] = $attribute;
            }
        }
        return $selectedAttributes;
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
        $token = $this->get(self::CMS_TOKEN, $storeId);
        if ($token && $token !== '') {
            return $token;
        }
        $token = bin2hex(openssl_random_pseudo_bytes(16));
        $this->set(self::CMS_TOKEN, $token, $storeId);
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
                    if ($token === $this->get(self::CMS_TOKEN, $store->getId())) {
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
        $emails = $this->get(self::REPORT_MAILS);
        $emails = trim(str_replace(array("\r\n", ',', ' '), ';', $emails), ';');
        $emails = explode(';', $emails);
        foreach ($emails as $email) {
            try {
                if ($email !== '' && Zend_Validate::is($email, 'EmailAddress')) {
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
     * Get authorized IPs
     *
     * @return array
     */
    public function getAuthorizedIps()
    {
        $authorizedIps = array();
        $ips = $this->get(self::AUTHORIZED_IPS);
        if (!empty($ips)) {
            $authorizedIps = trim(str_replace(array("\r\n", ',', '-', '|', ' '), ';', $ips), ';');
            $authorizedIps = array_filter(explode(';', $authorizedIps));
        }
        return $authorizedIps;
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
                        if (!in_array($currency, $allCurrencies, true)) {
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
     * Get list of Magento stores that have been activated in Lengow
     *
     * @param integer|null $storeId Magento store id
     *
     * @return array
     */
    public function getLengowActiveStores($storeId = null)
    {
        $lengowActiveStores = array();
        $storeCollection = Mage::getResourceModel('core/store_collection')->addFieldToFilter('is_active', 1);
        foreach ($storeCollection as $store) {
            if ($storeId && (int) $store->getId() !== $storeId) {
                continue;
            }
            // get Lengow config for this store
            if ($this->storeIsActive((int) $store->getId())) {
                $lengowActiveStores[] = $store;
            }
        }
        return $lengowActiveStores;
    }

    /**
     * Check if is a new merchant
     *
     * @return boolean
     */
    public function isNewMerchant()
    {
        list($accountId, $accessToken, $secretToken) = $this->getAccessIds();
        return !($accountId && $accessToken && $secretToken);
    }

    /**
     * Get Values by store or global
     *
     * @param integer|null $storeId Magento store id
     * @param boolean $toolbox get all values for toolbox or not
     *
     * @return array
     */
    public function getAllValues($storeId = null, $toolbox = false)
    {
        $rows = array();
        foreach (self::$lengowSettings as $key => $keyParams) {
            $value = null;
            if ((isset($keyParams[self::PARAM_EXPORT]) && !$keyParams[self::PARAM_EXPORT])
                || ($toolbox
                    && isset($keyParams[self::PARAM_EXPORT_TOOLBOX])
                    && !$keyParams[self::PARAM_EXPORT_TOOLBOX]
                )
            ) {
                continue;
            }
            if ($storeId) {
                if (isset($keyParams[self::PARAM_SHOP]) && $keyParams[self::PARAM_SHOP]) {
                    $value = $this->get($key, $storeId);
                    // added a check to differentiate the token shop from the cms token which are the same.
                    $genericKey = self::CMS_TOKEN === $key
                        ? self::$genericParamKeys[self::SHOP_TOKEN]
                        : self::$genericParamKeys[$key];
                    $rows[$genericKey] = $this->getValueWithCorrectType($key, $value);
                }
            } elseif (isset($keyParams[self::PARAM_GLOBAL]) && $keyParams[self::PARAM_GLOBAL]) {
                $value = $this->get($key);
                $rows[self::$genericParamKeys[$key]] = $this->getValueWithCorrectType($key, $value);
            }
        }
        return $rows;
    }

    /**
     * Get configuration value in correct type
     *
     * @param string $key Lengow configuration key
     * @param string|null $value configuration value for conversion
     *
     * @return array|boolean|integer|float|string|string[]|null
     */
    private function getValueWithCorrectType($key, $value = null)
    {
        $keyParams = self::$lengowSettings[$key];
        if (isset($keyParams[self::PARAM_RETURN])) {
            switch ($keyParams[self::PARAM_RETURN]) {
                case self::RETURN_TYPE_BOOLEAN:
                    return (bool) $value;
                case self::RETURN_TYPE_INTEGER:
                    return (int) $value;
                case self::RETURN_TYPE_FLOAT:
                    return (float) $value;
                case self::RETURN_TYPE_ARRAY:
                    return !empty($value)
                        ? explode(';', trim(str_replace(array("\r\n", ',', ' '), ';', $value), ';'))
                        : array();
            }
        }
        return $value;
    }
}
