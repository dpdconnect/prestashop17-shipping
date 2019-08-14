<?php
/**
 * This file is part of the Prestashop Shipping module of DPD Nederland B.V.
 *
 * Copyright (C) 2017  DPD Nederland B.V.
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 */

use DpdConnect\classes\JobRepo;
use DpdConnect\classes\BatchRepo;
use DpdConnect\classes\Database\LabelRepo;
use DpdConnect\classes\Connect\Label as ConnectLabel;
use DpdConnect\classes\enums\JobStatus;

class dpdconnectcallbackModuleFrontController extends ModuleFrontController
{
    public function postProcess()
    {
        $incomingData = json_decode(file_get_contents('php://input'), true);
        $state = $incomingData['state'];

        /**
         * At this point, we do not care about the callback having been
         * fired
         */
        if ($state >= 16) {
            $state -= 16;
        }

        if ($state === 4) {
            self::success($incomingData);
        }

        if ($state >= 8) {
            self::failure($incomingData);
        }

        $this->ajaxDie(json_encode([
            'success' => true,
        ]));
    }


    private static function success($incomingData)
    {
        $orderId = $incomingData['shipment']['orderId'];
        $externalId = $incomingData['jobid'];
        $parcelNumber = $incomingData['shipment']['trackingInfo']['parcelNumbers'][0];
        $shipmentIdentifier = $incomingData['shipment']['trackingInfo']['shipmentIdentifier'];
        $parcelNumbers = implode(',', $incomingData['shipment']['trackingInfo']['parcelNumbers']);

        $jobRepo = new JobRepo();
        $batchRepo = new BatchRepo();
        $job = $jobRepo->getByExternalId($externalId);

        try {
            $connectLabel = new ConnectLabel();
            $labelRepo = new LabelRepo();
            $label = $connectLabel->get($parcelNumber);
            $labelId = $labelRepo->create($orderId, $label, $job['type'], $shipmentIdentifier, $parcelNumbers);
            $jobRepo->updateStatus($job, JobStatus::STATUSSUCCESS, null, null, $labelId);
            $batchRepo->updateStatus($job);
        } catch (Exception $e) {
            $error = 'Could not download label after job completion.';
            $jobRepo->updateStatus($job, JobStatus::STATUSREQUEST, $error);
        }
    }

    private static function failure($incomingData)
    {
        $externalId = $incomingData['jobid'];

        $jobRepo = new JobRepo();
        $batchRepo = new BatchRepo();
        $job = $jobRepo->getByExternalId($externalId);
        $errors = $incomingData['error'];
        $stateMessage = $incomingData['stateMessage'];
        $jobRepo->updateStatus($job, JobStatus::STATUSFAILED, $stateMessage, $errors);
        $batchRepo->updateStatus($job);
    }
}
