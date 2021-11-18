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

use DpdConnect\classes\Connect\Connection;
use Hook;
use Address;
use Context;
use Country;
use Configuration;
use CheckoutDeliveryStep;
use Symfony\Component\Translation\TranslatorInterface;

class DpdCheckoutDeliveryStep extends CheckoutDeliveryStep
{
    public $dpdParcelPredict;
    public $dpdCarrier;
    public $dpdProductHelper;
    private $connection;

    public function __construct(Context $context, TranslatorInterface $translator)
    {
        parent::__construct($context, $translator);
        $this->dpdParcelPredict = new DpdParcelPredict();
        $this->dpdCarrier = new DpdCarrier();
        $this->dpdProductHelper = new DpdProductHelper();
        $this->connection = new Connection();
    }

    public function render(array $extraParams = array())
    {
        $templates = $this->renderTemplate(
            $this->getTemplate(),
            $extraParams,
            array(
                'hookDisplayBeforeCarrier' => Hook::exec('displayBeforeCarrier', array('cart' => $this->getCheckoutSession()->getCart())),
                'hookDisplayAfterCarrier' => Hook::exec('displayAfterCarrier', array('cart' => $this->getCheckoutSession()->getCart())),
                'id_address' => $this->getCheckoutSession()->getIdAddressDelivery(),
                'delivery_options' => $this->getCheckoutSession()->getDeliveryOptions(),
                'delivery_option' => $this->getCheckoutSession()->getSelectedDeliveryOption(),
                'recyclable' => $this->getCheckoutSession()->isRecyclable(),
                'recyclablePackAllowed' => $this->isRecyclablePackAllowed(),
                'delivery_message' => $this->getCheckoutSession()->getMessage(),
                'gift' => array(
                    'allowed' => $this->isGiftAllowed(),
                    'isGift' => $this->getCheckoutSession()->getGift()['isGift'],
                    'label' => $this->getTranslator()->trans(
                        'I would like my order to be gift wrapped %cost%',
                        array('%cost%' => $this->getGiftCostForLabel()),
                        'Shop.Theme.Checkout'
                    ),
                    'message' => $this->getCheckoutSession()->getGift()['message'],
                ),
            )
        );

        // own code

        $address = new Address($this->context->cart->id_address_delivery);

        if ($this->getCheckoutSession()->getIdAddressDelivery() == "0") {
            return $templates;
        }

        $country = new Country($address->id_country);
        $isoCode = $country->iso_code;


        // prevent attempts at getting parcelshops before enough data is available
        if (!$address->postcode) {
            return $templates;
        }

        $useDpdKey = Configuration::get('dpdconnect_use_dpd_key') == 1;

        $mapsKey = '';
        if (!$useDpdKey) {
            $mapsKey = Configuration::get('gmaps_key');
        }

        $link = new \Link();
        $this->context->smarty->assign([
            'baseUri' => __PS_BASE_URI__,
            'parcelshopId' => $this->dpdCarrier->getLatestCarrierByReferenceId($this->dpdProductHelper->getDpdParcelshopCarrierId()),
            'sender' => $this->context->cart->id_carrier,
            'shippingAddress' => sprintf('%s %s %s', $address->address1, $address->postcode, $address->country),
            'dpdPublicToken' => $this->connection->getPublicJwtToken(),
            'shopCountryCode' => $this->context->language->iso_code,
            'mapsKey' => $mapsKey,
            'cookieParcelId' => $this->context->cookie->parcelId,
            'oneStepParcelshopUrl' => $link->getModuleLink('dpdconnect', 'OneStepParcelshop'),
            'dpdParcelshopMapUrl' => Configuration::get('dpdconnect_url') . '/parcelshop/map/js',
        ]);

        $templates .= $this->renderTemplate(_PS_MODULE_DIR_ . 'dpdconnect' . DIRECTORY_SEPARATOR . 'views' . DIRECTORY_SEPARATOR . 'templates' . DIRECTORY_SEPARATOR . '1.7' . DIRECTORY_SEPARATOR . '_dpdLocator1.7.tpl');

        file_put_contents('log.txt', print_r($this->context->cookie, true));
        return $templates;
    }

    public function handleRequest(array $requestParams = [])
    {
        // Add custom validation to check if a parcelshop is selected when parcelshop carrier is chosen
        if ($this->isReachable() && isset($requestParams['confirmDeliveryOption'])) {
            $selectedDeliveryOption = $this->getCheckoutSession()->getSelectedDeliveryOption();

            // Delivery option always ends with a ','. Remove this so we can use it for comparing
            $carrierId = str_replace(',', '', $selectedDeliveryOption);

            // Check if selected carrier is parcelshop carrier
            if ((int)$carrierId === (int)$this->dpdCarrier->getLatestCarrierByReferenceId($this->dpdProductHelper->getDpdParcelshopCarrierId())) {

                // Check if parcelshop id is set in cookie
                if (empty($this->context->cookie->parcelId)) {
                    $this->setComplete(false);
                    $controller = $this->context->controller;
                    if (isset($controller)) {
                        $controller->errors[] = 'Please select a parcelshop';
                    }

                    return;
                }
            }
        }

        parent::handleRequest($requestParams);
    }
}
