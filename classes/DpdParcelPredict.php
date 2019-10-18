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
use DpdConnect\Sdk\Exceptions\AuthenticateException;
use Order;
use Carrier;
use DbQuery;
use Configuration;
use DpdConnect\classes\Version;
use DpdConnect\Sdk\ClientBuilder;
use DpdConnect\Sdk\Objects\MetaData;
use DpdConnect\Sdk\Objects\ObjectFactory;
use PrestaShop\PrestaShop\Adapter\LegacyLogger;

class DpdParcelPredict
{
    /**
     * @var \DpdConnect\Sdk\Client
     */
    public $dpdClient;
    public $Gmaps;
    public $DpdAuthentication;

    public function __construct()
    {
        $url = Configuration::get('dpdconnect_url');
        $username = Configuration::get('dpdconnect_username');
        $encryptedPassword = Configuration::get('dpdconnect_password');
        if ($encryptedPassword === null || $encryptedPassword === "" || $encryptedPassword === false) {
            return;
        }
        $password = DpdEncryptionManager::decrypt($encryptedPassword);
        $clientBuilder = new ClientBuilder($url, ObjectFactory::create(MetaData::class, [
            'webshopType' => Version::type(),
            'webshopVersion' => Version::webshop(),
            'pluginVersion' => Version::plugin(),
        ]));
        $this->dpdClient = $clientBuilder->buildAuthenticatedByPassword($username, $password);
        $this->Gmaps = new Gmaps();
    }

    public function getGeoData($postalCode, $isoCode)
    {
        return $this->Gmaps->getGeoData($postalCode, $isoCode);
    }

    public function getParcelShops($postalCode, $isoCode)
    {
        if (!$postalCode) {
            return;
        }

        try {
        $coordinates = $this->getGeoData($postalCode, $isoCode);
        $coordinates['countryIso'] = $isoCode;
        $parcelShops = $this->dpdClient->getParcelShop()->getList($coordinates);
        } catch (AuthenticateException $exception) {
            // Log error to the database
            \PrestaShopLoggerCore::addLog($exception->getMessage(), 3, null, 'DPDConnect');
            return false;
        }
        return $parcelShops;
    }


    public function getParcelShopId($orderId)
    {
        return Db::getInstance()->getValue("SELECT parcelshop_id FROM " . _DB_PREFIX_ . "parcelshop WHERE order_id = " . pSQL($orderId));
    }

    public function checkIfDpdSending($orderId)
    {
        if ($this->checkIfParcelSending($orderId) ||
            $this->checkIfSaturdayCarrier($orderId)||
            $this->checkIfClassicSaturdayCarrier($orderId) ||
            $this->checkIfPredictCarrier($orderId) ||
            $this->checkIfExpress12Carrier($orderId)||
            $this->checkIfExpress10Carrier($orderId) ||
            $this->checkIfGuarantee18Carrier($orderId) ||
            $this->checkIfClassicCarrier($orderId)
        ) {
            return true;
        }

        return false;
    }

    public function checkIfParcelSending($orderId)
    {
        $tempOrder = new Order($orderId);
        $tempCarrier = new Carrier($tempOrder->id_carrier);
        $tempCarrierReferenceId = $tempCarrier->id_reference;

        $dpdParcelshopCarrierId = Configuration::get('dpdconnect_parcelshop');

        if ($tempCarrierReferenceId == $dpdParcelshopCarrierId) {
            return true;
        } else {
            return false;
        }
    }

    public function checkIfParcelCarrier($orderId)
    {
        $parcelShopId = $this->getParcelShopId($orderId);
        $dpdParcelshopCarrierId = $this->checkIfParcelSending($orderId);

        if (($parcelShopId != null) && ($dpdParcelshopCarrierId)) {
            $result = true;
        } else {
            $result = false;
        }
        return $result;
    }

    public static function checkIfPredictCarrier($orderId)
    {
        $tempOrder = new Order($orderId);
        $tempCarrier = new Carrier($tempOrder->id_carrier);
        $tempCarrerreferenceId = $tempCarrier->id_reference;

        $dpdPredictCarrierId = Configuration::get('dpdconnect_predict');

        if ($tempCarrerreferenceId == $dpdPredictCarrierId) {
            return true;
        } else {
            return false;
        }
    }

    public function checkIfSaturdayCarrier($orderId)
    {
        $tempOrder = new Order($orderId);
        $tempCarrier = new Carrier($tempOrder->id_carrier);
        $tempCarrierReferenceId = $tempCarrier->id_reference;

        $dpdSaturdayCarrierId = Configuration::get('dpdconnect_saturday');

        if ($tempCarrierReferenceId == $dpdSaturdayCarrierId) {
            return true;
        } else {
            return false;
        }
    }

    public function checkIfClassicSaturdayCarrier($orderId)
    {
        $tempOrder = new Order($orderId);
        $tempCarrier = new Carrier($tempOrder->id_carrier);
        $tempCarrierReferenceId = $tempCarrier->id_reference;

        $dpdClassicSaturdayCarrierId = Configuration::get('dpdconnect_classic_saturday');

        if ($tempCarrierReferenceId == $dpdClassicSaturdayCarrierId) {
            return true;
        } else {
            return false;
        }
    }

    public function checkIfExpress12Carrier($orderId)
    {
        $tempOrder = new Order($orderId);
        $tempCarrier = new Carrier($tempOrder->id_carrier);
        $tempCarrierReferenceId = $tempCarrier->id_reference;

        $dpdExpress12CarrierId = Configuration::get('dpdconnect_express12');

        if ($tempCarrierReferenceId == $dpdExpress12CarrierId) {
            return true;
        } else {
            return false;
        }
    }

    public function checkIfExpress10Carrier($orderId)
    {
        $tempOrder = new Order($orderId);
        $tempCarrier = new Carrier($tempOrder->id_carrier);
        $tempCarrierReferenceId = $tempCarrier->id_reference;

        $dpdExpress10CarrierId = Configuration::get('dpdconnect_express10');

        if ($tempCarrierReferenceId == $dpdExpress10CarrierId) {
            return true;
        } else {
            return false;
        }
    }

    public function checkIfGuarantee18Carrier($orderId)
    {
        $tempOrder = new Order($orderId);
        $tempCarrier = new Carrier($tempOrder->id_carrier);
        $tempCarrierReferenceId = $tempCarrier->id_reference;

        $dpdGuarantee18CarrierId = Configuration::get('dpdconnect_guarantee18');

        if ($tempCarrierReferenceId == $dpdGuarantee18CarrierId) {
            return true;
        } else {
            return false;
        }
    }

    public function checkIfClassicCarrier($orderId)
    {
        $tempOrder = new Order($orderId);
        $tempCarrier = new Carrier($tempOrder->id_carrier);
        $tempCarrierReferenceId = $tempCarrier->id_reference;

        $dpdClassicCarrierId = Configuration::get('dpdconnect_classic');

        if ($tempCarrierReferenceId == $dpdClassicCarrierId) {
            return true;
        } else {
            return false;
        }
    }

    public function getLabelNumbersAndWeigth($orderId)
    {
        $sql = new DbQuery();
        $sql->from('dpdshipment_label');
        $sql->select('label_nummer, retour');
        $sql->where('order_id = ' . pSQL($orderId));



        $result = Db::getInstance()->ExecuteS($sql);
        return $result;
    }
}
