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
<div class="dpd tab-pane" id="labels">
    <div class="clear"></div>
        <div class="row label">
            {if !$isInDb}
            <a href="{{$urlGenerateLabel}}" id="generate-label" style="float: right" target="_blank" >
                <button class="btn btn-default" style="float: right" id="generateLabelButton"><i class="icon-print"> </i> {l s='Print DPD label' d='Modules.dpd_carrier'}</button>
            </a>
                <label for="parcel" style="color: black; margin-top: -20px; float: left; margin-left: 7px;"> {l s='Number of parcels' d='Modules.dpd_carrier'} </label>
                <div class=" col-lg-4 col-md-3 col-xs-6">
                    <div class="input-group">
                        <span class="input-group-btn">
                            <button class="btn btn-default" id='min-label'type="button">-</button>
                        </span>
                        <input type="number" class="form-control" id="parcel" value="1">
                        <span class="input-group-btn">
                            <button class="btn btn-default" id="plus-label" type="button">+</button>
                        </span>
                </div>
            </div>
            {else}
            <a href="{{$urlGenerateLabel}}" id="generate-label" target="_blank" >
                <button class="btn btn-default" style="float: right" id="showLabel"><i class="icon-download"> </i> {l s='Download DPD label' d='Modules.dpdconnect'}</button>
            </a>


            <a href="{{$deleteGeneratedLabel}}" id="deletefromdatabase" >
                <button class="btn btn-danger" style="float: left" id="deleteLabelfromDatabase"><i class="icon-eraser"> </i> {l s='Delete  DPD label' d='Modules.dpdconnect'}</button>
            </a>

            {/if}
        </div>
        <div class="row" style="margin-top: 10px;">
            {if !$isReturnInDb}
                <a href="{{$urlGenerateReturnLabel}}" style="float: right" id="generate-label-retour" target="_blank">
                    <button class="btn btn-default"><i class="icon-rotate-right"></i> {l s='Print Retour Label' d='Modules.dpdconnect'}</button>
                </a>
                <label for="parcel" style="color: black; margin-top: -23px; float: left; margin-left: 7px;"> {l s='Number of parcels' d='Modules.dpd_carrier'} </label>
                <div class=" col-lg-4 col-md-3 col-xs-6">
                    <div class="input-group">
                        <span class="input-group-btn">
                            <button class="btn btn-default" id='min-label-retour'type="button">-</button>
                        </span>
                        <input type="number" class="form-control" id="parcel-retour" value="1">
                        <span class="input-group-btn">
                            <button class="btn btn-default" id="plus-label-retour" type="button">+</button>
                        </span>
                    </div>
                </div>
            {else}
                <a href="{{$urlGenerateReturnLabel}}" style="float: right" target="_blank">
                    <button class="btn btn-default"><i class="icon-rotate-right"></i> {l s='Download Retour Label' d='Modules.dpdconnect'}</button>
                </a>

                <a href="{{$deleteGeneratedRetourLabel}}" id="deletefromdatabase" >
                    <button class="btn btn-danger" style="float: left" id="deleteLabelfromDatabase"><i class="icon-eraser"> </i> {l s='Delete  DPD label' d='Modules.dpdconnect'}</button>
                </a>
            {/if}
        </div>
    <div class="clearfix"></div>
</div>

{literal}
<script type="text/javascript">
    function setAmountOfParcels(inputField, minButton, plusButton, link) {
        if (inputField.val() !== undefined && inputField.val() !== '') {
            var url = link.attr('href');
            minButton.click(function () {
                var parcel = inputField.val();
                if (parcel > 1) {
                    parcel -= 1;
                }
                inputField.val(parcel);
                var newurl = url + '&parcel=' + inputField.val();
                link.attr('href', newurl);
            });
            plusButton.click(function () {
                var parcel = parseInt(inputField.val());
                if (parcel < 100) {
                    parcel += 1;
                }
                inputField.val(parcel);
                var newurl = url + '&parcel=' + inputField.val();
                link.attr('href', newurl);
            });
            link.click(function (e) {
                e.preventDefault();
                var newurl = url + '&parcel=' + inputField.val();
                window.location.href = newurl;
            });
        }
    }

    setAmountOfParcels($('#parcel'), $('#min-label'), $('#plus-label'), $('#generate-label'));
    setAmountOfParcels($('#parcel-retour'), $('#min-label-retour'), $('#plus-label-retour'), $('#generate-label-retour'))


</script>
{/literal}
