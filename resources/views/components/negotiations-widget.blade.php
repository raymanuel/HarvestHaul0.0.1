<style>
    .neg-glow {
        animation: neg-pulse-glow 2.5s ease-in-out infinite;
    }
    .neg-glow:hover {
        animation: none;
    }
    @keyframes neg-pulse-glow {
        0%, 100% { box-shadow: 0 0 24px rgba(58, 125, 68, 0.5), 0 0 80px rgba(58, 125, 68, 0.15); }
        50% { box-shadow: 0 0 40px rgba(58, 125, 68, 0.7), 0 0 100px rgba(58, 125, 68, 0.25); }
    }
    .dark .neg-glow {
        animation: neg-pulse-glow-dark 2.5s ease-in-out infinite;
    }
    .dark .neg-glow:hover {
        animation: none;
    }
    @keyframes neg-pulse-glow-dark {
        0%, 100% { box-shadow: 0 0 24px rgba(58, 125, 68, 0.4), 0 0 80px rgba(58, 125, 68, 0.15); }
        50% { box-shadow: 0 0 40px rgba(58, 125, 68, 0.6), 0 0 100px rgba(58, 125, 68, 0.2); }
    }
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
        class="w-14 h-14 rounded-full bg-[#3A7D44] hover:bg-[#2E6336] dark:bg-[#3A7D44] dark:hover:bg-[#2E6336] text-white shadow-2xl shadow-[#3A7D44]/50 hover:shadow-[#3A7D44]/60 dark:shadow-[#3A7D44]/60 transition-all duration-200 hover:scale-110 active:scale-95 flex items-center justify-center cursor-pointer relative neg-glow ring-2 ring-white/30 dark:ring-white/20"
        title="My Negotiations"
    >
        <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
        </svg>
        <span id="neg-badge" class="hidden absolute -top-1 -right-1 w-5 h-5 rounded-full bg-rose-500 text-white text-[9px] font-extrabold flex items-center justify-center shadow-lg ring-2 ring-white dark:ring-slate-800"></span>
    </button>

    {{-- Popup --}}
    <div id="neg-popup"
        class="hidden absolute bottom-16 right-0 w-96 max-h-[520px] bg-white dark:bg-slate-800/95 border border-slate-200/60 dark:border-[#3A7D44]/20 rounded-2xl shadow-2xl shadow-[#3A7D44]/10 dark:shadow-[#3A7D44]/20 overflow-hidden flex flex-col origin-bottom-right"
    >
        {{-- Header --}}
        <div class="flex items-center justify-between px-5 py-4 border-b border-slate-100 dark:border-[#3A7D44]/10 shrink-0 bg-gradient-to-r from-[#3A7D44]/5 to-transparent dark:from-[#3A7D44]/10 dark:to-transparent">
            <div>
                <h3 class="text-sm font-bold text-slate-800 dark:text-white heading-font">My Negotiations</h3>
                <p class="text-[10px] text-slate-400 dark:text-slate-500 font-medium">B2B crop price discussions</p>
            </div>
            <button id="neg-close" class="w-7 h-7 rounded-lg bg-slate-100 dark:bg-slate-700/60 hover:bg-slate-200 dark:hover:bg-slate-600/60 flex items-center justify-center text-slate-400 dark:text-slate-400 transition cursor-pointer">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>

        {{-- Loading --}}
        <div id="neg-loading" class="flex items-center justify-center py-12 hidden">
            <svg class="animate-spin w-6 h-6 text-[#3A7D44] dark:text-[#3A7D44]" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
        </div>

        {{-- Empty --}}
        <div id="neg-empty" class="flex-col items-center justify-center py-12 px-6 text-center hidden">
            <div class="w-12 h-12 rounded-full bg-slate-100 dark:bg-slate-700/50 flex items-center justify-center mb-3">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6 text-slate-400 dark:text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
                </svg>
            </div>
            <p class="text-sm font-bold text-slate-600 dark:text-slate-300">No active negotiations</p>
            <p class="text-[10px] text-slate-400 dark:text-slate-500 mt-1">Browse the Crop Board to start a deal.</p>
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
    const list = document.getElementById('neg-list');
    const badge = document.getElementById('neg-badge');

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
        badge.classList.add('hidden');
        badge.classList.remove('neg-badge-ping');
        loading.classList.remove('hidden');
        loading.classList.add('flex');
        list.classList.add('hidden');
        empty.classList.add('hidden');
        fetchList();
    }

    function closePopup() {
        isOpen = false;
        popup.classList.add('hidden');
        popup.classList.remove('flex');
        toggle.classList.remove('scale-110');
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
                    list.classList.add('hidden');
                    return;
                }

                loading.classList.add('hidden');
                loading.classList.remove('flex');
                empty.classList.add('hidden');
                empty.classList.remove('flex');
                list.classList.remove('hidden');
                renderList(data.negotiations);
            })
            .catch(() => {
                badge.classList.add('hidden');
                badge.classList.remove('neg-badge-ping');
                if (isOpen) {
                    loading.classList.add('hidden');
                    loading.classList.remove('flex');
                    empty.classList.remove('hidden');
                    empty.classList.add('flex');
                    list.classList.add('hidden');
                }
            });
    }

    function renderList(items) {
        list.innerHTML = '';
        items.forEach(item => {
            const statusClass = item.status === 'OPEN'
                ? 'text-harvest-700 dark:text-harvest bg-harvest/10 border border-harvest/10'
                : item.status === 'AGREED'
                ? 'text-[#3A7D44] dark:text-[#3A7D44] bg-[#3A7D44]/10 border border-[#3A7D44]/10'
                : 'text-[#1F4D25] dark:text-[#1F4D25] bg-[#1F4D25]/10 border border-[#1F4D25]/10';
            const initial = item.counterpart_name ? item.counterpart_name.charAt(0).toUpperCase() : '?';
            const avatarBg = item.is_buyer
                ? 'bg-harvest/10 dark:bg-harvest/20 text-harvest dark:text-harvest'
                : 'bg-[#3A7D44]/10 dark:bg-[#3A7D44]/10 text-[#3A7D44] dark:text-[#3A7D44]';
            const volume = item.volume ? '· ' + Number(item.volume).toLocaleString() + ' kg' : '';

            const unreadBadge = item.unread_count > 0
                ? '<span class="ml-1.5 inline-flex items-center justify-center w-4 h-4 rounded-full bg-rose-500 text-white text-[8px] font-extrabold shrink-0">'
                    + (item.unread_count > 9 ? '9+' : item.unread_count) + '</span>'
                : '';

            const el = document.createElement('a');
            el.href = item.url;
            el.className = 'flex items-start gap-3 px-5 py-3.5 hover:bg-[#1F4D25]/10/50 dark:hover:bg-[#1F4D25]/10 transition group';
            el.innerHTML = `
                <div class="w-9 h-9 rounded-xl flex items-center justify-center text-sm shrink-0 font-bold ${avatarBg}">${initial}</div>
                <div class="min-w-0 flex-1">
                    <div class="flex items-center justify-between gap-2">
                        <p class="text-xs font-bold text-slate-800 dark:text-slate-200 truncate">${escapeHtml(item.crop)}${unreadBadge}</p>
                        <span class="text-[9px] font-extrabold uppercase tracking-widest px-2 py-0.5 rounded shrink-0 ${statusClass}">${item.status}</span>
                    </div>
                    <p class="text-[10px] text-slate-400 dark:text-slate-500 mt-0.5">${escapeHtml(item.counterpart_name)} ${volume}</p>
                    <p class="text-[9px] text-slate-400 dark:text-slate-500 mt-0.5">${item.last_activity || ''}</p>
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
    pollTimer = setInterval(fetchList, 10000);

    toggle.addEventListener('click', togglePopup);
    closeBtn.addEventListener('click', closePopup);

    document.addEventListener('click', function(e) {
        if (isOpen && !widget.contains(e.target)) {
            closePopup();
        }
    });
})();
</script>
