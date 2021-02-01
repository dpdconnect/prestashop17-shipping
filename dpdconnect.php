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

require_once(_PS_MODULE_DIR_ . 'dpdconnect' . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'autoload.php');

use DpdConnect\classes\JobRepo;
use DpdConnect\classes\DpdHelper;
use DpdConnect\classes\DpdCarrier;
use DpdConnect\classes\enums\JobStatus;
use DpdConnect\classes\enums\ParcelType;
use DpdConnect\classes\DpdShippingList;
use DpdConnect\classes\DpdParcelPredict;
use DpdConnect\classes\DpdAuthentication;
use DpdConnect\classes\DpdLabelGenerator;
use DpdConnect\classes\DpdEncryptionManager;
use DpdConnect\classes\DpdCheckoutDeliveryStep;
use DpdConnect\classes\DpdDeliveryOptionsFinder;

class dpdconnect extends Module
{
    const VERSION = '1.2';

    public $dpdHelper;
    public $dpdCarrier;
    public $dpdParcelPredict;

    private $ownControllers = [
        'AdminDpdLabels' => 'DPD label',
        'AdminDownloadLabel' => 'DPD Download label',
        'AdminDpdShippingList' => 'DPD ShippingList',
        'AdminDpdProductAttributesController' => 'DPD Product Attributes',
    ];
    private $hooks = [
        'displayAdminOrderTabLink',
        'displayAdminOrderTabContent',
        'actionAdminBulkAffectZoneAfter',
        'actionCarrierProcess',
        'displayOrderConfirmation',
        'displayBeforeCarrier',
        'actionValidateOrder',
//        'actionAdminOrdersListingFieldsModifier', DEPRECATED SINCE PRESTASHOP 1.6
        'displayBackOfficeHeader',
    ];


    public function __construct()
    {
        $this->dpdHelper = new DpdHelper();
        $this->dpdCarrier = new DpdCarrier();
        $this->dpdParcelPredict = new DpdParcelPredict();

        // the information about the plugin.
        $this->version = self::VERSION;
        $this->name = "dpdconnect";
        $this->displayName = $this->l("DPD Connect");
        $this->author = "DPD Nederland B.V.";
        $this->tab = 'shipping_logistics';
        $this->limited_countries = ['be', 'lu', 'nl'];

        $this->ps_versions_compliancy = [
            'min' => '1.7',
            'max' => _PS_VERSION_
        ];
        $this->need_instance = 1;
        $this->bootstrap = true;

        parent::__construct();
    }

    /**
     * this is triggered when the plugin is installed
     */
    public function install()
    {
        // Install Tabs
        $parent_tab = new Tab();

        $parent_tab->name[$this->context->language->id] = $this->l('DPD configuration');
        $parent_tab->class_name = 'AdminDpd';
        $parent_tab->id_parent = 0; // Home tab
        $parent_tab->module = $this->name;
        $parent_tab->add();

        $attributesTab = new Tab();
        $attributesTab->name[$this->context->language->id] = $this->l('DPD Product Attributes');
        $attributesTab->class_name = 'AdminDpdProductAttributes';
        $attributesTab->id_parent = $parent_tab->id;
        $attributesTab->module = $this->name;
        $attributesTab->add();

        $batchTab = new Tab();
        $batchTab->name[$this->context->language->id] = $this->l('Batches');
        $batchTab->class_name = 'AdminDpdBatches';
        $batchTab->id_parent = $parent_tab->id;
        $batchTab->module = $this->name;
        $batchTab->add();

        $jobTab = new Tab();
        $jobTab->name[$this->context->language->id] = $this->l('Jobs');
        $jobTab->class_name = 'AdminDpdJobs';
        $jobTab->id_parent = $parent_tab->id;
        $jobTab->module = $this->name;
        $jobTab->add();

        if (parent::install()) {
            Configuration::updateValue('dpd', 'dpdconnect');
            Configuration::updateValue('dpdconnect_parcel_limit', 12);
        }
        if (!$this->dpdHelper->installDB()) {
            //TODO create log that database could not be installed.
            return false;
        }
        foreach ($this->hooks as $hookName) {
            if (!$this->registerHook($hookName)) {
            //TODO create a log that hook could not be installed.
                return false;
            }
        }
        if (!$this->dpdHelper->installControllers($this->ownControllers)) {
            //TODO create a log that hook could not be installed.
            return false;
        }
        if (!$this->dpdCarrier->createCarriers()) {
            //TODO create a log that the carrier could not be installed
            return false;
        }
        return true;
    }

    /**
     * this is triggered when the plugin is uninstalled
     */
    public function uninstall()
    {
        if (!parent::uninstall()) {
            return false;
        } else {
            // Uninstall Tabs
            $moduleTabs = Tab::getCollectionFromModule($this->name);
            if (!empty($moduleTabs)) {
                foreach ($moduleTabs as $moduleTab) {
                    $moduleTab->delete();
                }
            }
            $this->dpdCarrier->deleteCarriers();
            Configuration::updateValue('dpd', 'not installed');
            return true;
        }
    }

    public function getContent()
    {
        $output = '';

        if (Tools::isSubmit('submit'.$this->name)) {
            $connectusername = strval(Tools::getValue("dpdconnect_username"));
            $connectpassword = strval(Tools::getValue("dpdconnect_password"));
            if ($connectpassword == null) {
                $connectpassword = Configuration::get('dpdconnect_password');
            } else {
                $connectpassword = DpdEncryptionManager::encrypt($connectpassword);
            }
            $depot = strval(Tools::getValue("dpdconnect_depot"));
            $company = strval(Tools::getValue("company"));
            $street = strval(Tools::getValue("street"));
            $postalcode = strval(Tools::getValue("postalcode"));
            $place = strval(Tools::getValue("place"));
            $country = strval(Tools::getValue("country"));
            $email = strval(Tools::getValue("email"));
            $vatnumber = strval(Tools::getValue("vatnumber"));
            $eorinumber = strval(Tools::getValue("eorinumber"));
            $spr = strval(Tools::getValue("spr"));
            $accountType = Tools::getValue('account_type');
            $gmapsClientKey = Tools::getValue('gmaps_client_key');
            $gmapsServerKey = Tools::getValue('gmaps_server_key');
            $defaultProductHcs = Tools::getValue('default_product_hcs');
            $defaultProductWeight = Tools::getValue('default_product_weight');
            $defaultProductCountryOfOrigin = Tools::getValue('default_product_country_of_origin');
            $countryOfOriginFeature = Tools::getValue('country_of_origin_feature');
            $customsValueFeature = Tools::getValue('customs_value_feature');
            $hsCodeFeature = Tools::getValue('hs_code_feature');
            $connecturl = strval(Tools::getValue("dpdconnect_url"));
            $callbackUrl = Tools::getValue('callback_url');
            $asyncTreshold = Tools::getValue('async_treshold');

            if (!(
               empty($company) ||
               empty($street) ||
               empty($postalcode) ||
               empty($place) ||
               empty($country) ||
               empty($email) ||
               empty($accountType)
            )) {
                Configuration::updateValue('gmaps_client_key', $gmapsClientKey);
                Configuration::updateValue('gmaps_server_key', $gmapsServerKey);
                Configuration::updateValue('dpdconnect_username', $connectusername);
                if ($connectpassword) {
                    Configuration::updateValue('dpdconnect_password', $connectpassword);
                }
                Configuration::updateValue('dpdconnect_depot', $depot);
                Configuration::updateValue('dpdconnect_account_type', $accountType);
                Configuration::updateValue('dpdconnect_company', $company);
                Configuration::updateValue('dpdconnect_street', $street);
                Configuration::updateValue('dpdconnect_postalcode', $postalcode);
                Configuration::updateValue('dpdconnect_place', $place);
                Configuration::updateValue('dpdconnect_country', $country);
                Configuration::updateValue('dpdconnect_email', $email);
                Configuration::updateValue('dpdconnect_vatnumber', $vatnumber);
                Configuration::updateValue('dpdconnect_eorinumber', $eorinumber);
                Configuration::updateValue('dpdconnect_spr', $spr);
                Configuration::updateValue('dpdconnect_default_product_hcs', $defaultProductHcs);
                Configuration::updateValue('dpdconnect_default_product_weight', $defaultProductWeight);
                Configuration::updateValue('dpdconnect_default_product_country_of_origin', $defaultProductCountryOfOrigin);
                Configuration::updateValue('dpdconnect_country_of_origin_feature', $countryOfOriginFeature);
                Configuration::updateValue('dpdconnect_customs_value_feature', $customsValueFeature);
                Configuration::updateValue('dpdconnect_hs_code_feature', $hsCodeFeature);
                Configuration::updateValue('dpdconnect_url', $connecturl);
                Configuration::updateValue('dpdconnect_callback_url', $callbackUrl);
                Configuration::updateValue('dpdconnect_async_treshold', $asyncTreshold);
                $output .= $this->displayConfirmation($this->l('Settings updated'));

                $this->dpdCarrier->setCarrierForAccountType();
            } else {
                $output .= $this->displayError($this->l('Invalid Configuration value'));
            }
        }

        $formAccountSettings = [
            'legend' => [
                'title' => $this->l('Account Settings'),
            ],
            'input' => [
                [
                    'type' => 'text',
                    'label' => $this->l('DPD-Connect username'),
                    'name' => 'dpdconnect_username',
                    'required' => true
                ],
                [
                    'type' => 'password',
                    'label' => $this->l('DPD-Connect password'),
                    'name' => 'dpdconnect_password',
                    'required' => true
                ],
                [
                    'type' => 'text',
                    'label' => $this->l('DPD-Connect depot'),
                    'name' => 'dpdconnect_depot',
                    'required' => true
                ],
                [
                    'required' => true,
                    'type' => 'select',
                    'label' => $this->l('DPD Account type'),
                    'name' => 'account_type',
                    'options' => [
                        'query' => [
                            ['key' => '0', 'name' => $this->l('Please select DPD Account type')],
                            ['key' => 'b2b', 'name' => $this->l('B2B')],
                            ['key' => 'b2c', 'name' => $this->l('B2C')],
                        ],
                        'id' => 'key',
                        'name' => 'name'
                    ]
                ],
                [
                    'type' => 'text',
                    'label' => $this->l('Google Maps Static & Javascript API key'),
                    'name' => 'gmaps_client_key',
                    'required' => true
                ],
                [
                    'type' => 'text',
                    'label' => $this->l('Google Maps Geocoding API key'),
                    'name' => 'gmaps_server_key',
                    'required' => true
                ],
            ],
        ];

        $formAdres = [
            'legend' => [
                'title' => $this->l('Shipping Address'),
            ],
            'input' => [
                [
                    'type' => 'text',
                    'label' => $this->l('Company name'),
                    'name' => 'company',
                    'required' => true,
                ],
                [
                    'type' => 'text',
                    'label' => $this->l('Street + house number'),
                    'name' => 'street',
                    'required' => true
                ],
                [
                    'type' => 'text',
                    'label' => $this->l('Postal Code'),
                    'name' => 'postalcode',
                    'required' => true
                ],
                [
                    'type' => 'text',
                    'label' => $this->l('Place'),
                    'name' => 'place',
                    'required' => true
                ],
                [
                    'type' => 'text',
                    'label' => $this->l('Country code'),
                    'name' => 'country',
                    'required' => true
                ],
                [
                    'type' => 'text',
                    'label' => $this->l('Email'),
                    'name' => 'email',
                    'required' => true
                ],
                [
                    'type' => 'text',
                    'label' => $this->l('Vat Number'),
                    'name' => 'vatnumber',
                    'required' => false
                ],
                [
                    'type' => 'text',
                    'label' => $this->l('Eori Number'),
                    'name' => 'eorinumber',
                    'required' => false
                ],
                [
                    'type' => 'text',
                    'label' => $this->l('HMRC number'),
                    'hint' => $this->l('Mandatory if the value of the parcel is ≤ £ 135.'),
                    'name' => 'spr',
                    'required' => false
                ],
            ],
        ];

        $features = Feature::getFeatures($this->context->language->id);
        $features[] = null;

        $productSettings = [
            'legend' => [
                'title' => $this->l('Product settings'),
            ],
            'description' => 'Configure what features or default will be used for products. If features or defaults are not a solution, use the "DPD Product Attributes" tab instead.',
            'input' => [
                [
                    'type' => 'text',
                    'label' => $this->l('Default product weight'),
                    'name' => 'default_product_weight',
                    'required' => false
                ],
                [
                    'type' => 'select',
                    'label' => $this->l('Customs value Feature'),
                    'hint' => $this->l('Select the product feature where the customs value is defined. If features are not used for customs value, leave empty to use DPD Product attributes or regular product price.'),
                    'name' => 'customs_value_feature',
                    'options' => [
                        'query' => $features,
                        'id' => 'id_feature',
                        'name' => 'name',
                    ],
                    'required' => false
                ],
                [
                    'type' => 'select',
                    'label' => $this->l('Country of origin Feature'),
                    'hint' => $this->l('Select the product feature where the product of origin is defined. If features are not used for country of origin, leave empty.'),
                    'name' => 'country_of_origin_feature',
                    'options' => [
                        'query' => $features,
                        'id' => 'id_feature',
                        'name' => 'name',
                    ],
                    'required' => false
                ],
                [
                    'type' => 'text',
                    'label' => $this->l('Default Country of Origin'),
                    'name' => 'default_product_country_of_origin',
                    'required' => false
                ],
                [
                    'type' => 'select',
                    'label' => $this->l('Harmonized System Code Feature'),
                    'hint' => $this->l('Select the product feature where the Harmonized System Code is defined. If features are not used for harmonized system codes, leave empty.'),
                    'name' => 'hs_code_feature',
                    'options' => [
                        'query' => $features,
                        'id' => 'id_feature',
                        'name' => 'name',
                    ],
                    'required' => false
                ],
                [
                    'type' => 'text',
                    'label' => $this->l('Default Harmonized System Code'),
                    'name' => 'default_product_hcs',
                    'required' => false
                ],
            ],
        ];

        $advancedSettings = [
            'legend' => [
                'title' => $this->l('Advanced settings'),
            ],
            'description' => 'Settings below can be left empty, they are used for development and debugging purposes.',
            'input' => [
                [
                    'type' => 'text',
                    'label' => $this->l('DPD-Connect url'),
                    'name' => 'dpdconnect_url',
                    'required' => false
                ],
                [
                    'type' => 'text',
                    'label' => $this->l('Callback URL'),
                    'name' => 'callback_url',
                    'required' => false
                ],
                [
                    'type' => 'text',
                    'label' => $this->l('Async treshold'),
                    'name' => 'async_treshold',
                    'hint' => 'Max 10',
                    'placeholder' => '10',
                    'required' => false
                ],
            ],
        ];

        $submit = [
            'submit' => [
                'title' => $this->l('Save'),
                'class' => 'btn btn-default pull-right'
            ],
        ];

        return $output . $this->dpdHelper->displayConfigurationForm($this, $formAccountSettings, $formAdres, $productSettings, $advancedSettings, $submit);
    }

    public function hookDisplayAdminOrderTabLink($params)
    {
        $orderId = Tools::getValue('id_order');
        $parcelShopId = $this->dpdParcelPredict->getParcelShopId($orderId);

        if ($this->dpdParcelPredict->checkIfDpdSending($orderId)) {
            $this->context->smarty->assign([
                'isDpdCarrier' => $this->dpdParcelPredict->checkifParcelCarrier($orderId),
                'dpdParcelshopId' => $parcelShopId
            ]);
            return $this->display(__FILE__, '_adminOrderTab.tpl');
        }
    }

    public function hookDisplayAdminOrderTabContent($params)
    {
        $orderId = Tools::getValue('id_order');
        $parcelShopId = $this->dpdParcelPredict->getParcelShopId($orderId);
        $parcelCarrier = $this->dpdParcelPredict->checkifParcelCarrier($orderId);

        if ($this->dpdParcelPredict->checkIfDpdSending($orderId)) {
            $link = new LinkCore;
            $urlGenerateLabel = $link->getAdminLink('AdminDpdLabels');
            $urlGenerateLabel = $urlGenerateLabel . '&ids_order[]=' . $orderId;

            $urlGenerateReturnLabel = $urlGenerateLabel . '&return=true';



            $this->context->smarty->assign([
                'parcelCarrier' => $parcelCarrier,
                'parcelShopId' => $parcelShopId,
                'number' => DpdLabelGenerator::countLabels($orderId),
                'isInDb' => DpdLabelGenerator::getLabelOutOfDb($orderId),
                'urlGenerateLabel' => $urlGenerateLabel,
                'urlGenerateReturnLabel' => $urlGenerateReturnLabel,
                'isReturnInDb'=> DpdLabelGenerator::getLabelOutOfDb($orderId, true),
                'deleteGeneratedLabel' => $urlGenerateLabel . '&delete=true',
                'deleteGeneratedRetourLabel' => $urlGenerateReturnLabel . '&delete=true'
            ]);
            return $this->display(__FILE__, '_adminOrderTabLabels.tpl');
        }
    }


    public function hookActionCarrierProcess($params)
    {
         //this adds parcel-id to the coockie when the carrier is used
//      if((int)$params['cart']->id_carrier === (int)$this->dpdCarrier->getLatestCarrierByReferenceId(Configuration::get("dpdconnect_parcelshop"))){
//          if(!empty($_POST['parcel-id']) && !($_POST['parcel-id'] == '')) {
//              $this->context->cookie->parcelId = $_POST['parcel-id'];
//            }else{
//              $this->context->controller->errors[] = $this->l('Please select a parcelshop');
//            }
//        }

        if ((int)$params['cart']->id_carrier === (int)$this->dpdCarrier->getLatestCarrierByReferenceId(Configuration::get("dpdconnect_parcelshop"))) {
            if (empty($this->context->cookie->parcelId) || $this->context->cookie->parcelId == '') {
                $this->context->controller->errors[] = $this->l('Please select a parcelshop');
            }
        }
    }

    public function hookDisplayOrderConfirmation($params)
    {
        $order = $params['order'];
        if ((int)$order->id_carrier === (int)$this->dpdCarrier->getLatestCarrierByReferenceId(Configuration::get("dpdconnect_parcelshop"))) {
            if (!empty($this->context->cookie->parcelId) && !($this->context->cookie->parcelId == '')) {
                Db::getInstance()->insert('parcelshop', [
                    'order_id' => pSQL($order->id),
                    'parcelshop_id' => pSQL($params['cookie']->parcelId)
                ]);
                unset($this->context->cookie->parcelId) ;
            }
        }
    }

    // DEPRECATED SINCE PRESTASHOP 1.6
//    public function hookActionAdminOrdersListingFieldsModifier($list)
//    {
//        $optionsOrderStatus = [1 => 'Select'];
//
//        if (isset($list['select'])) {
//            $list['select'] .= ', label.order_id AS dpd_label';
//            $list['join'] .= sprintf(
//                ' LEFT JOIN %sdpdshipment_label AS label
//                         ON label.order_id = a.id_order
//                          AND label.retour = "0"
//                ',
//                _DB_PREFIX_
//            );
//
//            $list['select'] .= ', CONCAT_WS(",", latest_job.order_id, latest_job.status) AS dpd_job';
//            $list['join'] .= sprintf(
//                '
//                    LEFT JOIN (SELECT max(id_dpd_jobs) AS max_id,
//                                      order_id,
//                                      status,
//                                      type
//                                 FROM %sdpd_jobs
//                                WHERE type = "%s"
//                             GROUP BY order_id) AS latest_job
//                           ON (latest_job.order_id = a.id_order)
//                ',
//                _DB_PREFIX_,
//                ParcelType::TYPEREGULAR
//            );
//
//            $list['select'] .= ', return_label.order_id AS dpd_return_label';
//            $list['join'] .= sprintf(
//                '
//                    LEFT JOIN %sdpdshipment_label AS return_label
//                           ON return_label.order_id = a.id_order
//                          AND return_label.retour = "1"
//                ',
//                _DB_PREFIX_
//            );
//
//            $list['select'] .= ', CONCAT_WS(",", latest_return_job.order_id, latest_return_job.status) AS dpd_return_job';
//            $list['join'] .= sprintf(
//                '
//                    LEFT JOIN (SELECT max(id_dpd_jobs) AS max_id,
//                                      order_id,
//                                      status,
//                                      type
//                                 FROM %sdpd_jobs
//                                WHERE type = "%s"
//                             GROUP BY order_id) AS latest_return_job
//                           ON (latest_return_job.order_id = a.id_order)
//                ',
//                _DB_PREFIX_,
//                ParcelType::TYPERETURN
//            );
//        }
//
//        /**
//         * DPD label
//         */
//        $list['fields']['dpd_label'] = [
//            'title' => 'DPD Label',
//            'align' => 'text-center',
//            'filter_key' => 'label!order_id',
//            'callback' => 'renderPdfColumn',
//            'orderby' => false,
//            'type' => 'select',
//            'list' => $optionsOrderStatus,
//            'callback_object' => Module::getInstanceByName($this->name)
//        ];
//
//        /**
//         * DPD latest job
//         */
//        $list['fields']['dpd_job'] = [
//            'title' => 'DPD Job',
//            'align' => 'text-center',
//            'filter_key' => 'a!id_order',
//            'callback' => 'renderJobColumn',
//            'orderby' => false,
//            'type' => 'select',
//            'list' => $optionsOrderStatus,
//            'callback_object' => Module::getInstanceByName($this->name)
//        ];
//
//        /**
//         * DPD return label
//         */
//        $list['fields']['dpd_return_label'] = [
//            'title' => 'DPD return Label',
//            'align' => 'text-center',
//            'filter_key' => 'label!order_id',
//            'callback' => 'renderPdfReturnColumn',
//            'orderby' => false,
//            'type' => 'select',
//            'list' => $optionsOrderStatus,
//            'callback_object' => Module::getInstanceByName($this->name)
//        ];
//
//        /**
//         * DPD latest return job
//         */
//        $list['fields']['dpd_return_job'] = [
//            'title' => 'DPD return Job',
//            'align' => 'text-center',
//            'filter_key' => 'a!id_order',
//            'callback' => 'renderJobColumn',
//            'orderby' => false,
//            'type' => 'select',
//            'list' => $optionsOrderStatus,
//            'callback_object' => Module::getInstanceByName($this->name)
//        ];
//    }

    public function renderJobColumn($jobData)
    {
        if (!$jobData) {
            return;
        }

        list($id, $status) = explode(',', $jobData);

        $link = new LinkCore;
        $url = $link->getAdminLink('AdminDpdJobs') . sprintf('&submitFilterdpd_jobs=0&job_id=%s#dpd_jobs', $id);
        return sprintf('<a class="dpdTagUrl" href=%s>%s</a>', $url, JobStatus::tag($status));

        return;
    }

    public function renderPdfReturnColumn($orderId)
    {
        return $this->renderPdfColumn($orderId, true);
    }

    public function renderPdfColumn($orderId, $return = false)
    {
        if (!$orderId) {
            return;
        }

        $link = new LinkCore;
        $url = $link->getAdminLink('AdminDpdLabels');
        $url = $url . '&ids_order[]=' . $orderId;
        if ($return) {
            $url .= '&return=true';
        }
        return sprintf('<a href=%s><span class="label dpdTag">PDF</span></a>', $url);
    }

    public function hookDisplayBackOfficeHeader()
    {
        $this->context->controller->addCSS($this->_path . 'views/css/dpd.css', 'all');
    }

    public function dpdCarrier()
    {
        return new DpdCarrier();
    }

    public function dpdDeliveryOptionsFinder($context, $translator, $objectPresenter, $priceFormatter)
    {
        return new DpdDeliveryOptionsFinder(
            $context,
            $translator,
            $objectPresenter,
            $priceFormatter
        );
    }

    public function dpdCheckoutDeliveryStep($context, $translator)
    {
        return new DpdCheckoutDeliveryStep($context, $translator);
    }

    public function dpdLabelGenerator()
    {
        return new DpdLabelGenerator();
    }

    public function dpdShippingList()
    {
        return new DpdShippingList();
    }
}
