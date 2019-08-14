<?php

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

require_once (_PS_MODULE_DIR_ . 'dpdconnect' . DIRECTORY_SEPARATOR . 'dpdconnect.php');

class AdminCarrierWizardController extends AdminCarrierWizardControllerCore
{
    public $dpdCarrier;

    public function __construct()
    {
        $dpdconnect = new dpdconnect();
        $this->dpdCarrier = $dpdconnect->dpdCarrier();

        parent::__construct();
    }

    public function getStepOneFieldsValues($carrier)
    {
        return array(
            'id_carrier' => $this->getFieldValue($carrier, 'id_carrier'),
            'name' => $this->getFieldValue($carrier, 'name'),
            'delay' => $this->getFieldValue($carrier, 'delay'),
            'grade' => $this->getFieldValue($carrier, 'grade'),
            'url' => $this->getFieldValue($carrier, 'url'),
            //own code
            'showfromtime' => Configuration::get('dpdconnect_saturday_showfromtime'),
            'showfromday' => Configuration::get('dpdconnect_saturday_showfromday'),
            'showtillday' => Configuration::get('dpdconnect_saturday_showtillday'),
            'showtilltime' => Configuration::get('dpdconnect_saturday_showtilltime'),
        );
    }


    public function renderStepOne($carrier)
    {
        $this->fields_form = array(
            'form' => array(
                'id_form' => 'step_carrier_general',
                'input' => array(
                    array(
                        'type' => 'text',
                        'label' => $this->l('Carrier name'),
                        'name' => 'name',
                        'required' => true,
                        'hint' => array(
                            sprintf($this->l('Allowed characters: letters, spaces and "%s".'), '().-'),
                            $this->l('The carrier\'s name will be displayed during checkout.'),
                            $this->l('For in-store pickup, enter 0 to replace the carrier name with your shop name.')
                        )
                    ),
                    array(
                        'type' => 'text',
                        'label' => $this->l('Transit time'),
                        'name' => 'delay',
                        'lang' => true,
                        'required' => true,
                        'maxlength' => 512,
                        'hint' => $this->l('The estimated delivery time will be displayed during checkout.')
                    ),
                    array(
                        'type' => 'text',
                        'label' => $this->l('Speed grade'),
                        'name' => 'grade',
                        'required' => false,
                        'size' => 1,
                        'hint' => $this->l('Enter "0" for a longest shipping delay, or "9" for the shortest shipping delay.')
                    ),
                    array(
                        'type' => 'logo',
                        'label' => $this->l('Logo'),
                        'name' => 'logo'
                    ),
                    array(
                        'type' => 'text',
                        'label' => $this->l('Tracking URL'),
                        'name' => 'url',
                        'hint' => $this->l('Delivery tracking URL: Type \'@\' where the tracking number should appear. It will be automatically replaced by the tracking number.'),
                        'desc' => $this->l('For example: \'http://example.com/track.php?num=@\' with \'@\' where the tracking number should appear.')
                    ),

                )
            )
        );
        //own code
        if ($carrier->id == $this->dpdCarrier->getLatestCarrierByReferenceId(Configuration::get('dpdconnect_saturday'))
            || $carrier->id ==  $this->dpdCarrier->getLatestCarrierByReferenceId(Configuration::get('dpdconnect_classic_saturday')) ) {
            $this->fields_form['form']['input'][] = array(
                'required' => true,
                'type' => 'select',
                'label' => $this->l('Show from day'),
                'name' => 'showfromday',
                'options' => array(
                    'query' => array(
                        array('key' => '', 'name' => $this->l('Select a Day')),
                        array('key' => 'monday', 'name' => $this->l('Monday')),
                        array('key' => 'tuesday', 'name' => $this->l('Tuesday')),
                        array('key' => 'wednesday', 'name' => $this->l('Wednesday')),
                        array('key' => 'thursday', 'name' => $this->l('Thursday')),
                        array('key' => 'friday', 'name' => $this->l('Friday')),
                        array('key' => 'saturday', 'name' => $this->l('Saturday')),
                        array('key' => 'sunday', 'name' => $this->l('Sunday')),
                    ),
                    'id' => 'key',
                    'name' => 'name'
                )
            );

            $this->fields_form['form']['input'][] = array(
                'required' => true,
                'type' => 'text',
                'label' => $this->l('Show from time'),
                'name' => 'showfromtime',
                'hint' => $this->l('Time in 24h format'),
                'desc' => $this->l('For example: 18:00')
            );
            $this->fields_form['form']['input'][] = array(
                'required' => true,
                'type' => 'select',
                'label' => $this->l('Show till day'),
                'name' => 'showtillday',
                'options' => array(
                    'query' => array(
                        array('key' => '', 'name' => $this->l('Select a Day')),
                        array('key' => 'monday', 'name' => $this->l('Monday')),
                        array('key' => 'tuesday', 'name' => $this->l('Tuesday')),
                        array('key' => 'wednesday', 'name' => $this->l('Wednesday')),
                        array('key' => 'thursday', 'name' => $this->l('Thursday')),
                        array('key' => 'friday', 'name' => $this->l('Friday')),
                        array('key' => 'saturday', 'name' => $this->l('Saturday')),
                        array('key' => 'sunday', 'name' => $this->l('Sunday')),
                    ),
                    'id' => 'key',
                    'name' => 'name'
                )
            );

            $this->fields_form['form']['input'][] = array(
                'required' => true,
                'type' => 'text',
                'label' => $this->l('Show till time'),
                'name' => 'showtilltime',
                'hint' => $this->l('Time in 24h format'),
                'desc' => $this->l('For example: 18:00')
            );
        }
        // prestashops code
        $tpl_vars = array('max_image_size' => (int)Configuration::get('PS_PRODUCT_PICTURE_MAX_SIZE') / 1024 / 1024);
        $fields_value = $this->getStepOneFieldsValues($carrier);
        return parent::renderGenericForm(array('form' => $this->fields_form), $fields_value, $tpl_vars);
    }

    public function ajaxProcessFinishStep()
    {
        $return = array('has_error' => false);
        // own code
        if ($this->dpdCarrier->ifHasSameReferenceId(Tools::getValue('id_carrier'), Configuration::get('dpdconnect_saturday'))
            || $this->dpdCarrier->ifHasSameReferenceId(Tools::getValue('id_carrier'), Configuration::get('dpdconnect_classic_saturday'))  ) {
            if (Tools::getValue('showfromday') === '') {
                $return['has_error'] = true;
                $return['errors'][] = $this->l('Show from day is not set!');
            } elseif (Tools::getValue('showfromtime') == '') {
                $return['has_error'] = true;
                $return['errors'][] = $this->l('Show from time is not set!');
            } elseif (Tools::getValue('showtillday') === '') {
                $return['has_error'] = true;
                $return['errors'][] = $this->l('Show till day is not set!');
            } elseif (Tools::getValue('showtilltime') == '') {
                $return['has_error'] = true;
                $return['errors'][] = $this->l('Show till time is not set!');
            } else {
                Configuration::updateValue('dpdconnect_saturday_showfromday', Tools::getValue('showfromday'));
                Configuration::updateValue('dpdconnect_saturday_showfromtime', Tools::getValue('showfromtime'));
                Configuration::updateValue('dpdconnect_saturday_showtillday', Tools::getValue('showtillday'));
                Configuration::updateValue('dpdconnect_saturday_showtilltime', Tools::getValue('showtilltime'));
            }
        }
        //prestashops code
        if (!$this->tabAccess['edit']) {
            $return = array(
                'has_error' =>  true,
                $return['errors'][] = Tools::displayError('You do not have permission to use this wizard.')
            );
        } else {
            $this->validateForm(false);
            if ($id_carrier = Tools::getValue('id_carrier')) {
                $current_carrier = new Carrier((int)$id_carrier);

                // if update we duplicate current Carrier
                /** @var Carrier $new_carrier */
                $new_carrier = $current_carrier->duplicateObject();

                if (Validate::isLoadedObject($new_carrier)) {
                    // Set flag deteled to true for historization
                    $current_carrier->deleted = true;
                    $current_carrier->update();

                    // Fill the new carrier object
                    $this->copyFromPost($new_carrier, $this->table);
                    $new_carrier->position = $current_carrier->position;
                    $new_carrier->update();

                    $this->updateAssoShop((int)$new_carrier->id);
                    $this->duplicateLogo((int)$new_carrier->id, (int)$current_carrier->id);
                    $this->changeGroups((int)$new_carrier->id);

                    //Copy default carrier
                    if (Configuration::get('PS_CARRIER_DEFAULT') == $current_carrier->id) {
                        Configuration::updateValue('PS_CARRIER_DEFAULT', (int)$new_carrier->id);
                    }

                    // Call of hooks
                    Hook::exec('actionCarrierUpdate', array(
                        'id_carrier' => (int)$current_carrier->id,
                        'carrier' => $new_carrier
                    ));
                    $this->postImage($new_carrier->id);
                    $this->changeZones($new_carrier->id);
                    $new_carrier->setTaxRulesGroup((int)Tools::getValue('id_tax_rules_group'));
                    $carrier = $new_carrier;
                }
            } else {
                $carrier = new Carrier();
                $this->copyFromPost($carrier, $this->table);
                if (!$carrier->add()) {
                    $return['has_error'] = true;
                    $return['errors'][] = $this->l('An error occurred while saving this carrier.');
                }
            }

            if ($carrier->is_free) {
                //if carrier is free delete shipping cost
                $carrier->deleteDeliveryPrice('range_weight');
                $carrier->deleteDeliveryPrice('range_price');
            }
            if (Validate::isLoadedObject($carrier)) {
                if (!$this->changeGroups((int)$carrier->id)) {
                    $return['has_error'] = true;
                    $return['errors'][] = $this->l('An error occurred while saving carrier groups.');
                }

                if (!$this->changeZones((int)$carrier->id)) {
                    $return['has_error'] = true;
                    $return['errors'][] = $this->l('An error occurred while saving carrier zones.');
                }

                if (!$carrier->is_free) {
                    if (!$this->processRanges((int)$carrier->id)) {
                        $return['has_error'] = true;
                        $return['errors'][] = $this->l('An error occurred while saving carrier ranges.');
                    }
                }

                if (Shop::isFeatureActive() && !$this->updateAssoShop((int)$carrier->id)) {
                    $return['has_error'] = true;
                    $return['errors'][] = $this->l('An error occurred while saving associations of shops.');
                }

                if (!$carrier->setTaxRulesGroup((int)Tools::getValue('id_tax_rules_group'))) {
                    $return['has_error'] = true;
                    $return['errors'][] = $this->l('An error occurred while saving the tax rules group.');
                }

                if (Tools::getValue('logo')) {
                    if (Tools::getValue('logo') == 'null' && file_exists(_PS_SHIP_IMG_DIR_.$carrier->id.'.jpg')) {
                        unlink(_PS_SHIP_IMG_DIR_.$carrier->id.'.jpg');
                    } else {
                        $logo = basename(Tools::getValue('logo'));
                        if (!file_exists(_PS_TMP_IMG_DIR_.$logo) || !copy(_PS_TMP_IMG_DIR_.$logo, _PS_SHIP_IMG_DIR_.$carrier->id.'.jpg')) {
                            $return['has_error'] = true;
                            $return['errors'][] = $this->l('An error occurred while saving carrier logo.');
                        }
                    }
                }
                $return['id_carrier'] = $carrier->id;
            }
        }
        die(Tools::jsonEncode($return));
    }
}
