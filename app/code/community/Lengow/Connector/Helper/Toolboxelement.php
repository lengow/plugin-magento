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
 * @subpackage  Helper
 * @author      Team module <team-module@lengow.com>
 * @copyright   2021 Lengow SAS
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

/**
 * Helper toolboxelement
 */
class Lengow_Connector_Helper_Toolboxelement extends Mage_Core_Helper_Abstract
{
    /* Array data for toolbox content creation */
    const DATA_HEADER = 'header';
    const DATA_TITLE = 'title';
    const DATA_STATE = 'state';
    const DATA_MESSAGE = 'message';
    const DATA_SIMPLE = 'simple';
    const DATA_HELP = 'help';
    const DATA_HELP_LINK = 'help_link';
    const DATA_HELP_LABEL = 'help_label';

    /* Lengow cron jobs */
    const CRON_JOB_EXPORT = 'export_cron_lengow';
    const CRON_JOB_IMPORT = 'import_cron_lengow';

    /**
     * @var Lengow_Connector_Helper_Data Lengow helper instance
     */
    protected $_helper;

    /**
     * @var Lengow_Connector_Helper_Security Lengow security helper instance
     */
    protected $_securityHelper;

    /**
     * @var Lengow_Connector_Helper_Config Lengow config helper instance
     */
    protected $_configHelper;

    /**
     * @var Lengow_Connector_Helper_Import Lengow import helper instance
     */
    protected $_importHelper;

    /**
     * @var Lengow_Connector_Helper_Toolbox Lengow toolbox helper instance
     */
    protected $_toolboxHelper;

    /**
     * Construct
     */
    public function __construct()
    {
        $this->_helper = Mage::helper('lengow_connector');
        $this->_securityHelper = Mage::helper('lengow_connector/security');
        $this->_configHelper = Mage::helper('lengow_connector/config');
        $this->_importHelper = Mage::helper('lengow_connector/import');
        $this->_toolboxHelper = Mage::helper('lengow_connector/toolbox');
    }

    /**
     * Get array of requirements for toolbox
     *
     * @return string
     */
    public function getCheckList()
    {
        $checklistData = $this->_toolboxHelper->getData(Lengow_Connector_Helper_Toolbox::DATA_TYPE_CHECKLIST);
        $checklist = array(
            array(
                self::DATA_TITLE => $this->_helper->__('toolbox.screen.curl_message'),
                self::DATA_HELP => $this->_helper->__('toolbox.screen.curl_help'),
                self::DATA_HELP_LINK => $this->_helper->__('toolbox.screen.curl_help_link'),
                self::DATA_HELP_LABEL =>$this->_helper->__('toolbox.screen.curl_help_label'),
                self::DATA_STATE => $checklistData[Lengow_Connector_Helper_Toolbox::CHECKLIST_CURL_ACTIVATED],
            ),
            array(
                self::DATA_TITLE => $this->_helper->__('toolbox.screen.simple_xml_message'),
                self::DATA_HELP => $this->_helper->__('toolbox.screen.simple_xml_help'),
                self::DATA_HELP_LINK => $this->_helper->__('toolbox.screen.simple_xml_help_link'),
                self::DATA_HELP_LABEL => $this->_helper->__('toolbox.screen.simple_xml_help_label'),
                self::DATA_STATE => $checklistData[Lengow_Connector_Helper_Toolbox::CHECKLIST_SIMPLE_XML_ACTIVATED],
            ),
            array(
                self::DATA_TITLE => $this->_helper->__('toolbox.screen.json_php_message'),
                self::DATA_HELP => $this->_helper->__('toolbox.screen.json_php_help'),
                self::DATA_HELP_LINK => $this->_helper->__('toolbox.screen.json_php_help_link'),
                self::DATA_HELP_LABEL => $this->_helper->__('toolbox.screen.json_php_help_label'),
                self::DATA_STATE => $checklistData[Lengow_Connector_Helper_Toolbox::CHECKLIST_JSON_ACTIVATED],
            ),
            array(
                self::DATA_TITLE => $this->_helper->__('toolbox.screen.checksum_message'),
                self::DATA_HELP => $this->_helper->__('toolbox.screen.checksum_help'),
                self::DATA_STATE => $checklistData[Lengow_Connector_Helper_Toolbox::CHECKLIST_MD5_SUCCESS],
            ),
        );
        return $this->_getContent($checklist);
    }

    /**
     * Get all global information for toolbox
     *
     * @return string
     */
    public function getGlobalInformation()
    {
        $pluginData = $this->_toolboxHelper->getData(Lengow_Connector_Helper_Toolbox::DATA_TYPE_PLUGIN);
        $checklist = array(
            array(
                self::DATA_TITLE => $this->_helper->__('lengow_setting.global_magento_version_title'),
                self::DATA_MESSAGE => $pluginData[Lengow_Connector_Helper_Toolbox::PLUGIN_CMS_VERSION],
            ),
            array(
                self::DATA_TITLE => $this->_helper->__('lengow_setting.global_plugin_version_title'),
                self::DATA_MESSAGE => $pluginData[Lengow_Connector_Helper_Toolbox::PLUGIN_VERSION],
            ),
            array(
                self::DATA_TITLE => $this->_helper->__('toolbox.screen.php_version'),
                self::DATA_MESSAGE => $pluginData[Lengow_Connector_Helper_Toolbox::PLUGIN_PHP_VERSION],
            ),
            array(
                self::DATA_TITLE => $this->_helper->__('toolbox.screen.server_ip'),
                self::DATA_MESSAGE => $pluginData[Lengow_Connector_Helper_Toolbox::PLUGIN_SERVER_IP],
            ),
            array(
                self::DATA_TITLE => $this->_helper->__('toolbox.screen.authorized_ip_enable'),
                self::DATA_STATE => $pluginData[Lengow_Connector_Helper_Toolbox::PLUGIN_AUTHORIZED_IP_ENABLE],
            ),
            array(
                self::DATA_TITLE => $this->_helper->__('toolbox.screen.authorized_ip'),
                self::DATA_MESSAGE => implode(
                    ', ',
                    $pluginData[Lengow_Connector_Helper_Toolbox::PLUGIN_AUTHORIZED_IPS]
                ),
            ),
            array(
                self::DATA_TITLE => $this->_helper->__('toolbox.screen.export_on_a_file'),
                self::DATA_STATE => (bool) $this->_configHelper->get(
                    Lengow_Connector_Helper_Config::EXPORT_FILE_ENABLED
                ),
            ),
            array(
                self::DATA_TITLE => $this->_helper->__('toolbox.screen.export_cron_enable'),
                self::DATA_STATE => (bool) $this->_configHelper->get(
                    Lengow_Connector_Helper_Config::EXPORT_MAGENTO_CRON_ENABLED
                ),
            ),
            array(
                self::DATA_TITLE => $this->_helper->__('toolbox.screen.debug_disabled'),
                self::DATA_STATE => $pluginData[Lengow_Connector_Helper_Toolbox::PLUGIN_DEBUG_MODE_DISABLE],
            ),
            array(
                self::DATA_TITLE => $this->_helper->__('toolbox.screen.write_permission'),
                self::DATA_STATE => $pluginData[Lengow_Connector_Helper_Toolbox::PLUGIN_WRITE_PERMISSION],
            ),
            array(
                self::DATA_TITLE => $this->_helper->__('toolbox.screen.toolbox_url'),
                self::DATA_MESSAGE => $pluginData[Lengow_Connector_Helper_Toolbox::PLUGIN_TOOLBOX_URL],
            ),
        );

        return $this->_getContent($checklist);
    }

    /**
     * Get all import information for toolbox
     *
     * @return string
     */
    public function getImportInformation()
    {
        $synchronizationData = $this->_toolboxHelper->getData(
            Lengow_Connector_Helper_Toolbox::DATA_TYPE_SYNCHRONIZATION
        );
        $lastSynchronization = $synchronizationData[
            Lengow_Connector_Helper_Toolbox::SYNCHRONIZATION_LAST_SYNCHRONIZATION
        ];
        if ($lastSynchronization === 0) {
            $lastImportDate = $this->_helper->__('toolbox.screen.last_import_none');
            $lastImportType = $this->_helper->__('toolbox.screen.last_import_none');
        } else {
            $lastImportDate = $this->_helper->getDateInCorrectFormat($lastSynchronization, true);
            $lastSynchronizationType = $synchronizationData[
                Lengow_Connector_Helper_Toolbox::SYNCHRONIZATION_LAST_SYNCHRONIZATION_TYPE
            ];
            $lastImportType = $lastSynchronizationType === Lengow_Connector_Model_Import::TYPE_CRON
                ? $this->_helper->__('toolbox.screen.last_import_cron')
                : $this->_helper->__('toolbox.screen.last_import_manual');
        }
        if ($synchronizationData[Lengow_Connector_Helper_Toolbox::SYNCHRONIZATION_SYNCHRONIZATION_IN_PROGRESS]) {
            $importInProgress = $this->_helper->__(
                'toolbox.screen.rest_time_to_import',
                array('rest_time' => $this->_importHelper->restTimeToImport())
            );
        } else {
            $importInProgress = $this->_helper->__('toolbox.screen.no_import');
        }
        $checklist = array(
            array(
                self::DATA_TITLE => $this->_helper->__('toolbox.screen.global_token'),
                self::DATA_MESSAGE => $synchronizationData[Lengow_Connector_Helper_Toolbox::SYNCHRONIZATION_CMS_TOKEN],
            ),
            array(
                self::DATA_TITLE => $this->_helper->__('toolbox.screen.url_import'),
                self::DATA_MESSAGE => $synchronizationData[Lengow_Connector_Helper_Toolbox::SYNCHRONIZATION_CRON_URL],
            ),
            array(
                self::DATA_TITLE => $this->_helper->__('toolbox.screen.import_cron_enable'),
                self::DATA_STATE => (bool) $this->_configHelper->get(
                    Lengow_Connector_Helper_Config::SYNCHRONISATION_MAGENTO_CRON_ENABLED
                ),
            ),
            array(
                self::DATA_TITLE => $this->_helper->__('toolbox.screen.nb_order_imported'),
                self::DATA_MESSAGE => $synchronizationData[
                    Lengow_Connector_Helper_Toolbox::SYNCHRONIZATION_NUMBER_ORDERS_IMPORTED
                ],
            ),
            array(
                self::DATA_TITLE => $this->_helper->__('toolbox.screen.nb_order_to_be_sent'),
                self::DATA_MESSAGE => $synchronizationData[
                    Lengow_Connector_Helper_Toolbox::SYNCHRONIZATION_NUMBER_ORDERS_WAITING_SHIPMENT
                ],
            ),
            array(
                self::DATA_TITLE => $this->_helper->__('toolbox.screen.nb_order_with_error'),
                self::DATA_MESSAGE =>  $synchronizationData[
                    Lengow_Connector_Helper_Toolbox::SYNCHRONIZATION_NUMBER_ORDERS_IN_ERROR
                ],
            ),
            array(
                self::DATA_TITLE => $this->_helper->__('toolbox.screen.import_in_progress'),
                self::DATA_MESSAGE => $importInProgress,
            ),
            array(
                self::DATA_TITLE => $this->_helper->__('toolbox.screen.store_last_import'),
                self::DATA_MESSAGE => $lastImportDate,
            ),
            array(
                self::DATA_TITLE => $this->_helper->__('toolbox.screen.store_type_import'),
                self::DATA_MESSAGE => $lastImportType,
            ),
        );
        return $this->_getContent($checklist);
    }

    /**
     * Get all shop information for toolbox
     *
     * @return string
     */
    public function getExportInformation()
    {
        $content = '';
        $exportData = $this->_toolboxHelper->getData(Lengow_Connector_Helper_Toolbox::DATA_TYPE_SHOP);
        foreach ($exportData as $data) {
            if ($data[Lengow_Connector_Helper_Toolbox::SHOP_LAST_EXPORT] !== 0) {
                $lastExport = $this->_helper->getDateInCorrectFormat(
                    $data[Lengow_Connector_Helper_Toolbox::SHOP_LAST_EXPORT],
                    true
                );
            } else {
                $lastExport = $this->_helper->__('toolbox.screen.last_import_none');
            }
            $checklist = array(
                array(
                    self::DATA_HEADER => $data[Lengow_Connector_Helper_Toolbox::SHOP_NAME]
                        . ' (' . $data[Lengow_Connector_Helper_Toolbox::SHOP_ID] . ') '
                        . $data[Lengow_Connector_Helper_Toolbox::SHOP_DOMAIN_URL]
                ),
                array(
                    self::DATA_TITLE => $this->_helper->__('toolbox.screen.store_active'),
                    self::DATA_STATE => $data[Lengow_Connector_Helper_Toolbox::SHOP_ENABLED],
                ),
                array(
                    self::DATA_TITLE => $this->_helper->__('toolbox.screen.store_catalog_id'),
                    self::DATA_MESSAGE => implode (', ' , $data[Lengow_Connector_Helper_Toolbox::SHOP_CATALOG_IDS]),
                ),
                array(
                    self::DATA_TITLE => $this->_helper->__('toolbox.screen.store_product_total'),
                    self::DATA_MESSAGE => $data[Lengow_Connector_Helper_Toolbox::SHOP_NUMBER_PRODUCTS_AVAILABLE],
                ),
                array(
                    self::DATA_TITLE => $this->_helper->__('toolbox.screen.store_product_exported'),
                    self::DATA_MESSAGE => $data[Lengow_Connector_Helper_Toolbox::SHOP_NUMBER_PRODUCTS_EXPORTED],
                ),
                array(
                    self::DATA_TITLE => $this->_helper->__('toolbox.screen.store_export_token'),
                    self::DATA_MESSAGE => $data[Lengow_Connector_Helper_Toolbox::SHOP_TOKEN],
                ),
                array(
                    self::DATA_TITLE => $this->_helper->__('toolbox.screen.url_export'),
                    self::DATA_MESSAGE => $data[Lengow_Connector_Helper_Toolbox::SHOP_FEED_URL],
                ),
                array(
                    self::DATA_TITLE => $this->_helper->__('toolbox.screen.store_last_export'),
                    self::DATA_MESSAGE => $lastExport,
                ),
            );
            $content .= $this->_getContent($checklist);
        }
        return $content;
    }

    /**
     * Get all file information for toolbox
     *
     * @return string
     */
    public function getFileInformation()
    {
        $content = '';
        /** @var Mage_Core_Model_Store[] $stores */
        $stores = Mage::getResourceModel('core/store_collection');
        $sep = DIRECTORY_SEPARATOR;
        $lengowMediaPath = Mage::getBaseDir('media') . $sep . 'lengow';
        $lengowMediaUrl = Mage::getBaseUrl(Mage_Core_Model_Store::URL_TYPE_MEDIA) . 'lengow';
        foreach ($stores as $store) {
            $folderPath = $lengowMediaPath . $sep . $store->getCode() . $sep;
            $folderUrl = $lengowMediaUrl . $sep . $store->getCode() . $sep;
            $files = file_exists($folderPath) ? array_diff(scandir($folderPath), array('..', '.')) : array();
            $checklist = array();
            try {
                $checklist[] = array(
                    self::DATA_HEADER => $store->getName() . ' (' . $store->getId() . ') ' . $store->getBaseUrl()
                );
            } catch (\Exception $e) {
                $checklist[] = array(
                    self::DATA_HEADER => $store->getName() . ' (' . $store->getId() . ')'
                );
            }
            $checklist[] = array(
                self::DATA_TITLE => $this->_helper->__('toolbox.screen.folder_path'),
                self::DATA_MESSAGE => $folderPath,
            );
            if (!empty($files)) {
                $checklist[] = array(
                    self::DATA_SIMPLE => $this->_helper->__('toolbox.screen.file_list'),
                );
                foreach ($files as $file) {
                    $fileTimestamp = filectime($folderPath . $file);
                    $fileLink = '<a href="' . $folderUrl . $file . '" target="_blank">' . $file . '</a>';
                    $checklist[] = array(
                        self::DATA_TITLE => $fileLink,
                        self::DATA_MESSAGE => $this->_helper->getDateInCorrectFormat($fileTimestamp, true),
                    );
                }
            } else {
                $checklist[] = array(
                    self::DATA_SIMPLE => $this->_helper->__('toolbox.screen.no_file_exported'),
                );
            }
            $content .= $this->_getContent($checklist);
        }
        return $content;
    }

    /**
     * Get array of file information
     *
     * @param string $jobCode cron type (export or import)
     *
     * @return string
     */
    public function getCronInformation($jobCode)
    {
        $cronJobs = Mage::getModel('cron/schedule')->getCollection()->getData();
        $lengowCronJobs = array();
        foreach ($cronJobs as $cronJob) {
            if ($cronJob['job_code'] === $jobCode) {
                $lengowCronJobs[] = $cronJob;
            }
        }
        $lengowCronJobs = array_slice(array_reverse($lengowCronJobs), 0, 20);
        return $this->_getCronContent($lengowCronJobs);
    }

    /**
     * Get files checksum
     *
     * @return string
     */
    public function checkFileMd5()
    {
        $checklist = array();
        $checksumData = $this->_toolboxHelper->getData(Lengow_Connector_Helper_Toolbox::DATA_TYPE_CHECKSUM);
        $html = '<h3><i class="fa fa-commenting"></i> ' . $this->_helper->__('toolbox.screen.summary') . '</h3>';
        if ($checksumData[Lengow_Connector_Helper_Toolbox::CHECKSUM_AVAILABLE]) {
            $checklist[] = array(
                self::DATA_TITLE => $this->_helper->__(
                    'toolbox.screen.file_checked',
                    array('nb_file' =>  $checksumData[Lengow_Connector_Helper_Toolbox::CHECKSUM_NUMBER_FILES_CHECKED])
                ),
                self::DATA_STATE => true,
            );
            $checklist[] = array(
                self::DATA_TITLE => $this->_helper->__(
                    'toolbox.screen.file_modified',
                    array('nb_file' => $checksumData[Lengow_Connector_Helper_Toolbox::CHECKSUM_NUMBER_FILES_MODIFIED])
                ),
                self::DATA_STATE => $checksumData[Lengow_Connector_Helper_Toolbox::CHECKSUM_NUMBER_FILES_MODIFIED] === 0,
            );
            $checklist[] = array(
                self::DATA_TITLE => $this->_helper->__(
                    'toolbox.screen.file_deleted',
                    array('nb_file' => $checksumData[Lengow_Connector_Helper_Toolbox::CHECKSUM_NUMBER_FILES_DELETED])
                ),
                self::DATA_STATE => $checksumData[Lengow_Connector_Helper_Toolbox::CHECKSUM_NUMBER_FILES_DELETED] === 0,
            );
            $html .= $this->_getContent($checklist);
            if (!empty($checksumData[Lengow_Connector_Helper_Toolbox::CHECKSUM_FILE_MODIFIED])) {
                $fileModified = array();
                foreach ($checksumData[Lengow_Connector_Helper_Toolbox::CHECKSUM_FILE_MODIFIED] as $file) {
                    $fileModified[] = array(
                        self::DATA_TITLE => $file,
                        self::DATA_STATE => 0,
                    );
                }
                $html .= '<h3><i class="fa fa-list"></i> '
                    . $this->_helper->__('toolbox.screen.list_modified_file') . '</h3>';
                $html .= $this->_getContent($fileModified);
            }
            if (!empty($checksumData[Lengow_Connector_Helper_Toolbox::CHECKSUM_FILE_DELETED])) {
                $fileDeleted = array();
                foreach ($checksumData[Lengow_Connector_Helper_Toolbox::CHECKSUM_FILE_DELETED] as $file) {
                    $fileDeleted[] = array(
                        self::DATA_TITLE => $file,
                        self::DATA_STATE => 0,
                    );
                }
                $html .= '<h3><i class="fa fa-list"></i> '
                    . $this->_helper->__('toolbox.screen.list_deleted_file') . '</h3>';
                $html .= $this->_getContent($fileDeleted);
            }
        } else {
            $checklist[] = array(
                self::DATA_TITLE => $this->_helper->__('toolbox.screen.file_not_exists'),
                self::DATA_STATE => false,
            );
            $html .= $this->_getContent($checklist);
        }
        return $html;
    }

    /**
     * Get HTML Table content of checklist
     *
     * @param array $checklist all information for toolbox
     *
     * @return string|null
     */
    protected function _getContent($checklist = array())
    {
        if (empty($checklist)) {
            return null;
        }
        $out = '<table cellpadding="0" cellspacing="0">';
        foreach ($checklist as $check) {
            $out .= '<tr>';
            if (isset($check[self::DATA_HEADER])) {
                $out .= '<td colspan="2" align="center" style="border:0"><h4>'
                    . $check[self::DATA_HEADER] . '</h4></td>';
            } elseif (isset($check[self::DATA_SIMPLE])) {
                $out .= '<td colspan="2" align="center"><h5>' . $check[self::DATA_SIMPLE] . '</h5></td>';
            } else {
                $out .= '<td><b>' . $check[self::DATA_TITLE] . '</b></td>';
                if (isset($check[self::DATA_STATE])) {
                    if ($check[self::DATA_STATE]) {
                        $out .= '<td align="right"><i class="fa fa-check lengow-green"></td>';
                    } else {
                        $out .= '<td align="right"><i class="fa fa-times lengow-red"></td>';
                    }
                    if (!$check[self::DATA_STATE] && isset($check[self::DATA_HELP])) {
                        $out .= '<tr><td colspan="2"><p>' . $check[self::DATA_HELP];
                        if (array_key_exists(self::DATA_HELP_LINK, $check) && $check[self::DATA_HELP_LINK] !== '') {
                            $out .= '<br /><a target="_blank" href="'
                                . $check[self::DATA_HELP_LINK] . '">' . $check[self::DATA_HELP_LABEL] . '</a>';
                        }
                        $out .= '</p></td></tr>';
                    }
                } else {
                    $out .= '<td align="right">' . $check[self::DATA_MESSAGE] . '</td>';
                }
            }
            $out .= '</tr>';
        }
        $out .= '</table>';
        return $out;
    }

    /**
     * Get HTML Table content of cron job
     *
     * @param array $lengowCronJobs Lengow cron jobs
     *
     * @return string
     */
    protected function _getCronContent($lengowCronJobs = array())
    {
        $out = '<table cellpadding="0" cellspacing="0">';
        if (empty($lengowCronJobs)) {
            $out .= '<tr><td style="border:0">' . $this->_helper->__('toolbox.screen.no_cron_job_yet') . '</td></tr>';
        } else {
            $out .= '<tr>';
            $out .= '<th>' . $this->_helper->__('toolbox.screen.cron_status') . '</th>';
            $out .= '<th>' . $this->_helper->__('toolbox.screen.cron_message') . '</th>';
            $out .= '<th>' . $this->_helper->__('toolbox.screen.cron_scheduled_at') . '</th>';
            $out .= '<th>' . $this->_helper->__('toolbox.screen.cron_executed_at') . '</th>';
            $out .= '<th>' . $this->_helper->__('toolbox.screen.cron_finished_at') . '</th>';
            $out .= '</tr>';
            foreach ($lengowCronJobs as $lengowCronJob) {
                $out .= '<tr>';
                $out .= '<td>' . $lengowCronJob['status'] . '</td>';
                if ($lengowCronJob['messages'] != '') {
                    $out .= '<td><a class="lengow_tooltip" href="#">'
                        . $this->_helper->__('toolbox.screen.cron_see_message')
                        . '<span class="lengow_toolbox_message">'
                        . $lengowCronJob['messages'] . '</span></a></td>';
                } else {
                    $out .= '<td></td>';
                }
                $scheduledAt = $lengowCronJob['scheduled_at'] !== null
                    ? Mage::getModel('core/date')->date(
                        Lengow_Connector_Helper_Data::DATE_FULL,
                        strtotime($lengowCronJob['scheduled_at'])
                    )
                    : '';
                $out .= '<td>' . $scheduledAt . '</td>';
                $executedAt = $lengowCronJob['executed_at'] !== null
                    ? Mage::getModel('core/date')->date(
                        Lengow_Connector_Helper_Data::DATE_FULL,
                        strtotime($lengowCronJob['executed_at'])
                    )
                    : '';
                $out .= '<td>' . $executedAt . '</td>';
                $finishedAt = $lengowCronJob['finished_at'] !== null
                    ? Mage::getModel('core/date')->date(
                        Lengow_Connector_Helper_Data::DATE_FULL,
                        strtotime($lengowCronJob['finished_at'])
                    )
                    : '';
                $out .= '<td>' . $finishedAt . '</td>';
                $out .= '</tr>';
            }
        }
        $out .= '</table>';
        return $out;
    }
}
