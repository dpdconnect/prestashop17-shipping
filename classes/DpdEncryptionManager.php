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

class DpdEncryptionManager
{
    protected static $cipher = 'AES-128-CBC';
    protected static $key = _COOKIE_KEY_;

    public static function decrypt($encoded)
    {
        $decoded = base64_decode($encoded);

        list($iv, $encryption) = explode('::', $decoded, 2);

        $decryption = openssl_decrypt($encryption, self::$cipher, self::$key, OPENSSL_RAW_DATA, $iv);

        return $decryption;
    }

    public static function encrypt($decryption)
    {
        $ivlen = openssl_cipher_iv_length(self::$cipher);
        $iv = openssl_random_pseudo_bytes($ivlen);

        $encryption = openssl_encrypt($decryption, self::$cipher, self::$key, OPENSSL_RAW_DATA, $iv);

        return base64_encode($iv . '::' . $encryption);
    }
}
