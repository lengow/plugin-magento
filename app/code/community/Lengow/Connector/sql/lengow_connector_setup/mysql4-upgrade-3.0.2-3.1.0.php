<?php
/**
 * Copyright 2019 Lengow SAS
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
 * @copyright   2019 Lengow SAS
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

$version = '3.1.0';
$config = Mage::helper('lengow_connector/config');
$installedVersion = $config->get('installed_version');

if (version_compare($installedVersion, $version, '<')) {

    // *********************************************************
    //    Active Lengow tracker for versions 3.0.0 - 3.0.2
    // *********************************************************

    $trackingEnable = (bool)$config->get('tracking_enable');
    if (!$config->isNewMerchant() && !$trackingEnable) {
        $config->set('tracking_enable', 1);
        Mage::app()->getCacheInstance()->cleanType('config');
    }

    $config->set('installed_version', $version);
}
