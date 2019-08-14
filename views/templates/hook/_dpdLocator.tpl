<!-- DPDCarier !-->
<input name="parcel-id" type="hidden" id="parcel-id"/>
{literal}
<script>
    const parcelshopId = {/literal}{$parcelshopId}{literal};
    const sender = {/literal}{$sender}{literal};
    var longitude = {/literal}{$longitude|default:'""'}{literal};
    var latitude = {/literal}{$latitude|default:'""'}{literal};
    var parcelShops = {/literal} {$parcelshops|default:'""'} {literal};
    var saturdaySenderIsAllowed = {/literal} {$saturdaySenderIsAllowed} {literal};
    var saturdaySender = {/literal} {$saturdaySender} {literal};
    var classicSaturdaySender = {/literal} {$classicSaturdaySender} {literal};
    var cookieParcelId = "{/literal}{$cookieParcelId}{literal}";

    window.markers = [];
    window.infowindows = [];
    // saturdaycarrier
    if(!saturdaySenderIsAllowed){
        $('#opc_delivery_methods input.delivery_option_radio').each(function () {
            if(this.value === saturdaySender + ',' || this.value === classicSaturdaySender + ','){
                $(this).parent().parent().parent().parent().remove();
            };
        });
    }

    if(longitude != "" && latitude != "" && parcelShops != "") {
        if (!(window.isPageLoaded)) {
            DpdInitGoogleMaps();
            addChosen(cookieParcelId);
            $("#parcelshops").show();

        }
        if (orderProcess == 'order') {
            // normal checkout
            $(document).on('ready', function () {
                $('[id^="delivery_option_"]').each(function () {
                    if ($("#" + this.id).prop("checked")) {
                        if (this.value == parcelshopId + ',') {
                            $('#parcelshops').show();
                            $('.selected-parcelshop').show();
                        }
                    }
                });

                $(".delivery_options").on('change', '.delivery_option_radio', function () {
                    if ($("#" + this.id).prop("checked")) {
                        if (this.value === parcelshopId + ',') {
                            $('#parcelshops').show();
                        } else {
                            $('#parcelshops').hide();
                        }
                    }
                });
            });

            $("#parcelshops").on("click", ".ParcelShops", function () {
                $('#parcel-id').val(this.id).trigger('change');
                $('.standard-checkout').click();
            });

            $("#parcel-id").on('change', function () {
                addChosen($(this).val());
            });
        }
        else if (orderProcess == 'order-opc') {
            // one page checkout
            if ($("#parcelshops").length == 0) {
                DpdInitGoogleMaps();
                $('#opc_delivery_methods input.delivery_option_radio').each(function () {
                    if ($("#" + this.id).prop("checked")) {
                        if (this.value === parcelshopId + ',') {
                            // the the parcelshop sender is selected
                            $('#parcelshops').show();
                            initMap();
                            addChosen(cookieParcelId);
                        }
                    }
                });
            }

            $('#opc_delivery_methods input.delivery_option_radio').each(function () {
                if ($("#" + this.id).prop("checked")) {
                    if (this.value === parcelshopId + ',') {
                        $(document).on('ready', function () {
                            // the the parcelshop sender is selected
                            $('#parcelshops').show();
                            if (cookieParcelId.length !== 0) {
                                addChosen(cookieParcelId);
                            }
                        });
                    }
                }
            });

            $("#opc_delivery_methods").on('change', '.delivery_option_radio', function () {
                if (this.value === parcelshopId + ',') {
                    $('#parcelshops').show();
                } else {
                    $('#parcelshops').hide();
                }
            });

            $("#parcelshops").on("click", ".ParcelShops", function () {
                $('#parcel-id').val(this.id).trigger('change');
            });

            $("#parcel-id").on('change', function () {
                $.ajax({
                    type: 'POST',
                    url: baseUri + 'module/dpdconnect/OneStepParcelshop',
                    data: 'method=setParcelShop&parcelId=' + $(this).val() + '&parcelShopSenderId=' + parcelshopId + '&sender=' + sender,
                    dataType: 'json'
                });
                $(".dpd-alert").hide();
                closeInfoWindows();
                addChosen($(this).val());
            });


            // for verifying
            $(document).on('ready', function () {
                $('#opc_payment_methods-content').on('click', 'a', function (e) {
                    $('#opc_delivery_methods input.delivery_option_radio').each(function () {
                        if ($("#" + this.id).prop("checked")) {
                            if (this.value === parcelshopId + ',') {
                                // the parcel sender
                                if (!$('#parcel-id').val()) {
                                    e.preventDefault();
                                    $('.dpd-alert').show();
                                }
                            }
                        }
                    });
                })
            })
        }
    }

    function DpdInitGoogleMaps() {
        $('.delivery_options').append('<div id="parcelshops"></div>');
        $('#parcelshops').append('<div id="googlemap"></div>');
        $('#parcelshops').append('<ul id="googlemap_shops"></ul>');
        parcelShops.parcelShop.map(function (shop) {
            var content = "<img src='/img/pickup.png'/><strong class='modal-title'>" + shop.company + "</strong><br/>" + shop.street + " " + shop.houseNo + "<br/>" + shop.zipCode + " " + shop.city + "<hr>";
            var openingshours = "";

            for (var i = 0; i < shop.openingHours.length; i++) {
                var openingshours = openingshours + "<div class='modal-week-row'><strong class='modal-day'>" + shop.openingHours[i].weekday + "</strong>" + " " + "<p>" + shop.openingHours[i].openMorning + " - " + shop.openingHours[i].closeMorning + "  " + shop.openingHours[i].openAfternoon + " - " + shop.openingHours[i].closeAfternoon + "</p></div>";
            }

            $('#parcelshops').append('<div class="parcel_modal" id="info_' + shop.parcelShopId + '">' +
                '<img src="/modules/dpdconnect/img/pickup.png">' +
                '<a class="go-back"> Terug</a>' +
                '<strong class="modal-title">' + shop.company + '</strong><br>' +
                shop.street + ' ' + shop.houseNo + '<br>' + shop.zipCode + ' ' + shop.city +
                '<hr>' + openingshours +
                '<strong class="modal-link"><a id="' + shop.parcelShopId + '" class="ParcelShops">Ship to this parcel</a></strong>' +
                '</div>');

            $('#parcelshops').on('click', '.go-back', function () {
                $('#googlemap_shops').show();
                $('.parcel_modal').hide();
            });

            var sidebar_item = $("<li><div class='sidebar_single'><strong class='company'>" + shop.company + "</strong><br/><span class='address'>" + shop.street + " " + shop.houseNo + "</span><br/><span class='address'>" + shop.zipCode + " " + shop.city + "</span><br/><strong class='modal-link'><a id='more_info_" + shop.parcelShopId + "' class='more-information'>More information.</a></strong></div></li>");

            sidebar_item.on('click', '.more-information', function () {
                $('#googlemap_shops').hide();
                $('#info_' + shop.parcelShopId).show();
            });


            $('#googlemap_shops').append(sidebar_item);
        });

        if (!(window.isPageLoaded)) {
            $('head').append('<script src="https://maps.googleapis.com/maps/api/js?key={/literal}{$key}{literal}&callback=initMap"></s' + 'cript>');
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

        parcelshops.parcelShop.map(function(shop) {
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

            infowindow.setContent("<div class='info-modal-content'>" + content + openingshours + "<strong class='modal-link'><a id='"+shop.parcelShopId+"' class='ParcelShops'>{/literal}{l s='Ship to this parcel'}{literal}</a></strong> " + "</div>");
            window.infowindows.push(infowindow);

            google.maps.event.addListener(marker, 'click', (function (marker) {
                return function () {
                    closeInfoWindows();
                    infowindow.open(window.map, marker);
                }
            })(marker));

            window.markers.push(marker);

        });
    }

    function closeInfoWindows() {
        for (var i = 0; i < window.infowindows.length; i++) {
            window.infowindows[i].close();
        }
    }


    function addChosen(parcelId){
        var verified = false;
        $(parcelShops.parcelShop).each(function (index, value) {
            if (value.parcelShopId == parcelId){
                verified = true;
                if($('.selected-parcelshop').length !== 0){
                    $('.selected-parcelshop').remove();
                }
                selectedParcelShop = "<ul class='selected-parcelshop'> <li> <div class='sidebar_single'> <strong class='company'>" + value.company + "</strong> <br> <span class='address'>" + value.street + " " + value.houseNo + "</span> <br /> <span class='address '>" + value.zipCode + " " + value.city + "</span> <br /> </div> </li> </ul>";
                $('#parcelshops').parent().append(selectedParcelShop);
            }
        });
        if(verified){
            $("#parcel-id").val(parcelId);
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
