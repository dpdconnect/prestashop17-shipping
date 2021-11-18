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
    private $dpdCarrier;
    private $dpdProductHelper;

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

        $this->dpdClient->getAuthentication()->setJwtToken(
            Configuration::get('dpdconnect_jwt_token') ?: null
        );

        $this->dpdClient->getAuthentication()->setTokenUpdateCallback(function ($jwtToken) {
            Configuration::updateValue('dpdconnect_jwt_token', $jwtToken);
            $this->dpdClient->getAuthentication()->setJwtToken($jwtToken);
        });

        $dpdCarrier       = new DpdCarrier();
        $this->dpdCarrier = $dpdCarrier;
        $this->dpdProductHelper = new DpdProductHelper();
    }

    public function getParcelShopId($orderId)
    {
        return Db::getInstance()->getValue("SELECT parcelshop_id FROM " . _DB_PREFIX_ . "parcelshop WHERE order_id = " . pSQL($orderId));
    }

    // Check if order is sent by a DPD Carrier
    public function checkIfDpdSending($orderId)
    {
        $order = new Order($orderId);
        $orderCarrier = new Carrier($order->id_carrier);

        return $this->dpdProductHelper->isDpdCarrier($orderCarrier->id_reference);
    }

    // Check if order is sent by a DPD Carrier which uses a DPD Parcelshop Product
    public function checkIfParcelSending($orderId)
    {
        $order = new Order($orderId);
        $orderCarrier = new Carrier($order->id_carrier);

        $dpdProduct = $this->dpdProductHelper->getProductByCarrier($orderCarrier->id_reference);

        if (!$dpdProduct) {
            return false;
        }

        return strtolower($dpdProduct['type']) === 'parcelshop';
    }

    // Check if order has parcelshop id and is sent by a DPD Parcelshop Carrier
    public function checkIfParcelCarrier($orderId)
    {
        $parcelShopId = $this->getParcelShopId($orderId);
        $isDpdParcelshopOrder = $this->checkIfParcelSending($orderId);

        return $parcelShopId && $isDpdParcelshopOrder;
    }

    public function checkIfPredictCarrier($orderId)
    {
        $order = new Order($orderId);
        $orderCarrier = new Carrier($order->id_carrier);

        $dpdProduct = $this->dpdProductHelper->getProductByCarrier($orderCarrier->id_reference);

        if (!$dpdProduct) {
            return false;
        }

        return strtolower($dpdProduct['type']) === 'predict';
    }

    public function checkIfSaturdayCarrier($orderId)
    {
        $order = new Order($orderId);
        $orderCarrier = new Carrier($order->id_carrier);

        $dpdProduct = $this->dpdProductHelper->getProductByCarrier($orderCarrier->id_reference);

        if (!$dpdProduct) {
            return false;
        }

        return stripos($dpdProduct['name'], 'saturday') !== false;
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
