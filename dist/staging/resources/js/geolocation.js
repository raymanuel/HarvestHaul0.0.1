/**
 * Shared geolocation & reverse-geocoding helpers.
 *
 * Extracted from profile-farmer.blade.php and register-farmer.blade.php
 * to eliminate duplicated code.
 *
 * Usage:
 *   import { getCurrentPosition, reverseGeocode, parseAddressParts } from '../geolocation';
 */

/* ------------------------------------------------------------------ */
/*  Promisified geolocation                                            */
/* ------------------------------------------------------------------ */

/**
 * Wraps navigator.geolocation.getCurrentPosition in a Promise.
 *
 * @param {Object} [options] – override any PositionOptions fields.
 * @returns {Promise<GeolocationPosition>}
 */
export function getCurrentPosition(options = {}) {
    return new Promise((resolve, reject) => {
        navigator.geolocation.getCurrentPosition(resolve, reject, {
            enableHighAccuracy: true,
            timeout:            10000,
            maximumAge:         300000,
            ...options,
        });
    });
}

/* ------------------------------------------------------------------ */
/*  Reverse geocoding via Nominatim                                    */
/* ------------------------------------------------------------------ */

/**
 * Query OpenStreetMap Nominatim for the address at (lat, lng).
 *
 * @param {number} lat
 * @param {number} lng
 * @returns {Promise<Object>} raw Nominatim JSON response.
 */
export async function reverseGeocode(lat, lng) {
    const url =
        `https://nominatim.openstreetmap.org/reverse` +
        `?format=json&lat=${lat}&lon=${lng}&zoom=18&addressdetails=1`;
    const response = await fetch(url, {
        headers: { 'Accept-Language': 'en' },
    });
    if (!response.ok) throw new Error(`Nominatim ${response.status}`);
    return response.json();
}

/* ------------------------------------------------------------------ */
/*  Address-part parser (HarvestHaul convention)                       */
/* ------------------------------------------------------------------ */

/**
 * Pull the most meaningful address components from a Nominatim response
 * and join them into a short display string.
 *
 * @param {Object} data – Nominatim JSON response.
 * @returns {string} e.g. "Barangay San Isidro, General Santos, South Cotabato"
 */
export function parseAddressParts(data) {
    if (!data || !data.display_name) return '';

    const addr = data.address || {};
    const parts = [
        addr.village      || addr.suburb || addr.neighbourhood || addr.hamlet,
        addr.city         || addr.town   || addr.municipality,
        addr.province     || addr.state,
    ].filter(Boolean);

    return parts.length ? parts.join(', ') : data.display_name;
}
