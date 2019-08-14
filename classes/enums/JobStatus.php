<?php

namespace DpdConnect\classes\enums;

use dpdconnect;

class JobStatus
{
    const STATUSREQUEST = 'status_request';
    const STATUSQUEUED = 'status_queued';
    const STATUSPROCESSING = 'status_processing';
    const STATUSSUCCESS = 'status_success';
    const STATUSFAILED = 'status_failed';

    private static $module = null;

    public function __construct()
    {
        self::getModule();
    }

    private static function getModule()
    {
        if (is_null(self::$module)) {
            self::$module = new dpdconnect();
        }
        return self::$module;
    }

    public static function tag($status)
    {
        switch ($status) {
            case self::STATUSREQUEST:
                return "<span class='dpdTag request'>" . self::getModule()->l('Request', 'dpdconnect') . "</span>";
            case self::STATUSQUEUED:
                return "<span class='dpdTag queued'>" . self::getModule()->l('Queued', 'dpdconnect') . "</span>";
            case self::STATUSPROCESSING:
                return "<span class='dpdTag processing'>" . self::getModule()->l('Processing', 'dpdconnect') . "</span>";
            case self::STATUSSUCCESS:
                return "<span class='dpdTag success'>" . self::getModule()->l('Success', 'dpdconnect') . "</span>";
            case self::STATUSFAILED:
                return "<span class='dpdTag failed'>" . self::getModule()->l('Failed', 'dpdconnect') . "</span>";
            default:
                return;
        }
    }
}
