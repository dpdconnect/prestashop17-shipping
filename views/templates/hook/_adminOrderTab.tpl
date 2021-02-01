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
<li class="nav-item">
    <a class="nav-link" id="orderDpdTab" data-toggle="tab" href="#orderDpdTabContent" role="tab" aria-controls="orderDpdTabContent" aria-expanded="true" aria-selected="false">
        <i class="material-icons">note</i>
        DPD
        (<span class="count" id="dpd_label_count">{if $number == null}{$number = 0}{/if}{$number}</span>)
    </a>
</li>
