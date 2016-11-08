<?php

/**
 *
 * @category    Lengow
 * @package     Lengow_Connector
 * @author      Team module <team-module@lengow.com>
 * @copyright   2016 Lengow SAS
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
class Lengow_Connector_Helper_Toolbox extends Mage_Core_Helper_Abstract
{
    /**
     * @var Main helper
     */
    protected $_helper;

    /**
     * @var Security helper
     */
    protected $_security_helper;

    /**
     * @var Configuration helper
     */
    protected $_config_helper;

    /**
     * @var Import helper
     */
    protected $_import_helper;

    /**
     * Construct
     */
    public function __construct()
    {
        $this->_helper = Mage::helper('lengow_connector');
        $this->_security_helper = Mage::helper('lengow_connector/security');
        $this->_config_helper = Mage::helper('lengow_connector/config');
        $this->_import_helper = Mage::helper('lengow_connector/import');
    }

    /**
     * Get array of plugin informations
     *
     * @return string
     */
    public function getPluginInformations()
    {
        $checklist = array();
        $checklist[] = array(
            'title'   => $this->_helper->__('lengow_setting.global_magento_version_title'),
            'message' => Mage::getVersion(),
        );
        $checklist[] = array(
            'title'   => $this->_helper->__('lengow_setting.global_plugin_version_title'),
            'message' => $this->_security_helper->getPluginVersion(),
        );
        $checklist[] = array(
            'title'   => $this->_helper->__('toolbox.screen.server_ip'),
            'message' => $_SERVER["SERVER_ADDR"],
        );
        $checklist[] = array(
            'title'   => $this->_helper->__('toolbox.screen.authorized_ip'),
            'message' => $this->_config_helper->get('authorized_ip'),
        );
        $checklist[] = array(
            'title'   => $this->_helper->__('toolbox.screen.legacy_enable'),
            'state'   => (bool)$this->_config_helper->get('legacy_enable'),
        );
        $checklist[] = array(
            'title'   => $this->_helper->__('toolbox.screen.export_on_a_file'),
            'state'   => (bool)$this->_config_helper->get('file_enable'),
        );
        $checklist[] = array(
            'title'   => $this->_helper->__('toolbox.screen.export_cron_enable'),
            'state'   => (bool)$this->_config_helper->get('export_cron_enable'),
        );
        $checklist[] = array(
            'title'   => $this->_helper->__('toolbox.screen.pre_production_enable'),
            'state'   => !(bool)$this->_config_helper->get('preprod_mode_enable'),
        );
        $file_path = Mage::getBaseDir('media').DS.'lengow'.DS.'test.txt';
        $file = fopen($file_path, "w+");
        if ($file == false) {
            $state = false;
        } else {
            $state = true;
            unlink($file_path);
        }
        $checklist[] = array(
            'title'   => $this->_helper->__('toolbox.screen.write_permission'),
            'state'   => $state
        );
        return $this->_getContent($checklist);
    }

    /**
     * Get array of import information
     *
     * @return string
     */
    public function getImportInformations()
    {
        $checklist = array();
        $checklist[] = array(
            'title'   => $this->_helper->__('toolbox.screen.global_token'),
            'message' => $this->_config_helper->getToken(),
        );
        $checklist[] = array(
            'title'   => $this->_helper->__('toolbox.screen.url_import'),
            'message' => Mage::getUrl('lengow/cron'),
        );
        $checklist[] = array(
            'title'   => $this->_helper->__('toolbox.screen.import_cron_enable'),
            'state'   => (bool)$this->_config_helper->get('import_cron_enable'),
        );
        $order = Mage::getModel('lengow/import_order');
        $nb_order_imported = $order->countOrderImportedByLengow();
        $order_with_error = $order->countOrderWithError();
        $order_to_be_sent = $order->countOrderToBeSent();
        $checklist[] = array(
            'title'   => $this->_helper->__('toolbox.screen.nb_order_imported'),
            'message' => $nb_order_imported,
        );
        $checklist[] = array(
            'title'   => $this->_helper->__('toolbox.screen.nb_order_to_be_sent'),
            'message' => $order_to_be_sent,
        );
        $checklist[] = array(
            'title'   => $this->_helper->__('toolbox.screen.nb_order_with_error'),
            'message' => $order_with_error,
        );
        $last_import =  $this->_import_helper->getLastImport();
        $last_import_date = $last_import['timestamp'] == 'none'
            ? $this->_helper->__('toolbox.screen.last_import_none')
            : $this->_helper->getDateInCorrectFormat($last_import['timestamp'], true);
        if ($last_import['type'] == 'none') {
            $last_import_type = $this->_helper->__('toolbox.screen.last_import_none');
        } elseif ($last_import['type'] == 'cron') {
            $last_import_type = $this->_helper->__('toolbox.screen.last_import_cron');
        } else {
            $last_import_type = $this->_helper->__('toolbox.screen.last_import_manual');
        }
        if ($this->_import_helper->importIsInProcess()) {
            $import_in_progress = $this->_helper->__(
                'toolbox.screen.rest_time_to_import',
                array('rest_time' => $this->_import_helper->restTimeToImport())
            );
        } else {
            $import_in_progress = $this->_helper->__('toolbox.screen.no_import');
        }
        $checklist[] = array(
            'title'   => $this->_helper->__('toolbox.screen.import_in_progress'),
            'message' => $import_in_progress,
        );
        $checklist[] = array(
            'title'   => $this->_helper->__('toolbox.screen.store_last_import'),
            'message' => $last_import_date,
        );
        $checklist[] = array(
            'title'   => $this->_helper->__('toolbox.screen.store_type_import'),
            'message' => $last_import_type,
        );
        return $this->_getContent($checklist);
    }

    /**
     * Get array of export informations
     *
     * @param $store Magento store
     *
     * @return string
     */
    public function getExportInformations($store)
    {
        $export = Mage::getModel('lengow/export', array("store_id" => $store->getId()));
        $checklist = array();
        $checklist[] = array(
            'header'  => $store->getName().' ('.$store->getId().') '.$store->getBaseUrl(),
        );
        $checklist[] = array(
            'title'   => $this->_helper->__('toolbox.screen.store_active'),
            'state'   => (bool)$this->_config_helper->get('store_enable', $store->getId()),
        );
        $checklist[] = array(
            'title'   => $this->_helper->__('toolbox.screen.store_product_total'),
            'message' => $export->getTotalProduct(),
        );
        $checklist[] = array(
            'title'   => $this->_helper->__('toolbox.screen.store_product_exported'),
            'message' => $export->getTotalExportedProduct(),
        );
        $checklist[] = array(
            'title'   => $this->_helper->__('toolbox.screen.store_export_token'),
            'message' => $this->_config_helper->getToken($store->getId()),
        );
        $checklist[] = array(
            'title'   => $this->_helper->__('toolbox.screen.url_export'),
            'message' => $export->getExportUrl(),
        );
        $last_export_date = $this->_config_helper->get('last_export', $store->getId());
        $checklist[] = array(
            'title'   => $this->_helper->__('toolbox.screen.store_last_export'),
            'message' => $this->_helper->getDateInCorrectFormat($last_export_date, true),
        );
        return $this->_getContent($checklist);
    }

    /**
     * Get array of file informations
     *
     * @param $store Magento store
     *
     * @return string
     */
    public function getFileInformations($store)
    {
        $folder_path = Mage::getBaseDir('media').DS.'lengow'.DS.$store->getCode().DS;
        $folder_url = Mage::getBaseUrl(Mage_Core_Model_Store::URL_TYPE_MEDIA).'lengow'.DS.$store->getCode().DS;
        $files = @array_diff(scandir($folder_path), array('..', '.'));
        $checklist = array();
        $checklist[] = array(
            'header'  => $store->getName().' ('.$store->getId().') '.$store->getBaseUrl(),
        );
        $checklist[] = array(
            'title'   => $this->_helper->__('toolbox.screen.folder_path'),
            'message' => $folder_path,
        );
        if (count($files) > 0) {
            $checklist[] = array(
                'simple' => $this->_helper->__('toolbox.screen.file_list'),
            );
            foreach ($files as $file) {
                $file_timestamp = filectime($folder_path.$file);
                $file_link = '<a href="'.$folder_url.$file.'" target="_blank">'.$file.'</a>';
                $checklist[] = array(
                    'title'   => $file_link,
                    'message' => $this->_helper->getDateInCorrectFormat($file_timestamp, true),
                );
            }
        } else {
            $checklist[] = array(
                'simple' => $this->_helper->__('toolbox.screen.no_file_exported'),
            );
        }
        return $this->_getContent($checklist);
    }

    /**
     * Get array of file informations
     *
     * @param string $type type of action (export or import)
     *
     * @return string
     */
    public function getCronInformation($type)
    {
        $job_code = $type == 'import' ? 'import_cron_lengow' : 'export_cron_lengow';
        $cron_jobs = Mage::getModel('cron/schedule')->getCollection()->getData();
        $lengow_cron_jobs = array();
        foreach ($cron_jobs as $cron_job) {
            if ($cron_job['job_code'] == $job_code) {
                $lengow_cron_jobs[] = $cron_job;
            }
        }
        $lengow_cron_jobs = array_slice(array_reverse($lengow_cron_jobs), 0, 20);
        return $this->_getCronContent($lengow_cron_jobs);
    }

    /**
     * Get files checksum
     *
     * @return string
     */
    public function checkFileMd5()
    {
        $checklist = array();
        $file_name = Mage::getModuleDir('etc', 'Lengow_Connector').DS.'checkmd5.csv';
        $html = '<h3><i class="fa fa-commenting"></i> '.$this->_helper->__('toolbox.screen.summary').'</h3>';
        $file_counter = 0;
        if (file_exists($file_name)) {
            $file_errors = array();
            $file_deletes = array();
            if (($file = fopen($file_name, "r")) !== false) {
                while (($data = fgetcsv($file, 1000, "|")) !== false) {
                    $file_counter++;
                    $file_path = Mage::getBaseDir().$data[0];
                    if (file_exists($file_path)) {
                        $file_md5 = md5_file($file_path);
                        if ($file_md5 !== $data[1]) {
                            $file_errors[] = array(
                                'title' => $file_path,
                                'state' => false
                            );
                        }
                    } else {
                        $file_deletes[] = array(
                            'title' => $file_path,
                            'state' => false
                        );
                    }
                }
                fclose($file);
            }
            $checklist[] = array(
                'title' => $this->_helper->__(
                    'toolbox.screen.file_checked',
                    array('nb_file' => $file_counter)
                ),
                'state' => true
            );
            $checklist[] = array(
                'title' => $this->_helper->__(
                    'toolbox.screen.file_modified',
                    array('nb_file' => count($file_errors))
                ),
                'state' => (count($file_errors) > 0 ? false : true)
            );
            $checklist[] = array(
                'title' => $this->_helper->__(
                    'toolbox.screen.file_deleted',
                    array('nb_file' => count($file_deletes))
                ),
                'state' => (count($file_deletes) > 0 ? false : true)
            );
            $html.= $this->_getContent($checklist);
            if (count($file_errors) > 0) {
                $html.= '<h3><i class="fa fa-list"></i> '
                    .$this->_helper->__('toolbox.screen.list_modified_file').'</h3>';
                $html.= $this->_getContent($file_errors);
            }
            if (count($file_deletes) > 0) {
                $html.= '<h3><i class="fa fa-list"></i> '
                    .$this->_helper->__('toolbox.screen.list_deleted_file').'</h3>';
                $html.= $this->_getContent($file_deletes);
            }
        } else {
            $checklist[] = array(
                'title' => $this->_helper->__('toolbox.screen.file_not_exists'),
                'state' => false
            );
            $html.= $this->_getContent($checklist);
        }
        return $html;
    }

    /**
     * Get HTML Table content of checklist
     *
     * @param array $checklist
     *
     * @return string
     */
    protected function _getContent($checklist = array())
    {
        if (empty($checklist)) {
            return null;
        }
        $out = '<table cellpadding="0" cellspacing="0">';
        foreach ($checklist as $check) {
            $out.= '<tr>';
            if (isset($check['header'])) {
                $out .= '<td colspan="2" align="center" style="border:0"><h4>'.$check['header'].'</h4></td>';
            } elseif (isset($check['simple'])) {
                $out.= '<td colspan="2" align="center"><h5>'.$check['simple'].'</h5></td>';
            } else {
                $out.= '<td><b>'.$check['title'].'</b></td>';
                if (isset($check['state'])) {
                    if ($check['state']) {
                        $out.= '<td align="right"><i class="fa fa-check lengow-green"></td>';
                    } else {
                        $out.= '<td align="right"><i class="fa fa-times lengow-red"></td>';
                    }
                } else {
                    $out.= '<td align="right">'.$check['message'].'</td>';
                }
            }
            $out.= '</tr>';
        }
        $out.= '</table>';
        return $out;
    }

    /**
     * Get HTML Table content of cron job
     *
     * @param array $lengow_cron_jobs
     *
     * @return string
     */
    protected function _getCronContent($lengow_cron_jobs = array())
    {
        $out = '<table cellpadding="0" cellspacing="0">';
        if (count($lengow_cron_jobs) == 0) {
            $out.= '<tr><td style="border:0">'.$this->_helper->__('toolbox.screen.no_cron_job_yet').'</td></tr>';
        } else {
            $out.= '<tr>';
            $out.= '<th>'.$this->_helper->__('toolbox.screen.cron_status').'</th>';
            $out.= '<th>'.$this->_helper->__('toolbox.screen.cron_message').'</th>';
            $out.= '<th>'.$this->_helper->__('toolbox.screen.cron_scheduled_at').'</th>';
            $out.= '<th>'.$this->_helper->__('toolbox.screen.cron_executed_at').'</th>';
            $out.= '<th>'.$this->_helper->__('toolbox.screen.cron_finished_at').'</th>';
            $out.= '</tr>';
            foreach ($lengow_cron_jobs as $lengow_cron_job) {
                $out.= '<tr>';
                $out.= '<td>'.$lengow_cron_job['status'].'</td>';
                if ($lengow_cron_job['messages'] != '') {
                    $out.= '<td><a class="lengow_tooltip" href="#">'
                        .$this->_helper->__('toolbox.screen.cron_see_message')
                        .'<span class="lengow_toolbox_message">'
                        .$lengow_cron_job['messages'].'</span></a></td>';
                } else {
                    $out.= '<td></td>';
                }
                $scheduled_at = !is_null($lengow_cron_job['scheduled_at'])
                    ? Mage::getModel('core/date')->date('Y-m-d H:i:s', strtotime($lengow_cron_job['scheduled_at']))
                    : '';
                $out.= '<td>'.$scheduled_at.'</td>';
                $executed_at = !is_null($lengow_cron_job['executed_at'])
                    ? Mage::getModel('core/date')->date('Y-m-d H:i:s', strtotime($lengow_cron_job['executed_at']))
                    : '';
                $out.= '<td>'.$executed_at.'</td>';
                $finished_at = !is_null($lengow_cron_job['finished_at'])
                    ? Mage::getModel('core/date')->date('Y-m-d H:i:s', strtotime($lengow_cron_job['finished_at']))
                    : '';
                $out.= '<td>'.$finished_at.'</td>';
                $out.= '</tr>';
            }
        }
        $out.= '</table>';
        return $out;
    }
}
