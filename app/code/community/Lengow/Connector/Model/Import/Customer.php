<?php

/**
 *
 * @category    Lengow
 * @package     Lengow_Connector
 * @author      Team module <team-module@lengow.com>
 * @copyright   2016 Lengow SAS
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

class Lengow_Connector_Model_Import_Customer extends Mage_Customer_Model_Customer
{
    /**
     * @var array API fields for an address
     */
    protected $_address_api_nodes = array(
        'company',
        'civility',
        'email',
        'last_name',
        'first_name',
        'first_line',
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
     * @param object  $order_data
     * @param array   $shipping_address
     * @param integer $store_id
     * @param string  $marketplace_sku
     * @param boolean $log_output
     */
    public function createCustomer($order_data, $shipping_address, $store_id, $marketplace_sku, $log_output)
    {
        $id_website = Mage::getModel('core/store')->load($store_id)->getWebsiteId();
        $array = array(
            'billing_address'  => $this->_extractAddressDataFromAPI($order_data->billing_address),
            'delivery_address' => $this->_extractAddressDataFromAPI($shipping_address)
        );
        // generation of fictitious email
        $domain = (!Mage::helper('lengow_connector/data')->getHost($store_id)
            ? 'magento.shop'
            : Mage::helper('lengow_connector/data')->getHost($store_id)
        );
        $array['billing_address']['email'] = 'generated-email+'.$marketplace_sku.'@'.$domain;
        Mage::helper('lengow_connector/data')->log(
            'Import',
            Mage::helper('lengow_connector/data')->setLogMessage('log.import.generate_unique_email', array(
                'email' => $array['billing_address']['email']
            )),
            $log_output,
            $marketplace_sku
        );
        // first get by email
        $this->setWebsiteId($id_website)->loadByEmail($array['billing_address']['email']);
        if (!$this->getId()) {
            $this->setImportMode(true);
            $this->setWebsiteId($id_website);
            $this->setConfirmation(null);
            $this->setForceConfirmed(true);
            $this->setPasswordHash($this->hashPassword($this->generatePassword(8)));
            $this->setFromLengow(1);
        }
        // Billing address
        $temp_billing_names = array(
            'firstname' => $array['billing_address']['first_name'],
            'lastname'  => $array['billing_address']['last_name'],
        );
        $billing_names = $this->_getNames($temp_billing_names);
        $array['billing_address']['first_name'] = $billing_names['firstname'];
        $array['billing_address']['last_name'] = $billing_names['lastname'];
        $billing_address = $this->_convertAddress($array['billing_address']);
        $this->addAddress($billing_address);
        // Shipping address
        $temp_shipping_names = array(
            'firstname' => $array['delivery_address']['first_name'],
            'lastname' => $array['delivery_address']['last_name'],
        );
        $shipping_names = $this->_getNames($temp_shipping_names);
        $array['delivery_address']['first_name'] = $shipping_names['firstname'];
        $array['delivery_address']['last_name'] = $shipping_names['lastname'];
        // Get relay id if exist
        if (count($shipping_address->trackings) > 0 && !is_null($shipping_address->trackings[0]->relay->id)) {
            $array['delivery_address']['tracking_relay'] = $shipping_address->trackings[0]->relay->id;
        }
        $shipping_address = $this->_convertAddress($array['delivery_address'], 'shipping');
        $this->addAddress($shipping_address);
        Mage::helper('core')->copyFieldset('lengow_convert_address', 'to_customer', $array['billing_address'], $this);
        // set group
        $this->setGroupId(Mage::helper('lengow_connector/config')->get('customer_group', $store_id));
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
        foreach ($this->_address_api_nodes as $node) {
            $temp[$node] = (string)$api->{$node};
        }
        return $temp;
    }

    /**
     * Convert a array to customer address model
     *
     * @param array  $data
     * @param string $type
     *
     * @return  Mage_Customer_Model_Address
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
        Mage::helper('core')->copyFieldset('lengow_convert_address', 'to_'.$type.'_address', $data, $address);
        $address_1 = $data['first_line'];
        $address_2 = $data['second_line'];
        // Fix address 1
        if (empty($address_1) && !empty($address_2)) {
            $address_1 = $address_2;
            $address_2 = null;
        }
        // Fix address 2
        if (!empty($address_2)) {
            $address_1 = $address_1."\n".$address_2;
        }
        $address_3 = $data['complement'];
        if (!empty($address_3)) {
            $address_1 = $address_1."\n".$address_3;
        }
        // adding relay to address
        if (isset($data['tracking_relay'])) {
            $address_1 .= ' - Relay : '.$data['tracking_relay'];
        }
        $address->setStreet($address_1);
        $tel_1 = $data['phone_office'];
        $tel_2 = $data['phone_mobile'];
        $tel_1 = empty($tel_1) ? $tel_2 : $tel_1;
        if (!empty($tel_1)) {
            $this->setTelephone($tel_1);
        }
        if (!empty($tel_1)) {
            $address->setFax($tel_1);
        } else {
            if (!empty($tel_2)) {
                $address->setFax($tel_2);
            }
        }
        $codeRegion = substr(str_pad($address->getPostcode(), 5, '0', STR_PAD_LEFT), 0, 2);
        $id_region = Mage::getModel('directory/region')->getCollection()
            ->addRegionCodeFilter($codeRegion)
            ->addCountryFilter($address->getCountry())
            ->getFirstItem()
            ->getId();
        $address->setRegionId($id_region);
        $address->setCustomer($this);
        return $address;
    }

    /**
     * Check if firstname or lastname are empty
     *
     * @param array $array
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
        if (empty($array['lastname'])) {
            $array['lastname'] = '__';
        }
        if (empty($array['firstname'])) {
            $array['firstname'] == '__';
        }
        return $array;
    }

    /**
     * Split fullname
     *
     * @param string $fullname
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
