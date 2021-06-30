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
<div class="tab-pane d-print-block fade" id="orderDpdTabContent" role="tabpanel" aria-labelledby="orderDpdTab">
    <div class="card card-details">
        <div class="card-header d-none d-print-block">
            {* This never gets displayed anyways *}
        </div>
        <div class="card-body">
            <div class="form-group row">
                {if !$isInDb}
                    <div class="col-sm">
                        <div class="text-right" >
                            <a href="{{$urlGenerateLabel}}" id="generate-label" style="float: right" target="_blank" >
                                <br><br>
                                <button class="btn btn-default" style="float: left; display: block;" id="generateLabelButton">
                                    <i class="material-icons">print</i>
                                    {l s='Print DPD label' mod='dpdconnect'}
                                </button>
                            </a>
                        </div>
                        <div class="form-control-label text-left" style="float: left;">
                            <label for="parcel" style="color: black;"> {l s='Number of parcels' mod='dpdconnect'} </label>
                        </div>
                        <br><br>
                        <div class="input-group col-sm-4" style="padding-left: 0; margin-left: 0; margin-top: -5px;">
                            <span class="input-group-btn">
                                <button class="btn btn-default" id='min-label' type="button">-</button>
                            </span>
                            <input type="number" class="form-control" id="parcel" value="1">
                            <span class="input-group-btn">
                                <button class="btn btn-default" id="plus-label" type="button">+</button>
                            </span>
                        </div>
                    </div>
                {else}
                    <div style="margin-left: 15px;">
                        <a href="{{$urlGenerateLabel}}" id="generate-label" target="_blank" >
                            <button class="btn btn-default" style="float: left" id="showLabel">
                                <i class="material-icons">cloud_download</i>
                                {l s='Download DPD label' mod='dpdconnect'}
                            </button>
                        </a>
                        <a href="{{$deleteGeneratedLabel}}" id="deletefromdatabase" >
                            <button class="btn btn-danger" style="float: right; margin-left: 10px;" id="deleteLabelfromDatabase">
                                <i class="material-icons">delete_forever</i>
                                {l s='Delete DPD label' mod='dpdconnect'}
                            </button>
                        </a>
                    </div>
                {/if}
            </div>
            <div class="form-group row" style="margin-top: 10px;">
                {if !$isReturnInDb}
                    <div class="col-sm">
                        <div class="text-right">
                            <a href="{{$urlGenerateReturnLabel}}" style="float: right" id="generate-label-retour" target="_blank">
                                <br><br>
                                <button class="btn btn-default">
                                    <i class="material-icons">rotate_left</i>
                                    {l s='Print Retour Label' mod='dpdconnect'}
                                </button>
                            </a>
                        </div>
                        <div class="form-control-label text-left" style="float: left;">
                            <label for="parcel" style="color: black; float: left;"> {l s='Number of parcels' mod='dpdconnect'} </label>
                        </div>
                        <br><br>
                        <div class="input-group col-sm-4" style="padding-left: 0; margin-left: 0; margin-top: -5px;">
                            <span class="input-group-btn">
                                <button class="btn btn-default" id='min-label-retour' type="button">-</button>
                            </span>
                            <input type="number" class="form-control" id="parcel-retour" value="1">
                            <span class="input-group-btn">
                                <button class="btn btn-default" id="plus-label-retour" type="button">+</button>
                            </span>
                        </div>
                    </div>
                {else}
                    <div style="margin: 15px;">
                        <a href="{{$urlGenerateReturnLabel}}" style="float: left" target="_blank">
                            <button class="btn btn-default">
                                <i class="material-icons">cloud_download</i>
                                {l s='Download Retour Label' mod='dpdconnect'}
                            </button>
                        </a>

                        <a href="{{$deleteGeneratedRetourLabel}}" id="deletefromdatabase">
                            <button class="btn btn-danger" style="float: right; margin-left: 10px;" id="deleteLabelfromDatabase">
                                <i class="material-icons">delete_forever</i>
                                {l s='Delete DPD label' mod='dpdconnect'}
                            </button>
                        </a>
                    </div>
                {/if}
            </div>
            <div class="clearfix"></div>
        </div>
    </div>
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