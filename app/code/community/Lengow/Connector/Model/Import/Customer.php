<?php
/**
 * Copyright 2017 Lengow SAS
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
 * @copyright   2017 Lengow SAS
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

/**
 * Model import customer
 */
class Lengow_Connector_Model_Import_Customer extends Mage_Customer_Model_Customer
{
    /**
     * @var array API fields for an address
     */
    protected $_addressApiNodes = array(
        'company',
        'civility',
        'email',
        'last_name',
        'first_name',
        'first_line',
        'full_name',
        'second_line',
        'complement',
        'zipcode',
        'city',
        'common_country_iso_a2',
        'phone_home',
        'phone_office',
        'phone_mobile',
    );

    /**
     * Convert array to customer model
     *
     * @param object $orderData order data
     * @param array $shippingAddress shipping address data
     * @param integer $storeId Magento store id
     * @param string $marketplaceSku marketplace sku
     * @param boolean $logOutput see log or not
     *
     * @return Lengow_Connector_Model_Import_Customer
     */
    public function createCustomer($orderData, $shippingAddress, $storeId, $marketplaceSku, $logOutput)
    {
        $idWebsite = Mage::getModel('core/store')->load($storeId)->getWebsiteId();
        $array = array(
            'billing_address' => $this->_extractAddressDataFromAPI($orderData->billing_address),
            'delivery_address' => $this->_extractAddressDataFromAPI($shippingAddress)
        );
        // generation of fictitious email
        $domain = (!Mage::helper('lengow_connector/data')->getHost($storeId)
            ? 'magento.shop'
            : Mage::helper('lengow_connector/data')->getHost($storeId)
        );
        $array['billing_address']['email'] = $marketplaceSku . '-' . $orderData->marketplace . '@' . $domain;
        Mage::helper('lengow_connector/data')->log(
            'Import',
            Mage::helper('lengow_connector/data')->setLogMessage(
                'log.import.generate_unique_email',
                array('email' => $array['billing_address']['email'])
            ),
            $logOutput,
            $marketplaceSku
        );
        // first get by email
        $this->setWebsiteId($idWebsite)->loadByEmail($array['billing_address']['email']);
        if (!$this->getId()) {
            $this->setImportMode(true);
            $this->setWebsiteId($idWebsite);
            $this->setConfirmation(null);
            $this->setForceConfirmed(true);
            $this->setPasswordHash($this->hashPassword($this->generatePassword(8)));
            $this->setFromLengow(1);
        }
        // Billing address
        $tempBillingNames = array(
            'firstname' => $array['billing_address']['first_name'],
            'lastname' => $array['billing_address']['last_name'],
            'fullname' => $array['billing_address']['full_name']
        );
        $billingNames = $this->_getNames($tempBillingNames);
        $array['billing_address']['first_name'] = $billingNames['firstname'];
        $array['billing_address']['last_name'] = $billingNames['lastname'];
        $billingAddress = $this->_convertAddress($array['billing_address']);
        $this->addAddress($billingAddress);
        // Shipping address
        $tempShippingNames = array(
            'firstname' => $array['delivery_address']['first_name'],
            'lastname' => $array['delivery_address']['last_name'],
            'fullname' => $array['delivery_address']['full_name']
        );
        $shippingNames = $this->_getNames($tempShippingNames);
        $array['delivery_address']['first_name'] = $shippingNames['firstname'];
        $array['delivery_address']['last_name'] = $shippingNames['lastname'];
        // Get relay id if exist
        if (count($shippingAddress->trackings) > 0 && !is_null($shippingAddress->trackings[0]->relay->id)) {
            $array['delivery_address']['tracking_relay'] = $shippingAddress->trackings[0]->relay->id;
        }
        $shippingAddress = $this->_convertAddress($array['delivery_address'], 'shipping');
        $this->addAddress($shippingAddress);
        Mage::helper('core')->copyFieldset('lengow_convert_address', 'to_customer', $array['billing_address'], $this);
        // set group
        $this->setGroupId(Mage::helper('lengow_connector/config')->get('customer_group', $storeId));
        $this->save();
        return $this;
    }

    /**
     * Extract address data from API
     *
     * @param array $api API nodes containing the data
     *
     * @return array
     */
    protected function _extractAddressDataFromAPI($api)
    {
        $temp = array();
        foreach ($this->_addressApiNodes as $node) {
            $temp[$node] = (string)$api->{$node};
        }
        return $temp;
    }

    /**
     * Convert a array to customer address model
     *
     * @param array $data address data
     * @param string $type address type (billing or shipping)
     *
     * @return Mage_Customer_Model_Address
     */
    protected function _convertAddress(array $data, $type = 'billing')
    {
        $address = Mage::getModel('customer/address');
        $address->setId(null);
        $address->setIsDefaultBilling(true);
        $address->setIsDefaultShipping(false);
        if ($type == 'shipping') {
            $address->setIsDefaultBilling(false);
            $address->setIsDefaultShipping(true);
        }
        Mage::helper('core')->copyFieldset('lengow_convert_address', 'to_' . $type . '_address', $data, $address);
        $firstLine = $data['first_line'];
        $secondLine = $data['second_line'];
        // Fix first line address
        if (empty($firstLine) && !empty($secondLine)) {
            $firstLine = $secondLine;
            $secondLine = null;
        }
        // Fix second line address
        if (!empty($secondLine)) {
            $firstLine = $firstLine . "\n" . $secondLine;
        }
        $thirdLine = $data['complement'];
        if (!empty($thirdLine)) {
            $firstLine = $firstLine . "\n" . $thirdLine;
        }
        // adding relay to address
        if (isset($data['tracking_relay'])) {
            $firstLine .= ' - Relay : ' . $data['tracking_relay'];
        }
        $address->setStreet($firstLine);
        $phoneOffice = $data['phone_office'];
        $phoneMobile = $data['phone_mobile'];
        $phoneHome = $data['phone_home'];
        $phoneOffice = empty($phoneOffice) ? $phoneMobile : $phoneOffice;
        $phoneOffice = empty($phoneOffice) ? $phoneHome : $phoneOffice;
        if (!empty($phoneOffice)) {
            $this->setTelephone($phoneOffice);
        }
        if (!empty($phoneOffice)) {
            $address->setFax($phoneOffice);
        } else {
            if (!empty($phoneMobile)) {
                $address->setFax($phoneMobile);
            } elseif (!empty($phoneHome)) {
                $address->setFax($phoneHome);
            }
        }
        $codeRegion = substr(str_pad($address->getPostcode(), 5, '0', STR_PAD_LEFT), 0, 2);
        $regionId = Mage::getModel('directory/region')->getCollection()
            ->addRegionCodeFilter($codeRegion)
            ->addCountryFilter($address->getCountry())
            ->getFirstItem()
            ->getId();
        $address->setRegionId($regionId);
        $address->setCustomer($this);
        return $address;
    }

    /**
     * Check if firstname or lastname are empty
     *
     * @param array $array name and lastname of the customer
     *
     * @return array
     */
    protected function _getNames($array)
    {
        if (empty($array['firstname'])) {
            if (!empty($array['lastname'])) {
                $array = $this->_splitNames($array['lastname']);
            }
        }
        if (empty($array['lastname'])) {
            if (!empty($array['firstname'])) {
                $array = $this->_splitNames($array['firstname']);
            }
        }
        // check full name if last_name and first_name are empty
        if (empty($array['lastname']) && empty($array['firstname'])) {
            $array = $this->_splitNames($array['fullname']);
        }
        if (empty($array['lastname'])) {
            $array['lastname'] = '__';
        }
        if (empty($array['firstname'])) {
            $array['firstname'] = '__';
        }
        return $array;
    }

    /**
     * Split fullname
     *
     * @param string $fullname fullname of the customer
     *
     * @return array
     */
    protected function _splitNames($fullname)
    {
        $split = explode(' ', $fullname);
        if ($split && count($split)) {
            $names['firstname'] = $split[0];
            $names['lastname'] = '';
            for ($i = 1; $i < count($split); $i++) {
                if (!empty($names['lastname'])) {
                    $names['lastname'] .= ' ';
                }
                $names['lastname'] .= $split[$i];
            }
        } else {
            $names['firstname'] = '__';
            $names['lastname'] = empty($fullname) ? '__' : $fullname;
        }
        return $names;
    }
}
