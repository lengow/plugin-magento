<?php
/**
 * Copyright 2020 Lengow SAS
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
 * @subpackage  sql
 * @author      Team module <team-module@lengow.com>
 * @copyright   2020 Lengow SAS
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

$version = '3.2.0';
/** @var Lengow_Connector_Helper_Config $configHelper */
$configHelper = Mage::helper('lengow_connector/config');
$installedVersion = $configHelper->get('installed_version');

if (version_compare($installedVersion, $version, '<')) {

    // ***********************************************************
    // Delete statistic configurations for versions 3.0.0 - 3.1.3
    // ***********************************************************

    Mage::getModel('core/config')->deleteConfig('lengow_global_options/advanced/last_statistic_update');
    Mage::getModel('core/config')->deleteConfig('lengow_global_options/advanced/order_statistic');

    // *************************************************************
    // Delete preprod mode configuration for versions 3.0.0 - 3.1.3
    // *************************************************************

    Mage::getModel('core/config')->deleteConfig('lengow_import_options/advanced/import_preprod_mode_enable');

    $configHelper->set('installed_version', $version);
}
