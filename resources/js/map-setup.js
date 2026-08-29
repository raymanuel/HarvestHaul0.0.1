/**
 * Shared Leaflet map helpers.
 *
 * Extracted from route-optimization.blade.php, profile-farmer.blade.php,
 * profile-logistics.blade.php, and tracking/index.blade.php.
 *
 * Usage:
 *   import { initMap, createCircleIcon, fitBounds } from '../map-setup';
 *
 * Assumes Leaflet (L) is available globally via a <script> tag or
 * window.L.
 */

/* ------------------------------------------------------------------ */
/*  Map initialisation                                                 */
/* ------------------------------------------------------------------ */

/**
 * Create a Leaflet map centred on the HarvestHaul default location
 * (Southern Mindanao) with standard OSM tiles.
 *
 * @param {string} elementId – id of the container div (without `#`).
 * @param {Object} [options]
 * @param {number} [options.lat]   – default 6.1164
 * @param {number} [options.lng]   – default 125.1716
 * @param {number} [options.zoom]  – default 13
 * @param {number} [options.maxZoom] – default 19
 * @returns {L.Map}
 */
export function initMap(elementId, options = {}) {
    const {
        lat     = 6.1164,
        lng     = 125.1716,
        zoom    = 13,
        maxZoom = 19,
    } = options;

    const map = L.map(elementId).setView([lat, lng], zoom);

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors',
        maxZoom,
    }).addTo(map);

    return map;
}

/* ------------------------------------------------------------------ */
/*  Circle-style icons (dots)                                          */
/* ------------------------------------------------------------------ */

/**
 * Create a simple coloured circle marker icon (used for farmers,
 * buyers, truck stops, etc.).
 *
 * @param {Object}  opts
 * @param {string}  [opts.color]      – CSS colour, default '#16283C'
 * @param {number}  [opts.size]       – diameter in px, default 20
 * @param {string}  [opts.borderColor] – default 'white'
 * @param {string}  [opts.label]       – optional text inside the circle
 * @returns {L.DivIcon}
 */
export function createCircleIcon(opts = {}) {
    const {
        color       = '#16283C',
        size        = 20,
        borderColor = 'white',
        label       = '',
    } = opts;

    const half = size / 2;
    const fontSize = Math.round(size * 0.45);

    const inner = label
        ? `<span style="
               display:flex; align-items:center; justify-content:center;
               width:100%; height:100%; color:#fff;
               font-size:${fontSize}px; font-weight:700;
               line-height:1;">${label}</span>`
        : '';

    return L.divIcon({
        className: '',
        iconSize:  [size, size],
        iconAnchor: [half, half],
        html: `<div style="
            width:${size}px; height:${size}px;
            border-radius:50%;
            background:${color};
            border:3px solid ${borderColor};
            box-shadow:0 2px 6px rgba(0,0,0,0.3);
            display:flex; align-items:center; justify-content:center;
        ">${inner}</div>`,
    });
}

/* ------------------------------------------------------------------ */
/*  Lettered pin icons (route optimisation style)                      */
/* ------------------------------------------------------------------ */

/**
 * Create a map-marker-style pin with a letter inside.
 *
 * @param {string} letter – single character to display.
 * @param {Object} [opts]
 * @param {string} [opts.bgColor]   – default '#16283C'
 * @param {string} [opts.textColor] – default '#fff'
 * @returns {L.DivIcon}
 */
export function createLetterPin(letter, opts = {}) {
    const { bgColor = '#16283C', textColor = '#fff' } = opts;

    return L.divIcon({
        className: 'custom-marker',
        iconSize:  [32, 42],
        iconAnchor: [16, 42],
        html: `<div style="
            width:32px; height:42px; position:relative;
        ">
            <div style="
                width:32px; height:32px; border-radius:50% 50% 50% 0;
                background:${bgColor}; transform:rotate(-45deg);
                display:flex; align-items:center; justify-content:center;
                box-shadow:0 2px 6px rgba(0,0,0,0.3);
            ">
                <span style="
                    transform:rotate(45deg);
                    color:${textColor}; font-weight:700; font-size:14px;
                ">${letter}</span>
            </div>
        </div>`,
    });
}

/* ------------------------------------------------------------------ */
/*  Utility: fit map to bounds                                         */
/* ------------------------------------------------------------------ */

/**
 * Fit a Leaflet map to the given coordinates with padding,
 * handling the single-point edge case.
 *
 * @param {L.Map}    map
 * @param {Array<[number, number]>} coords – array of [lat, lng].
 * @param {Object}   [opts]
 * @param {number}   [opts.padding] – default 50
 * @param {number}   [opts.maxZoom] – fallback zoom for single point
 */
export function fitBounds(map, coords, opts = {}) {
    const { padding = 50, maxZoom = 16 } = opts;
    if (!coords.length) return;
    if (coords.length === 1) {
        map.setView(coords[0], maxZoom);
    } else {
        map.fitBounds(L.latLngBounds(coords), { padding });
    }
}
