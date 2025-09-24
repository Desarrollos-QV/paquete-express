<input type="hidden" id="coords" value="{{ $data->coords }}" name="coords">
<div id="map" style="width:100%;height:500px;"></div>
<script src="https://unpkg.com/h3-js@4.1.0/dist/h3-js.umd.js"></script>
<script>
    function initMap() {
        const id = "{{ isset($data->id) ? $data->id : 'null' }}";
        var map;
        let zoneBase;
        let h3index;
        var geocoder = new google.maps.Geocoder;
        var coords = [];
        var coords_in = (document.getElementById('coords').value != '') ? JSON.parse(document.getElementById('coords')
            .value) : document.getElementById('coords');
        var perimetro;
        var coverage = document.getElementById('coverage');
        var input = document.getElementById('pac-input');
        var autocomplete = new google.maps.places.Autocomplete(input);
        var hexBoundary;
        // Pintamos el poligono

        console.log(id)
        navigator.geolocation.getCurrentPosition(
            (position) => {
                lat = position.coords.latitude;
                lng = position.coords.longitude;
                map = new google.maps.Map(
                    document.getElementById('map'), {
                        center: {
                            lat: lat,
                            lng: lng
                        },
                        zoom: 12,
                        disableDefaultUI: true
                    }
                );


                if (id != 'null') {
                    map.setCenter({
                        lat: coords_in[0].lat,
                        lng: coords_in[0].lng
                    });


                    // Define a zoneBase and set its editable property to true.
                    zoneBase = new google.maps.Polygon({
                        paths: coords_in,
                        strokeColor: "#FF0000",
                        strokeOpacity: 0.8,
                        strokeWeight: 2,
                        fillColor: "#FF0000",
                        fillOpacity: 0.35,
                        draggable: true,
                        editable: true,
                    });


                    zoneBase.setMap(map);

                    zoneBase.addListener("dragend", CreateCoordsArr);
                    zoneBase.addListener("changed", CreateCoordsArr);

                    google.maps.event.addListenerOnce(map, 'idle', function() {
                        GetCoordsArr();
                    });

                    google.maps.event.addListener(zoneBase.getPath(), 'set_at', function() {
                        GetCoordsArr();
                    });

                    google.maps.event.addListener(zoneBase.getPath(), 'insert_at', function() {
                        GetCoordsArr();
                    });
                } else {

                    // Specify just the place data fields that you need.
                    autocomplete.setFields(['place_id', 'geometry', 'name', 'formatted_address']);

                    // Convert a lat/lng point to a hexagon index at resolution 6
                    h3index = h3.latLngToCell(lat, lng, 7);

                    // Get the center of the hexagon
                    // const hexCenterCoordinates = h3.cellToLatLng(h3index);
                    // Get the vertices of the hexagon
                    hexBoundary = h3.cellToBoundary(h3index);

                    const triangleCoords = [];
                    // Rellenamos
                    for (let p = 0; p < hexBoundary.length; p++) {
                        const element = hexBoundary[p];
                        triangleCoords.push({
                            lat: element[0],
                            lng: element[1]
                        })
                    }

                    // Define a zoneBase and set its editable property to true.
                    zoneBase = new google.maps.Polygon({
                        paths: triangleCoords,
                        strokeColor: "#FF0000",
                        strokeOpacity: 0.8,
                        strokeWeight: 2,
                        fillColor: "#FF0000",
                        fillOpacity: 0.35,
                        draggable: true,
                        editable: true,
                    });


                    zoneBase.setMap(map);

                    zoneBase.addListener("dragend", CreateCoordsArr);
                    zoneBase.addListener("changed", CreateCoordsArr);

                    google.maps.event.addListenerOnce(map, 'idle', function() {
                        GetCoordsArr();
                    });

                    google.maps.event.addListener(zoneBase.getPath(), 'set_at', function() {
                        GetCoordsArr();
                    });

                    google.maps.event.addListener(zoneBase.getPath(), 'insert_at', function() {
                        GetCoordsArr();
                    });
                }
            },
            () => {
                handleLocationError(true, infowindow, map.getCenter());
            }
        );

        autocomplete.addListener('place_changed', function() {

            var place = autocomplete.getPlace();

            if (!place.place_id) {
                return;
            }

            geocoder.geocode({
                'placeId': place.place_id
            }, function(results, status) {
                if (status !== 'OK') {
                    window.alert('Geocoder failed due to: ' + status);
                    return;
                }

                map.setCenter(results[0].geometry.location);

                // Convert a lat/lng point to a hexagon index at resolution 6                
                h3index = h3.latLngToCell(results[0].geometry.location.lat(), results[0].geometry
                    .location.lng(), 6);

                // Get the center of the hexagon
                // const hexCenterCoordinates = h3.cellToLatLng(h3index);
                // Get the vertices of the hexagon
                hexBoundary = h3.cellToBoundary(h3index);

                const triangleCoords = [];
                // Rellenamos
                for (let p = 0; p < hexBoundary.length; p++) {
                    const element = hexBoundary[p];
                    triangleCoords.push({
                        lat: element[0],
                        lng: element[1]
                    })
                }

                // Actualizamos el poligono de ubicacion
                zoneBase.setPaths(triangleCoords);

                zoneBase.addListener("dragend", CreateCoordsArr);
                zoneBase.addListener("changed", CreateCoordsArr);

                google.maps.event.addListenerOnce(map, 'idle', function() {
                    GetCoordsArr();
                });

                google.maps.event.addListener(zoneBase.getPath(), 'set_at', function() {
                    GetCoordsArr();
                });

                google.maps.event.addListener(zoneBase.getPath(), 'insert_at', function() {
                    GetCoordsArr();
                });
            });
        });


        function CreateCoordsArr(event) {
            const polygon = this;
            const vertices = polygon.getPath();
            let coords = [];
            for (let i = 0; i < vertices.getLength(); i++) {
                const xy = vertices.getAt(i);
                coords.push({
                    lat: xy.lat(),
                    lng: xy.lng()
                });
            }
            console.log("New coords : ", coords);
            document.getElementById('coords').value = JSON.stringify(coords);
            perimetro = google.maps.geometry.spherical.computeLength(zoneBase.getPath());
            coverage.value = (perimetro / 1000);
        }

        function GetCoordsArr() {
            const vertices = zoneBase.getPath();
            let coords = [];
            for (let i = 0; i < vertices.getLength(); i++) {
                const xy = vertices.getAt(i);
                coords.push({
                    lat: xy.lat(),
                    lng: xy.lng()
                });
            }

            console.log("New coords : ", coords);
            document.getElementById('coords').value = JSON.stringify(coords);

            perimetro = google.maps.geometry.spherical.computeLength(zoneBase.getPath());
            coverage.value = (perimetro / 1000).toFixed(2);
        }
    }
</script>
<script
    src="https://maps.googleapis.com/maps/api/js?key=AIzaSyBqYcfefGddEiqR-OlfaLMSWP5m2RdMk18&libraries=places,geometry,drawing&callback=initMap">
</script>
