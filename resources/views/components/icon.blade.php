@props([
    'name' => 'check',
    'size' => 'w-4 h-4',
    'class' => '',
])

@php
    $classes = "{$size} {$class}";
    $paths = '';

    switch ($name) {
        case 'check':
            $paths = '<path d="M20 6L9 17l-5-5"/>';
            break;
        case 'x-mark':
            $paths = '<path d="M18 6L6 18M6 6l12 12"/>';
            break;
        case 'warning':
            $paths = '<path d="M12 9v4m0 4h.01M10.29 3.86l-8.6 14.86A2 2 0 003.4 22h17.2a2 2 0 001.71-3.28l-8.6-14.86a2 2 0 00-3.42 0z"/>';
            break;
        case 'lock':
            $paths = '<rect x="5" y="11" width="14" height="10" rx="2"/><path d="M8 11V7a4 4 0 118 0v4"/>';
            break;
        case 'pin':
            $paths = '<path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7z"/><circle cx="12" cy="9" r="2.5"/>';
            break;
        case 'search':
            $paths = '<circle cx="11" cy="11" r="7"/><path d="M21 21l-4.35-4.35"/>';
            break;
        case 'folder':
            $paths = '<path d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z"/>';
            break;
        case 'tag':
            $paths = '<path d="M20.59 13.41l-7.17 7.17a2 2 0 01-2.83 0L2 12V2h10l8.59 8.59a2 2 0 010 2.82z"/><circle cx="7" cy="7" r="1.5" fill="currentColor"/>';
            break;
        case 'chat':
            $paths = '<path d="M21 15a2 2 0 01-2 2H7l-4 4V5a2 2 0 012-2h14a2 2 0 012 2v10z"/>';
            break;
        case 'document':
            $paths = '<path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8l-6-6z"/><path d="M14 2v6h6M16 13H8M16 17H8M10 9H8"/>';
            break;
        case 'camera':
            $paths = '<path d="M23 19a2 2 0 01-2 2H3a2 2 0 01-2-2V8a2 2 0 012-2h4l2-3h6l2 3h4a2 2 0 012 2v11z"/><circle cx="12" cy="13" r="4"/>';
            break;
        case 'paperclip':
            $paths = '<path d="M21.44 11.05l-9.19 9.19a6 6 0 01-8.49-8.49l9.19-9.19a4 4 0 015.66 5.66l-9.2 9.19a2 2 0 01-2.83-2.83l8.49-8.48"/>';
            break;
        case 'building':
            $paths = '<path d="M3 21h18M5 21V7l8-4v18M13 21V3l6 4v14"/><path d="M9 9h1M9 13h1M17 9h1M17 13h1"/>';
            break;
        case 'car':
            $paths = '<path d="M5 17h14M5 17a2 2 0 01-2-2v-4l2.6-5.2A2 2 0 017.4 5h9.2a2 2 0 011.8 1.1L21 11v4a2 2 0 01-2 2M5 17a2 2 0 002 2h10a2 2 0 002-2"/><circle cx="7.5" cy="17" r="1.5"/><circle cx="16.5" cy="17" r="1.5"/>';
            break;
        case 'map':
            $paths = '<polygon points="1 6 1 22 8 18 16 22 23 18 23 2 16 6 8 2 1 6"/><line x1="8" y1="2" x2="8" y2="18"/><line x1="16" y1="6" x2="16" y2="22"/>';
            break;
        case 'calculator':
            $paths = '<rect x="4" y="2" width="16" height="20" rx="2"/><path d="M8 6h8M8 10h8M8 14h4M8 18h4M16 14v4"/>';
            break;
        case 'sun':
            $paths = '<circle cx="12" cy="12" r="5"/><path d="M12 1v2M12 21v2M4.22 4.22l1.42 1.42M18.36 18.36l1.42 1.42M1 12h2M21 12h2M4.22 19.78l1.42-1.42M18.36 5.64l1.42-1.42"/>';
            break;
        case 'moon':
            $paths = '<path d="M21 12.79A9 9 0 1111.21 3 7 7 0 0021 12.79z"/>';
            break;
        case 'cloud-sun':
            $paths = '<path d="M12 2v2M4.93 4.93l1.41 1.41M20 12h2M17.66 4.93l-1.41 1.41"/><circle cx="12" cy="10" r="4"/><path d="M6 18a4 4 0 01-.88-7.9A5.5 5.5 0 0116.5 10H17a4 4 0 010 8H6z"/>';
            break;
        case 'gauge':
            $paths = '<path d="M12 2v4M12 18v4M4.93 4.93l2.83 2.83M16.24 16.24l2.83 2.83M2 12h4M18 12h4M4.93 19.07l2.83-2.83M16.24 7.76l2.83-2.83"/><circle cx="12" cy="12" r="3"/>';
            break;
        case 'fuel':
            $paths = '<path d="M3 22V6a2 2 0 012-2h8a2 2 0 012 2v16"/><path d="M3 22h12"/><path d="M15 9l4-2v9a3 3 0 01-6 0V7l2 2"/>';
            break;
        case 'plus':
            $paths = '<path d="M12 5v14M5 12h14"/>';
            break;
        case 'inbox':
            $paths = '<polyline points="22 12 16 12 14 15 10 15 8 12 2 12"/><path d="M5.45 5.11L2 12v6a2 2 0 002 2h16a2 2 0 002-2v-6l-3.45-6.89A2 2 0 0016.76 4H7.24a2 2 0 00-1.79 1.11z"/>';
            break;
        case 'seedling':
            $paths = '<path d="M12 22V12"/><path d="M12 12c0-4 4-7 8-7-1 4-4 7-8 7z"/><path d="M12 12c0-4-4-7-8-7 1 4 4 7 8 7z"/><path d="M6 20c0-3 3-5 6-5"/><path d="M18 20c0-3-3-5-6-5"/>';
            break;
        case 'edit':
            $paths = '<path d="M11 4H4a2 2 0 00-2 2v14a2 2 0 002 2h14a2 2 0 002-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 013 3L12 15l-4 1 1-4 9.5-9.5z"/>';
            break;
        case 'trash':
            $paths = '<polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 01-2 2H7a2 2 0 01-2-2V6m3 0V4a2 2 0 012-2h4a2 2 0 012 2v2"/>';
            break;
        case 'truck':
            $paths = '<rect x="1" y="3" width="15" height="13"/><polygon points="16 8 20 8 23 11 23 16 16 16 16 8"/><circle cx="5.5" cy="18.5" r="2.5"/><circle cx="18.5" cy="18.5" r="2.5"/>';
            break;
        case 'package':
            $paths = '<path d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>';
            break;
        case 'check-circle':
            $paths = '<path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>';
            break;
        case 'cart':
            $paths = '<path d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 100 4 2 2 0 000-4z"/>';
            break;
        case 'users':
            $paths = '<path d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/>';
            break;
        case 'map-pin':
            $paths = '<path d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>';
            break;
        case 'clock':
            $paths = '<path d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>';
            break;
        default:
            $paths = '<path d="M20 6L9 17l-5-5"/>';
            break;
    }
@endphp

<svg {{ $attributes->merge(['class' => $classes]) }} viewBox="0 0 24 24" fill="none" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">
    <g style="filter: drop-shadow(0.6px 0.9px 0.6px rgba(15, 46, 21, 0.30));">
        <g stroke="#0f2e15" stroke-opacity="0.34" stroke-width="3.4" transform="translate(0.8 1)">
            {!! $paths !!}
        </g>
        <g stroke="currentColor" stroke-width="2">
            {!! $paths !!}
        </g>
    </g>
</svg>
