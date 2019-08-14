<?php

namespace DpdConnect\classes\Database;

use Db;
use DbQuery;
use DpdConnect\classes\enums\ParcelType;

class LabelRepo
{
    const TABLE = 'dpdshipment_label';

    public function create($orderId, $contents, $type, $shipmentIdentifier, $parcelNumbers)
    {
        if (!is_array($parcelNumbers)) {
            $parcelNumbers = [$parcelNumbers];
        }

        if ($type == ParcelType::TYPERETURN) {
            $retour = '1';
        } else {
            $retour = '0';
        }

        Db::getInstance()->insert(self::TABLE, [
            'mps_id' => $shipmentIdentifier,
            'label_nummer' => serialize($parcelNumbers),
            'order_id' => (int) $orderId,
            'created_at' => (string) date('y-m-d h:i:s'),
            'shipped' => 0,
            'label' => addslashes($contents),
            'retour' => $retour,
        ]);

        return Db::getInstance()->Insert_ID();
    }

    public function get($id)
    {
        $sql = new DbQuery();
        $sql->from(self::TABLE);
        $sql->select('*');
        $sql->where(sprintf('id_dpdcarrier_label = "%s"', $id));

        return Db::getInstance()->getRow($sql);
    }
}
