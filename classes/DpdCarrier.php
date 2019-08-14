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
use Exception;
use CarrierModule;
use Configuration;

class DpdCarrier extends CarrierModule
{
    public $carrierNames;
    public $dpdPrefix;

    public function __construct()
    {
        $this->dpdPrefix = 'dpdconnect_';

        $this->carrierNames['predict'] = [
            'name' => 'DPD Predict',
            'description' => $this->l('DPD predict delivery'),
            'type' => 'b2c'
        ];

        $this->carrierNames['parcelshop'] = [
            'name' => 'DPD Parcelshop',
            'description' => $this->l('deliver it at a Parcelshop'),
            'type' => 'b2c'
        ];

        $this->carrierNames['saturday'] = [
            'name' => 'DPD Zaterdag',
            'description' => $this->l('only deliver at a saturday'),
            'type' => 'b2c',
        ];

        $this->carrierNames['classic_saturday'] = [
            'name' => 'DPD Classic Zaterdag',
            'description' =>  $this->l('only deliver at a saturday'),
            'type' => 'b2b'
        ];

        $this->carrierNames['classic'] = [
            'name' => 'DPD Classic',
            'description' =>  $this->l('DPD classic delivery'),
            'type' => 'b2b'
        ];

        $this->carrierNames['guarantee18'] = [
            'name' => 'Guarantee 18:00',
            'description' =>  $this->l('DPD Guarantee 18:00 delivery'),
            'type' => 'b2b'
        ];

        $this->carrierNames['express12'] = [
            'name' => 'Express 12:00',
            'description' =>  $this->l('DPD Express 12:00 delivery'),
            'type' => 'b2b'
        ];

        $this->carrierNames['express10'] = [
            'name' => 'Express 10:00',
            'description' =>  $this->l('DPD Express 10:00 delivery'),
            'type' => 'b2b'
        ];
    }

    public function createCarriers()
    {
        foreach ($this->carrierNames as $prefix => $info) {
            if (!Configuration::get($this->dpdPrefix . strtolower($prefix))) {
                $carrier = new Carrier();

                $carrier->url ='//tracking.dpd.de/parcelstatus?query=@';
                $carrier->name = $info['name'];
                $carrier->delay[Configuration::get('PS_LANG_DEFAULT')] = (string)$info['description'];
                $carrier->active = 0;
                $carrier->deleted = 1;
                $carrier->shipping_handling = 1;
                $carrier->range_behavior = 0;
                $carrier->shipping_external = 0;
                $carrier->add();
                $carrier->id_reference = $carrier->id;
                $carrier->update();
                Configuration::updateValue($this->dpdPrefix . strtolower($prefix), $carrier->id);
//                  copy(dirname(__DIR__) . '/../logo.png', _PS_SHIP_IMG_DIR_ . '/' . (int)$carrier->id . '.jpg'); //assign carrier logo
            }
        }
        $this->setCarrierForAccountType();
        return true;
    }

    public function setCarrierForAccountType()
    {
        $accountType = Configuration::get($this->dpdPrefix . 'account_type');

        foreach ($this->carrierNames as $prefix => $info) {
            $configCarrierId = Configuration::get($this->dpdPrefix . $prefix);

            $carrierId = $this->getLatestCarrierByReferenceId($configCarrierId, false);
            if ($info['type'] == $accountType) {
                $this->unDeleteCarrier($carrierId);
            } else {
                $this->softDeleteCarriers($carrierId);
            }
        }
    }


    public function deleteCarriers()
    {
        foreach ($this->carrierNames as $prefix => $info) {
            $carrier_id = $this->getLatestCarrierByReferenceId(Configuration::get($this->dpdPrefix . strtolower($prefix)));
            if ($this->softDeleteCarriers($carrier_id)) {
                $output = true;
            } else {
                $output = false;
            }
        }

        return $output;
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

    public function getShortNameShipping($carrierId)
    {
        $tempCarrier = new Carrier($carrierId);
        $tempCarrierReferenceId = $tempCarrier->id_reference;

        foreach ($this->carrierNames as $prefix => $info) {
            if (Configuration::get($this->dpdPrefix . $prefix) == $tempCarrierReferenceId) {
                if ($prefix == 'guarantee18') {
                    return 'DPD 18';
                } elseif ($prefix == 'classic_saturday') {
                    return 'DPD B2B Sat';
                } elseif ($prefix == 'saturday') {
                    return 'DPD B2C Sat';
                } elseif ($prefix == 'express12') {
                    return 'DPD 12';
                } elseif ($prefix == 'express10') {
                    return 'DPD 10';
                } else {
                    return $tempCarrier->name;
                }
            }
        }
    }
}
