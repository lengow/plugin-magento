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

$version = '3.2.2';
/** @var Lengow_Connector_Helper_Config $configHelper */
$configHelper = Mage::helper('lengow_connector/config');
$installedVersion = $configHelper->get(Lengow_Connector_Helper_Config::PLUGIN_VERSION);

if (version_compare($installedVersion, $version, '<')) {

    $installer = $this;
    $installer->startSetup();
    $tableName = $installer->getTable('lengow_order');
    if ((bool) $installer->getConnection()->showTableStatus($tableName)) {
        // add order_types attribute in table lengow_order
        $columnName = 'order_types';
        if (!(bool) $installer->getConnection()->tableColumnExists($tableName, $columnName)) {
            $installer->getConnection()
                ->addColumn(
                    $tableName,
                    $columnName,
                    array(
                        'type' => Mage::getVersion() < '1.6.0.0'
                            ? Varien_Db_Ddl_Table::TYPE_LONGVARCHAR
                            : Varien_Db_Ddl_Table::TYPE_TEXT,
                        'nullable' => true,
                        'default' => null,
                        'after' => 'order_item',
                        'comment' => 'Order Types'
                    ));
        }

    }
    $installer->endSetup();

    $configHelper->set(Lengow_Connector_Helper_Config::PLUGIN_VERSION, $version);
}
