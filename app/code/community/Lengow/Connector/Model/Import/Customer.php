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
     * @var array current alias of mister
     */
    protected $_currentMale = array(
        'M',
        'M.',
        'Mr',
        'Mr.',
        'Mister',
        'Monsieur',
        'monsieur',
        'mister',
        'm.',
        'mr ',
        'sir',
    );

    /**
     * @var array current alias of miss
     */
    protected $_currentFemale = array(
        'Mme',
        'mme',
        'Mm',
        'mm',
        'Mlle',
        'mlle',
        'Madame',
        'madame',
        'Mademoiselle',
        'madamoiselle',
        'Mrs',
        'mrs',
        'Mrs.',
        'mrs.',
        'Miss',
        'miss',
        'Ms',
        'ms',
    );

    /**
     * Convert array to customer model
     *
     * @param object $orderData order data
     * @param object $shippingAddress shipping address data
     * @param integer $storeId Magento store id
     * @param string $marketplaceSku marketplace sku
     * @param boolean $logOutput see log or not
     *
     * @throws \Exception
     *
     * @return Mage_Customer_Model_Customer
     */
    public function createCustomer($orderData, $shippingAddress, $storeId, $marketplaceSku, $logOutput)
    {
        /** @var Lengow_Connector_Helper_Data $helper */
        $helper = Mage::helper('lengow_connector');
        // generation of fictitious email
        $host = $helper->getHost($storeId);
        $domain = !$host ? 'magento.shop' : $host;
        $customerEmail = $marketplaceSku . '-' . $orderData->marketplace . '@' . $domain;
        $helper->log(
            Lengow_Connector_Helper_Data::CODE_IMPORT,
            $helper->setLogMessage('log.import.generate_unique_email', array('email' => $customerEmail)),
            $logOutput,
            $marketplaceSku
        );
        // create or load customer if not exist
        $customer = $this->_getOrCreateCustomer($customerEmail, $storeId, $orderData->billing_address);
        // create or load default billing address if not exist
        $billingAddress = $this->_getOrCreateAddress($customer, $orderData->billing_address);
        if (!$billingAddress->getId()) {
            $customer->addAddress($billingAddress);
        }
        // create or load default shipping address if not exist
        $shippingAddress = $this->_getOrCreateAddress($customer, $shippingAddress, true);
        if (!$shippingAddress->getId()) {
            $customer->addAddress($shippingAddress);
        }
        $customer->save();
        return $customer;
    }

    /**
     * Create or load customer based on API data
     *
     * @param string $customerEmail fictitious customer email
     * @param integer $storeId Magento store id
     * @param object $billingData billing address data
     *
     * @throws \Exception
     *
     * @return Mage_Customer_Model_Customer
     */
    private function _getOrCreateCustomer($customerEmail, $storeId, $billingData)
    {
        $websiteId = Mage::getModel('core/store')->load($storeId)->getWebsiteId();
        // first get by email
        /** @var Mage_Customer_Model_Customer $customer */
        $customer = Mage::getModel('customer/customer');
        $customer->setWebsiteId($websiteId);
        $customer->setGroupId(Mage::helper('lengow_connector/config')->get('customer_group', $storeId));
        $customer->loadByEmail($customerEmail);
        // create new subscriber without send a confirmation email
        if (!$customer->getId()) {
            $customerNames = $this->_getNames($billingData);
            $customer->setImportMode(true);
            $customer->setWebsiteId($websiteId);
            $customer->setCompany((string)$billingData->company);
            $customer->setLastname($customerNames['lastName']);
            $customer->setFirstname($customerNames['firstName']);
            $customer->setEmail($customerEmail);
            $customer->setTaxvat((string)$billingData->vat_number);
            $customer->setConfirmation(null);
            $customer->setForceConfirmed(true);
            $customer->setPasswordHash($this->hashPassword($this->generatePassword(8)));
            $customer->setFromLengow(1);
        }
        return $customer;
    }

    /**
     * Create or load address based on API data
     *
     * @param Mage_Customer_Model_Customer $customer Magento customer instance
     * @param object $addressData address data
     * @param boolean $isShippingAddress is shipping address
     *
     * @return Mage_Customer_Model_Address
     */
    private function _getOrCreateAddress($customer, $addressData, $isShippingAddress = false)
    {
        $names = $this->_getNames($addressData);
        $street = $this->_getAddressStreet($addressData, $isShippingAddress);
        $postcode = (string)$addressData->zipcode;
        $city = ucfirst(strtolower(preg_replace('/[!<>?=+@{}_$%]/sim', '', $addressData->city)));
        $defaultAddress = $isShippingAddress
            ? $customer->getDefaultShippingAddress()
            : $customer->getDefaultBillingAddress();
        if (!$defaultAddress || !$this->_addressIsAlreadyCreated($defaultAddress, $names, $street, $postcode, $city)) {
            /** @var Mage_Customer_Model_Address $address */
            $address = Mage::getModel('customer/address');
            $address->setId(null);
            $address->setCustomer($customer);
            $address->setIsDefaultBilling(!$isShippingAddress);
            $address->setIsDefaultShipping($isShippingAddress);
            $address->setCompany((string)$addressData->company);
            $address->setFirstname($names['firstName']);
            $address->setLastname($names['lastName']);
            $address->setStreet($street);
            $address->setPostcode($postcode);
            $address->setCity($city);
            $address->setCountryId((string)$addressData->common_country_iso_a2);
            $phoneNumbers = $this->_getPhoneNumbers($addressData);
            $address->setTelephone($phoneNumbers['phone']);
            $address->setFax($phoneNumbers['secondPhone']);
            $address->setVatId((string)$addressData->vat_number);
            $regionId = $this->_getMagentoRegionId($address->getCountry(), $postcode);
            if ($regionId) {
                $address->setRegionId($regionId);
            }
        } else {
            $address = $defaultAddress;
        }
        return $address;
    }

    /**
     * Check if address is already created for this customer
     *
     * @param Mage_Customer_Model_Address $defaultAddress Magento Address instance
     * @param array $names names from Api
     * @param string $street street from Api
     * @param string $postcode postcode from Api
     * @param string $city city from Api
     *
     * @return boolean
     */
    private function _addressIsAlreadyCreated($defaultAddress, $names, $street, $postcode, $city)
    {
        $firstName = isset($names['firstName']) ? $names['firstName'] : '';
        $lastName = isset($names['lastName']) ? $names['lastName'] : '';
        $defaultAddressStreet = is_array($defaultAddress->getStreet())
            ? implode("\n", $defaultAddress->getStreet())
            : $defaultAddress->getStreet();
        if ($defaultAddress->getFirstname() === $firstName
            && $defaultAddress->getLastname() === $lastName
            && $defaultAddressStreet === $street
            && $defaultAddress->getPostcode() === $postcode
            && $defaultAddress->getCity() === $city
        ) {
            return true;
        }
        return false;
    }

    /**
     * Check if first name or last name are empty
     *
     * @param object $addressData API address data
     *
     * @return array
     */
    private function _getNames($addressData)
    {
        $names = array(
            'firstName' => trim($addressData->first_name),
            'lastName' => trim($addressData->last_name),
            'fullName' => $this->_cleanFullName($addressData->full_name),
        );
        if (empty($names['lastName']) && empty($names['firstName'])) {
            $names = $this->_splitNames($names['fullName']);
        } else {
            if (empty($names['lastName'])) {
                $names = $this->_splitNames($names['lastName']);
            } elseif (empty($names['firstName'])) {
                $names = $this->_splitNames($names['firstName']);
            }
        }
        unset($names['fullName']);
        $names['firstName'] = !empty($names['firstName']) ? ucfirst(strtolower($names['firstName'])) : '__';
        $names['lastName'] = !empty($names['lastName']) ? ucfirst(strtolower($names['lastName'])) : '__';
        return $names;
    }

    /**
     * Clean full name field without salutation
     *
     * @param string $fullName full name of the customer
     *
     * @return string
     */
    private function _cleanFullName($fullName)
    {
        $split = explode(' ', $fullName);
        if ($split && !empty($split)) {
            $fullName = (in_array($split[0], $this->_currentMale) || in_array($split[0], $this->_currentFemale))
                ? ''
                : $split[0];
            for ($i = 1; $i < count($split); $i++) {
                if (!empty($fullName)) {
                    $fullName .= ' ';
                }
                $fullName .= $split[$i];
            }
        }
        return $fullName;
    }

    /**
     * Split full name to get first name and last name
     *
     * @param string $fullName full name of the customer
     *
     * @return array
     */
    private function _splitNames($fullName)
    {
        $split = explode(' ', $fullName);
        if ($split && !empty($split)) {
            $names['firstName'] = $split[0];
            $names['lastName'] = '';
            for ($i = 1; $i < count($split); $i++) {
                if (!empty($names['lastName'])) {
                    $names['lastName'] .= ' ';
                }
                $names['lastName'] .= $split[$i];
            }
        } else {
            $names = ['firstName' => '', 'lastName' => ''];
        }
        return $names;
    }

    /**
     * Get clean address street
     *
     * @param object $addressData API address data
     * @param boolean $isShippingAddress is shipping address
     *
     * @return string
     */
    private function _getAddressStreet($addressData, $isShippingAddress = false)
    {
        $street = trim($addressData->first_line);
        $secondLine = trim($addressData->second_line);
        $complement = trim($addressData->complement);
        if (empty($street)) {
            if (!empty($secondLine)) {
                $street = $secondLine;
                $secondLine = '';
            } elseif (!empty($complement)) {
                $street = $complement;
                $complement = '';
            }
        }
        // get relay id for shipping addresses
        if ($isShippingAddress
            && !empty($addressData->trackings)
            && isset($addressData->trackings[0]->relay)
            && $addressData->trackings[0]->relay->id !== null
        ) {
            $relayId = 'Relay id: ' . $addressData->trackings[0]->relay->id;
            $complement .= !empty($complement) ? ' - ' . $relayId : $relayId;
        }
        if (!empty($secondLine)) {
            $street .= "\n" . $secondLine;
        }
        if (!empty($complement)) {
            $street .= "\n" . $complement;
        }
        return strtolower($street);
    }

    /**
     * Get phone and second phone numbers
     *
     * @param object $addressData API address data
     *
     * @return array
     */
    private function _getPhoneNumbers($addressData)
    {
        $phoneHome = $addressData->phone_home;
        $phoneMobile = $addressData->phone_mobile;
        $phoneOffice = $addressData->phone_office;
        if (empty($phoneHome)) {
            if (!empty($phoneMobile)) {
                $phoneHome = $phoneMobile;
                $phoneMobile = $phoneOffice ? $phoneOffice : '';
            } elseif (!empty($phoneOffice)) {
                $phoneHome = $phoneOffice;
            }
        } else {
            if (empty($phoneMobile) && !empty($phoneOffice)) {
                $phoneMobile = $phoneOffice;
            }
        }
        if ($phoneHome === $phoneMobile) {
            $phoneMobile = '';
        }
        return array(
            'phone' => !empty($phoneHome) ? $this->_cleanPhoneNumber($phoneHome) : '__',
            'secondPhone' => !empty($phoneMobile) ? $this->_cleanPhoneNumber($phoneMobile) : '',
        );
    }

    /**
     * Clean phone number
     *
     * @param string $phoneNumber phone number to clean
     *
     * @return string
     */
    private function _cleanPhoneNumber($phoneNumber)
    {
        if (!$phoneNumber) {
            return '';
        }
        return preg_replace('/[^0-9]*/', '', $phoneNumber);
    }

    /**
     * Get Magento region id
     *
     * @param string $country Magento Country
     * @param string $postcode address postcode
     *
     * @return string
     */
    private function _getMagentoRegionId($country, $postcode)
    {
        $codeRegion = substr(str_pad($postcode, 5, '0', STR_PAD_LEFT), 0, 2);
        $regionId = Mage::getModel('directory/region')->getCollection()
            ->addRegionCodeFilter($codeRegion)
            ->addCountryFilter($country)
            ->getFirstItem()
            ->getId();
        return $regionId;
    }
}
