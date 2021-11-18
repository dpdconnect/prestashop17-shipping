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
use Tab;
use Tools;
use Language;
use HelperForm;
use Configuration;
use AdminController;

class DpdHelper
{
    const MODULENAME = 'dpdconnect';

    public function displayConfigurationForm($module, $formAccountSettings, $formAdress, $productSettings, $advancedSettings, $submit)
    {
        // Get the default language id of the shop
        $default_lang_id = (int)Configuration::get('PS_LANG_DEFAULT');

        // Set al the fields of the form
        $fields_form[0]['form'] = $formAccountSettings;
        $fields_form[1]['form'] = $formAdress;
        $fields_form[2]['form'] = $productSettings;
        $fields_form[3]['form'] = $advancedSettings;
        $fields_form[4]['form'] = $submit;

        $helperForm = new HelperForm();
        $helperForm->module = $module;
        $helperForm->name_controller = $module->name;
        $helperForm->token = Tools::getAdminTokenLite('AdminModules');
        $helperForm->currentIndex = AdminController::$currentIndex.'&configure='.$module->name;

        // Language
        $helperForm->default_form_language = $default_lang_id;
        $helperForm->allow_employee_form_lang = $default_lang_id;

        // Title
        $helperForm->title = $module->displayName;
        $helperForm->show_toolbar = true;
        $helperForm->toolbar_scroll = true;
        $helperForm->submit_action = 'submit'.$module->name;

        // Load current value
        $helperForm->fields_value['dpdconnect_username'] = Configuration::get('dpdconnect_username');
        $helperForm->fields_value['dpdconnect_password'] = Configuration::get('dpdconnect_password');
        $helperForm->fields_value['dpdconnect_depot'] = Configuration::get('dpdconnect_depot');
        $helperForm->fields_value['company'] = Configuration::get('dpdconnect_company');
        $helperForm->fields_value['street'] = Configuration::get('dpdconnect_street');
        $helperForm->fields_value['postalcode'] = Configuration::get('dpdconnect_postalcode');
        $helperForm->fields_value['place'] = Configuration::get('dpdconnect_place');
        $helperForm->fields_value['country'] = Configuration::get('dpdconnect_country');
        $helperForm->fields_value['email'] = Configuration::get('dpdconnect_email');
        $helperForm->fields_value['vatnumber'] = Configuration::get('dpdconnect_vatnumber');
        $helperForm->fields_value['eorinumber'] = Configuration::get('dpdconnect_eorinumber');
        $helperForm->fields_value['spr'] = Configuration::get('dpdconnect_spr');
        $helperForm->fields_value['maps_key'] =  Configuration::get('dpdconnect_maps_key');
        $helperForm->fields_value['use_dpd_key'] =  Configuration::get('dpdconnect_use_dpd_key');
        $helperForm->fields_value['default_product_hcs'] =  Configuration::get('dpdconnect_default_product_hcs');
        $helperForm->fields_value['default_product_weight'] =  Configuration::get('dpdconnect_default_product_weight');
        $helperForm->fields_value['default_product_country_of_origin'] =  Configuration::get('dpdconnect_default_product_country_of_origin');
        $helperForm->fields_value['country_of_origin_feature'] =  Configuration::get('dpdconnect_country_of_origin_feature');
        $helperForm->fields_value['customs_value_feature'] =  Configuration::get('dpdconnect_customs_value_feature');
        $helperForm->fields_value['hs_code_feature'] =  Configuration::get('dpdconnect_hs_code_feature');
        $helperForm->fields_value['age_check_attribute'] =  Configuration::get('dpdconnect_age_check_attribute');
        $helperForm->fields_value['dpdconnect_url'] = Configuration::get('dpdconnect_url');
        $helperForm->fields_value['callback_url'] =  Configuration::get('dpdconnect_callback_url');
        $helperForm->fields_value['async_treshold'] =  Configuration::get('dpdconnect_async_treshold');

        return $helperForm->generateForm($fields_form);
    }

    public function installDB()
    {
            $query = file_get_contents(__DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'install' . DIRECTORY_SEPARATOR . 'install.sql');


            $query = preg_replace('/_PREFIX_/', _DB_PREFIX_, $query);

            return Db::getInstance()->execute($query);
    }

    public function update()
    {
        if (
            Configuration::get('dpdconnect_v1_3_update') !== '1' &&
            version_compare(\dpdconnect::VERSION, '1.2', '>')
        ) {
            // Mark old dpd carriers as deprecated
            $deprecatedCarrierIds = [];
            $deprecatedCarrierIds[] = Configuration::get('dpdconnect_predict');
            $deprecatedCarrierIds[] = Configuration::get('dpdconnect_parcelshop');
            $deprecatedCarrierIds[] = Configuration::get('dpdconnect_saturday');
            $deprecatedCarrierIds[] = Configuration::get('dpdconnect_classic_saturday');
            $deprecatedCarrierIds[] = Configuration::get('dpdconnect_classic');
            $deprecatedCarrierIds[] = Configuration::get('dpdconnect_guarantee18');
            $deprecatedCarrierIds[] = Configuration::get('dpdconnect_express12');
            $deprecatedCarrierIds[] = Configuration::get('dpdconnect_express10');

            foreach ($deprecatedCarrierIds as $deprecatedCarrierId) {
                $deprecatedCarrier = new \Carrier($deprecatedCarrierId);

                if ($deprecatedCarrier->id == 0) {
                    continue;
                }

                $deprecatedCarrier->name = '*DEPRECATED*' . $deprecatedCarrier->name . '*DEPRECATED*';
                $deprecatedCarrier->active = 0;
                $deprecatedCarrier->deleted = 0;
                $deprecatedCarrier->delay[Configuration::get('PS_LANG_DEFAULT')] = 'DEPRECATED';
                $deprecatedCarrier->update();
            }

            // Retrieve old maps key if it exists
            $oldMapsKey = '';
            if (Configuration::get('gmaps_client_key')) {
                $oldMapsKey = Configuration::get('gmaps_client_key');
            } elseif (Configuration::get('gmaps_server_key')) {
                $oldMapsKey = Configuration::get('gmaps_server_key');
            }

            Configuration::updateValue('dpdconnect_maps_key', $oldMapsKey);

            if ($oldMapsKey) {
                Configuration::updateValue('dpdconnect_use_dpd_key', 0);
            } else {
                Configuration::updateValue('dpdconnect_use_dpd_key', 1);
            }

            // Finish update
            Configuration::updateValue('dpdconnect_v1_3_update', '1');
        }

        return true;
    }

    public function installControllers($controllerNames)
    {
        foreach ($controllerNames as $controllerName => $userReadableName) {
            $tab = new Tab();
            $tab->active = 1;
            $tab->class_name = $controllerName;
            // Hide the tab from the menu.
            $tab->id_parent = -1;
            $tab->module = self::MODULENAME;
            $tab->name = array();
            foreach (Language::getLanguages(true) as $lang) {
                $tab->name[$lang['id_lang']] = $userReadableName;
            }
            $tab->add();
        }
        return true;
    }

    public function cacheGeoData($parcelPredict, $params, $cookie)
    {
        $cookie->address = serialize(
            array(
                'postcode' => $params['address']->postcode,
                'city' => $params['address']->city
            )
        );

        $geoData = $parcelPredict->getGeoData($params['address']->postcode, $params['address']->city);
        $parcelShops = $parcelPredict->getParcelShops($params['address']->postcode, $params['address']->city);
//      var_dump($parcelShops); die;
        $cookie->geoData = serialize($geoData);
        $cookie->parcelShops = base64_encode(json_encode($parcelShops));
//      var_dump(($cookie->parcelShops)); die;


        unset($cookie->parcelId);
    }
}
