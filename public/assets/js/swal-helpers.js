/**
 * Shared SweetAlert2 helpers extracted from layout.blade.php.
 *
 * Usage (ES module / Vite):
 *   import { SWAL_COLORS, swalConfirm, handleFlashMessages } from '../swal-helpers';
 */

/* ------------------------------------------------------------------ */
/*  Dark-mode-aware color constants                                    */
/* ------------------------------------------------------------------ */

export const SWAL_COLORS = {
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
export function swalConfirm(formOrCallback, opts = {}) {
    const defaults = {
        title:        opts.title       || 'Are you sure?',
        text:         opts.text        || 'This action cannot be undone.',
        icon:         opts.icon        || 'warning',
        confirmText:  opts.confirmText  || 'Yes, proceed',
        cancelText:   opts.cancelText   || 'Cancel',
        confirmColor: opts.confirmColor || '#16283C',
        cancelColor:  opts.cancelColor  || '#64748b',
    };

    Swal.fire({
        title:              defaults.title,
        text:               defaults.text,
        icon:               defaults.icon,
        showCancelButton:   true,
        confirmButtonText:  defaults.confirmText,
        cancelButtonText:   defaults.cancelText,
        confirmButtonColor: defaults.confirmColor,
        cancelButtonColor:  defaults.cancelColor,
        background:         SWAL_COLORS.background,
        color:              SWAL_COLORS.color,
        customClass:        { popup: 'rounded-xl shadow-2xl' },
        reverseButtons:     true,
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

/* ------------------------------------------------------------------ */
/*  Flash-message handler                                              */
/* ------------------------------------------------------------------ */

/**
 * Read `session` flash data from `<meta>` tags (or a global config object)
 * and display the corresponding SweetAlert toast / popup.
 *
 * For Blade integration, expose session values via meta tags:
 *
 *   @if(session('error'))
 *     <meta name="flash-error" content="{{ session('error') }}">
 *   @endif
 *   @if(session('warning'))
 *     <meta name="flash-warning" content="{{ session('warning') }}">
 *   @endif
 *
 * Then call  handleFlashMessages()  on DOMContentLoaded.
 */
export function handleFlashMessages() {
    const errorMeta   = document.querySelector('meta[name="flash-error"]');
    const warningMeta = document.querySelector('meta[name="flash-warning"]');

    if (errorMeta) {
        const errorText = errorMeta.getAttribute('content');
        const isGateNotice =
            errorText.includes('pending verification') ||
            errorText.includes('No new data');

        Swal.fire({
            icon:               isGateNotice ? 'info'    : 'error',
            title:              isGateNotice ? 'Notice'  : 'Error',
            text:               errorText,
            timer:              4500,
            timerProgressBar:   true,
            showConfirmButton:  true,
            confirmButtonText:  'OK',
            confirmButtonColor: isGateNotice ? '#16283C' : '#ef4444',
            toast:              false,
            background:         SWAL_COLORS.background,
            color:              SWAL_COLORS.color,
            customClass:        { popup: 'rounded-xl shadow-lg' },
            ariaLive:           'assertive',
        });
    }

    if (warningMeta) {
        Swal.fire({
            icon:               'warning',
            title:              'Notice',
            text:               warningMeta.getAttribute('content'),
            timer:              5000,
            timerProgressBar:   true,
            showConfirmButton:  true,
            confirmButtonText:  'OK',
            confirmButtonColor: '#f59e0b',
            toast:              false,
            background:         SWAL_COLORS.background,
            color:              SWAL_COLORS.color,
            customClass:        { popup: 'rounded-xl shadow-lg' },
            ariaLive:           'assertive',
        });
    }
}
