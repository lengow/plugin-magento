<?php

/**
 *
 * @category    Lengow
 * @package     Lengow_Connector
 * @author      Team module <team-module@lengow.com>
 * @copyright   2016 Lengow SAS
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

class Lengow_Connector_Model_Observer
{
    /**
     * Display Lengow Menu on demand
     */
    public function updateAdminMenu()
    {
        $menu = Mage::getSingleton('admin/config')->getAdminhtmlConfig()->getNode('menu/lengowtab/children');
        foreach ($menu->children() as $childName => $child) {
            $menu->setNode($childName.'/disabled', '0');
        }
    }
}
