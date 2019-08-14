<?php

namespace DpdConnect\classes;

use Db;
use DbQuery;
use DpdConnect\classes\JobRepo;
use DpdConnect\classes\enums\JobStatus;
use DpdConnect\classes\enums\BatchStatus;

class BatchRepo
{
    const TABLE = 'dpd_batches';

    public function create($shipmentCount)
    {
        $data = [
            'status' => BatchStatus::STATUSQUEUED,
            'created_at' => date('Y-m-d H:i:s'),
            'shipment_count' => $shipmentCount,
            'success_count' => null,
            'failure_count' => null,
        ];

        Db::getInstance()->insert(self::TABLE, $data);

        return Db::getInstance()->Insert_ID();
    }

    public function updateStatus($job)
    {
        $batch = $this->getByJobId($job['id_dpd_jobs']);
        $count = $this->countSuccessAndFailures($job['batch_id']);
        $batchStatus = $this->parseStatus($batch, $count);

        /**
         * Update the batch with new counts
         */
        $data = [
            'success_count' => $count['success'],
            'failure_count' => $count['failed'],
            'status' => $batchStatus,
        ];

        $where = 'id_dpd_batches = ' . $job['batch_id'];

        Db::getInstance()->update(self::TABLE, $data, $where);
    }

    private function getByJobId($jobId)
    {
        $innerSql = new DbQuery();
        $sql = new DbQuery();
        $sql->from(self::TABLE);
        $sql->select('*');
        $sql->where(sprintf(
            'id_dpd_batches IN (SELECT batch_id
                       FROM %s%s
                      WHERE id_dpd_jobs = %s)',
            _DB_PREFIX_,
            JobRepo::TABLE,
            $jobId
        ));

        return Db::getInstance()->getRow($sql);
    }

    private function countSuccessAndFailures($batchId)
    {
        $sql = new DbQuery();
        $sql->from(JobRepo::TABLE);
        $sql->select(sprintf('
            SUM(IF (status = "%s", 1, 0)) AS success,
            SUM(IF (status = "%s", 1, 0)) AS failed',
            JobStatus::STATUSSUCCESS,
            JobStatus::STATUSFAILED
        ));
        $sql->where(sprintf('batch_id = %s', $batchId));

        return Db::getInstance()->getRow($sql);
    }

    private function parseStatus($batch, $count)
    {
        if ($batch['shipment_count'] > ($count['failed'] + $count['success'])) {
            return BatchStatus::STATUSPROCESSING;
        }

        if ($batch['shipment_count'] === $count['success']) {
            return BatchStatus::STATUSSUCCESS;
        }

        if ($batch['shipment_count'] === $count['failed']) {
            return BatchStatus::STATUSFAILED;
        }

        if ($batch['shipment_count'] > $count['failed']) {
            return BatchStatus::STATUSPARTIALLYFAILED;
        }
    }
}
