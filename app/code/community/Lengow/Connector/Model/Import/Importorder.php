<?php

/**
 *
 * @category    Lengow
 * @package     Lengow_Connector
 * @author      Team module <team-module@lengow.com>
 * @copyright   2016 Lengow SAS
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

class Lengow_Connector_Model_Import_Importorder extends Varien_Object
{
    /**
     * @var Lengow_Connector_Helper_Data
     */
    protected $_helper = null;

    /**
     * @var Lengow_Connector_Helper_Import
     */
    protected $_import_helper = null;

    /**
     * @var Lengow_Connector_Helper_Config
     */
    protected $_config = null;

    /**
     * @var Lengow_Connector_Model_Import_Order
     */
    protected $_model_order = null;

    /**
     * @var integer store id
     */
    protected $_store_id = null;

    /**
     * @var boolean use preprod mode
     */
    protected $_preprod_mode = false;

    /**
     * @var boolean display log messages
     */
    protected $_log_output = false;

    /**
     * @var array valid products lengow
     */
    protected $_lengow_products;

    /**
     * @var Lengow_Connector_Model_Import_Marketplace
     */
    protected $_marketplace;

    /**
     * @var string id lengow of current order
     */
    protected $_marketplace_sku;

    /**
     * @var string marketplace label
     */
    protected $_marketplace_label;

    /**
     * @var integer id of delivery address for current order
     */
    protected $_delivery_address_id;

    /**
     * @var mixed
     */
    protected $_order_data;

    /**
     * @var mixed
     */
    protected $_package_data;

    /**
     * @var boolean
     */
    protected $_first_package;

    /**
     * @var boolean re-import order
     */
    protected $_is_reimported = false;

    /**
     * @var integer id of the record Lengow order table
     */
    protected $_order_lengow_id;

    /**
     * @var string
     */
    protected $_order_state_marketplace;

    /**
     * @var string
     */
    protected $_order_state_lengow;

    /**
     * @var float
     */
    protected $_processing_fee;

    /**
     * @var float
     */
    protected $_shipping_cost;

    /**
     * @var float
     */
    protected $_order_amount;

    /**
     * @var integer
     */
    protected $_order_items;

    /**
     * @var string
     */
    protected $_carrier_name = null;

    /**
     * @var string
     */
    protected $_carrier_method = null;

    /**
     * @var string
     */
    protected $_tracking_number = null;

    /**
     * @var boolean
     */
    protected $_shipped_by_mp = false;

    /**
     * @var string
     */
    protected $_relay_id = null;

    /**
     * Construct the import order manager
     *
     * @param array params optional options
     *
     * integer  store_id       Id store for current order
     * boolean  preprod_mode   preprod mode
     * boolean  log_output     display log messages
     */
    public function __construct($params = array())
    {
        // get params
        $this->_store_id            = $params['store_id'];
        $this->_preprod_mode        = $params['preprod_mode'];
        $this->_log_output          = $params['log_output'];
        $this->_marketplace_sku     = $params['marketplace_sku'];
        $this->_delivery_address_id = $params['delivery_address_id'];
        $this->_order_data          = $params['order_data'];
        $this->_package_data        = $params['package_data'];
        $this->_first_package       = $params['first_package'];
        $this->_import_helper       = $params['import_helper'];
        // get helpers
        $this->_helper = Mage::helper('lengow_connector/data');
        $this->_config = Mage::helper('lengow_connector/config');
        $this->_model_order = Mage::getModel('lengow/import_order');
        // get marketplace and Lengow order state
        $this->_marketplace = $this->_import_helper->getMarketplaceSingleton(
            (string)$this->_order_data->marketplace,
            $this->_store_id
        );
        $this->_marketplace_label = $this->_marketplace->label_name;
        $this->_order_state_marketplace = (string)$this->_order_data->marketplace_status;
        $this->_order_state_lengow = $this->_marketplace->getStateLengow($this->_order_state_marketplace);
    }

    /**
     * Create or update order
     *
     * @return mixed
     */
    public function importOrder()
    {
        // if log import exist and not finished
        $import_log = $this->_model_order->orderIsInError(
            $this->_marketplace_sku,
            $this->_delivery_address_id,
            'import'
        );
        if ($import_log) {
            $decoded_message = $this->_helper->decodeLogMessage($import_log['message'], 'en_GB');
            $this->_helper->log(
                'Import',
                $this->_helper->setLogMessage('log.import.error_already_created', array(
                    'decoded_message' => $decoded_message,
                    'date_message'    => $import_log['created_at']
                )),
                $this->_log_output,
                $this->_marketplace_sku
            );
            return false;
        }
        // recovery id if the command has already been imported
        $order_id = $this->_model_order->getOrderIdIfExist(
            $this->_marketplace_sku,
            (string)$this->_marketplace->name,
            $this->_delivery_address_id
        );
        // update order state if already imported
        if ($order_id) {
            $order_updated = $this->_checkAndUpdateOrder($order_id);
            if ($order_updated && isset($order_updated['update'])) {
                return $this->_returnResult('update', $order_updated['order_lengow_id'], $order_id);
            }
            if (!$this->_is_reimported) {
                return false;
            }
        }
        // // checks if an external id already exists
        $order_magento_id = $this->_checkExternalIds($this->_order_data->merchant_order_id);
        if ($order_magento_id && !$this->_preprod_mode && !$this->_is_reimported) {
            $this->_helper->log(
                'Import',
                $this->_helper->setLogMessage('log.import.external_id_exist', array(
                    'order_id' => $order_magento_id
                )),
                $this->_log_output,
                $this->_marketplace_sku
            );
            return false;
        }
        // if order is cancelled or new -> skip
        if (!$this->_import_helper->checkState($this->_order_state_marketplace, $this->_marketplace)) {
            $this->_helper->log(
                'Import',
                $this->_helper->setLogMessage('log.import.current_order_state_unavailable', array(
                    'order_state_marketplace' => $this->_order_state_marketplace,
                    'marketplace_name'        => $this->_marketplace->name
                )),
                $this->_log_output,
                $this->_marketplace_sku
            );
            return false;
        }
        // get a record in the lengow order table
        $this->_order_lengow_id = $this->_model_order->getLengowOrderId(
            $this->_marketplace_sku,
            $this->_delivery_address_id
        );
        if (!$this->_order_lengow_id) {
            // created a record in the lengow order table
            if (!$this->_createLengowOrder()) {
                $this->_helper->log(
                    'Import',
                    $this->_helper->setLogMessage('log.import.lengow_order_not_saved'),
                    $this->_log_output,
                    $this->_marketplace_sku
                );
                return false;
            } else {
                $this->_helper->log(
                    'Import',
                    $this->_helper->setLogMessage('log.import.lengow_order_saved'),
                    $this->_log_output,
                    $this->_marketplace_sku
                );
            }
        }
        // load lengow order
        $order_lengow = $this->_model_order->load((int)$this->_order_lengow_id);
        // checks if the required order data is present
        if (!$this->_checkOrderData()) {
            return $this->_returnResult('error', $this->_order_lengow_id);
        }
        // get order amount and load processing fees and shipping cost
        $this->_order_amount = $this->_getOrderAmount();
        // load tracking data
        $this->_loadTrackingData();
        // get customer name and email
        $customer_name = $this->_getCustomerName();
        $customer_email = (!is_null($this->_order_data->billing_address->email)
            ? (string)$this->_order_data->billing_address->email
            : (string)$this->_package_data->delivery->email
        );
        // update Lengow order with new informations
        $order_lengow->updateOrder(array(
            'currency'             => $this->_order_data->currency->iso_a3,
            'total_paid'           => $this->_order_amount,
            'order_item'           => $this->_order_items,
            'customer_name'        => $customer_name,
            'customer_email'       => $customer_email,
            'commission'           => (float) $this->_order_data->commission,
            'carrier'              => $this->_carrier_name,
            'method'               => $this->_carrier_method,
            'tracking'             => $this->_tracking_number,
            'sent_marketplace'     => $this->_shipped_by_mp,
            'delivery_country_iso' => $this->_package_data->delivery->common_country_iso_a2,
            'order_lengow_state'   => $this->_order_state_lengow
        ));
        // try to import order
        try {
            // check if the order is shipped by marketplace
            if ($this->_shipped_by_mp) {
                $this->_helper->log(
                    'Import',
                    $this->_helper->setLogMessage('log.import.order_shipped_by_marketplace', array(
                        'markeplace_name' => $this->_marketplace->name
                    )),
                    $this->_log_output,
                    $this->_marketplace_sku
                );
                if (!$this->_config->get('import_ship_mp_enabled', $this->_store_id)) {
                    $order_lengow->updateOrder(
                        array(
                            'order_process_state'   => 2,
                            'extra'                 => Mage::helper('core')->jsonEncode($this->_order_data)
                        )
                    );
                    return false;
                }
            }
            // Create or Update customer with addresses
            $customer = Mage::getModel('lengow/import_customer');
            $customer->createCustomer(
                $this->_order_data,
                $this->_package_data->delivery,
                $this->_store_id,
                $this->_marketplace_sku,
                $this->_log_output
            );
            // Create Magento Quote
            $quote = $this->_createQuote($customer);
            // Create Magento order
            $order = $this->_makeOrder($quote);

            if ($order) {
                // Save order line id in lengow_order_line table
                $order_line_saved = $this->_saveLengowOrderLine($order, $quote);
                $this->_helper->log(
                    'Import',
                    $this->_helper->setLogMessage('log.import.lengow_order_line_saved', array(
                        'order_line_saved' => $order_line_saved
                    )),
                    $this->_log_output,
                    $this->_marketplace_sku
                );
                $this->_helper->log(
                    'Import',
                    $this->_helper->setLogMessage('log.import.order_successfully_imported', array(
                        'order_id' => $order->getIncrementId()
                    )),
                    $this->_log_output,
                    $this->_marketplace_sku
                );
                // Update state to shipped
                if ($this->_order_state_lengow == 'shipped' || $this->_order_state_lengow == 'closed') {
                    $this->_model_order->toShip(
                        $order,
                        $this->_carrier_name,
                        $this->_carrier_method,
                        $this->_tracking_number
                    );
                    $this->_helper->log(
                        'Import',
                        $this->_helper->setLogMessage('log.import.order_state_updated', array(
                            'state_name' => 'Complete'
                        )),
                        $this->_log_output,
                        $this->_marketplace_sku
                    );
                }
                // Update Lengow order record
                $order_lengow->updateOrder(array(
                    'order_id'            => $order->getId(),
                    'order_sku'           => $order->getIncrementId(),
                    'order_process_state' => $this->_model_order->getOrderProcessState($this->_order_state_lengow),
                    'extra'               => Mage::helper('core')->jsonEncode($this->_order_data),
                    'order_lengow_state'  => $this->_order_state_lengow,
                    'is_in_error'         => 0
                ));
                $this->_helper->log(
                    'Import',
                    $this->_helper->setLogMessage('log.import.lengow_order_updated'),
                    $this->_log_output,
                    $this->_marketplace_sku
                );
            } else {
                throw new Lengow_Connector_Model_Exception(
                    $this->_helper->setLogMessage('lengow_log.exception.order_is_empty')
                );
            }

            if ($this->_is_reimported
                || ($this->_shipped_by_mp && !$this->_config->get('import_stock_ship_mp', $this->_store_id))
            ) {
                if ($this->_is_reimported) {
                    $log_message = $this->_helper->setLogMessage('log.import.quantity_back_reimported_order');
                } else {
                    $log_message = $this->_helper->setLogMessage('log.import.quantity_back_shipped_by_marketplace');
                }
                $this->_helper->log(
                    'Import',
                    $log_message,
                    $this->_log_output,
                    $this->_marketplace_sku);

                $this->addQuantityBack($quote);
            }

            // Inactivate quote (Test)
            $quote->setIsActive(false)->save();
        } catch (Lengow_Connector_Model_Exception $e) {
            $error_message = $e->getMessage();
        } catch (Exception $e) {
            $error_message = '[Magento error]: "'.$e->getMessage().'" '.$e->getFile().' line '.$e->getLine();
        }
        if (isset($error_message)) {
            $order_error = Mage::getModel('lengow/import_ordererror');
            $order_error->createOrderError(array(
                'order_lengow_id' => $this->_order_lengow_id,
                'message'         => $error_message,
                'type'            => 'import'
            ));
            $decoded_message = $this->_helper->decodeLogMessage($error_message, 'en_GB');
            $this->_helper->log(
                'Import',
                $this->_helper->setLogMessage('log.import.order_import_failed', array(
                    'decoded_message' => $decoded_message
                )),
                $this->_log_output,
                $this->_marketplace_sku
            );
            $order_lengow->updateOrder(array(
                'extra'              => Mage::helper('core')->jsonEncode($this->_order_data),
                'order_lengow_state' => $this->_order_state_lengow,
            ));
            return $this->_returnResult('error', $this->_order_lengow_id);
        }
        return $this->_returnResult('new', $this->_order_lengow_id, $order->getId());
    }

    /**
     * Add quantity back to stock
     * @param array     $products   list of products
     *
     * @return this
     */
    protected function addQuantityBack($quote)
    {
        $products = $quote->getLengowProduct();

        foreach ($products as $productId => $product) {
            Mage::getModel('cataloginventory/stock')->backItemQty($productId,$product['quantity']);
        }

        return $this;
    }

    /**
     * Return an array of result for each order
     *
     * @param string    $type_result        Type of result (new, update, error)
     * @param integer   $order_lengow_id    ID of the lengow order record
     * @param integer   $order_id           Order ID Magento
     *
     * @return array
     */
    protected function _returnResult($type_result, $order_lengow_id, $order_id = null)
    {
        $result = array(
            'order_id'         => $order_id,
            'order_lengow_id'  => $order_lengow_id,
            'marketplace_sku'  => $this->_marketplace_sku,
            'marketplace_name' => (string)$this->_marketplace->name,
            'lengow_state'     => $this->_order_state_lengow,
            'order_new'        => ($type_result == 'new' ? true : false),
            'order_update'     => ($type_result == 'update' ? true : false),
            'order_error'      => ($type_result == 'error' ? true : false)
        );
        return $result;
    }

     /**
     * Check the command and updates data if necessary
     *
     * @param integer $order_id Order ID Magento
     *
     * @return boolean
     */
    protected function _checkAndUpdateOrder($order_id)
    {
        $order = Mage::getModel('sales/order')->load($order_id);
        $this->_helper->log(
            'Import',
            $this->_helper->setLogMessage('log.import.order_already_imported', array(
                'order_id' => $order->getIncrementId()
            )),
            $this->_log_output,
            $this->_marketplace_sku
        );
        $order_lengow_id = $this->_model_order->getLengowOrderIdWithOrderId($order_id);
        $result = array('order_lengow_id' => $order_lengow_id);
        // Lengow -> Cancel and reimport order
        if ($order->getData('is_reimported_lengow') == 1) {
            $this->_helper->log(
                'Import',
                $this->_helper->setLogMessage('log.import.order_ready_to_reimport', array(
                    'order_id' => $order->getIncrementId()
                )),
                $this->_log_output,
                $this->_marketplace_sku
            );
            $this->_is_reimported = true;
            return false;
        } else {
            // try to update magento order, lengow order and finish actions if necessary
            $order_updated = $this->_model_order->updateState(
                $order,
                $this->_order_state_lengow,
                $this->_order_data,
                $this->_package_data,
                $order_lengow_id
            );
            if ($order_updated) {
                $result['update'] = true;
                $this->_helper->log(
                    'Import',
                    $this->_helper->setLogMessage('log.import.order_state_updated', array('state_name' => $order_updated)),
                    $this->_log_output,
                    $this->_marketplace_sku
                );
            }
        }
        unset($order);
        return $result;
    }

    /**
     * Checks if order data are present
     *
     * @return boolean
     */
    protected function _checkOrderData()
    {
        $error_messages = array();
        if (count($this->_package_data->cart) == 0) {
            $error_messages[] = $this->_helper->setLogMessage('lengow_log.error.no_product');
        }
        if (!isset($this->_order_data->currency->iso_a3)) {
            $error_messages[] = $this->_helper->setLogMessage('lengow_log.error.no_currency');
        }
        if ($this->_order_data->total_order == -1) {
            $error_messages[] = $this->_helper->setLogMessage('lengow_log.error.no_change_rate');
        }
        if (is_null($this->_order_data->billing_address)) {
            $error_messages[] = $this->_helper->setLogMessage('lengow_log.error.no_billing_address');
        } elseif (is_null($this->_order_data->billing_address->common_country_iso_a2)) {
            $error_messages[] = $this->_helper->setLogMessage('lengow_log.error.no_country_for_billing_address');
        }
        if (is_null($this->_package_data->delivery->common_country_iso_a2)) {
            $error_messages[] = $this->_helper->setLogMessage('lengow_log.error.no_country_for_delivery_address');
        }
        if (count($error_messages) > 0) {
            foreach ($error_messages as $error_message) {
                $order_error = Mage::getModel('lengow/import_ordererror');
                $order_error->createOrderError(array(
                    'order_lengow_id' => $this->_order_lengow_id,
                    'message'         => $error_message,
                    'type'            => 'import'
                ));
                $decoded_message = $this->_helper->decodeLogMessage($error_message, 'en_GB');
                $this->_helper->log(
                    'Import',
                    $this->_helper->setLogMessage('log.import.order_import_failed', array(
                        'decoded_message' => $decoded_message
                    )),
                    $this->_log_output,
                    $this->_marketplace_sku
                );
            };
            return false;
        }
        return true;
    }

    /**
     * Checks if an external id already exists
     *
     * @param array $external_ids
     *
     * @return mixed
     */
    protected function _checkExternalIds($external_ids)
    {
        $line_id = false;
        $order_magento_id = false;
        if (!is_null($external_ids) && count($external_ids) > 0) {
            foreach ($external_ids as $external_id) {
                $line_id = $this->_model_order->getOrderIdWithDeliveryAddress(
                    (int)$external_id,
                    (int)$this->_delivery_address_id
                );
                if ($line_id) {
                    $order_magento_id = $external_id;
                    break;
                }
            }
        }
        return $order_magento_id;
    }

    /**
     * Get order amount
     *
     * @return float
     */
    protected function _getOrderAmount()
    {
        $this->_processing_fee = (float)$this->_order_data->processing_fee;
        $this->_shipping_cost = (float)$this->_order_data->shipping;
        // rewrite processing fees and shipping cost
        if ($this->_first_package == false) {
            $this->_processing_fee = 0;
            $this->_shipping_cost = 0;
            $this->_helper->log(
                'Import',
                $this->_helper->setLogMessage('log.import.rewrite_processing_fee'),
                $this->_log_output,
                $this->_marketplace_sku
            );
            $this->_helper->log(
                'Import',
                $this->_helper->setLogMessage('log.import.rewrite_shipping_cost'),
                $this->_log_output,
                $this->_marketplace_sku
            );
        }
        // get total amount and the number of items
        $nb_items = 0;
        $total_amount = 0;
        foreach ($this->_package_data->cart as $product) {
            // check whether the product is canceled for amount
            if (!is_null($product->marketplace_status)) {
                $state_product = $this->_marketplace->getStateLengow((string)$product->marketplace_status);
                if ($state_product == 'canceled' || $state_product == 'refused') {
                    continue;
                }
            }
            $nb_items += (int)$product->quantity;
            $total_amount += (float)$product->amount;
        }
        $this->_order_items = $nb_items;
        $order_amount = $total_amount + $this->_processing_fee + $this->_shipping_cost;
        return $order_amount;
    }

    /**
     * Get tracking data and update Lengow order record
     *
     * @param mixed $package
     *
     * @return mixed
     */
    protected function _loadTrackingData()
    {
        $trackings = $this->_package_data->delivery->trackings;
        if (count($trackings) > 0) {
            $this->_carrier_name     = (!is_null($trackings[0]->carrier) ? (string)$trackings[0]->carrier : null);
            $this->_carrier_method   = (!is_null($trackings[0]->method) ? (string)$trackings[0]->method : null);
            $this->_tracking_number  = (!is_null($trackings[0]->number) ? (string)$trackings[0]->number : null);
            $this->_relay_id         = (!is_null($trackings[0]->relay->id) ? (string)$trackings[0]->relay->id : null);
            if (!is_null($trackings[0]->is_delivered_by_marketplace) && $trackings[0]->is_delivered_by_marketplace) {
                $this->_shipped_by_mp = true;
            }
        }
    }

    /**
     * Get customer name
     *
     * @return string
     */
    protected function _getCustomerName()
    {
        $firstname = (string)$this->_order_data->billing_address->first_name;
        $lastname = (string)$this->_order_data->billing_address->last_name;
        $firstname = ucfirst(strtolower($firstname));
        $lastname = ucfirst(strtolower($lastname));
        return $firstname.' '.$lastname;
    }

    /**
     * Create quote
     *
     * @param Lengow_Connector_Model_Import_Customer $customer
     *
     * @return Lengow_Connector_Model_Import_Quote
     */
    protected function _createQuote(Lengow_Connector_Model_Import_Customer $customer)
    {
        $quote = Mage::getModel('lengow/import_quote')
            ->setIsMultiShipping(false)
            ->setStore(Mage::app()->getStore($this->_store_id))
            ->setIsSuperMode(true); // set quote to supermode
        // import customer addresses into quote
        // Set billing Address
        $customer_billing_address = Mage::getModel('customer/address')->load($customer->getDefaultBilling());
        $billing_address = Mage::getModel('sales/quote_address')
            ->setShouldIgnoreValidation(true)
            ->importCustomerAddress($customer_billing_address)
            ->setSaveInAddressBook(0);
        // Set shipping Address
        $customer_shipping_address = Mage::getModel('customer/address')->load($customer->getDefaultShipping());
        $shipping_address = Mage::getModel('sales/quote_address')
            ->setShouldIgnoreValidation(true)
            ->importCustomerAddress($customer_shipping_address)
            ->setSaveInAddressBook(0)
            ->setSameAsBilling(0);
        $quote->assignCustomerWithAddressChange($customer, $billing_address, $shipping_address);
        // check if store include tax (Product and shipping cost)
        $priceIncludeTax = Mage::helper('tax')->priceIncludesTax($quote->getStore());
        $shippingIncludeTax = Mage::helper('tax')->shippingPriceIncludesTax($quote->getStore());
        // add product in quote
        $quote->addLengowProducts(
            $this->_package_data->cart,
            $this->_marketplace,
            $this->_marketplace_sku,
            $this->_log_output,
            $priceIncludeTax
        );
        // Get shipping cost with tax
        $shipping_cost = $this->_processing_fee + $this->_shipping_cost;
        // if shipping cost not include tax -> get shipping cost without tax
        if (!$shippingIncludeTax) {
            $basedOn = Mage::getStoreConfig(
                Mage_Tax_Model_Config::CONFIG_XML_PATH_BASED_ON,
                $quote->getStore()
            );
            $country_id = ($basedOn == 'shipping')
                ? $shipping_address->getCountryId()
                : $billing_address->getCountryId();
            $shippingTaxClass = Mage::getStoreConfig(
                Mage_Tax_Model_Config::CONFIG_XML_PATH_SHIPPING_TAX_CLASS,
                $quote->getStore()
            );
            $taxCalculator = Mage::getModel('tax/calculation');
            $taxRequest = new Varien_Object();
            $taxRequest->setCountryId($country_id)
                ->setCustomerClassId($customer->getTaxClassId())
                ->setProductClassId($shippingTaxClass);
            $tax_rate = (float)$taxCalculator->getRate($taxRequest);
            $tax_shipping_cost = (float)$taxCalculator->calcTaxAmount($shipping_cost, $tax_rate, true);
            $shipping_cost = $shipping_cost - $tax_shipping_cost;
        }
        // update shipping rates for current order
        $rates = $quote->getShippingAddress()
            ->setCollectShippingRates(true)
            ->collectShippingRates()
            ->getShippingRatesCollection();
        $shipping_method = $this->_updateRates($rates, $shipping_cost);
        // set shipping price and shipping method for current order
        $quote->getShippingAddress()
            ->setShippingPrice($shipping_cost)
            ->setShippingMethod($shipping_method);
        // collect totals
        $quote->collectTotals();
        // Re-ajuste cents for item quote
        // Conversion Tax Include > Tax Exclude > Tax Include maybe make 0.01 amount error
        if (!$priceIncludeTax) {
            if ($quote->getGrandTotal() != $this->_order_amount) {
                $quote_items = $quote->getAllItems();
                foreach ($quote_items as $item) {
                    $lengow_product = $quote->getLengowProducts((string)$item->getProduct()->getId());
                    if ($lengow_product['amount'] != $item->getRowTotalInclTax()) {
                        $diff = $lengow_product['amount'] - $item->getRowTotalInclTax();
                        $item->setPriceInclTax($item->getPriceInclTax() + ($diff / $item->getQty()));
                        $item->setBasePriceInclTax($item->getPriceInclTax());
                        $item->setPrice($item->getPrice() + ($diff / $item->getQty()));
                        $item->setOriginalPrice($item->getPrice());
                        $item->setRowTotal($item->getRowTotal() + $diff);
                        $item->setBaseRowTotal($item->getRowTotal());
                        $item->setRowTotalInclTax($lengow_product['amount']);
                        $item->setBaseRowTotalInclTax($item->getRowTotalInclTax());
                    }
                }
            }
        }
        // set payment method lengow
        $payment_type = (string)(count($this->_order_data->payments) > 0
            ? $this->_order_data->payments[0]->type
            : null
        );
        $quote->getPayment()->importData(
            array(
                'method'      => 'lengow',
                'marketplace' => (string)$this->_order_data->marketplace.' - '.$payment_type,
            )
        );
        $quote->save();
        return $quote;
    }

    /**
     * Update Rates with shipping cost
     *
     * @param Mage_Sales_Model_Quote_Address_Rate $rates
     * @param float                               $shipping_cost
     * @param string                              $shipping_method
     * @param boolean                             $first            stop recursive effect
     *
     * @return boolean
     */
    protected function _updateRates($rates, $shipping_cost, $shipping_method = null, $first = true)
    {
        if (!$shipping_method) {
            $shipping_method = $this->_config->get('import_shipping_method', $this->_store_id);
        }
        if (empty($shipping_method)) {
            $shipping_method = 'lengow_lengow';
        }
        foreach ($rates as &$rate) {
            // make sure the chosen shipping method is correct
            if ($rate->getCode() == $shipping_method) {
                if ($rate->getPrice() != $shipping_cost) {
                    $rate->setPrice($shipping_cost);
                    $rate->setCost($shipping_cost);
                }
                return $rate->getCode();
            }
        }
        // stop recursive effect
        if (!$first) {
            return 'lengow_lengow';
        }
        // get lengow shipping method if selected shipping method is unavailable
        $this->_helper->log(
            'Import',
            $this->_helper->setLogMessage('log.import.shipping_method_unavailable'),
            $this->_log_output,
            $this->_marketplace_sku
        );
        return $this->updateRates($rates, $shipping_cost, 'lengow_lengow', false);
    }

    /**
     * Create order
     *
     * @param Lengow_Connector_Model_Import_Quote $quote
     *
     * @return Mage_Sales_Model_Order
     */
    protected function _makeOrder(Lengow_Connector_Model_Import_Quote $quote)
    {
        $additional_data = array(
            'from_lengow'                => true,
            'marketplace_lengow'         => (string)$this->_order_data->marketplace,
            'order_id_lengow'            => (string)$this->_marketplace_sku,
            'delivery_address_id_lengow' => (int)$this->_delivery_address_id,
            'is_reimported_lengow'       => false,
            'global_currency_code'       => (string)$this->_order_data->currency->iso_a3,
            'base_currency_code'         => (string)$this->_order_data->currency->iso_a3,
            'store_currency_code'        => (string)$this->_order_data->currency->iso_a3,
            'order_currency_code'        => (string)$this->_order_data->currency->iso_a3
        );
        $service = Mage::getModel('sales/service_quote', $quote);
        $service->setOrderData($additional_data);
        $order = false;
        if (method_exists($service, 'submitAll')) {
            $service->submitAll();
            $order = $service->getOrder();
        } else {
            $order = $service->submit();
        }
        if (!$order) {
            throw new Lengow_Connector_Model_Exception(
                $this->_helper->setLogMessage('lengow_log.exception.order_failed_with_quote')
            );
        }
        // modify order dates to use actual dates
        // Get all params to create order
        if (!is_null($this->_order_data->marketplace_order_date)) {
            $order_date = (string)$this->_order_data->marketplace_order_date;
        } else {
            $order_date = (string)$this->_order_data->imported_at;
        }
        $order->setCreatedAt(Mage::getModel('core/date')->date('Y-m-d H:i:s', strtotime($order_date)));
        $order->setUpdatedAt(Mage::getModel('core/date')->date('Y-m-d H:i:s', strtotime($order_date)));
        $order->save();
        // Re-ajuste cents for total and shipping cost
        // Conversion Tax Include > Tax Exclude > Tax Include maybe make 0.01 amount error
        $priceIncludeTax = Mage::helper('tax')->priceIncludesTax($quote->getStore());
        $shippingIncludeTax = Mage::helper('tax')->shippingPriceIncludesTax($quote->getStore());
        if (!$priceIncludeTax || !$shippingIncludeTax) {
            if ($order->getGrandTotal() != $this->_order_amount) {
                // check Grand Total
                $diff = $this->_order_amount - $order->getGrandTotal();
                $order->setGrandTotal($this->_order_amount);
                $order->setBaseGrandTotal($order->getGrandTotal());
                // if the difference is only on the grand total, removing the difference of shipping cost
                if (($order->getSubtotalInclTax() + $order->getShippingInclTax()) == $this->_order_amount) {
                    $order->setShippingAmount($order->getShippingAmount() + $diff);
                    $order->setBaseShippingAmount($order->getShippingAmount());
                } else {
                    // check Shipping Cost
                    $diff_shipping = 0;
                    $shipping_cost = $this->_processing_fee + $this->_shipping_cost;
                    if ($order->getShippingInclTax() != $shipping_cost) {
                        $diff_shipping = ($shipping_cost - $order->getShippingInclTax());
                        $order->setShippingAmount($order->getShippingAmount() + $diff_shipping);
                        $order->setBaseShippingAmount($order->getShippingAmount());
                        $order->setShippingInclTax($shipping_cost);
                        $order->setBaseShippingInclTax($order->getShippingInclTax());
                    }
                    // update Subtotal without shipping cost
                    $order->setSubtotalInclTax($order->getSubtotalInclTax() + ($diff - $diff_shipping));
                    $order->setBaseSubtotalInclTax($order->getSubtotalInclTax());
                    $order->setSubtotal($order->getSubtotal() + ($diff - $diff_shipping));
                    $order->setBaseSubtotal($order->getSubtotal());
                }
            }
            $order->save();
        }
        // generate invoice for order
        if ($order->canInvoice()) {
            $this->_model_order->toInvoice($order);
        }
        $carrier_name = $this->_carrier_name;
        if (is_null($carrier_name) || $carrier_name == 'None') {
            $carrier_name = $this->_carrier_method;
        }
        $order->setShippingDescription(
            $order->getShippingDescription().' [marketplace shipping method : '.$carrier_name.']'
        );
        $order->save();
        return $order;
    }

    /**
     * Create a order in lengow orders table
     *
     * @return boolean
     */
    protected function _createLengowOrder()
    {
        // Get all params to create order
        if (!is_null($this->_order_data->marketplace_order_date)) {
            $order_date = (string)$this->_order_data->marketplace_order_date;
        } else {
            $order_date = (string)$this->_order_data->imported_at;
        }
        $params = array(
            'store_id'            => (int)$this->_store_id,
            'marketplace_sku'     => $this->_marketplace_sku,
            'marketplace_name'    => strtolower((string)$this->_order_data->marketplace),
            'marketplace_label'   => (string)$this->_marketplace_label,
            'delivery_address_id' => (int)$this->_delivery_address_id,
            'order_lengow_state'  => $this->_order_state_lengow,
            'order_date'          => Mage::getModel('core/date')->date('Y-m-d H:i:s', strtotime($order_date)),
            'is_in_error'         => 1
        );
        if (isset($this->_order_data->comments) && is_array($this->_order_data->comments)) {
            $params['message'] = join(',', $this->_order_data->comments);
        } else {
            $params['message'] = (string)$this->_order_data->comments;
        }
        // Create lengow order
        $this->_model_order->createOrder($params);
        // Get lengow order id
        $this->_order_lengow_id = $this->_model_order->getLengowOrderId(
            $this->_marketplace_sku,
            $this->_delivery_address_id
        );
        if (!$this->_order_lengow_id) {
            return false;
        }
        return true;
    }

    /**
     * Save order line in lengow orders line table
     *
     * @param Mage_Sales_Model_Order $order Magento order
     * @param Mage_Sales_Model_Quote $quote Magento quote
     *
     * @return string
     */
    protected function _saveLengowOrderLine($order, $quote)
    {
        $order_line_saved = false;
        $lengow_products = $quote->getLengowProducts();
        foreach ($lengow_products as $product) {
            $order_line = Mage::getModel('lengow/import_orderline');
            $order_line->createOrderLine(array(
                'order_id'      => (int)$order->getId(),
                'order_line_id' => $product['order_line_id']
            ));
            $order_line_saved .= (!$order_line_saved ? $product['order_line_id'] : ' / '.$product['order_line_id']);
        }
        return $order_line_saved;
    }

}
