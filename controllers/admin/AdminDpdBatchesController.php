<?php

require_once(_PS_MODULE_DIR_ . 'dpdconnect' . DIRECTORY_SEPARATOR . 'dpdconnect.php');

use DpdConnect\classes\enums\BatchStatus;

class AdminDpdBatchesController extends ModuleAdminController
{
    public function __construct()
    {
        $this->table = 'dpd_batches';
        $this->bootstrap = true;
        $this->list_simple_header = true; // Enable again once the sort and filter options are made to work
        $this->context = Context::getContext();
        $this->addRowAction('details');

        parent::__construct();
    }

    public function renderList()
    {
        $this->fields_list = [
            'id_dpd_batches' => [
                'title' => $this->l('ID'),
                'width' => 'auto',
                'remove_onclick' => true,
            ],
            'created_at' => [
                'title' => $this->l('Created at'),
                'width' => 'auto',
                'remove_onclick' => true,
            ],
            'shipment_count' => [
                'title' => $this->l('Shipment count'),
                'width' => 'auto',
                'remove_onclick' => true,
            ],
            'success_count' => [
                'title' => $this->l('Success count'),
                'width' => 'auto',
                'search' => false,
                'remove_onclick' => true,
            ],
            'failure_count' => [
                'title' => $this->l('Failure count'),
                'width' => 'auto',
                'remove_onclick' => true,
            ],
            'status' => [
                'title' => $this->l('Status'),
                'width' => 'auto',
                'callback' => 'renderStatus',
                'remove_onclick' => true,
            ],
        ];

        $lists = parent::renderList();

        parent::initToolbar();

        return $lists;
    }

    public function renderStatus($status)
    {
        return BatchStatus::tag($status);
    }

    public function displayDetailsLink($token = null, $id = null, $name = null)
    {
        $linkCore = new LinkCore;
        $link = $linkCore->getAdminLink('AdminDpdJobs') . sprintf('&submitFilterdpd_jobs=0&batch_id=%s#dpd_jobs', $id);
        return '<a href="' . $link . '">View jobs</a>';
    }
}
