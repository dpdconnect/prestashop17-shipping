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


namespace DpdConnect\Controller\Admin;

require_once(_PS_MODULE_DIR_ . 'dpdconnect' . DIRECTORY_SEPARATOR . 'dpdconnect.php');
require(_PS_MODULE_DIR_ . 'dpdconnect' . DIRECTORY_SEPARATOR . 'classes' . DIRECTORY_SEPARATOR .'pdf' . DIRECTORY_SEPARATOR . 'HTMLTemplateDPDShippingList.php');

use Context;
use Dispatcher;
use dpdconnect;
use PDF;
use PrestaShopBundle\Controller\Admin\FrameworkBundleAdminController;
use Tools;

class AdminDpdBulkActionsController extends FrameworkBundleAdminController
{
    public $dpdShippingList;

    public function __construct()
    {
        $dpdconnect = new dpdconnect();
        $this->dpdShippingList = $dpdconnect->dpdShippingList();
    }

    public function processBulkShippingListDPD()
    {
        $id_lang = Context::getContext()->language->id;
        $params = array('token' => Tools::getAdminTokenLite('AdminDpdShippingList')) ;
        $orderUrl = Dispatcher::getInstance()->createUrl('AdminDpdShippingList', $id_lang, $params, false);
        $orderIds = Tools::getValue('order_orders_bulk');

        if (!empty($orderIds)) {
            foreach ($orderIds as $orderId) {
                $orderUrl .= '&ids_order[]=' . $orderId;
            }

            return $this->redirect($orderUrl);
        }
    }

    public function processPrintDpdLabels()
    {
        $id_lang = Context::getContext()->language->id;
        $params = array('token' => Tools::getAdminTokenLite('AdminDpdLabels')) ;
        $orderUrl = Dispatcher::getInstance()->createUrl('AdminDpdLabels', $id_lang, $params, false);
        $orderIds = Tools::getValue('order_orders_bulk');

        if (!empty($orderIds)) {
            foreach ($orderIds as $orderId) {
                $orderUrl .= '&ids_order[]=' . $orderId;
            }

            return $this->redirect($orderUrl);
        }
    }

    public function processPrintDpdReturnLabels()
    {
        $id_lang = Context::getContext()->language->id;
        $params = array('token' => Tools::getAdminTokenLite('AdminDpdLabels')) ;
        $orderUrl = Dispatcher::getInstance()->createUrl('AdminDpdLabels', $id_lang, $params, false);
        $orderIds = Tools::getValue('order_orders_bulk');

        if (!empty($orderIds)) {
            foreach ($orderIds as $orderId) {
                $orderUrl .= '&ids_order[]=' . $orderId;
                $orderUrl .= '&return=true';
            }

            return $this->redirect($orderUrl);
        }
    }
}
