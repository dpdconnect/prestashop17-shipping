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

class HTMLTemplateDPDShippingList extends HTMLTemplate
{
    public function __construct($object, $smarty)
    {
        $this->smarty = $smarty;
        $this->title = 'DPD Shipping List';
        $this->date = date('d-m-Y H:i');
        $this->object = $object;
    }

    public function getFilename()
    {
        return 'DPDShippingList' . date("Ymdhis") . '.pdf';
    }

    public function getBulkFilename()
    {
        return 'DpdShippingList' . date("Ymdhis") . '.pdf';
    }

    public function getHeader()
    {
        $this->assignCommonHeaderData();
        return $this->smarty->fetch(_PS_MODULE_DIR_ . 'dpdconnect' . DIRECTORY_SEPARATOR . 'pdf' . DIRECTORY_SEPARATOR . 'header.tpl');
    }

    public function getContent()
    {
        return $this->smarty->fetch(_PS_MODULE_DIR_ . 'dpdconnect' . DIRECTORY_SEPARATOR . 'pdf' . DIRECTORY_SEPARATOR . 'content.tpl');
    }

    public function assignHookData($object)
    {
        $this->smarty->assign(array(
            'amount' => count($object),
            'list' => $object,
            'date_now' => $this->date,
            'company_name' => Configuration::get("dpdconnect_company"),
            'company_street' => Configuration::get("dpdconnect_street"),
            'company_country' => Configuration::get("dpdconnect_country"),
            'company_postalcode' => Configuration::get("dpdconnect_postalcode"),
            'company_place' => Configuration::get("dpdconnect_place"),
            'styletd' => 'line-height: 10px;',
            'styleth' => 'line-height: 10px; border-bottom: 1px solid black; '
        ));
    }

    public function getLogo()
    {
        return _PS_MODULE_DIR_ . 'dpdconnect' . DIRECTORY_SEPARATOR . 'logo.png';
    }

    public function getFooter()
    {
        return ;
    }


    public function getPagination()
    {
        return $this->smarty->fetch(_PS_MODULE_DIR_ . 'dpdconnect' . DIRECTORY_SEPARATOR . 'pdf' . DIRECTORY_SEPARATOR . 'pagination.tpl');
    }
}
