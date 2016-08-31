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
    protected $_cache_time = 18000;

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
        foreach ($params as $shop_token => $values) {
            if ($store = $config->getStoreByToken($shop_token)) {
                $list_key = array(
                    'account_id'   => false,
                    'access_token' => false,
                    'secret_token' => false
                );
                foreach ($values as $key => $value) {
                    if (!in_array($key, array_keys($list_key))) {
                        continue;
                    }
                    if (strlen($value) > 0) {
                        $list_key[$key] = true;
                        $config->set($key, $value, $store->getId());
                    }
                }
                $find_false_value = false;
                foreach ($list_key as $key => $value) {
                    if (!$value) {
                        $find_false_value = true;
                        break;
                    }
                }
                if (!$find_false_value) {
                    $config->set('store_enable', true, $store->getId());
                } else {
                    $config->set('store_enable', false, $store->getId());
                }
            }
        }
        // Clean config cache to valid configuration
        Mage::app()->getCacheInstance()->cleanType('config');
    }

    /**
     * Check if store is follow by Lengow
     *
     * @param $store_id
     *
     * @return boolean
     */
    public function checkSyncShop($store_id)
    {
        // TODO check shop synchronisation with account API
        return false;
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
                    $account_id = $config->get('account_id', $store->getId());
                    if (strlen($account_id) > 0) {
                        return false;
                    }
                }
            }
        }
        return true;
    }

    /**
     * Generate mailto for help page
     */
    public function getMailTo()
    {
        $mailto = $this->getSyncData();
        $mail = 'support.lengow.zendesk@lengow.com';
        $subject = Mage::helper('lengow_connector')->__('help.screen.mailto_subject');
        $result = Mage::getModel('lengow/connector')->queryApi('get', '/v3.0/cms');
        $body = '%0D%0A%0D%0A%0D%0A%0D%0A%0D%0A'
            .Mage::helper('lengow_connector')->__('help.screen.mail_lengow_support_title').'%0D%0A';
        if (isset($result->cms)) {
            $body.= 'commun_account : '.$result->cms->common_account.'%0D%0A';
        }
        foreach ($mailto as $key => $value) {
            if ($key == 'domain_name' || $key == 'token' || $key == 'return_url' || $key == 'shops') {
                continue;
            }
            $body.= $key.' : '.$value.'%0D%0A';
        }
        $shops = $mailto['shops'];
        $i = 1;
        foreach ($shops as $shop) {
            foreach ($shop as $item => $value) {
                if ($item == 'name') {
                    $body.= 'Store '.$i.' : '.$value.'%0D%0A';
                } elseif ($item == 'feed_url') {
                    $body.= $value . '%0D%0A';
                }
            }
            $i++;
        }
        $html = '<a href="mailto:'.$mail;
        $html.= '?subject='.$subject;
        $html.= '&body='.$body.'" ';
        $html.= 'title="'.Mage::helper('lengow_connector')->__('help.screen.need_some_help').'" target="_blank">';
        $html.= Mage::helper('lengow_connector')->__('help.screen.mail_lengow_support');
        $html.= '</a>';
        return $html;
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
        if ($this->isNewMerchant()) {
            return false;
        }
        $config = Mage::helper('lengow_connector/config');
        if (!$force) {
            $updated_at = $config->get('last_option_cms_update');
            if (!is_null($updated_at) && (time() - strtotime($updated_at)) < $this->_cache_time) {
                return false;
            }
        }
        $options = Mage::helper('core')->jsonEncode($this->getOptionData());
        $connector = Mage::getModel('lengow/connector');
        $return = $connector->queryApi('put', '/v3.0/cms', null, array(), $options);
        $config->set('last_option_cms_update', date('Y-m-d H:i:s'));
        return true;
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
            $updated_at = $config->get('last_statistic_update');
            if (!is_null($updated_at) && (time() - strtotime($updated_at)) < $this->_cache_time) {
                return json_decode($config->get('order_statistic'), true);
            }
        }
        $return = array();
        $return['total_order'] = 0;
        $return['nb_order'] = 0;
        $return['currency'] = '';
        // get stats by store
        $store_collection = Mage::getResourceModel('core/store_collection')->addFieldToFilter('is_active', 1);
        $i = 0;
        $all_currencies = array();
        $account_ids = array();
        foreach ($store_collection as $store) {
            $account_id = $config->get('account_id', $store->getId());
            if (is_null($account_id) || in_array($account_id, $account_ids) || empty($account_id)) {
                continue;
            }
            // TODO test call API for return statistics
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
                $return['total_order'] += $result->level0->revenue;
                $return['nb_order'] += $result->level0->transactions;
                $return['currency'] = $result->currency->iso_a3;
            }
            $account_ids[] = $account_id;
            $i++;
            // Get store currencies
            $store_currencies = Mage::app()->getStore($store->getId())->getAvailableCurrencyCodes();
            if (is_array($store_currencies)) {
                foreach ($store_currencies as $currency) {
                    if (!in_array($currency, $all_currencies)) {
                        $all_currencies[] = $currency;
                    }
                }
            }
        }
        if ($return['currency'] && in_array($return['currency'], $all_currencies)) {
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
            $updated_at = $config->get('last_status_update');
            if (!is_null($updated_at) && (time() - strtotime($updated_at)) < $this->_cache_time) {
                return json_decode($config->get('account_status'), true);
            }
        }
        // TODO call API for return a customer id or false
        //$result = Mage::getModel('lengow/connector')->queryApi('get', '/v3.0/cms');
        $result = true;
        if ($result) {
            // TODO call API with customer id parameter for return status account
            //$status = Mage::getModel('lengow/connector')->queryApi('get', '/v3.0/cms');
            $status = array();
            $status['type'] = 'free_trial';
            $status['day'] = 10;
            if ($status) {
                $config->set('account_status', Mage::helper('core')->jsonEncode($status));
                $config->set('last_status_update', date('Y-m-d H:i:s'));
                return $status;
            }
        }
        return false;
    }
}
