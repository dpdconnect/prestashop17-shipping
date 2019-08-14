<?php

namespace DpdConnect\classes;

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

use dpdconnect;

class DpdError
{
    public $errors;
    private static $module = null;

    public function __construct()
    {
        self::getModule();
        $this->setErrorMessage();
    }

    private static function getModule()
    {
        if (is_null(self::$module)) {
            self::$module = new dpdconnect();
        }
        return self::$module;
    }

    public function setErrorMessage()
    {
        $this->errors['TOO_MANY_PARCELS'] = self::$module->l('Not allowed to have more than 50 parcel\'s', 'dpderror');
        $this->errors['ID_IS_NOT_SET'] = self::$module->l('The ID %s is not set', 'dpderror');
        $this->errors['ORDER_ID_DOES_NOT_EXIST'] = self::$module->l('The ID %s doesn\'t exist', 'dpderror');
        $this->errors['NOT_SHIPPED_BY_DPD'] = self::$module->l('The order %s is not shipped by DPD', 'dpderror');
        $this->errors['WEIGHT_TO_HEAVY'] = self::$module->l('The weight of one parcel can\'t be higher then 31.5. Try to ship it in more parcel\'s', 'dpderror');
        $this->errors['CANCELED'] = self::$module->l('The order %s has been canceled', 'dpderror');
        $this->errors['LOGIN_8'] = self::$module->l('The DelisId or DelisPassword is wrong', 'dpderror');
        $this->errors['PRINT_LABEL'] = self::$module->l('Something went wrong while printing the label', 'dpderror');
        $this->errors['DPD_CONNECT_ERROR'] = self::$module->l('Something went wrong while printing the label', 'dpderror');
        $this->errors['DPD_CONNECT_EXCEPTION'] = self::$module->l('Something went wrong while printing the label', 'dpderror');
    }

    public function get()
    {
        $args = func_get_args();
        $args[0] = self::$module->l($this->errors[$args[0]]);
        return call_user_func_array('sprintf', $args);
    }
}
