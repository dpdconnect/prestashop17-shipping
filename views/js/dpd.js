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
last_action = '';

function sendBulkAction(form, action){
    String.prototype.splice = function(index, remove, string) {
        return (this.slice(0, index) + string + this.slice(index + Math.abs(remove)));
    };

    var form_action = $(form).attr('action');

    if (form_action.replace(/(?:(?:^|\n)\s+|\s+(?:$|\n))/g,'').replace(/\s+/g,' ') == '')
        return false;

    if (form_action.indexOf('#') == -1) {
        $(form).attr('action', form_action + '&' + action);
    }
    else {
       if(last_action != '') {
           $(form).attr('action', form_action.replace(last_action, action));
            last_action = action;
       }else{
           $(form).attr('action',  form_action.splice(form_action.lastIndexOf('&'), 0, '&' + action));
           last_action = action;
       }
    }
    $(form).submit();
}