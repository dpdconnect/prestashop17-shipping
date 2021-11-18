<?php
Class Product extends ProductCore
{
    public $dpd_shipping_product;
    public $dpd_carrier_description;

    public function __construct($id_product = null, $full = false, $id_lang = null, $id_shop = null, 	$context = null)
    {
        self::$definition['fields']['dpd_shipping_product'] = array('type' => self::TYPE_HTML, 'validate' => 'isCleanHtml');
        self::$definition['fields']['dpd_carrier_description'] = array('type' => self::TYPE_HTML, 'validate' => 'isCleanHtml');
        parent::__construct($id_product, $full, $id_lang, $id_shop, $context);
    }
}