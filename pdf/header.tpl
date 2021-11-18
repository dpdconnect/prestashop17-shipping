{*
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
*}
<table style="width: 100%; ">
    <tr>
        <td style=" text-align: left; ">
            <br /> {l s='Customer address' mod='dpdconnect'}
            <br /> {$company_name}
            <br /> {$company_street}
            <br /> {$company_country}-{$company_postalcode} {$company_place}
        </td>
        <td style="text-align: left;">
            <br /> {l s='Date' mod='dpdconnect'}: {$date_now}
            <br /> {l s='Amount of parcels' mod='dpdconnect'}: {$amount}
        </td>
        <td style=" text-align: right; ">
            {if $logo_path}
                <img src="{$logo_path|addslashes}" style="width:50px; height:50px;" />
            {/if}
        </td>

    </tr>
</table>