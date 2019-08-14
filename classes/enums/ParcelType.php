<?php

namespace DpdConnect\classes\enums;

class ParcelType
{
    const TYPEREGULAR = 1;
    const TYPERETURN = 2;
    const TYPESATURDAY = 3;

    public static function parse($return, $manualSaturday)
    {
        if ($return) {
            return self::TYPERETURN;
        }

        if ($manualSaturday) {
            return self::TYPESATURDAY;
        }

        return self::TYPEREGULAR;
    }
}
