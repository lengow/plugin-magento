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
     * @var string
     */
    protected $_order_state_marketplace;

    /**
     * @var string
     */
    protected $_order_state_lengow;


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
        $import_log = $this->_model_order->orderIsInError($this->_marketplace_sku, $this->_delivery_address_id, 'import');
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
        $order_id = $this->_model_order->getOrderIdFromLengowOrders(
            $this->_marketplace_sku,
            (string)$this->_marketplace->name,
            $this->_delivery_address_id
        );
        // update order state if already imported
        if ($order_id) {
            // TODO
            // $order_updated = $this->_checkAndUpdateOrder($order_id);
            // if ($order_updated && isset($order_updated['update'])) {
            //     return $this->_returnResult('update', $order_updated['order_lengow_id'], $order_id);
            // }
            // if (!$this->_is_reimported) {
            //     return false;
            // }
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
        // TODO
        // // if order is cancelled or new -> skip
        // if (!LengowImport::checkState($this->order_state_marketplace, $this->marketplace)) {
        //     LengowMain::log(
        //         'Import',
        //         LengowMain::setLogMessage('log.import.current_order_state_unavailable', array(
        //             'order_state_marketplace' => $this->order_state_marketplace,
        //             'marketplace_name'        => $this->marketplace->name
        //         )),
        //         $this->log_output,
        //         $this->marketplace_sku
        //     );
        //     return false;
        // }
        // // get a record in the lengow order table
        // $this->id_order_lengow = LengowOrder::getIdFromLengowOrders($this->marketplace_sku, $this->delivery_address_id);
        // if (!$this->id_order_lengow) {
        //     // created a record in the lengow order table
        //     if (!$this->createLengowOrder()) {
        //         LengowMain::log(
        //             'Import',
        //             LengowMain::setLogMessage('log.import.lengow_order_not_saved'),
        //             $this->log_output,
        //             $this->marketplace_sku
        //         );
        //         return false;
        //     } else {
        //         LengowMain::log(
        //             'Import',
        //             LengowMain::setLogMessage('log.import.lengow_order_saved'),
        //             $this->log_output,
        //             $this->marketplace_sku
        //         );
        //     }
        // }
        // // checks if the required order data is present
        // if (!$this->checkOrderData()) {
        //     return $this->returnResult('error', $this->id_order_lengow);
        // }
        // // get order amount and load processing fees and shipping cost
        // $this->order_amount = $this->getOrderAmount();
        // // load tracking data
        // $this->loadTrackingData();
        // // get customer name
        // $customer_name = $this->getCustomerName();
        // // update Lengow order with new informations
        // LengowOrder::updateOrderLengow(
        //     $this->id_order_lengow,
        //     array(
        //         'total_paid'            => $this->order_amount,
        //         'order_item'            => $this->order_items,
        //         'customer_name'         => pSQL($customer_name),
        //         'carrier'               => pSQL($this->carrier_name),
        //         'method'                => pSQL($this->carrier_method),
        //         'tracking'              => pSQL($this->tracking_number),
        //         'sent_marketplace'      => (int)$this->shipped_by_mp,
        //         'delivery_country_iso'  => pSQL((string)$this->package_data->delivery->common_country_iso_a2),
        //         'order_lengow_state'    => pSQL($this->order_state_lengow)
        //     )
        // );
        // // try to import order
        // try {
        //     // check if the order is shipped by marketplace
        //     if ($this->shipped_by_mp) {
        //         LengowMain::log(
        //             'Import',
        //             LengowMain::setLogMessage('log.import.order_shipped_by_marketplace', array(
        //                 'markeplace_name' => $this->marketplace->name
        //             )),
        //             $this->log_output,
        //             $this->marketplace_sku
        //         );
        //         if (!LengowConfiguration::getGlobalValue('LENGOW_IMPORT_SHIP_MP_ENABLED')) {
        //             LengowOrder::updateOrderLengow(
        //                 $this->id_order_lengow,
        //                 array(
        //                     'order_process_state'   => 2,
        //                     'extra'                 => pSQL(Tools::jsonEncode($this->order_data))
        //                 )
        //             );
        //             return false;
        //         }
        //     }
        //     // get products
        //     $products = $this->getProducts();
        //     // create a cart with customer, billing address and shipping address
        //     $cart_data = $this->getCartData();
        //     if (_PS_VERSION_ < '1.5') {
        //         $cart = new LengowCart($this->context->cart->id);
        //     } else {
        //         $cart = new LengowCart();
        //     }
        //     $cart->assign($cart_data);
        //     $cart->validateLengow();
        //     $cart->force_product = $this->force_product;
        //     // add products to cart
        //     $cart->addProducts($products, $this->force_product);
        //     // add cart to context
        //     $this->context->cart = $cart;
        //     // create payment
        //     $order_list = $this->createAndValidatePayment($cart, $products);
        //     // if no order in list
        //     if (empty($order_list)) {
        //         throw new LengowException(LengowMain::setLogMessage('lengow_log.exception.order_list_is_empty'));
        //     } else {
        //         foreach ($order_list as $order) {
        //             // add order comment from marketplace to prestashop order
        //             if (_PS_VERSION_ >= '1.5') {
        //                 $this->addCommentOrder((int)$order->id, $this->order_data->comments);
        //             }
        //             $success_message = LengowMain::setLogMessage('log.import.order_successfully_imported', array(
        //                 'order_id' => $order->id
        //             ));
        //             $success = LengowOrder::updateOrderLengow(
        //                 $this->id_order_lengow,
        //                 array(
        //                     'id_order'              => (int)$order->id,
        //                     'order_process_state'   => LengowOrder::getOrderProcessState($this->order_state_lengow),
        //                     'extra'                 => pSQL(Tools::jsonEncode($this->order_data)),
        //                     'order_lengow_state'    => pSQL($this->order_state_lengow),
        //                     'is_reimported'         => 0
        //                 )
        //             );
        //             if (!$success) {
        //                 LengowMain::log(
        //                     'Import',
        //                     LengowMain::setLogMessage('log.import.lengow_order_not_updated'),
        //                     $this->log_output,
        //                     $this->marketplace_sku
        //                 );
        //             } else {
        //                 LengowMain::log(
        //                     'Import',
        //                     LengowMain::setLogMessage('log.import.lengow_order_updated'),
        //                     $this->log_output,
        //                     $this->marketplace_sku
        //                 );
        //             }
        //             // Save order line id in lengow_order_line table
        //             $order_line_saved = $this->saveLengowOrderLine($order, $products);
        //             LengowMain::log(
        //                 'Import',
        //                 LengowMain::setLogMessage('log.import.lengow_order_line_saved', array(
        //                     'order_line_saved' => $order_line_saved
        //                 )),
        //                 $this->log_output,
        //                 $this->marketplace_sku
        //             );
        //             // if more than one order (different warehouses)
        //             LengowMain::log('Import', $success_message, $this->log_output, $this->marketplace_sku);
        //         }
        //         // ensure carrier compatibility with SoColissimo & Mondial Relay
        //         $this->checkCarrierCompatibility($order);
        //     }
        //     if ($this->is_reimported
        //         || ($this->shipped_by_mp && !LengowConfiguration::getGlobalValue('LENGOW_IMPORT_STOCK_SHIP_MP'))
        //     ) {
        //         if ($this->is_reimported) {
        //             $log_message = LengowMain::setLogMessage('log.import.quantity_back_reimported_order');
        //         } else {
        //             $log_message = LengowMain::setLogMessage('log.import.quantity_back_shipped_by_marketplace');
        //         }
        //         LengowMain::log('Import', $log_message, $this->log_output, $this->marketplace_sku);
        //         $this->addQuantityBack($products);
        //     }
        // } catch (LengowException $e) {
        //     $error_message = $e->getMessage();
        // } catch (Exception $e) {
        //     $error_message = '[Prestashop error] "'.$e->getMessage().'" '.$e->getFile().' | '.$e->getLine();
        // }
        // if (isset($error_message)) {
        //     if (isset($cart)) {
        //         $cart->delete();
        //     }
        //     LengowOrder::addOrderLog($this->id_order_lengow, $error_message, 'import');
        //     $decoded_message = LengowMain::decodeLogMessage($error_message, 'en');
        //     LengowMain::log(
        //         'Import',
        //         LengowMain::setLogMessage('log.import.order_import_failed', array(
        //             'decoded_message' => $decoded_message
        //         )),
        //         $this->log_output,
        //         $this->marketplace_sku
        //     );
        //     LengowOrder::updateOrderLengow(
        //         $this->id_order_lengow,
        //         array(
        //             'extra'                 => pSQL(Tools::jsonEncode($this->order_data)),
        //             'order_lengow_state'    => pSQL($this->order_state_lengow),
        //             'is_reimported'         => 0
        //         )
        //     );
        //     return $this->returnResult('error', $this->id_order_lengow);
        // }
        // return $this->returnResult('new', $this->id_order_lengow, (int)$order->id);
    }

    /**
     * Return an array of result for each order
     *
     * @param string    $type_result        Type of result (new, update, error)
     * @param integer   $order_lengow_id    ID of the lengow order record
     * @param integer   $order_id           Order ID Prestashop
     *
     * @return array
     */
    protected function _returnResult($type_result, $order_lengow_id, $order_id = null)
    {
        $result = array(
            'order_id'              => $order_id,
            'id_order_lengow'       => $order_lengow_id,
            'marketplace_sku'       => $this->_marketplace_sku,
            'marketplace_name'      => (string)$this->marketplace->name,
            'lengow_state'          => $this->_order_state_lengow,
            'order_new'             => ($type_result == 'new' ? true : false),
            'order_update'          => ($type_result == 'update' ? true : false),
            'order_error'           => ($type_result == 'error' ? true : false)
        );
        return $result;
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
                $line_id = $this->_model_order->getIdFromLengowDeliveryAddress(
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
}
