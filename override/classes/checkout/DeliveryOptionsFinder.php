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


require_once(_PS_MODULE_DIR_ . 'dpdconnect' . DIRECTORY_SEPARATOR . 'dpdconnect.php');

class DeliveryOptionsFinder extends DeliveryOptionsFinderCore
{
    public function getDeliveryOptions()
    {
        $dpdconnect = new dpdconnect();
        $this->dpdCarrier = $dpdconnect->dpdCarrier;
        $this->dpdProductHelper = $dpdconnect->dpdProductHelper;

        $carriers_available = parent::getDeliveryOptions();

        if (!$this->dpdCarrier->checkIfSaturdayAllowed()) {
            foreach ($carriers_available as $key => $availableCarrier) {
                $availableCarrierId = str_replace(',', '', $key);

                if ($this->dpdCarrier->isSaturdayCarrier($availableCarrierId)) {
                    unset($carriers_available[$key]);
                }
            }
        }

        return $carriers_available;
    }

}
