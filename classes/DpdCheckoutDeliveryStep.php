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

    public function __construct(Context $context, TranslatorInterface $translator)
    {
        parent::__construct($context, $translator);
        $this->dpdParcelPredict = new DpdParcelPredict();
        $this->dpdCarrier = new DpdCarrier();
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


        $geoData = $this->dpdParcelPredict->getGeoData($address->postcode, $isoCode);
        $parcelShops = json_encode($this->dpdParcelPredict->getParcelShops($address->postcode, $isoCode));

        // prevent attempts at getting parcelshops before enough data is available
        if (!$address->postcode) {
            return $templates;
        }

        $link = new \Link();

        $parcelShopInfo = array(
            'baseUri' => __PS_BASE_URI__,
            'parcelshopId' => $this->dpdCarrier->getLatestCarrierByReferenceId(Configuration::get("dpdconnect_parcelshop")),
            'sender' => $this->context->cart->id_carrier,
            'key' => Configuration::get('gmaps_client_key'),
            'longitude' => $geoData['longitude'],
            'latitude' => $geoData['latitude'],
            'cookieParcelId' => $this->context->cookie->parcelId,
        );
        $this->context->smarty->assign($parcelShopInfo);
        $this->context->smarty->assign([
            'parcelshops' => $parcelShops,
            'oneStepParcelshopUrl' => $link->getModuleLink('dpdconnect', 'OneStepParcelshop')
        ]);

        $templates .= $this->renderTemplate(_PS_MODULE_DIR_ . 'dpdconnect' . DIRECTORY_SEPARATOR . 'views' . DIRECTORY_SEPARATOR . 'templates' . DIRECTORY_SEPARATOR . '1.7' . DIRECTORY_SEPARATOR . '_dpdLocator1.7.tpl');

        file_put_contents('log.txt', print_r($this->context->cookie, true));
        return $templates;
    }
}
