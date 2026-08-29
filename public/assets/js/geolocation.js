/**
 * Geolocation + reverse geocoding utilities.
 * Loaded via <script src> on pages that need location features.
 * Exposes: window.getCurrentPosition, window.reverseGeocode, window.useMyLocation
 */

function getCurrentPosition(options) {
    options = options || {};
    return new Promise(function(resolve, reject) {
        navigator.geolocation.getCurrentPosition(resolve, reject, {
            enableHighAccuracy: true,
            timeout: options.timeout || 10000,
            maximumAge: options.maximumAge || 300000
        });
    });
}

function reverseGeocode(lat, lng) {
    return fetch('https://nominatim.openstreetmap.org/reverse?format=json&lat=' + lat + '&lon=' + lng + '&zoom=18&addressdetails=1')
        .then(function(response) { return response.json(); });
}

function useMyLocation(marker, map, latInput, lngInput, addressInput) {
    if (!navigator.geolocation) {
        alert('Geolocation is not supported by your browser.');
        return;
    }
    navigator.geolocation.getCurrentPosition(
        function(position) {
            var lat = position.coords.latitude;
            var lng = position.coords.longitude;

            marker.setLatLng([lat, lng]);
            map.setView([lat, lng], 15);

            latInput.value = lat.toFixed(6);
            lngInput.value = lng.toFixed(6);

            reverseGeocode(lat, lng).then(function(data) {
                if (data && data.display_name && addressInput) {
                    addressInput.value = data.display_name;
                }
            }).catch(function() {});
        },
        function(error) {
            var msg = 'Unable to retrieve your location.';
            if (error.code === 1) msg = 'Location permission denied. Please allow location access in your browser settings.';
            else if (error.code === 2) msg = 'Location unavailable. Please try again.';
            else if (error.code === 3) msg = 'Location request timed out. Please try again.';
            alert(msg);
        },
        { enableHighAccuracy: true, timeout: 10000, maximumAge: 300000 }
    );
}
