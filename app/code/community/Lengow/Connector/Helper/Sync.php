<?php
ini_set('display_errors', 1);
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
     * Get Sync Data (Inscription / Update)
     * @return array
     */
    public function getSyncData()
    {
        $config = Mage::helper('lengow_connector/config');
        $data = array();
        $data['domain_name'] = $_SERVER["SERVER_NAME"];
        $data['token'] = $config->getToken();
        $data['type'] = 'magento';
        $data['version'] = Mage::getVersion();
        $data['plugin_version'] = (string)Mage::getConfig()->getNode()->modules->Lengow_Connector->version;
        $data['email'] = Mage::getSingleton('admin/session')->getUser()->getEmail();
        $data['return_url'] = 'http://'.$_SERVER["SERVER_NAME"].$_SERVER["REQUEST_URI"];
        foreach (Mage::app()->getWebsites() as $website) {
            foreach ($website->getGroups() as $group) {
                $stores = $group->getStores();
                foreach ($stores as $store) {
                    $export = Mage::getModel('lengow/export', array(
                        "store_id" => $store->getId(),
                    ));
                    $data['shops'][$store->getId()]['token'] = $config->getToken($store->getId());
                    $data['shops'][$store->getId()]['name'] = $store->getName();
                    $data['shops'][$store->getId()]['domain'] = $store->getBaseUrl();
                    $data['shops'][$store->getId()]['feed_url'] = $export->getExportUrl();
                    $data['shops'][$store->getId()]['cron_url'] = Mage::getUrl('lengow/cron');
                    $data['shops'][$store->getId()]['nb_product_total'] = $export->getTotalProduct();
                    $data['shops'][$store->getId()]['nb_product_exported'] = $export->getTotalExportedProduct();
                }
            }
        }
        return $data;
    }

    /**
     * Sync Lengow Information
     * @param $params
     */
    public static function sync($params)
    {
        $config = Mage::helper('lengow_connector/config');
        foreach ($params as $shop_token => $values) {
            if ($store = $config->getStoreByToken($shop_token)) {
                $list_key = array(
                    'account_id'   => false,
                    'access_token' => false,
                    'secret_token' => false
                );
                foreach ($values as $k => $v) {
                    if (!in_array($k, array_keys($list_key))) {
                        continue;
                    }
                    if (strlen($v) > 0) {
                        $list_key[$k] = true;
                        $config->set($k, $v, $store->getId());
                    }
                }
                $findFalseValue = false;
                foreach ($list_key as $k => $v) {
                    if (!$v) {
                        $findFalseValue = true;
                        break;
                    }
                }
                if (!$findFalseValue) {
                    $config->set('store_enable', true, $store->getId());
                } else {
                    $config->set('store_enable', false, $store->getId());
                }
            }
        }
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
        if (isset($result->cms)) {
            $body = 'commun_account : '.$result->cms->common_account;
        } else {
            $body = '';
        }
        foreach ($mailto as $key => $value) {
            if ($key == 'domain_name' || $key == 'token' || $key == 'return_url' || $key == 'shops') {
                continue;
            }
            $body .= $key.' : '.$value.'%0D%0A';
        }
        $shops = $mailto['shops'];
        $i = 1;
        foreach ($shops as $shop) {
            foreach ($shop as $item => $value) {
                if ($item == 'name') {
                    $body .= 'Store '.$i.' : '.$value.'%0D%0A';
                } elseif ($item == 'feed_url') {
                    $body .= $value . '%0D%0A';
                }
            }
            $i++;
        }
        $html = '<a href="mailto:'. $mail;
        $html.= '?subject='. $subject;
        $html.= '&body='. $body .'" ';
        $html.= 'title="'.Mage::helper('lengow_connector')->__('help.screen.need_some_help').'" target="_blank">';
        $html.= Mage::helper('lengow_connector')->__('help.screen.mail_lengow_support');
        $html.= '</a>';
        return $html;
    }
}
