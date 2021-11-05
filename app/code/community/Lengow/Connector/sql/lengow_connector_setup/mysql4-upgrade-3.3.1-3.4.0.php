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
 * @subpackage  sql
 * @author      Team module <team-module@lengow.com>
 * @copyright   2021 Lengow SAS
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

$version = '3.4.0';
/** @var Lengow_Connector_Helper_Config $configHelper */
$configHelper = Mage::helper('lengow_connector/config');
$installedVersion = $configHelper->get(Lengow_Connector_Helper_Config::PLUGIN_VERSION);

if (version_compare($installedVersion, $version, '<')) {
    $configHelper->set(Lengow_Connector_Helper_Config::PLUGIN_VERSION, $version);
}
