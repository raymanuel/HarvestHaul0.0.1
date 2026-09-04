/* ------------------------------------------------------------------ */
/*  Dark-mode-aware color constants                                    */
/* ------------------------------------------------------------------ */

const SWAL_COLORS = {
    get background() {
        return document.documentElement.classList.contains('dark')
            ? '#1e293b'
            : '#fff';
    },
    get color() {
        return document.documentElement.classList.contains('dark')
            ? '#e2e8f0'
            : '#1e293b';
    },
};

/* Theme-aware confirm-button fill. Keeps the brand navy/gold family but
   trades the exact tone so the button reads against the popup surface:
   - navy → gold-light on dark popups, navy on light popups
   - gold → dark-gold on light popups (AA), gold-light on dark popups
   Any other hint (amber/red/…) is returned untouched. */
function resolveConfirmColor(hint) {
    var dark = document.documentElement.classList.contains('dark');
    if (hint) {
        var h = hint.toLowerCase();
        if (h === '#16283c') return dark ? '#D7BC7A' : '#16283C';
        if (h === '#bfa05a') return dark ? '#D7BC7A' : '#7C6527';
        return hint;
    }
    return dark ? '#D7BC7A' : '#16283C';
}

/* SweetAlert defaults confirm-button text to white. On the light-gold fill
   that fails contrast, so swap it to ink. */
function confirmTextColor(fill) {
    var f = fill && fill.toLowerCase();
    return (f === '#d7bc7a' || f === '#bfa05a') ? '#0E1620' : '#fff';
}

function isDarkTheme() {
    return document.documentElement.classList.contains('dark');
}

/* ------------------------------------------------------------------ */
/*  Global SweetAlert confirm helper                                   */
/* ------------------------------------------------------------------ */

/**
 * Show a confirmation dialog and submit a form or invoke a callback
 * when the user confirms.
 *
 * @param {HTMLFormElement|Function} formOrCallback  – form to .submit() or
 *        a plain function to call on confirm.
 * @param {Object}  opts
 * @param {string}  [opts.title]        – dialog title
 * @param {string}  [opts.text]         – dialog body text
 * @param {string}  [opts.icon]         – SweetAlert icon (warning / error / …)
 * @param {string}  [opts.confirmText]  – confirm-button label
 * @param {string}  [opts.cancelText]   – cancel-button label
 * @param {string}  [opts.confirmColor] – confirm-button background colour
 * @param {string}  [opts.cancelColor]  – cancel-button background colour
 */
function swalConfirm(formOrCallback, opts = {}) {
    const defaults = {
        title:        opts.title       || 'Are you sure?',
        text:         opts.text        || 'This action cannot be undone.',
        icon:         opts.icon        || 'warning',
        confirmText:  opts.confirmText  || 'Yes, proceed',
        cancelText:   opts.cancelText   || 'Cancel',
        confirmColor: opts.confirmColor || (isDarkTheme() ? '#D7BC7A' : '#16283C'),
        cancelColor:  opts.cancelColor  || '#5A6573',
    };

    const confirmFill = resolveConfirmColor(defaults.confirmColor);

    Swal.fire({
        title:              defaults.title,
        text:               defaults.text,
        icon:               defaults.icon,
        showCancelButton:   true,
        confirmButtonText:  defaults.confirmText,
        cancelButtonText:   defaults.cancelText,
        confirmButtonColor: confirmFill,
        cancelButtonColor:  defaults.cancelColor,
        background:         SWAL_COLORS.background,
        color:              SWAL_COLORS.color,
        customClass:        { popup: 'rounded-xl shadow-2xl' },
        reverseButtons:     true,
        didOpen:            () => {
            const btn = document.querySelector('.swal2-confirm');
            if (btn) btn.style.color = confirmTextColor(confirmFill);
        },
    }).then((result) => {
        if (result.isConfirmed) {
            if (typeof formOrCallback === 'function') {
                formOrCallback();
            } else if (formOrCallback && formOrCallback.submit) {
                formOrCallback.submit();
            }
        }
    });
}

window.swalConfirm = swalConfirm;

/* ------------------------------------------------------------------ */
/*  Next-steps popup (AJAX flows that have no inline flash banner)     */
/* ------------------------------------------------------------------ */

/**
 * Render `window.__nextSteps` ({title, message, steps[], cta{label,url}})
 * as a single SweetAlert. Used by client-side (AJAX) flows where there
 * is no page reload and therefore no inline flash banner.
 */
function showNextSteps() {
    const ns = window.__nextSteps;
    if (!ns) return;

    const stepsHtml = (Array.isArray(ns.steps) && ns.steps.length)
        ? '<div style="text-align:left;font-size:13px;line-height:1.6;margin-top:8px">' +
          ns.steps.map(s => '<div style="display:flex;gap:8px"><span>' + '→' + '</span><span>' + s + '</span></div>').join('') +
          '</div>'
        : '';

    const cta = (ns.cta && ns.cta.label && ns.cta.url)
        ? '<a href="' + ns.cta.url + '" style="display:inline-block;margin-top:14px;background:' + (isDarkTheme() ? '#D7BC7A' : '#16283C') + ';color:' + (isDarkTheme() ? '#0E1620' : '#fff') + ';font-weight:700;font-size:13px;padding:8px 16px;border-radius:10px">' + ns.cta.label + '</a>'
        : '';

    const confirmFill = resolveConfirmColor('#16283C');

    Swal.fire({
        title: ns.title || 'Done',
        html: '<div>' + (ns.message || '') + stepsHtml + '</div>' + cta,
        icon: 'success',
        showConfirmButton: true,
        confirmButtonText: 'OK',
        confirmButtonColor: confirmFill,
        background: SWAL_COLORS.background,
        color: SWAL_COLORS.color,
        customClass: { popup: 'rounded-xl shadow-lg' },
        ariaLive: 'assertive',
        didOpen: () => {
            const btn = document.querySelector('.swal2-confirm');
            if (btn) btn.style.color = confirmTextColor(confirmFill);
        },
    });
}

window.showNextSteps = showNextSteps;

/* ------------------------------------------------------------------ */
/*  Toast popup (replaces inline flash banners)                        */
/* ------------------------------------------------------------------ */

/**
 * Show an auto-dismissing top-right toast. Type-coloured icon + accent.
 *
 * @param {string} type     – 'success' | 'error' | 'warning'
 * @param {string} message  – toast body text
 * @param {Object} [cta]    – { label, url } optional call-to-action link
 */
function swalToast(type, message, cta) {
    if (!window.Swal || !message) return;

    var iconColor = {
        success: '#16a34a',
        error:   '#b91c1c',
        warning: '#a16207',
    }[type] || '#16283C';

    var ctaHtml = '';
    if (cta && cta.label && cta.url) {
        ctaHtml = '<a href="' + cta.url + '" style="display:inline-block;margin-left:10px;margin-top:2px;background:' + (isDarkTheme() ? '#D7BC7A' : '#16283C') + ';color:' + (isDarkTheme() ? '#0E1620' : '#fff') + ';font-weight:700;font-size:11px;padding:5px 12px;border-radius:8px;text-decoration:none">' + cta.label + '</a>';
    }

    Swal.fire({
        icon: type === 'warning' ? 'warning' : (type === 'error' ? 'error' : 'success'),
        html: '<div style="display:inline">' + message + '</div>' + ctaHtml,
        toast: true,
        position: 'top-end',
        timer: 4500,
        timerProgressBar: true,
        showConfirmButton: false,
        background: SWAL_COLORS.background,
        color: SWAL_COLORS.color,
        customClass: {
            popup: 'rounded-xl shadow-lg border text-xs',
            title: 'font-bold',
        },
    });
    setTimeout(function () {
        var pop = document.querySelector('.swal2-toast .swal2-popup');
        if (pop) pop.style.border = '1px solid ' + iconColor + '44';
    }, 0);
}

window.swalToast = swalToast;

document.addEventListener('DOMContentLoaded', function () {
    showNextSteps();
});
