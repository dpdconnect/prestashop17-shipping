<?php

namespace DpdConnect\classes;

use dpdconnect;

class Version
{
    const SHOP = 'Prestashop';

    public static function type()
    {
        return self::SHOP;
    }

    public static function webshop()
    {
        return _PS_VERSION_;
    }

    public static function plugin()
    {
        return dpdconnect::VERSION;
    }
}
