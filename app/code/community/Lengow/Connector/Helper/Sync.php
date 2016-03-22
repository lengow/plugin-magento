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
     * Get Sync Data (Inscription / Update)
     * @return array
     */
    public static function getSyncData()
    {
        $data = array();
        $data['domain_name'] = $_SERVER["SERVER_NAME"];
        $data['token'] = ''; //LengowMain::getToken();
        $data['type'] = 'magento';
        $data['version'] = Mage::getVersion();
        $data['plugin_version'] = ''; //LengowConfiguration::getGlobalValue('LENGOW_VERSION');
        $data['email'] = ''; //LengowConfiguration::get('PS_SHOP_EMAIL');
        $data['return_url'] = ''; //'http://'.$_SERVER["SERVER_NAME"].$_SERVER["REQUEST_URI"];

        /*$shopCollection = LengowShop::findAll(true);
        foreach ($shopCollection as $row) {
            $shopId = $row['id_shop'];

            $lengowExport = new LengowExport(array("shop_id" => $shopId));
            $shop = new LengowShop($shopId);
            $data['shops'][$row['id_shop']]['token'] = LengowMain::getToken($shopId);
            $data['shops'][$row['id_shop']]['name'] = $shop->name;
            $data['shops'][$row['id_shop']]['domain'] = $shop->domain;
            $data['shops'][$row['id_shop']]['feed_url'] = LengowMain::getExportUrl($shop->id);
            $data['shops'][$row['id_shop']]['cron_url'] = LengowMain::getImportUrl($shop->id);
            $data['shops'][$row['id_shop']]['nb_product_total'] = $lengowExport->getTotalProduct();
            $data['shops'][$row['id_shop']]['nb_product_exported'] = $lengowExport->getTotalExportProduct();
        }*/
        return $data;
    }
}
