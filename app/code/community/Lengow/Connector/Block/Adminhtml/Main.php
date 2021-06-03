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
 * @subpackage  Block
 * @author      Team module <team-module@lengow.com>
 * @copyright   2021 Lengow SAS
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

/**
 * Block adminhtml main content
 */
class Lengow_Connector_Block_Adminhtml_Main extends Mage_Core_Block_Template
{
    /**
     * @var Lengow_Connector_Helper_Data Lengow helper instance
     */
    protected $_helper;

    /**
     * @var Lengow_Connector_Helper_Config Lengow config helper instance
     */
    protected $_configHelper;

    /**
     * @var Lengow_Connector_Helper_Security Lengow security helper instance
     */
    protected $_securityHelper;

    /**
     * @var Lengow_Connector_Helper_Sync Lengow sync helper instance
     */
    protected $_syncHelper;

    /**
     * @var array Lengow status account
     */
    protected $_statusAccount = array();

    /**
     * @var array Lengow plugin data
     */
    protected $_pluginData = array();

    /**
     * @var array Lengow plugin links
     */
    protected $_pluginLinks = array();

    /**
     * Construct
     */
    protected function _construct()
    {
        parent::_construct();
        $this->_helper = Mage::helper('lengow_connector');
        $this->_configHelper = Mage::helper('lengow_connector/config');
        $this->_securityHelper = Mage::helper('lengow_connector/security');
        $this->_syncHelper = Mage::helper('lengow_connector/sync');
        $this->_statusAccount = $this->_syncHelper->getStatusAccount();
        $this->_pluginData = $this->_syncHelper->getPluginData();
        // get actual plugin urls in current language
        $this->_pluginLinks = $this->_syncHelper->getPluginLinks(Mage::app()->getLocale()->getLocaleCode());
    }

    /**
     * Prepare and return block's html output
     *
     * @return Lengow_Connector_Block_Adminhtml_Main
     */
    protected function _prepareLayout()
    {
        parent::_prepareLayout();
        return $this;
    }

    /**
     * Check if plugin is a preprod version
     *
     * @return boolean
     */
    public function isPreprodPlugin()
    {
        return Lengow_Connector_Model_Connector::LENGOW_URL === 'lengow.net';
    }

    /**
     * Check if debug mode is active
     *
     * @return boolean
     */
    public function debugModeIsActive()
    {
        return $this->_configHelper->debugModeIsActive();
    }

    /**
     * Get Lengow solution url
     *
     * @return string
     */
    public function getLengowSolutionUrl()
    {
        return '//my.' . Lengow_Connector_Model_Connector::LENGOW_URL;
    }

    /**
     * Get plugin version
     *
     * @return string
     */
    public function getPluginVersion()
    {
        return $this->_securityHelper->getPluginVersion();
    }

    /**
     * Free trial is enabled
     *
     * @return boolean
     */
    public function freeTrialIsEnabled()
    {
        return isset($this->_statusAccount['type'], $this->_statusAccount['expired'])
            && $this->_statusAccount['type'] === 'free_trial'
            && !$this->_statusAccount['expired'];
    }

    /**
     * Recovers the number of days of free trial
     *
     * @return integer
     */
    public function getFreeTrialDays()
    {
        return isset($this->_statusAccount['day']) ? (int) $this->_statusAccount['day'] : 0;
    }

    /**
     * New plugin version is available
     *
     * @return boolean
     */
    public function newPluginVersionIsAvailable()
    {
        return ($this->_pluginData && isset($this->_pluginData['version']))
            && version_compare($this->_securityHelper->getPluginVersion(), $this->_pluginData['version'], '<');
    }

    /**
     * Get new plugin version
     *
     * @return string
     */
    public function getNewPluginVersion()
    {
        return ($this->_pluginData && isset($this->_pluginData['version'])) ? $this->_pluginData['version'] : '';
    }

    /**
     * Get new plugin download link
     *
     * @return string
     */
    public function getNewPluginDownloadLink()
    {
        return ($this->_pluginData && isset($this->_pluginData['download_link']))
            ? $this->getLengowSolutionUrl() . $this->_pluginData['download_link']
            : '';
    }

    /**
     * Return CMS minimal version compatibility
     *
     * @return string
     */
    public function getCmsMinVersion()
    {
        return ($this->_pluginData && isset($this->_pluginData['cms_min_version']))
            ? $this->_pluginData['cms_min_version']
            : '';
    }

    /**
     * Return CMS maximal version compatibility
     *
     * @return string
     */
    public function getCmsMaxVersion()
    {
        return ($this->_pluginData && isset($this->_pluginData['cms_max_version']))
            ? $this->_pluginData['cms_max_version']
            : '';
    }

    /**
     * Return all required extensions
     *
     * @return array
     */
    public function getPluginExtensions()
    {
        return ($this->_pluginData && isset($this->_pluginData['extensions']))
            ? $this->_pluginData['extensions']
            : array();
    }

    /**
     * Return plugin help center link for current locale
     *
     * @return string
     */
    public function getHelpCenterLink()
    {
        return $this->_pluginLinks[Lengow_Connector_Helper_Sync::LINK_TYPE_HELP_CENTER];
    }


    /**
     * Return plugin changelog link for current locale
     *
     * @return string
     */
    public function getChangelogLink()
    {
        return $this->_pluginLinks[Lengow_Connector_Helper_Sync::LINK_TYPE_CHANGELOG];
    }

    /**
     * Return plugin update guide link for current locale
     *
     * @return string
     */
    public function getUpdateGuideLink()
    {
        return $this->_pluginLinks[Lengow_Connector_Helper_Sync::LINK_TYPE_UPDATE_GUIDE];
    }

    /**
     * Return Lengow support link for current locale
     *
     * @return string
     */
    public function getSupportLink()
    {
        return $this->_pluginLinks[Lengow_Connector_Helper_Sync::LINK_TYPE_SUPPORT];
    }

    /**
     * Checks if the plugin upgrade modal should be displayed or not
     *
     * @return boolean
     */
    public function showPluginUpgradeModal()
    {
        if (!$this->newPluginVersionIsAvailable()) {
            return false;
        }
        $updatedAt = $this->_configHelper->get(Lengow_Connector_Helper_Config::LAST_UPDATE_PLUGIN_MODAL);
        if ($updatedAt !== null && (time() - (int) $updatedAt) < 86400) {
            return false;
        }
        $this->_configHelper->set(Lengow_Connector_Helper_Config::LAST_UPDATE_PLUGIN_MODAL, time());
        return true;
    }
}
