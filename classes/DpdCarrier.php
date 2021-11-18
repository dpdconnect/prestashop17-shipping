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
use Carrier;
use DbQuery;
use DpdConnect\classes\Connect\Product;
use CarrierModule;
use Configuration;

class DpdCarrier extends CarrierModule
{
    public $dpdProductHelper;
    public $dpdPrefix;

    public function __construct()
    {
        $this->dpdProductHelper = new DpdProductHelper();
        $this->dpdPrefix = 'dpdconnect_';
    }

    public function createCarriers()
    {
        $dpdProducts = [];
        try {
            $connectProduct = new Product();
            $dpdProducts = $connectProduct->getList();
        } catch (\Exception $exception) {
            return true;
        }

//        foreach ($dpdProducts as $dpdProduct) {
//            $carrier = $this->dpdProductHelper->getCarrierByProduct($dpdProduct);
//
//            // Carrier for this product exists, but is soft-deleted
//            if ($carrier) {
//                $this->unDeleteCarrier($carrier['carrier_id']);
//            } else {
//                // Carrier for this product does not exist, so we create a new one
//                $this->createCarrier($dpdProduct);
//            }
//        }

        $existingDpdCarriers = $this->dpdProductHelper->getDpdCarriers();
        // Undo soft-delete on existing DPD Carriers if they exist
        if ($existingDpdCarriers) {

            foreach ($existingDpdCarriers as $existingDpdCarrier) {
                // Prevent undeleting carriers that use a disabled DPD Product
                if (in_array($existingDpdCarrier['dpd_product_code'], array_column($dpdProducts, 'code'))) {
                    $this->unDeleteCarrier($this->getLatestCarrierByReferenceId($existingDpdCarrier['carrier_id'], false));
                }
            }
        }

        return true;
    }

    public function deleteCarriers()
    {
        $dpdCarriers = $this->dpdProductHelper->getDpdCarriers();

        array_walk($dpdCarriers, function ($dpdCarrier) {
            $this->softDeleteCarriers($this->getLatestCarrierByReferenceId($dpdCarrier['carrier_id']));
        });

        return true;
    }

    public function getLatestCarrierByReferenceId($id_carrier, $except_deleted = true)
    {
        if ($id_carrier === false) {
            return;
        }
        $sql = new DbQuery();
        $sql->from('carrier');
        $sql->select('id_carrier');
        $sql->where('id_reference = ' . pSQL($id_carrier));
        if ($except_deleted) {
            $sql->where('deleted != 1');
        }
        $sql->orderBy('id_carrier DESC');
        $sql->limit(1);

        $result = Db::getInstance()->ExecuteS($sql);
        if (count($result) == 0) {
            return $id_carrier;
        } else {
            return current($result)['id_carrier'];
        }
    }

    public function ifHasSameReferenceId($carrierId, $referenceId)
    {
        $tempCarrier = new Carrier($carrierId);
        return $tempCarrier->id_reference  == $referenceId;
    }

    public function createCarrier(array $dpdProduct)
    {
        $carrier = new Carrier();

        $carrier->url ='//tracking.dpd.de/parcelstatus?query=@';
        $carrier->name = $dpdProduct['name'];
        $carrier->delay[Configuration::get('PS_LANG_DEFAULT')] = (string)$dpdProduct['description'] ?: (string)$dpdProduct['name'];
        $carrier->active = 0;
        $carrier->deleted = 0;
        $carrier->shipping_handling = 1;
        $carrier->range_behavior = 0;
        $carrier->shipping_external = 0;
        $carrier->add();
        $carrier->id_reference = $carrier->id;
        $carrier->update();

//        Configuration::updateValue($this->dpdPrefix . strtolower($prefix), $carrier->id);

        $dpdProductHelper = new DpdProductHelper();
        $dpdProductHelper->mapProductToCarrier($dpdProduct, $carrier->id);

        return $carrier;
    }

    public function softDeleteCarriers($carrier_id)
    {
        Db::getInstance()->update('carrier', array('deleted' => 1), 'id_carrier = '. pSQL($carrier_id));
        return true;
    }

    public function unDeleteCarrier($carrier_id)
    {
        Db::getInstance()->update('carrier', array('deleted' => 0), 'id_carrier = '. pSQL($carrier_id));
        return true;
    }

    public function isSaturdayCarrier($carrierId)
    {
        $carrier = new Carrier($carrierId);

        $dpdProduct = $this->dpdProductHelper->getProductByCarrier($carrier->id_reference);

        if (!$dpdProduct) {
            return false;
        }

        return stripos($dpdProduct['name'], 'saturday') !== false;
    }

    public function checkIfSaturdayAllowed()
    {
        $showfromday = Configuration::get('dpdconnect_saturday_showfromday');
        $showfromtime = Configuration::get('dpdconnect_saturday_showfromtime');
        $showtilltime = Configuration::get('dpdconnect_saturday_showtilltime');
        $showtillday = Configuration::get('dpdconnect_saturday_showtillday');
        if (empty($showfromday) || empty($showfromtime) || empty($showtillday) || empty($showtilltime)) {
            return false;
        }
        $showfromtime = explode(':', $showfromtime);
        $firstDate = new \DateTime($showfromday . ' this week ' . $showfromtime[0] . ' hours ' . $showfromtime[1] . ' minutes 00 seconds');
        $showtilltime = explode(':', $showtilltime);
        $lastDate = new \DateTime($showtillday . ' this week ' . $showtilltime[0] . ' hours ' . $showtilltime[1] . ' minutes 59 seconds');

        $today = new \DateTime();

        return $today >= $firstDate && $today <= $lastDate;
    }



    public function getOrderShippingCost($params, $shipping_cost)
    {
    }

    public function getOrderShippingCostExternal($params)
    {
    }
}
