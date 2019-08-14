<?php

require_once(_PS_MODULE_DIR_ . 'dpdconnect' . DIRECTORY_SEPARATOR . 'dpdconnect.php');

use DpdConnect\classes\JobRepo;
use DpdConnect\classes\enums\JobStatus;
use DpdConnect\classes\Database\LabelRepo;

class AdminDpdJobsController extends ModuleAdminController
{
    public function __construct()
    {
        $this->table = 'dpd_jobs';
        $this->bootstrap = true;
        $this->context = Context::getContext();
        $this->_defaultOrderWay = 'DESC';

        parent::__construct();

        $jobId = Tools::getValue('job_id');
        $batchId = Tools::getValue('batch_id');

        if ($jobId) {
            return $this->jobView($jobId);
        }

        if ($batchId) {
            return $this->jobListByBatch($batchId);
        }

        return $this->jobList();
    }

    public function jobList()
    {
        $this->addRowAction('details');
        $this->fields_list = [
            'id_dpd_jobs' => [
                'title' => $this->l('ID'),
                'width' => 'auto',
                'remove_onclick' => true,
            ],
            'created_at' => [
                'title' => $this->l('Created at'),
                'width' => 'auto',
                'remove_onclick' => true,
            ],
            'batch_id' => [
                'title' => $this->l('Batch ID'),
                'width' => 'auto',
                'havingFilter' => true,
                'remove_onclick' => true,
            ],
            'order_id' => [
                'title' => $this->l('Order ID'),
                'width' => 'auto',
                'remove_onclick' => true,
            ],
            'status' => [
                'title' => $this->l('Status'),
                'width' => 'auto',
                'callback' => 'renderStatus',
                'remove_onclick' => true,
            ],
            'type' => [
                'title' => $this->l('Type'),
                'width' => 'auto',
                'callback' => 'renderType',
                'remove_onclick' => true,
            ],
            'error' => [
                'title' => $this->l('Error'),
                'width' => 'auto',
                'callback' => 'renderSimpleError',
                'remove_onclick' => true,
            ],
            'state_message' => [
                'title' => $this->l('Message'),
                'width' => 'auto',
                'remove_onclick' => true,
            ],
        ];
    }

    public function jobListByBatch($batchId)
    {
        $this->toolbar_title = sprintf('Jobs for batch %s', $batchId);
        $this->list_simple_header = true;
        $this->processResetFilters($list_id = null);
        $this->_filterHaving = sprintf('batch_id = %s', $batchId);
        $this->addRowAction('details');
        $this->fields_list = [
            'id_dpd_jobs' => [
                'title' => $this->l('ID'),
                'width' => 'auto',
                'remove_onclick' => true,
            ],
            'created_at' => [
                'title' => $this->l('Created at'),
                'width' => 'auto',
                'remove_onclick' => true,
            ],
            'batch_id' => [
                'title' => $this->l('Batch ID'),
                'width' => 'auto',
                'havingFilter' => true,
                'remove_onclick' => true,
            ],
            'order_id' => [
                'title' => $this->l('Order ID'),
                'width' => 'auto',
                'remove_onclick' => true,
            ],
            'status' => [
                'title' => $this->l('Status'),
                'width' => 'auto',
                'callback' => 'renderStatus',
                'remove_onclick' => true,
            ],
            'type' => [
                'title' => $this->l('Type'),
                'width' => 'auto',
                'callback' => 'renderType',
                'remove_onclick' => true,
            ],
            'error' => [
                'title' => $this->l('Error'),
                'width' => 'auto',
                'callback' => 'renderSimpleError',
                'remove_onclick' => true,
            ],
            'state_message' => [
                'title' => $this->l('Message'),
                'width' => 'auto',
                'remove_onclick' => true,
            ],
        ];
    }

    public function jobView($jobId)
    {
        $this->toolbar_title = sprintf('Job %s', $jobId);
        $this->list_simple_header = true;
        $this->processResetFilters($list_id = null);
        $this->_filterHaving = sprintf('id_dpd_jobs = %s', $jobId);
        $this->fields_list = [
            'id_dpd_jobs' => [
                'title' => $this->l('ID'),
                'width' => 'auto',
                'remove_onclick' => true,
            ],
            'created_at' => [
                'title' => $this->l('Created at'),
                'width' => 'auto',
                'remove_onclick' => true,
            ],
            'batch_id' => [
                'title' => $this->l('Batch ID'),
                'width' => 'auto',
                'havingFilter' => true,
                'remove_onclick' => true,
            ],
            'order_id' => [
                'title' => $this->l('Order ID'),
                'width' => 'auto',
                'remove_onclick' => true,
            ],
            'status' => [
                'title' => $this->l('Status'),
                'width' => 'auto',
                'callback' => 'renderStatus',
                'remove_onclick' => true,
            ],
            'type' => [
                'title' => $this->l('Type'),
                'width' => 'auto',
                'callback' => 'renderType',
                'remove_onclick' => true,
            ],
            'error' => [
                'title' => $this->l('Error'),
                'width' => 'auto',
                'callback' => 'renderFullError',
                'remove_onclick' => true,
            ],
            'state_message' => [
                'title' => $this->l('Message'),
                'width' => 'auto',
                'remove_onclick' => true,
            ],
        ];
    }

    public function renderSimpleError($error)
    {
        if (empty($error)) {
            return null;
        }

        $unserialized = unserialize($error);
        if (isset($unserialized['_embedded']) && isset($unserialized['_embedded']['errors'])) {
            $errorCount = count($unserialized['_embedded']['errors']);
            $firstError = $unserialized['_embedded']['errors'][0]['message'];
            if ($errorCount === 1) {
                return $firstError;
            }
            return $firstError . ' ' . sprintf(__('And %s more errors.', 'dpdconnect'), $errorCount - 1);
        }
    }

    public function renderStatus($status)
    {
        return JobStatus::tag($status);
    }

    public function renderType($type)
    {
        if ($type === '1') {
            return $this->l('Shipping label');
        }
        if ($type === '2') {
            return $this->l('Return label');
        }
        if ($type === '3') {
            return $this->l('Saturday label');
        }
    }

    public function renderFullError($error)
    {
        return '<pre>' . print_r(unserialize($error), true) . '</pre>';
    }

    public function displayDetailsLink($token = null, $id = null, $name = null)
    {
        $linkCore = new LinkCore();
        $jobRepo = new JobRepo();
        $labelRepo = new LabelRepo();
        $job = $jobRepo->get($id);

        $labelId = $job['label_id'];
        $label = $labelRepo->get($labelId);
        $response = '';
        if ($label) {
            $pdfLink = $linkCore->getAdminLink('AdminDownloadLabel') . '&label_id=' . $labelId;
            $response .= '<a href="' . $pdfLink . '">PDF</a>';
        }
        $jobLink = $linkCore->getAdminLink('AdminDpdJobs') . sprintf('&submitFilterdpd_jobs=0&job_id=%s#dpd_jobs', $id);
        $response .= '<a href="' . $jobLink . '">View Job</a>';

        return $response;
    }
}
