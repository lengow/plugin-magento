<?php
/**
 * Copyright 2021 Lengow SAS
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
 * @subpackage  Model
 * @author      Team module <team-module@lengow.com>
 * @copyright   2021 Lengow SAS
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

/**
 * Model catalog
 */
class Lengow_Connector_Model_Catalog extends Varien_Object
{
    /**
     * Check if the account has catalogs not linked to a cms
     *
     * @return boolean
     */
    public function hasCatalogNotLinked()
    {
        $lengowCatalogs = Mage::getModel('lengow/connector')->queryApi(
            Lengow_Connector_Model_Connector::GET,
            Lengow_Connector_Model_Connector::API_CMS_CATALOG
        );
        if (!$lengowCatalogs) {
            return false;
        }
        foreach ($lengowCatalogs as $catalog) {
            if (!is_object($catalog) || $catalog->shop) {
                continue;
            }
            return true;
        }
        return false;
    }

    /**
     * Get all catalogs available in Lengow
     *
     * @return array
     */
    public function getCatalogList()
    {
        $catalogList = array();
        $lengowCatalogs = Mage::getModel('lengow/connector')->queryApi(
            Lengow_Connector_Model_Connector::GET,
            Lengow_Connector_Model_Connector::API_CMS_CATALOG
        );
        if (!$lengowCatalogs) {
            return $catalogList;
        }
        foreach ($lengowCatalogs as $catalog) {
            if (!is_object($catalog) || $catalog->shop) {
                continue;
            }
            /** @var Lengow_Connector_Helper_Data $helper */
            $helper = Mage::helper('lengow_connector/data');
            if ($catalog->name !== null) {
                $name = $catalog->name;
            } else {
                $name = $helper->decodeLogMessage(
                    'lengow_log.connection.catalog',
                    null,
                    array('catalog_id' => $catalog->id)
                );
            }
            $status = $catalog->is_active
                ? $helper->decodeLogMessage('lengow_log.connection.status_active')
                : $helper->decodeLogMessage('lengow_log.connection.status_draft');
            $label = $helper->decodeLogMessage(
                'lengow_log.connection.catalog_label',
                null,
                array(
                    'catalog_id' => $catalog->id,
                    'catalog_name' => $name,
                    'nb_products' => $catalog->products ? $catalog->products : 0,
                    'catalog_status' => $status,
                )
            );
            $catalogList[] = array(
                'label' => $label,
                'value' => $catalog->id,
            );
        }
        return $catalogList;
    }

    /**
     * Link all catalogs by API
     *
     * @param array $catalogsByStores all catalog ids organised by stores
     *
     * @return boolean
     */
    public function linkCatalogs($catalogsByStores)
    {
        $catalogsLinked = false;
        $hasCatalogToLink = false;
        if (empty($catalogsByStores)) {
            return $catalogsLinked;
        }
        /** @var Lengow_Connector_Helper_Config $configHelper */
        $configHelper = Mage::helper('lengow_connector/config');
        $linkCatalogData = array(
            'cms_token' => $configHelper->getToken(),
            'shops' => array(),
        );
        foreach ($catalogsByStores as $storeId => $catalogIds) {
            if (empty($catalogIds)) {
                continue;
            }
            $hasCatalogToLink = true;
            $shopToken = $configHelper->getToken($storeId);
            $linkCatalogData['shops'][] = array(
                'shop_token' => $shopToken,
                'catalogs_id' => $catalogIds,
            );
            /** @var Lengow_Connector_Helper_Data $helper */
            $helper = Mage::helper('lengow_connector/data');
            $helper->log(
                Lengow_Connector_Helper_Data::CODE_CONNECTION,
                $helper->setLogMessage(
                    'log.connection.try_link_catalog',
                    array(
                        'catalog_ids' => implode(', ', $catalogIds),
                        'shop_token' => $shopToken,
                        'store_id' => $storeId,
                    )
                )
            );
        }
        if ($hasCatalogToLink) {
            $result = Mage::getModel('lengow/connector')->queryApi(
                Lengow_Connector_Model_Connector::POST,
                Lengow_Connector_Model_Connector::API_CMS_MAPPING,
                array(),
                Mage::helper('core')->jsonEncode($linkCatalogData)
            );
            if (isset($result->cms_token)) {
                $catalogsLinked = true;
            }
        }
        return $catalogsLinked;
    }
}
