<?php

/**
 *
 * @category    Lengow
 * @package     Lengow_Connector
 * @author      Team module <team-module@lengow.com>
 * @copyright   2016 Lengow SAS
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
class Lengow_Connector_Helper_Sync extends Mage_Core_Helper_Abstract
{
    /**
     * Get Statistic with all store every 5 hours
     */
    protected $_cacheTime = 18000;

    /**
     * Get Sync Data (Inscription / Update)
     *
     * @return array
     */
    public function getSyncData()
    {
        $config = Mage::helper('lengow_connector/config');
        $data = array();
        $data['domain_name']    = $_SERVER["SERVER_NAME"];
        $data['token']          = $config->getToken();
        $data['type']           = 'magento';
        $data['version']        = Mage::getVersion();
        $data['plugin_version'] = (string)Mage::getConfig()->getNode()->modules->Lengow_Connector->version;
        $data['email']          = Mage::getStoreConfig('trans_email/ident_general/email');
        $data['return_url']     = 'http://'.$_SERVER["SERVER_NAME"].$_SERVER["REQUEST_URI"];
        foreach (Mage::app()->getWebsites() as $website) {
            foreach ($website->getGroups() as $group) {
                $stores = $group->getStores();
                foreach ($stores as $store) {
                    $export = Mage::getModel('lengow/export', array("store_id" => $store->getId()));
                    $data['shops'][$store->getId()]['token']                   = $config->getToken($store->getId());
                    $data['shops'][$store->getId()]['name']                    = $store->getName();
                    $data['shops'][$store->getId()]['domain']                  = $store->getBaseUrl();
                    $data['shops'][$store->getId()]['feed_url']                = $export->getExportUrl();
                    $data['shops'][$store->getId()]['cron_url']                = Mage::getUrl('lengow/cron');
                    $data['shops'][$store->getId()]['total_product_number']    = $export->getTotalProduct();
                    $data['shops'][$store->getId()]['exported_product_number'] = $export->getTotalExportedProduct();
                    $data['shops'][$store->getId()]['configured']              = $this->checkSyncStore($store->getId());
                }
            }
        }
        return $data;
    }

    /**
     * Sync Lengow Information
     *
     * @param $params
     */
    public function sync($params)
    {
        $config = Mage::helper('lengow_connector/config');
        foreach ($params as $shopToken => $values) {
            if ($store = $config->getStoreByToken($shopToken)) {
                $listKey = array(
                    'account_id'   => false,
                    'access_token' => false,
                    'secret_token' => false
                );
                foreach ($values as $key => $value) {
                    if (!in_array($key, array_keys($listKey))) {
                        continue;
                    }
                    if (strlen($value) > 0) {
                        $listKey[$key] = true;
                        $config->set($key, $value, $store->getId(), false);
                    }
                }
                $findFalseValue = false;
                foreach ($listKey as $key => $value) {
                    if (!$value) {
                        $findFalseValue = true;
                        break;
                    }
                }
                if (!$findFalseValue) {
                    $config->set('store_enable', true, $store->getId(), false);
                } else {
                    $config->set('store_enable', false, $store->getId(), false);
                }
            }
        }
        // Clean config cache to valid configuration
        Mage::app()->getCacheInstance()->cleanType('config');
    }

    /**
     * Check if store is follow by Lengow
     *
     * @param $storeId
     *
     * @return boolean
     */
    public function checkSyncStore($storeId)
    {
        return Mage::helper('lengow_connector/config')->get('store_enable', $storeId)
            && Mage::getModel('lengow/connector')->getConnectorByStore($storeId);
    }

    /**
     * Check if is a new marchant
     *
     * @return boolean
     */
    public function isNewMerchant()
    {
        $config = Mage::helper('lengow_connector/config');
        foreach (Mage::app()->getWebsites() as $website) {
            foreach ($website->getGroups() as $group) {
                $stores = $group->getStores();
                foreach ($stores as $store) {
                    $accountId = $config->get('account_id', $store->getId());
                    if (strlen($accountId) > 0) {
                        return false;
                    }
                }
            }
        }
        return true;
    }

    /**
     * Set CMS options
     *
     * @param boolean $force Force cache Update
     *
     * @return boolean
     */
    public function setCmsOption($force = false)
    {
        $config = Mage::helper('lengow_connector/config');
        if ($this->isNewMerchant() || (bool)$config->get('preprod_mode_enable')) {
            return false;
        }
        if (!$force) {
            $updatedAt = $config->get('last_option_cms_update');
            if (!is_null($updatedAt) && (time() - strtotime($updatedAt)) < $this->_cacheTime) {
                return false;
            }
        }
        $options = Mage::helper('core')->jsonEncode($this->getOptionData());
        $connector = Mage::getModel('lengow/connector');
        $connector->queryApi('put', '/v3.0/cms', null, array(), $options);
        $config->set('last_option_cms_update', date('Y-m-d H:i:s'));
        return true;
    }

    /**
     * Get Status Account
     *
     * @param boolean $force Force cache Update
     *
     * @return mixed
     */
    public function getStatusAccount($force = false)
    {
        $config = Mage::helper('lengow_connector/config');
        if (!$force) {
            $updatedAt = $config->get('last_status_update');
            if (!is_null($updatedAt) && (time() - strtotime($updatedAt)) < $this->_cacheTime) {
                return json_decode($config->get('account_status'), true);
            }
        }
        $result = Mage::getModel('lengow/connector')->queryApi(
            'get',
            '/v3.0/subscriptions'
        );
        if (isset($result->subscription)) {
            $status = array();
            $status['type'] = $result->subscription->billing_offer->type;
            $status['day'] = - round((strtotime(date("c")) - strtotime($result->subscription->renewal)) / 86400);
            if ($status['day'] < 0) {
                $status['day'] = 0;
            }
            if ($status) {
                $config->set('account_status', Mage::helper('core')->jsonEncode($status));
                $config->set('last_status_update', date('Y-m-d H:i:s'));
                return $status;
            }
        }
        return false;
    }

    /**
     * Get Statistic with all store
     *
     * @param boolean $force Force cache Update
     *
     * @return array
     */
    public function getStatistic($force = false)
    {
        $config = Mage::helper('lengow_connector/config');
        if (!$force) {
            $updatedAt = $config->get('last_statistic_update');
            if (!is_null($updatedAt) && (time() - strtotime($updatedAt)) < $this->_cacheTime) {
                return json_decode($config->get('order_statistic'), true);
            }
        }
        $return = array();
        $return['total_order'] = 0;
        $return['nb_order'] = 0;
        $return['currency'] = '';
        // get stats by store
        $storeCollection = Mage::getResourceModel('core/store_collection')->addFieldToFilter('is_active', 1);
        $i = 0;
        $allCurrencies = array();
        $accountIds = array();
        foreach ($storeCollection as $store) {
            $accountId = $config->get('account_id', $store->getId());
            if (is_null($accountId) || in_array($accountId, $accountIds) || empty($accountId)) {
                continue;
            }
            $connector = Mage::getModel('lengow/connector');
            $result = $connector->queryApi(
                'get',
                '/v3.0/stats',
                $store->getId(),
                array(
                    'date_from' => date('c', strtotime(date('Y-m-d').' -10 years')),
                    'date_to'   => date('c'),
                    'metrics'   => 'year',
                )
            );
            if (isset($result->level0)) {
                $stats = $result->level0[0];
                $return['total_order'] += $stats->revenue;
                $return['nb_order'] += $stats->transactions;
                $return['currency'] = $result->currency->iso_a3;
            }
            $accountIds[] = $accountId;
            $i++;
            // Get store currencies
            $storeCurrencies = Mage::app()->getStore($store->getId())->getAvailableCurrencyCodes();
            if (is_array($storeCurrencies)) {
                foreach ($storeCurrencies as $currency) {
                    if (!in_array($currency, $allCurrencies)) {
                        $allCurrencies[] = $currency;
                    }
                }
            }
        }
        if ($return['currency'] && in_array($return['currency'], $allCurrencies)) {
            $return['total_order'] = Mage::app()->getLocale()
                ->currency($return['currency'])
                ->toCurrency($return['total_order']);
        } else {
            $return['total_order'] = number_format($return['total_order'], 2, ',', ' ');
        }
        $return['nb_order'] = (int)$return['nb_order'];
        $config->set('order_statistic', Mage::helper('core')->jsonEncode($return));
        $config->set('last_statistic_update', date('Y-m-d H:i:s'));
        return $return;
    }

    /**
     * Get Sync Data (Inscription / Update)
     *
     * @return array
     */
    public static function getOptionData()
    {
        $config = Mage::helper('lengow_connector/config');
        $data = array();
        $data['cms'] = array(
            'token'          => $config->getToken(),
            'type'           => 'magento',
            'version'        => Mage::getVersion(),
            'plugin_version' => (string)Mage::getConfig()->getNode()->modules->Lengow_Connector->version,
            'options'        => $config->getAllValues()
        );
        foreach (Mage::app()->getWebsites() as $website) {
            foreach ($website->getGroups() as $group) {
                $stores = $group->getStores();
                foreach ($stores as $store) {
                    $export = Mage::getModel('lengow/export', array("store_id" => $store->getId()));
                    $data['shops'][] = array(
                        'enabled'                 => (bool)$config->get('store_enable', $store->getId()),
                        'token'                   => $config->getToken($store->getId()),
                        'store_name'              => $store->getName(),
                        'domain_url'              => $store->getBaseUrl(),
                        'feed_url'                => $export->getExportUrl(),
                        'cron_url'                => Mage::getUrl('lengow/cron'),
                        'total_product_number'    => $export->getTotalProduct(),
                        'exported_product_number' => $export->getTotalExportedProduct(),
                        'options'                 => $config->getAllValues($store->getId())
                    );
                }
            }
        }
        return $data;
    }
}
