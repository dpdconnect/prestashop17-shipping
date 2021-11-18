<?php

namespace DpdConnect\classes;

use OrderCore;
use Order;
use Product;

class FreshFreezeHelper
{
    const TYPE_DEFAULT = 'default';
    const TYPE_FRESH = 'fresh';
    const TYPE_FREEZE = 'freeze';

    public static function ordersContainFreshFreezeProducts($orderIds)
    {
        foreach ($orderIds as $orderId) {
            /** @var OrderCore $order */
            $order = new Order($orderId);

            /** @var array $orderProduct */
            foreach ($order->getProducts() as $orderProduct) {
                $dpdShippingProduct = $orderProduct['dpd_shipping_product'];

                if (in_array(strtolower($dpdShippingProduct), [self::TYPE_FRESH, self::TYPE_FREEZE])) {
                    return true;
                }
            }
        }

        return false;
    }

    public static function bundleOrders($orderIds)
    {
        $bundledOrders = [];

        foreach ($orderIds as $orderId) {
            /** @var OrderCore $order */
            $order = new Order($orderId);

            /** @var array $orderProduct */
            foreach ($order->getProducts() as $orderProduct) {
                $dpdShippingProduct = $orderProduct['dpd_shipping_product'];

                $bundledOrders[$order->id][$dpdShippingProduct][] = $orderProduct;
            }
        }

        return $bundledOrders;
    }

    // Default date is current date + 5 weekdays
    public static function getDefaultDate()
    {
        return date('Y-m-d', strtotime("+5 weekday"));
    }

    public static function collectFreshFreezeData()
    {
        $data = [];

        foreach ($_POST as $key => $value) {
            // Check if key starts with 'dpd_'. If not, skip this iteration
            if (strpos($key, 'dpd_') !== 0) {
                continue;
            }

            // Extract expiration date (yyyy-mm-dd) from key
            if (strpos($key, 'dpd_expiration_date') === 0) {
                $explodedString = explode('_', $key);

                $orderId = $explodedString[3];
                $orderProductId = $explodedString[4];

                $data[$orderId][$orderProductId]['expiration_date'] = $value;
            }

            // Extract carrier description from key
            if (strpos($key, 'dpd_carrier_description') === 0) {
                $explodedString = explode('_', $key);

                $orderId = $explodedString[3];
                $orderProductId = $explodedString[4];

                $data[$orderId][$orderProductId]['carrier_description'] = $value;
            }
        }

        return $data;
    }

    public static function shippingTypeIsFreshOrFreeze($shippingType)
    {
        return in_array(strtolower($shippingType), [self::TYPE_FRESH, self::TYPE_FREEZE]);
    }
}
