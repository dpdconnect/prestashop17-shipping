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

use Configuration;

class Gmaps
{
    public function getGeoData($postal_code, $isoCode)
    {
        $gmapsKey = Configuration::get('gmaps_server_key');
        $data = urlencode('country:' . $isoCode . '|postal_code:' . $postal_code);
        $url = "https://maps.googleapis.com/maps/api/geocode/json?key=" . $gmapsKey . "&address=" . $data . '&sensor=false';
        $ch = curl_init();
        $timeout = 5;
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeout);
        $source = curl_exec($ch);
        curl_close($ch);
        $gmapsData = json_decode($source);

        $latitude = null;
        $longitude = null;
        if (count($gmapsData->results) > 0) {
            $latitude = $gmapsData->results[0]->geometry->location->lat;
            $longitude = $gmapsData->results[0]->geometry->location->lng;
        }

        return [
            'longitude' => $longitude,
            'latitude' => $latitude,
        ];
    }
}
