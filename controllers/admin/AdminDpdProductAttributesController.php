<?php

require_once(_PS_MODULE_DIR_ . 'dpdconnect' . DIRECTORY_SEPARATOR . 'dpdconnect.php');

class AdminDpdProductAttributesController extends ModuleAdminController
{
    public function __construct()
    {
        $this->table = 'dpd_product_attributes';
        $this->bootstrap = true;
        // $this->list_simple_header = true; // Enable again once the sort and filter options are made to work
        $this->bulk_actions = [
            'delete' => [
                'text' => 'Delete selected',
                'confirm' => 'Delete selected items?',
            ]
        ];
        $this->context = Context::getContext();

        parent::__construct();
    }

    public function renderList()
    {
        $this->addRowAction('edit');
        $this->addRowAction('delete');
        $this->bulk_actions = [
            'delete' => [
                'text' => $this->l('Delete selected'),
                'confirm' => $this->l('Delete selected items?')
            ],
        ];
        $this->fields_list = [
            'product_id' => [
                'title' => $this->l('Product ID'),
                'width' => 'auto',
            ],
            'hs_code' => [
                'title' => $this->l('HS Code'),
                'width' => 'auto',
            ],
            'customs_value' => [
                'title' => $this->l('Customs Value'),
                'width' => 'auto',
            ],
            'country_of_origin' => [
                'title' => $this->l('Country of Origin'),
                'width' => 'auto',
            ],
        ];

        $lists = parent::renderList();

        parent::initToolbar();

        return $lists;
    }


    public function renderForm()
    {
        $this->fields_form = [
            [
                'form' => [
                    'legend' => [
                        'title' => $this->l('HS Code'),
                        'image' => '../img/admin/cog.gif'
                    ],
                    'input' => [
                        [
                            'type' => 'text',
                            'lang' => false,
                            'label' => $this->l('Product ID:'),
                            'name' => 'product_id',
                            'size' => 40,
                        ],
                        [
                            'type' => 'text',
                            'label' => $this->l('HS Code:'),
                            'name' => 'hs_code',
                            'readonly' => false,
                            'disabled' => false,
                            'size' => 40,
                        ],
                        [
                            'type' => 'text',
                            'label' => $this->l('Customs Value:'),
                            'name' => 'customs_value',
                            'readonly' => false,
                            'disabled' => false,
                            'size' => 40,
                        ],
                        [
                            'type' => 'text',
                            'label' => $this->l('Country of Origin:'),
                            'name' => 'country_of_origin',
                            'readonly' => false,
                            'disabled' => false,
                            'size' => 2,
                        ],
                    ],
                    'submit' => [
                        'title' => $this->l('Save'),
                        'class' => 'button'
                    ],
                ],
            ],
        ];

        $helper = new HelperForm();
        $helper->title = 'DPD Product Attributes';
        $helper->show_toolbar = true;
        $helper->toolbar_scroll = true;

        $helper->submit_action = 'insert' . $this->table;
        $helper->fields_value['product_id'] = null;
        $helper->fields_value['hs_code'] = null;
        $helper->fields_value['customs_value'] = null;
        $helper->fields_value['country_of_origin'] = null;

        $id = Tools::getValue('id_dpd_product_attributes');

        if ($id) {
            $this->fields_form[0]['form']['input'][0]['readonly'] = true;
            $this->fields_form[0]['form']['input'][0]['disabled'] = true;
            $sql = new DbQuery();
            $sql->from('dpd_product_attributes');
            $sql->select('product_id');
            $sql->select('hs_code');
            $sql->select('customs_value');
            $sql->select('country_of_origin');
            $sql->where('id_dpd_product_attributes = ' . $id);
            $results = Db::getInstance()->executeS($sql)[0];
            $productId = $results['product_id'];
            $hsCode = $results['hs_code'];
            $customsValue = $results['customs_value'];
            $countryOfOrigin = $results['country_of_origin'];

            $helper->submit_action = 'edit' . $this->table;
            $helper->fields_value['product_id'] = $productId;
            $helper->fields_value['hs_code'] = $hsCode;
            $helper->fields_value['customs_value'] = $customsValue;
            $helper->fields_value['country_of_origin'] = $countryOfOrigin;
        }

        return $helper->generateForm($this->fields_form);
    }

    public function postProcess()
    {
        if (Tools::isSubmit('submitBulkdelete' . $this->table)) {
            $this->bulkDelete();
        }

        if (Tools::isSubmit('delete' . $this->table)) {
            $this->delete();
        }

        if (Tools::isSubmit('edit' . $this->table)) {
            $this->edit();
        }

        if (Tools::isSubmit('insert' . $this->table)) {
            $this->create();
        }
    }

    public function bulkDelete()
    {
        $ids = implode(',', Tools::getValue('dpd_product_attributesBox'));
        Db::getInstance()->delete($this->table, 'id_dpd_product_attributes' . ' IN ('. $ids . ')');
    }

    public function delete()
    {
        $id = Tools::getValue('id_dpd_product_attributes');
        Db::getInstance()->delete($this->table, 'id_dpd_product_attributes' . ' = '. $id);
    }

    public function edit()
    {
        $id = Tools::getValue('id_dpd_product_attributes');
        $productId = Tools::getValue('product_id');
        $hsCode = Tools::getValue('hs_code');
        $customsValue = Tools::getValue('customs_value');
        $countryOfOrigin = Tools::getValue('country_of_origin');
        $sql = 'UPDATE ' ._DB_PREFIX_ . 'dpd_product_attributes 
                   SET hs_code =' . $hsCode . ', 
                       customs_value = ' . $customsValue . ',
                       country_of_origin = "' . $countryOfOrigin . '"
                 WHERE id_dpd_product_attributes = ' . $id;
        try {
            DB::getInstance()->execute($sql);
            Tools::redirectAdmin(self::$currentIndex.'&conf=4&token='.$this->token);
        } catch (Exception $e) {
            $this->errors[] = Tools::displayError('An error has occurred: Can\'t save the current object');
            return;
        }
    }

    public function create()
    {
        $productId = Tools::getValue('product_id');
        $hsCode = Tools::getValue('hs_code');
        $customsValue = Tools::getValue('customs_value');
        $countryOfOrigin = Tools::getValue('country_of_origin');
        try {
            $result = Db::getInstance()->insert('dpd_product_attributes', [
                'product_id' => $productId,
                'hs_code' => $hsCode,
                'customs_value' => $customsValue,
                'country_of_origin' => $countryOfOrigin,
            ]);
            if ($result === false) {
                $this->errors[] = Tools::displayError('Creating HS Code failed. Probably HS Code already exists for Product ID ' . $productId);
                return;
            }
            Tools::redirectAdmin(self::$currentIndex.'&conf=4&token='.$this->token);
        } catch (PrestaShopException $e) { // prestashop only throws this in case of dev mode
            if (substr($e->getMessage(), 0, 15) === 'Duplicate entry') {
                throw new PrestaShopException('HS Code for Product ID ' . $productId . ' is already set');
            }
            $this->errors[] = Tools::displayError('An error has occurred: Can\'t save the current object');
        }
    }
}
