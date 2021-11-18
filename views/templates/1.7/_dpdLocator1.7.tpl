<!-- DPDCrier !-->
<input name="parcel-id" type="hidden" id="parcel-id"/>
{literal}
<script>
    const baseUri = "{/literal}{$baseUri}{literal}";
    const parcelshopId = {/literal}{$parcelshopId}{literal};
    const sender = {/literal}{$sender}{literal};
    const shippingAddress = "{/literal}{$shippingAddress}{literal}";
    const dpdPublicToken = "{/literal}{$dpdPublicToken}{literal}";
    const shopCountryCode = "{/literal}{$shopCountryCode}{literal}";
    const mapsKey = "{/literal}{$mapsKey}{literal}";
    const cookieParcelId = "{/literal}{$cookieParcelId}{literal}";
    const oneStepParcelshopUrl = "{/literal}{$oneStepParcelshopUrl nofilter}{literal}";
    const dpdParcelshopMapUrl = "{/literal}{$dpdParcelshopMapUrl}{literal}";

    var mapInitialized = false;

    document.addEventListener('DOMContentLoaded', function(){
        jQuery(document).on('ready', function () {
            $.getScript(dpdParcelshopMapUrl, function() {

                DPDConnect.onParcelshopSelected = function (parcelshop) {
                    // Store selected parcelshop
                    jQuery.post(oneStepParcelshopUrl, {
                        'method': 'setParcelShop',
                        'parcelId': parcelshop.parcelShopId,
                        'parcelShopSenderId': parcelshopId,
                        'sender': sender
                    });

                    jQuery("#parcel-id").val(parcelshop.parcelShopId);
                    jQuery(".dpd-alert").hide();
                    jQuery(".alert-danger").hide();
                };

                // Loop through every delivery option to see if a DPD Parcelshop carrier is selected
                jQuery(".delivery-options input").each(function () {
                    if (jQuery("#" + this.id).prop("checked")) {

                        // Check if DPD Parcelshop carrier is selected
                        if (this.value === parcelshopId + ',') {
                            if (mapInitialized) {
                                showContainer();
                            } else {
                                initMap();
                            }
                        }
                    }
                });

                // Hide or show the parcelshop selector when a different delivery option is selected
                jQuery('.delivery-options input').on('change', function () {
                    if (this.value === parcelshopId + ',') {
                        if (mapInitialized) {
                            showContainer();
                        } else {
                            initMap();
                        }
                    } else {
                        hideContainer();
                    }
                });

                // On one page checkout, prevent continuing when no parcelshop has been selected
                jQuery('#opc_payment_methods-content').on('click', 'a', function (e) {
                    jQuery('#opc_delivery_methods input.delivery_option_radio').each(function () {
                        if (jQuery("#" + this.id).prop("checked")) {

                            // Check if delivery option is a DPD Parcelshop carrier
                            if (this.value === parcelshopId + ',') {

                                // Show alert when no parcelshop has been chosen
                                if (!jQuery('#parcel-id').val()) {
                                    e.preventDefault();
                                    jQuery('.dpd-alert').show();
                                }
                            }
                        }
                    });
                });

                // In normal checkout, prevent continuing when no parcelshop has been selected
                jQuery('#dpd-connect\\\\classes\\\\dpd-checkout-delivery-step .continue').on('click', function(e) {
                    if (jQuery('.delivery-options input:checked').val() == parcelshopId + ',' && !jQuery('#parcel-id').val()) {
                        e.preventDefault();
                        jQuery('.dpd-alert').show();
                    }
                });
            });
        });
    });

    function initMap() {
        if (mapInitialized) {
            return;
        }

        addDivs();

        // If mapsKey is empty that means user chose to use DPD's key
        if (mapsKey != '') {
            DPDConnect.show(dpdPublicToken, shippingAddress, shopCountryCode, mapsKey);
        } else {
            DPDConnect.show(dpdPublicToken, shippingAddress, shopCountryCode);
        }

        mapInitialized = true;
    }

    function addDivs() {
        if(jQuery('#dpd-connect-container').length == 0 ){
            jQuery('#dpd-connect\\\\classes\\\\dpd-checkout-delivery-step .content .form-fields').append(
                '<div id="dpd-connect-container"></div>'
            );
        }
        if(jQuery('#dpd-connect-map-container').length == 0 ){
            jQuery('#dpd-connect-container').append('<div id="dpd-connect-map-container" style="width: 100%; height: 450px;"></div>');
        }
        if(jQuery('#dpd-connect-selected-container').length == 0 ){
            jQuery('#dpd-connect-container').append('<div id="dpd-connect-selected-container" style="display: none;">' +
                '{/literal}{l s='Selected parcelshop' mod='dpdconnect'}{literal}:<br />\n' +
                '<strong>%%company%%</strong><br />\n' +
                '%%street%% %%houseNo%%<br />\n' +
                '%%zipCode%% %%city%%<br />\n' +
                '<br \>' +
                '<a href="#" id="dpd-connect-change-parcelshop" onclick="onParcelshopChange(event)"><strong>{/literal}{l s='Change' mod='dpdconnect'}{literal}</strong></a>\n' +
                '</div>');

        }
    }

    function onParcelshopChange(event) {
        // Prevent jumping to top of page when clicking on 'Change' button of selected parcelshop
        event.preventDefault();

        hideSelectedContainer();
        showMapContainer();
    }

    function showContainer() {
        jQuery('#dpd-connect-container').show();
    }

    function hideContainer() {
        jQuery('#dpd-connect-container').hide();
    }

    function showMapContainer() {
        DPDConnect.getMapContainer().style.display = 'block';
    }

    function hideMapContainer() {
        DPDConnect.getMapContainer().style.display = 'none';
    }

    function showSelectedContainer() {
        DPDConnect.getSelectedContainer().style.display = 'block';
    }

    function hideSelectedContainer() {
        DPDConnect.getSelectedContainer().style.display = 'none';
    }
</script>
{/literal}

<div class="dpd-alert alert alert-danger">
    <p>{l s='There is 1 error' mod='dpdconnect'}</p>
    <ol>
        <li>{l s='Please select a parcelshop' mod='dpdconnect'}</li>
    </ol>
</div>