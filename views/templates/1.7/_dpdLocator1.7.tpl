<!-- DPDCrier !-->
<input name="parcel-id" type="hidden" id="parcel-id"/>
{literal}
<script>
    const parcelshopId = {/literal}{$parcelshopId}{literal};
    const sender = {/literal}{$sender}{literal};
    var longitude = {/literal}{$longitude|default:'""'}{literal};
    var latitude = {/literal}{$latitude|default:'""'}{literal};
    var parcelShops = {/literal}JSON.parse({$parcelshops|@json_encode nofilter}){literal};
    var cookieParcelId = "{/literal}{$cookieParcelId}{literal}";
    var baseUri = "{/literal}{$baseUri}{literal}";

    document.addEventListener('DOMContentLoaded', function(){
        window.markers = [];
        window.infowindows = [];


        if(longitude != "" && latitude != "" && parcelShops != "") {
            if (!(window.isPageLoaded)) {
                jQuery(document).on('ready', function () {
                    DpdInitGoogleMaps();
                    addChosen(cookieParcelId);
                    jQuery("#parcelshops").hide();
                    jQuery(".selected-parcelshop").hide();
                });
            }


            jQuery(document).on('ready', function () {
                if (jQuery("#parcelshops").length == 0) {
                    DpdInitGoogleMaps();
                    jQuery('#parcelshops').hide();
                    jQuery('.delivery-options input').each(function () {
                        if (jQuery("#" + this.id).prop("checked")) {
                            if (this.value === parcelshopId + ',') {
                                // the the parcelshop sender is selected
                                jQuery('#parcelshops').show();
                                addChosen(cookieParcelId);
                                jQuery('.selected-parcelshop').show();

                            }
                        }
                    });
                }


                jQuery('.delivery-options input').each(function () {
                    if (jQuery("#" + this.id).prop("checked")) {
                        if (this.value === parcelshopId + ',') {
                            // the the parcelshop sender is selected
                            jQuery('#parcelshops').show();
                            if (cookieParcelId.length !== 0) {
                                addChosen(cookieParcelId);
                            }
                        }
                    }
                });

                jQuery('.delivery-options input').on('change', function () {
                    if (this.value === parcelshopId + ',') {
                        jQuery('#parcelshops').show();
                        jQuery('.selected-parcelshop').show();
                    } else {
                        jQuery('#parcelshops').hide();
                        jQuery('.selected-parcelshop').hide();
                    }
                });

                jQuery("#parcelshops").on("click", ".ParcelShops", function (e) {
                    jQuery('#parcel-id').val(this.id).trigger('change');
                });

                jQuery("#parcel-id").on('change', function () {
                    jQuery.ajax({
                        type: 'POST',
                        url: baseUri + 'module/dpdconnect/OneStepParcelshop',
                        data: 'method=setParcelShop&parcelId=' + jQuery(this).val() + '&parcelShopSenderId=' + parcelshopId + '&sender=' + sender,
                        dataType: 'json'
                    });
                    jQuery(".dpd-alert").hide();
                    addChosen(jQuery(this).val());
                    jQuery(".alert-danger").hide();
                });
            });

            // for verifying
            jQuery(document).on('ready', function () {
                jQuery('#opc_payment_methods-content').on('click', 'a', function (e) {
                    jQuery('#opc_delivery_methods input.delivery_option_radio').each(function () {
                        if (jQuery("#" + this.id).prop("checked")) {
                            if (this.value === parcelshopId + ',') {
                                // the parcel sender
                                if (!jQuery('#parcel-id').val()) {
                                    e.preventDefault();
                                    jQuery('.dpd-alert').show();
                                }
                            }
                        }
                    });
                })
            })
        }
    });



    function DpdInitGoogleMaps() {
        jQuery('#dpd-connect\\\\classes\\\\dpd-checkout-delivery-step .content .form-fields').append('<div id="parcelshops"></div>');
        jQuery('#parcelshops').append('<div id="googlemap"></div>');
        jQuery('#parcelshops').append('<ul id="googlemap_shops"></ul>');
        parcelShops.map(function (shop) {
            var content = "<img src='/img/pickup.png'/><strong class='modal-title'>" + shop.company + "</strong><br/>" + shop.street + " " + shop.houseNo + "<br/>" + shop.zipCode + " " + shop.city + "<hr>";
            var openingshours = "";

            for (var i = 0; i < shop.openingHours.length; i++) {
                var openingshours = openingshours + "<div class='modal-week-row'><strong class='modal-day'>" + shop.openingHours[i].weekday + "</strong>" + " " + "<p>" + shop.openingHours[i].openMorning + " - " + shop.openingHours[i].closeMorning + "  " + shop.openingHours[i].openAfternoon + " - " + shop.openingHours[i].closeAfternoon + "</p></div>";
            }

            jQuery('#parcelshops').append('<div class="parcel_modal" id="info_' + shop.parcelShopId + '">' +
                '<img src="/modules/dpdconnect/img/pickup.png">' +
                '<a class="go-back"> Terug</a>' +
                '<strong class="modal-title">' + shop.company + '</strong><br>' +
                shop.street + ' ' + shop.houseNo + '<br>' + shop.zipCode + ' ' + shop.city +
                '<hr>' + openingshours +
                '<strong class="modal-link"><a id="' + shop.parcelShopId + '" class="ParcelShops">Ship to this parcel</a></strong>' +
                '</div>');

            jQuery('#parcelshops').on('click', '.go-back', function () {
                jQuery('#googlemap_shops').show();
                jQuery('.parcel_modal').hide();
            });

            var sidebar_item = jQuery("<li><div class='sidebar_single'><strong class='company'>" + shop.company + "</strong><br/><span class='address'>" + shop.street + " " + shop.houseNo + "</span><br/><span class='address'>" + shop.zipCode + " " + shop.city + "</span><br/><strong class='modal-link'><a id='more_info_" + shop.parcelShopId + "' class='more-information'>More information.</a></strong></div></li>");

            sidebar_item.on('click', '.more-information', function () {
                jQuery('#googlemap_shops').hide();
                jQuery('#info_' + shop.parcelShopId).show();
            });


            jQuery('#googlemap_shops').append(sidebar_item);
        });



        if (!(window.isPageLoaded)) {
            jQuery('head').append('<script src="https://maps.googleapis.com/maps/api/js?key={/literal}{$key}{literal}&callback=initMap"></s' + 'cript>');
            window.isPageLoaded = true;
        }
    }

    function initMap() {
        var styledMapType = new google.maps.StyledMapType(
            [
                {
                    "elementType": "geometry",
                    "stylers": [
                        {
                            "color": "#f5f5f5"
                        }
                    ]
                },
                {
                    "elementType": "labels.icon",
                    "stylers": [
                        {
                            "visibility": "off"
                        }
                    ]
                },
                {
                    "elementType": "labels.text.fill",
                    "stylers": [
                        {
                            "color": "#616161"
                        }
                    ]
                },
                {
                    "elementType": "labels.text.stroke",
                    "stylers": [
                        {
                            "color": "#f5f5f5"
                        }
                    ]
                },
                {
                    "featureType": "administrative.land_parcel",
                    "elementType": "labels.text.fill",
                    "stylers": [
                        {
                            "color": "#bdbdbd"
                        }
                    ]
                },
                {
                    "featureType": "poi",
                    "elementType": "geometry",
                    "stylers": [
                        {
                            "color": "#eeeeee"
                        }
                    ]
                },
                {
                    "featureType": "poi",
                    "elementType": "labels.text.fill",
                    "stylers": [
                        {
                            "color": "#757575"
                        }
                    ]
                },
                {
                    "featureType": "poi.park",
                    "elementType": "geometry",
                    "stylers": [
                        {
                            "color": "#e5e5e5"
                        }
                    ]
                },
                {
                    "featureType": "poi.park",
                    "elementType": "labels.text.fill",
                    "stylers": [
                        {
                            "color": "#9e9e9e"
                        }
                    ]
                },
                {
                    "featureType": "road",
                    "elementType": "geometry",
                    "stylers": [
                        {
                            "color": "#ffffff"
                        }
                    ]
                },
                {
                    "featureType": "road.arterial",
                    "elementType": "labels.text.fill",
                    "stylers": [
                        {
                            "color": "#757575"
                        }
                    ]
                },
                {
                    "featureType": "road.highway",
                    "elementType": "geometry",
                    "stylers": [
                        {
                            "color": "#dadada"
                        }
                    ]
                },
                {
                    "featureType": "road.highway",
                    "elementType": "labels.text.fill",
                    "stylers": [
                        {
                            "color": "#616161"
                        }
                    ]
                },
                {
                    "featureType": "road.local",
                    "elementType": "labels.text.fill",
                    "stylers": [
                        {
                            "color": "#9e9e9e"
                        }
                    ]
                },
                {
                    "featureType": "transit.line",
                    "elementType": "geometry",
                    "stylers": [
                        {
                            "color": "#e5e5e5"
                        }
                    ]
                },
                {
                    "featureType": "transit.station",
                    "elementType": "geometry",
                    "stylers": [
                        {
                            "color": "#eeeeee"
                        }
                    ]
                },
                {
                    "featureType": "water",
                    "elementType": "geometry",
                    "stylers": [
                        {
                            "color": "#d2e4f3"
                        }
                    ]
                },
                {
                    "featureType": "water",
                    "elementType": "labels.text.fill",
                    "stylers": [
                        {
                            "color": "#9e9e9e"
                        }
                    ]
                }
            ],
            {name: 'Styled Map'});

        // Create a map object, and include the MapTypeId to add
        // to the map type control.
        window.map = new google.maps.Map(document.getElementById('googlemap'), {
            center: {lat: latitude, lng: longitude},
            zoom: 11,
            mapTypeControlOptions: {
                mapTypeIds: ['styled_map']
            }
        });

        //Associate the styled map with the MapTypeId and set it to display.
        window.map.mapTypes.set('styled_map', styledMapType);
        window.map.setMapTypeId('styled_map');

        setParcelshops(parcelShops);
    }
    function setParcelshops(parcelshops) {

        parcelshops.map(function(shop) {
            var marker_image = new google.maps.MarkerImage('/modules/dpdconnect/img/pickup.png', new google.maps.Size(57, 62), new google.maps.Point(0, 0), new google.maps.Point(0, 31));

            var marker = new google.maps.Marker({
                position: new google.maps.LatLng(parseFloat(shop.latitude),parseFloat(shop.longitude)),
                icon: marker_image,
                map: window.map
            });

            var infowindow = new google.maps.InfoWindow();


            var content = "<img src='/modules/dpdconnect/img/pickup.png'/><strong class='modal-title'>"+shop.company+"</strong><br/>"+ shop.street + " " + shop.houseNo + "<br/>" + shop.zipCode + " " + shop.city + "<hr>";
            var openingshours = "";

            for (var i = 0; i < shop.openingHours.length; i++) {
                var openingshours = openingshours + "<div class='modal-week-row'><strong class='modal-day'>" +shop.openingHours[i].weekday + "</strong>" + " "+ "<p>"+ shop.openingHours[i].openMorning + " - " + shop.openingHours[i].closeMorning + "  " + shop.openingHours[i].openAfternoon + " - " + shop.openingHours[i].closeAfternoon +"</p></div>";
            }

            infowindow.setContent(
                "<div class='info-modal-content'>" +
                    content +
                    "<strong class='modal-link'><a id='"+shop.parcelShopId+"' class='ParcelShops'>{/literal}{l s='Ship to this parcelshop'}{literal}</a></strong> " +
                    openingshours +
                "</div>"
            );
            window.infowindows.push(infowindow);

            google.maps.event.addListener(marker, 'click', (function (marker) {
                return function () {
                    infowindow.open(window.map, marker);
                }
            })(marker));


            window.markers.push(marker);
            $("alert alert-danger").hide();
        });
    }

    function addChosen(parcelId){
        var verified = false;
        jQuery(parcelShops).each(function (index, value) {
            if (value.parcelShopId == parcelId){
                verified = true;
                if(jQuery('.selected-parcelshop').length !== 0){
                    jQuery('.selected-parcelshop').remove();
                }
                selectedParcelShop = "<ul class='selected-parcelshop'> <li> <div class='sidebar_single'> <strong class='company'>" + value.company + "</strong> <br> <span class='address'>" + value.street + " " + value.houseNo + "</span> <br /> <span class='address '>" + value.zipCode + " " + value.city + "</span> <br /> </div> </li> </ul>";
                jQuery('#parcelshops').parent().append(selectedParcelShop);
            }
        });
        if(verified){
            jQuery("#parcel-id").val(parcelId);
        }

    }
    {/literal}
</script>

<div class="dpd-alert alert alert-danger">
    <p>{l s='There is 1 error' d='Modules.dpd_carrier'}</p>
    <ol>
        <li>{l s='Please select a parcelshop' d='Modules.dpd_carrier'}</li>
    </ol>
</div>
