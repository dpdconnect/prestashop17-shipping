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

use Order;
use Address;
use Customer;

class DpdShippingList
{
    public $dpdParcelPredict;
    public $dpdCarrier;

    public function __construct()
    {
        $this->dpdParcelPredict = new DpdParcelPredict();
        $this->dpdCarrier = new DpdCarrier();
    }

    public function generateData($orderIds)
    {
        $data = array();
        foreach ($orderIds as $orderId) {
            $order = new Order($orderId);
            if (!$this->dpdParcelPredict->checkIfDpdSending($order->id)) {
                continue;
            }
            $labelsNumbersAndWeight = $this->dpdParcelPredict->getLabelNumbersAndWeigth($order->id);
            if (!$labelsNumbersAndWeight) {
                continue;
            }
            foreach ($labelsNumbersAndWeight as $labelNumbersAndWeight) {
                // so it skips retour label's
                if ($labelNumbersAndWeight['retour'] == 1) {
                    continue;
                }

                $labelNumbersAndWeightUnserialized = unserialize($labelNumbersAndWeight['label_nummer']);

                // IF ONLY ONE LABEL ADDING ARRAY LAYER
                if (count($labelNumbersAndWeightUnserialized) == 1) {
                    $labelNumbersAndWeightArray = array();
                    $labelNumbersAndWeightArray[] = $labelNumbersAndWeightUnserialized;
                } else {
                    $labelNumbersAndWeightArray = $labelNumbersAndWeightUnserialized;
                }

                foreach ($labelNumbersAndWeightArray as $labelNumberAndWeight) {
                    $parcelLabelNumber = $labelNumberAndWeight;
                    //$parcelLabelNumber = $labelNumberAndWeight->parcelLabelNumber;
                    //$weight = $labelNumberAndWeight->weight;

                    $tempCustomer = new Customer($order->id_customer);
                    $tempAddres = new Address($order->id_address_delivery);

                    $name = '';
                    if (!empty($tempCustomer->company)) {
                        $name = $tempCustomer->company . ',';
                    }
                    $name .= $tempCustomer->firstname . ' ' . $tempCustomer->lastname;

                    $street = $tempAddres->address1;
                    if (!empty($tempAddres->address2)) {
                        $street .= $tempAddres->address2;
                    }

                    $postcode = $tempAddres->postcode;
                    $city = $tempAddres->city;

                    $carrier = new \Carrier($order->id_carrier);

                    $parcel = array(
                        'parcelLabelNumber' => reset($parcelLabelNumber),
                        'carrierName' => $carrier->name,
                        'customerName' => $name,
                        'address' => $street,
                        'postcode' => $postcode,
                        'city' => $city,
                        'referenceNumber' => $order->id,
                        //'weight' => round($weight, 2) . ' g',
                    );

                    $data[] = $parcel;
                }
            }
        }
        return $data;
    }
}
