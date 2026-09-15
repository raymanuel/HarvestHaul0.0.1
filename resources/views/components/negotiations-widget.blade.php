<style>
    .neg-badge-ping {
        animation: neg-ping 1.5s ease-in-out infinite;
    }
    @keyframes neg-ping {
        0% { transform: scale(1); }
        50% { transform: scale(1.15); }
        100% { transform: scale(1); }
    }
</style>
<div id="neg-widget" class="fixed bottom-6 right-6 z-50">
    {{-- Floating button --}}
    <button id="neg-toggle"
        class="w-14 h-14 rounded-full bg-[#16283C] hover:bg-brand-dark dark:bg-[#16283C] dark:hover:bg-[#0E1620] text-white dark:text-[#D7BC7A] shadow-lg transition-all duration-200 flex items-center justify-center cursor-pointer relative ring-2 ring-white/30 dark:ring-gold/40"
        aria-label="My Deals"
        aria-expanded="false"
        aria-controls="neg-popup"
    >
        <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
        </svg>
        <span id="neg-badge" class="hidden absolute -top-1 -right-1 w-5 h-5 rounded-full bg-rose-500 text-white text-[10px] font-extrabold flex items-center justify-center shadow-lg ring-2 ring-white dark:ring-slate-800" aria-live="polite" aria-label="Unread conversation count"></span>
    </button>

    {{-- Popup --}}
    <div id="neg-popup" role="dialog" aria-modal="false" aria-label="Deals conversations" tabindex="-1"
        class="hidden absolute bottom-16 right-0 w-[calc(100vw-3rem)] max-w-96 max-h-[min(520px,calc(100vh-6rem))] bg-white dark:bg-slate-800/95 border border-slate-200/60 dark:border-[#16283C]/20 rounded-2xl shadow-xl overflow-hidden flex flex-col origin-bottom-right"
    >
        {{-- Header --}}
        <div class="flex items-center justify-between px-5 py-4 border-b border-slate-100 dark:border-[#16283C]/10 shrink-0">
            <div>
                <h3 class="text-sm font-bold text-slate-800 dark:text-white heading-font">My Deals</h3>
                <p class="text-[10px] text-slate-500 dark:text-slate-400 font-medium">Your crop &amp; haul conversations</p>
            </div>
            <button id="neg-close" aria-label="Close negotiations panel" class="w-7 h-7 rounded-xl bg-slate-100 dark:bg-slate-700/60 hover:bg-slate-200 dark:hover:bg-slate-600/60 flex items-center justify-center text-slate-500 dark:text-slate-400 transition cursor-pointer">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>

        {{-- Loading --}}
        <div id="neg-loading" class="flex items-center justify-center py-12 hidden">
            <svg class="animate-spin w-6 h-6 text-[#16283C] dark:text-[#D7BC7A]" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
        </div>

        {{-- Empty --}}
        <div id="neg-empty" class="flex-col items-center justify-center py-12 px-6 text-center hidden">
            <div class="w-12 h-12 rounded-full bg-slate-100 dark:bg-slate-700/50 flex items-center justify-center mb-3">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6 text-slate-500 dark:text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
                </svg>
            </div>
            <p class="text-sm font-bold text-slate-600 dark:text-slate-300">No active deals</p>
            <p class="text-[10px] text-slate-500 dark:text-slate-400 mt-1">Browse the Crop Board to start a deal.</p>
        </div>

        {{-- Error --}}
        <div id="neg-error" class="flex-col items-center justify-center py-12 px-6 text-center hidden">
            <div class="w-12 h-12 rounded-full bg-slate-100 dark:bg-slate-700/50 flex items-center justify-center mb-3">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6 text-slate-500 dark:text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-2.024-.833-2.794 0L5.207 18.5c-.77.833.192 2.5 1.732 2.5z" />
                </svg>
            </div>
            <p class="text-sm font-bold text-slate-600 dark:text-slate-300">Couldn't load your deals</p>
            <p class="text-[10px] text-slate-500 dark:text-slate-400 mt-1">Check your connection and try again.</p>
            <button id="neg-retry" class="mt-3 px-4 py-2 rounded-xl bg-[#16283C]/10 hover:bg-[#16283C]/20 dark:bg-[#D7BC7A]/10 dark:hover:bg-[#D7BC7A]/20 text-xs font-bold text-[#16283C] dark:text-[#D7BC7A] transition cursor-pointer">Try again</button>
        </div>

        {{-- List --}}
        <div id="neg-list" class="overflow-y-auto flex-1 divide-y divide-slate-100 dark:divide-sky-900/20 hidden"></div>
    </div>
</div>

<script>
(function() {
    const widget = document.getElementById('neg-widget');
    const toggle = document.getElementById('neg-toggle');
    const closeBtn = document.getElementById('neg-close');
    const popup = document.getElementById('neg-popup');
    const loading = document.getElementById('neg-loading');
    const empty = document.getElementById('neg-empty');
    const error = document.getElementById('neg-error');
    const list = document.getElementById('neg-list');
    const badge = document.getElementById('neg-badge');
    const retry = document.getElementById('neg-retry');

    let isOpen = false;
    let pollTimer = null;

    function togglePopup() {
        isOpen = !isOpen;
        if (isOpen) {
            openPopup();
        } else {
            closePopup();
        }
    }

    function openPopup() {
        popup.classList.remove('hidden');
        popup.classList.add('flex');
        toggle.classList.add('scale-110');
        toggle.setAttribute('aria-expanded', 'true');
        badge.classList.add('hidden');
        badge.classList.remove('neg-badge-ping');
        loading.classList.remove('hidden');
        loading.classList.add('flex');
        list.classList.add('hidden');
        empty.classList.add('hidden');
        error.classList.add('hidden');
        error.classList.remove('flex');
        popup.focus();
        fetchList();
    }

    function closePopup() {
        isOpen = false;
        popup.classList.add('hidden');
        popup.classList.remove('flex');
        toggle.classList.remove('scale-110');
        toggle.setAttribute('aria-expanded', 'false');
        toggle.focus();
    }

    function fetchList() {
        fetch('/negotiations/list')
            .then(r => r.json())
            .then(data => {
                if (!isOpen) {
                    const totalUnread = data.negotiations
                        ? data.negotiations.reduce((sum, i) => sum + (i.unread_count || 0), 0)
                        : 0;
                    if (totalUnread > 0) {
                        badge.classList.remove('hidden');
                        badge.textContent = totalUnread > 99 ? '99+' : totalUnread;
                        badge.classList.add('neg-badge-ping');
                    } else {
                        badge.classList.add('hidden');
                        badge.classList.remove('neg-badge-ping');
                    }
                    return;
                }

                if (!data.negotiations || data.negotiations.length === 0) {
                    loading.classList.add('hidden');
                    loading.classList.remove('flex');
                    empty.classList.remove('hidden');
                    empty.classList.add('flex');
                    error.classList.add('hidden');
                    error.classList.remove('flex');
                    list.classList.add('hidden');
                    return;
                }

                loading.classList.add('hidden');
                loading.classList.remove('flex');
                empty.classList.add('hidden');
                empty.classList.remove('flex');
                error.classList.add('hidden');
                error.classList.remove('flex');
                list.classList.remove('hidden');
                renderList(data.negotiations);
            })
            .catch(() => {
                badge.classList.add('hidden');
                badge.classList.remove('neg-badge-ping');
                if (isOpen) {
                    loading.classList.add('hidden');
                    loading.classList.remove('flex');
                    empty.classList.add('hidden');
                    empty.classList.remove('flex');
                    list.classList.add('hidden');
                    error.classList.remove('hidden');
                    error.classList.add('flex');
                }
            });
    }

    function renderList(items) {
        list.innerHTML = '';
        items.forEach(item => {
            const statusClass = item.type === 'haul'
                ? 'text-soil dark:text-soil-light bg-soil/10 border border-soil/10'
                : item.status === 'OPEN'
                ? 'text-gold-700 dark:text-gold-light bg-gold/10 border border-gold/10'
                : item.status === 'AGREED'
                ? 'text-[#16283C] dark:text-[#D7BC7A] bg-[#16283C]/10 border border-[#16283C]/10'
                : 'text-[#0E1620] dark:text-[#E9EEF4] bg-[#0E1620]/10 border border-[#0E1620]/10';
            const initial = item.counterpart_name ? item.counterpart_name.charAt(0).toUpperCase() : '?';
            const avatarBg = item.is_buyer
                ? 'bg-gold/10 dark:bg-gold/20 text-gold-700 dark:text-gold-light'
                : 'bg-[#16283C]/10 dark:bg-[#16283C]/10 text-[#16283C] dark:text-[#D7BC7A]';
            const volume = item.volume ? ' ' + Number(item.volume).toLocaleString() + ' kg' : '';
            const typeTag = item.type === 'haul'
                ? '<span class="inline-flex items-center px-1.5 py-0.5 rounded bg-slate-100 dark:bg-slate-700/60 text-[10px] font-bold uppercase tracking-wider text-slate-500 dark:text-slate-300 mr-1.5 align-middle">Haul</span>'
                : '';

            const unreadBadge = item.unread_count > 0
                ? '<span class="ml-1.5 inline-flex items-center justify-center w-4 h-4 rounded-full bg-rose-500 text-white text-[10px] font-extrabold shrink-0">'
                    + (item.unread_count > 9 ? '9+' : item.unread_count) + '</span>'
                : '';

            const el = document.createElement('a');
            el.href = item.url;
            el.className = 'flex items-start gap-3 px-5 py-3.5 hover:bg-[#0E1620]/10 dark:hover:bg-[#0E1620]/10 transition group';
            el.innerHTML = `
                <div class="w-9 h-9 rounded-xl flex items-center justify-center text-sm shrink-0 font-bold ${avatarBg}">${initial}</div>
                <div class="min-w-0 flex-1">
                    <div class="flex items-center justify-between gap-2">
                        <p class="text-xs font-bold text-slate-800 dark:text-slate-200 truncate">${typeTag}<span class="align-middle">${escapeHtml(item.crop)}</span>${unreadBadge}</p>
                        <span class="text-[10px] font-extrabold uppercase tracking-widest px-2 py-0.5 rounded shrink-0 ${statusClass}">${item.status}</span>
                    </div>
                    <p class="text-[10px] text-slate-500 dark:text-slate-400 mt-0.5">${escapeHtml(item.counterpart_name)} ${volume} ${item.rate_label || ''}</p>
                    <p class="text-[10px] text-slate-500 dark:text-slate-400 mt-0.5">${item.last_activity || ''}</p>
                </div>
            `;
            list.appendChild(el);
        });
    }

    function escapeHtml(str) {
        if (!str) return '';
        const div = document.createElement('div');
        div.textContent = str;
        return div.innerHTML;
    }

    fetchList();
    // Reuse the shared 10s poller from notification-dropdown where present;
    // fall back to a private interval only if the shared registry is missing.
    if (window.hhPollTasks) {
        window.hhPollTasks.push(fetchList);
    } else {
        pollTimer = setInterval(fetchList, 10000);
    }

    toggle.addEventListener('click', togglePopup);
    closeBtn.addEventListener('click', closePopup);
    retry.addEventListener('click', function() {
        error.classList.add('hidden');
        error.classList.remove('flex');
        loading.classList.remove('hidden');
        loading.classList.add('flex');
        fetchList();
    });

    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && isOpen) {
            closePopup();
        }
    });

    document.addEventListener('click', function(e) {
        if (isOpen && !widget.contains(e.target)) {
            closePopup();
        }
    });
})();
</script>
