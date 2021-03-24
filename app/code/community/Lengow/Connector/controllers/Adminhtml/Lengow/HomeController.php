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
 * @subpackage  controllers
 * @author      Team module <team-module@lengow.com>
 * @copyright   2021 Lengow SAS
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

/**
 * Adminhtml lengow homeController
 */
class Lengow_Connector_Adminhtml_Lengow_HomeController extends Mage_Adminhtml_Controller_Action
{
    /**
     * Init action
     *
     * @return Lengow_Connector_Adminhtml_Lengow_HomeController
     */
    protected function _initAction()
    {
        $this->loadLayout()->_setActiveMenu('lengowtab');
        return $this;
    }

    /**
     * Index Action
     *
     * @return mixed
     */
    public function indexAction()
    {
        $isAjax = Mage::app()->getRequest()->isAjax();
        if ($isAjax) {
            $action = (string)$this->getRequest()->getParam('action');
            if ($action !== '') {
                switch ($action) {
                    case 'go_to_credentials':
                        $displayContent = $this->getLayout()
                            ->createBlock('lengow/adminhtml_home_content')
                            ->setTemplate('lengow/home/cms.phtml');
                        $data = array('content' => $displayContent->toHtml());
                        $this->getResponse()->setBody(Mage::helper('core')->jsonEncode($data));
                        break;
                    case 'connect_cms':
                        $cmsConnected = false;
                        $hasCatalogToLink = false;
                        $accessToken = Mage::app()->getRequest()->getParam('accessToken');
                        $secret = Mage::app()->getRequest()->getParam('secret');
                        $credentialsValid = $this->_checkApiCredentials($accessToken, $secret);
                        if ($credentialsValid) {
                            $cmsConnected = $this->_connectCms();
                            if ($cmsConnected) {
                                $hasCatalogToLink = $this->_hasCatalogToLink();
                            }
                        }
                        $displayContent = $this->getLayout()
                            ->createBlock('lengow/adminhtml_home_content')
                            ->setTemplate('lengow/home/cms_result.phtml')
                            ->setCredentialsValid($credentialsValid)
                            ->setCmsConnected($cmsConnected)
                            ->setHasCataloToLink($hasCatalogToLink);
                        $data = array('content' => $displayContent->toHtml());
                        $this->getResponse()->setBody(Mage::helper('core')->jsonEncode($data));
                        break;
                    case 'go_to_catalog':
                        $retry = Mage::app()->getRequest()->getParam('retry') !== 'false';
                        if ($retry) {
                            Mage::helper('lengow_connector/config')->resetCatalogIds();
                        }
                        $catalogList = $this->_getCatalogList();
                        $displayContent = $this->getLayout()
                            ->createBlock('lengow/adminhtml_home_content')
                            ->setTemplate('lengow/home/catalog.phtml')
                            ->setCatalogList($catalogList);
                        $data = array('content' => $displayContent->toHtml());
                        $this->getResponse()->setBody(Mage::helper('core')->jsonEncode($data));
                        break;
                    case 'link_catalogs':
                        $catalogsLinked = true;
                        $catalogSelected = Mage::app()->getRequest()->getParam('catalogSelected') !== null
                            ? Mage::app()->getRequest()->getParam('catalogSelected')
                            : array();
                        if (!empty($catalogSelected)) {
                            $catalogsLinked = $this->_saveCatalogsLinked($catalogSelected);
                        }
                        $displayContent = $this->getLayout()
                            ->createBlock('lengow/adminhtml_home_content')
                            ->setTemplate('lengow/home/catalog_failed.phtml');
                        $data = array(
                            'success' => $catalogsLinked,
                            'content' => $displayContent->toHtml(),
                        );
                        $this->getResponse()->setBody(Mage::helper('core')->jsonEncode($data));
                        break;
                }
            }
        } else {
            if (Mage::helper('lengow_connector/config')->isNewMerchant()) {
                $this->_initAction()->renderLayout();
            } else {
                $this->_redirect('adminhtml/lengow_dashboard/index');
            }
        }
        return $this;
    }

    /**
     * Is allowed
     *
     * @return boolean
     */
    protected function _isAllowed()
    {
        return Mage::getSingleton('admin/session')->isAllowed('lengowtab/home');
    }

    /**
     * Check API credentials and save them in Database
     *
     * @param string $accessToken access token for api
     * @param string $secret secret for api
     *
     * @return boolean
     */
    private function _checkApiCredentials($accessToken, $secret)
    {
        $accessIdsSaved = false;
        $accountId = Mage::getModel('lengow/connector')->getAccountIdByCredentials($accessToken, $secret);
        if ($accountId) {
            $accessIdsSaved = Mage::helper('lengow_connector/config')->setAccessIds(
                array(
                    'account_id' => $accountId,
                    'access_token' => $accessToken,
                    'secret_token' => $secret,
                )
            );
        }
        return $accessIdsSaved;
    }

    /**
     * Connect cms with Lengow
     *
     * @return boolean
     */
    private function _connectCms()
    {
        /** @var Lengow_Connector_Helper_Data $helper */
        $helper = Mage::helper('lengow_connector/data');
        /** @var Lengow_Connector_Helper_Sync $syncHelper */
        $syncHelper = Mage::helper('lengow_connector/sync');
        /** @var Lengow_Connector_Helper_Config $configHelper */
        $configHelper = Mage::helper('lengow_connector/config');
        $cmsToken = $configHelper->getToken();
        $cmsConnected = $syncHelper->syncCatalog(true);
        if (!$cmsConnected) {
            $syncData = Mage::helper('core')->jsonEncode(($syncHelper->getSyncData()));
            $result = Mage::getModel('lengow/connector')->queryApi(
                Lengow_Connector_Model_Connector::POST,
                Lengow_Connector_Model_Connector::API_CMS,
                array(),
                $syncData
            );
            if (isset($result->common_account)) {
                $cmsConnected = true;
                $messageKey = 'log.connection.cms_creation_success';
            } else {
                $messageKey = 'log.connection.cms_creation_failed';
            }
        } else {
            $messageKey = 'log.connection.cms_already_exist';
        }
        $helper->log(
            Lengow_Connector_Helper_Data::CODE_CONNECTION,
            $helper->setLogMessage($messageKey, array('cms_token' => $cmsToken))
        );
        // reset access ids if cms creation failed
        if (!$cmsConnected) {
            $configHelper->resetAccessIds();
        }
        return $cmsConnected;
    }

    /**
     * Get all catalogs available in Lengow
     *
     * @return boolean
     */
    private function _hasCatalogToLink()
    {
        $lengowActiveStores = Mage::helper('lengow_connector/config')->getLengowActiveStores();
        if (empty($lengowActiveStores)) {
            return Mage::getModel('lengow/catalog')->hasCatalogNotLinked();
        }
        return false;
    }

    /**
     * Get all catalogs available in Lengow
     *
     * @return array
     */
    private function _getCatalogList()
    {
        $lengowActiveStores = Mage::helper('lengow_connector/config')->getLengowActiveStores();
        if (empty($lengowActiveStores)) {
            return Mage::getModel('lengow/catalog')->getCatalogList();
        }
        // if cms already has one or more linked catalogs, nothing is done
        return array();
    }

    /**
     * Save catalogs linked in database and send data to Lengow with call API
     *
     * @param array $catalogSelected
     *
     * @return boolean
     */
    private function _saveCatalogsLinked($catalogSelected)
    {
        $catalogsLinked = true;
        $catalogsByStores = array();
        foreach ($catalogSelected as $catalog) {
            $catalogsByStores[$catalog['shopId']] = $catalog['catalogId'];
        }
        if (!empty($catalogsByStores)) {
            /** @var Lengow_Connector_Helper_Config $configHelper */
            $configHelper = Mage::helper('lengow_connector/config');
            // save catalogs ids and active shop in lengow configuration
            foreach ($catalogsByStores as $storeId => $catalogIds) {
                $configHelper->setCatalogIds($catalogIds, $storeId);
                $configHelper->setActiveStore($storeId);
            }
            // save last update date for a specific settings (change synchronisation interval time)
            $configHelper->set('last_setting_update', time());
            // link all catalogs selected by API
            $catalogsLinked = Mage::getModel('lengow/catalog')->linkCatalogs($catalogsByStores);
            /** @var Lengow_Connector_Helper_Data $helper */
            $helper = Mage::helper('lengow_connector/data');
            $messageKey = $catalogsLinked
                ? 'log.connection.link_catalog_success'
                : 'log.connection.link_catalog_failed';
            $helper->log(Lengow_Connector_Helper_Data::CODE_CONNECTION, $helper->setLogMessage($messageKey));
        }
        return $catalogsLinked;
    }
}
