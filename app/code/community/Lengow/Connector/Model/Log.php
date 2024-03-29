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
 * Model log
 */
class Lengow_Connector_Model_Log extends Mage_Core_Model_Abstract
{
    /**
     * @var string Lengow log table name
     */
    const TABLE_LOG = 'lengow_log';

    /* Action fields */
    const FIELD_ID = 'id';
    const FIELD_DATE = 'date';
    const FIELD_MESSAGE = 'message';

    /* Log params for export */
    const LOG_DATE = 'date';
    const LOG_LINK = 'link';

    /**
     * @var integer life of log files in days
     */
    const LOG_LIFE = 20;

    /**
     * @var array $_fieldList field list for the table lengow_order_line
     * required => Required fields when creating registration
     * update   => Fields allowed when updating registration
     */
    protected $_fieldList = array(
        self::FIELD_MESSAGE => array(
            Lengow_Connector_Helper_Data::FIELD_REQUIRED => true,
            Lengow_Connector_Helper_Data::FIELD_CAN_BE_UPDATED => false,
        ),
    );

    /**
     * Constructor
     */
    protected function _construct()
    {
        parent::_construct();
        $this->_init('lengow/log');
    }

    /**
     * Create Lengow log
     *
     * @param array $params log parameters
     *
     * @return Lengow_Connector_Model_Log|false
     */
    public function createLog($params = array())
    {
        foreach ($this->_fieldList as $key => $value) {
            if (!array_key_exists($key, $params) && $value[Lengow_Connector_Helper_Data::FIELD_REQUIRED]) {
                return false;
            }
        }
        foreach ($params as $key => $value) {
            $this->setData($key, $value);
        }
        $this->setData(self::FIELD_DATE, Mage::getModel('core/date')->gmtDate(Lengow_Connector_Helper_Data::DATE_FULL));
        try {
            return $this->save();
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Find logs by date
     *
     * @param string $date log date
     *
     * @return array
     */
    public function getLogsByDate($date)
    {
        $collection = $this->getCollection()
            ->addFieldToFilter(
                self::FIELD_DATE,
                array(
                    'from' => date(Lengow_Connector_Helper_Data::DATE_DAY . ' 00:00:00', strtotime($date)),
                    'to' => date(Lengow_Connector_Helper_Data::DATE_DAY . ' 23:59:59', strtotime($date)),
                    'datetime' => true,
                )
            );
        return $collection->getData();
    }

    /**
     * Check if log date is available
     *
     * @param string $date log date
     *
     * @return boolean
     */
    public function logDateIsAvailable($date)
    {
        $resource = Mage::getSingleton('core/resource');
        $write = $resource->getConnection('core_write');
        $select = $write->select()
            ->from($resource->getTableName('lengow/log'), 'COUNT(*)')
            ->where('date >=?', date(Lengow_Connector_Helper_Data::DATE_DAY . ' 00:00:00', strtotime($date)))
            ->where('date <=?', date(Lengow_Connector_Helper_Data::DATE_DAY . ' 23:59:59', strtotime($date)));
        $result = (int) $write->fetchOne($select);
        return $result > 0;
    }

    /**
     * Get all available log dates
     *
     * @return array
     */
    public function getAvailableLogDates()
    {
        $logDates = array();
        for ($i = 0; $i <= self::LOG_LIFE; $i++) {
            $date = new DateTime();
            $logDate = $date->modify('-' . $i . ' day')->format(Lengow_Connector_Helper_Data::DATE_DAY);
            if ($this->logDateIsAvailable($logDate)) {
                $logDates[] = $logDate;
            }
        }
        return $logDates;
    }

    /**
     * Download log file individually or globally
     *
     * @param string|null $date date for a specific log file
     */
    public function download($date = null)
    {
        $contents = '';
        if ($date && preg_match('/^(\d{4}-\d{2}-\d{2})$/', $date)) {
            $fileName = $date . '.txt';
            $logs = $this->getLogsByDate($date);
            foreach ($logs as $log) {
                $contents .= $log[self::FIELD_DATE] . ' - ' . $log[self::FIELD_MESSAGE] . "\r\n";
            }
        } else {
            $fileName = 'logs.txt';
            $logDates = array_reverse($this->getAvailableLogDates());
            foreach ($logDates as $logDate) {
                $logs = $this->getLogsByDate($logDate);
                foreach ($logs as $log) {
                    $contents .= $log[self::FIELD_DATE] . ' - ' . $log[self::FIELD_MESSAGE] . "\r\n";
                }
            }
        }
        header('Content-type: text/plain');
        header('Content-Disposition: attachment; filename="' . $fileName . '"');
        echo $contents;
    }
}
