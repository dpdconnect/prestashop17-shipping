<?php

namespace DpdConnect\classes;

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

use Db;
use DbQuery;
use Configuration;
use PrestaShopLogger;
use DpdConnect\classes\enums\JobStatus;

class JobRepo
{
    const TABLE = 'dpd_jobs';

    public function create($batchId, $externalId, $orderId, $type)
    {
        Db::getInstance()->insert('dpd_jobs', [
            'created_at' => date('Y-m-d H:i:s'),
            'external_id' => $externalId,
            'batch_id' => $batchId,
            'order_id' => $orderId,
            'status' => JobStatus::STATUSQUEUED,
            'type' => $type,
        ]);

        return Db::getInstance()->Insert_ID();
    }

    public function get($id)
    {
        $sql = new DbQuery();
        $sql->from(self::TABLE);
        $sql->select('*');
        $sql->where(sprintf('id_dpd_jobs = "%s"', $id));

        return Db::getInstance()->getRow($sql);
    }

    public function getByExternalId($externalId)
    {
        $sql = new DbQuery();
        $sql->from(self::TABLE);
        $sql->select('*');
        $sql->where(sprintf('external_id = "%s"', $externalId));

        return Db::getInstance()->getRow($sql);
    }

    public function getByOrderId($orderId)
    {
        $sql = new DbQuery();
        $sql->from(self::TABLE);
        $sql->select('*');
        $sql->where(sprintf('order_id = "%s"', $orderId));

        return Db::getInstance()->getRow($sql);
    }

    public function updateStatus($job, $status, $stateMessage = null, $errors = null, $labelId = null)
    {
        $data = [
            'status' => $status,
            'state_message' => $stateMessage,
        ];

        if ($errors) {
            $data['error'] = serialize($errors);
        }

        if ($labelId) {
            $data['label_id'] = $labelId;
        }

        $where = 'id_dpd_jobs = ' . $job['id_dpd_jobs'];

        Db::getInstance()->update(self::TABLE, $data, $where);
    }
}
