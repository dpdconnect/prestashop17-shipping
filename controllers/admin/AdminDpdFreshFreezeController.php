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

use Configuration;
use Context;
use DpdConnect\classes\DpdLabelGenerator;
use DpdConnect\classes\FreshFreezeHelper;
use Image;
use Order;
use OrderCore;
use PrestaShopBundle\Controller\Admin\FrameworkBundleAdminController;
use Product;
use Tools;

class AdminDpdFreshFreezeController extends FrameworkBundleAdminController
{
    public $bootstrap;
    public $orderIds;
    public $context;
    public $twig;
    public $parcels;
    public $return;

    public function __construct()
    {
        $this->bootstrap = true;
        $this->context = Context::getContext();
        $this->orderIds = Tools::getValue('ids_order');
        $this->parcels = Tools::getValue('parcel');
        $this->return = Tools::getValue('return');

        parent::__construct();
    }

    public function renderView($view = null, array $parameters = array())
    {
        // Redirect back to order overview if no orderIds have been supplied
        if (empty($this->orderIds)) {
            $ordersUrl = $this->context->link->getTabLink([
                'route_name' => 'admin_orders_index',
                'class_name' => 'AdminOrders'
            ]);

            Tools::redirectAdmin($ordersUrl);
        }

        $this->context->controller->addJqueryUI('ui.datepicker');

        $bundledOrders = FreshFreezeHelper::bundleOrders($this->orderIds);

        $orderProducts = [];
        foreach ($this->orderIds as $orderId) {
            // Continue if label(s) already exist, so that no fresh/freeze data has to be entered for this order
            if (DpdLabelGenerator::getLabelOutOfDb($orderId)) {
                continue;
            }

            /** @var OrderCore $order */
            $order = new Order($orderId);

            // Collect all fresh and freeze order products in one array for easier handling
            /** @var \ProductCore $orderProduct */
            foreach ($order->getProducts() as $orderProduct) {
                $orderProducts[$order->id] = [];

                if (isset($bundledOrders[$order->id][FreshFreezeHelper::TYPE_FRESH])) {
                    $orderProducts[$order->id] = array_merge($orderProducts[$order->id], $bundledOrders[$order->id][FreshFreezeHelper::TYPE_FRESH]);
                }
                if (isset($bundledOrders[$order->id][FreshFreezeHelper::TYPE_FREEZE])) {
                    $orderProducts[$order->id] = array_merge($orderProducts[$order->id], $bundledOrders[$order->id][FreshFreezeHelper::TYPE_FREEZE]);
                }
            }
        }

        $redirectUrl = $this->context->link->getAdminLink('AdminDpdLabels');
        $redirectUrl = $redirectUrl . '&' . http_build_query([
           'ids_order' => $this->orderIds,
           'parcel' => $this->parcels,
           'return' => $this->return
       ]);

        // Assign imageurl to every product
        foreach ($orderProducts as $orderId => $products) {
            foreach ($products as $index => $product) {
                $id_image = Product::getCover((int)$orderProducts[$orderId][$index]['id_product']);
                if ($id_image) {
                    $image = new Image($id_image['id_image']);
                    $image_url = _PS_BASE_URL_._THEME_PROD_DIR_.$image->getExistingImgPath().".jpg";
                } else {
                    $image_url = _PS_BASE_URL_._THEME_PROD_DIR_ . $this->context->language->iso_code . '-default-large_default.jpg';
                }

                $orderProducts[$orderId][$index]['image_url'] = $image_url;
            }
        }

        return $this->render('@Modules/dpdconnect/views/templates/admin/fresh_freeze/form.html.twig', [
            'weight_unit' => Configuration::get('PS_WEIGHT_UNIT'),
            'orderProducts' => $orderProducts,
            'defaultDate' => FreshFreezeHelper::getDefaultDate(),
            'redirectUrl' => $redirectUrl
        ]);
    }
}
