{{-- Shared Notification Dropdown Component --}}
{{-- Used by both layout.blade.php and driver-view.blade.php --}}
<div class="relative" id="notifications-menu">
    <button onclick="toggleNotificationsDropdown()" aria-label="Toggle notifications" class="relative w-10 h-10 rounded-xl bg-slate-50 dark:bg-slate-700 border border-slate-100 dark:border-slate-700 flex items-center justify-center text-slate-500 hover:text-slate-800 dark:text-slate-400 dark:hover:text-white transition cursor-pointer">
        <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
        </svg>
        <span id="notification-badge" class="hidden absolute -top-1 -right-1 min-w-5 h-5 px-1 rounded-full bg-red-500 text-white text-[10px] font-extrabold flex items-center justify-center border border-white dark:border-slate-800" aria-live="polite" aria-label="Unread notification count"></span>
    </button>

    {{-- Dropdown Menu --}}
    <div id="notifications-dropdown" class="hidden absolute right-0 mt-2 w-80 bg-white dark:bg-slate-800 border border-slate-200/85 dark:border-slate-700 rounded-2xl shadow-xl z-50 overflow-hidden">
        <div class="px-4 py-3 bg-slate-50 dark:bg-slate-900/40 border-b border-slate-150 dark:border-slate-700/60 flex items-center justify-between">
            <span class="text-[10px] font-bold text-slate-700 dark:text-slate-400 uppercase tracking-wider">Notifications</span>
            <button onclick="markAllNotificationsAsRead()" class="text-[9px] text-[#16283C] dark:text-[#D7BC7A] font-bold hover:underline">Mark all read</button>
        </div>
        <div id="notifications-list" class="max-h-64 overflow-y-auto divide-y divide-slate-100 dark:divide-slate-700">
            <p class="text-center text-xs text-slate-500 dark:text-slate-400 py-6">No notifications</p>
        </div>
    </div>
</div>

<script>
    function toggleNotificationsDropdown() {
        var dropdown = document.getElementById('notifications-dropdown');
        dropdown.classList.toggle('hidden');
    }

    // Close notification dropdown when clicking outside
    window.addEventListener('click', function(e) {
        var dropdown = document.getElementById('notifications-dropdown');
        var menu = document.getElementById('notifications-menu');
        if (dropdown && menu && !menu.contains(e.target)) {
            dropdown.classList.add('hidden');
        }
    });

    // Fetch Notifications (with SweetAlert toast for new ones)
    var fetchNotifications = (function () {
        var NOTIF_USER_ID = {{ auth()->id() ?? 'null' }};
        var SEEN_KEY = 'hh_seen_notifs_' + NOTIF_USER_ID;
        var seenIds = {};

        try {
            JSON.parse(localStorage.getItem(SEEN_KEY) || '[]').forEach(function (id) {
                seenIds[id] = true;
            });
        } catch (e) {
            seenIds = {};
        }

        function persistSeen() {
            if (!NOTIF_USER_ID) return;
            try {
                var ids = Object.keys(seenIds).sort(function (a, b) { return a - b; }).slice(-50);
                localStorage.setItem(SEEN_KEY, JSON.stringify(ids));
            } catch (e) {}
        }

        return function () {
            fetch('/api/notifications', { headers: { 'Accept': 'application/json' } })
                .then(function (r) { return r.json(); })
                .then(function (data) {
                    if (!data.notifications) return;
                    var badge = document.getElementById('notification-badge');
                    if (data.unread_count > 0) {
                        badge.classList.remove('hidden');
                        badge.textContent = data.unread_count > 99 ? '99+' : data.unread_count;
                    } else {
                        badge.classList.add('hidden');
                        badge.textContent = '';
                    }

                    // Alert each notification once per browser: seen ids survive reloads via localStorage.
                    var newestUnread = null;
                    for (var i = 0; i < data.notifications.length; i++) {
                        if (!data.notifications[i].read_at) {
                            newestUnread = data.notifications[i];
                            break;
                        }
                    }

                    if (newestUnread && !seenIds[newestUnread.id]) {
                        seenIds[newestUnread.id] = true;
                        persistSeen();
                        Swal.fire({
                            icon: null,
                            title: newestUnread.title,
                            text: newestUnread.message,
                            timer: 4000,
                            timerProgressBar: true,
                            showConfirmButton: false,
                            toast: true,
                            position: 'top-end',
                            background: document.documentElement.classList.contains('dark') ? '#1e293b' : '#fff',
                            color: document.documentElement.classList.contains('dark') ? '#e2e8f0' : '#1e293b',
                            customClass: { popup: 'rounded-xl shadow-lg border border-[#16283C]/20 text-xs' }
                        });
                    }

                    var list = document.getElementById('notifications-list');
                    if (data.notifications.length === 0) {
                        list.innerHTML = '<p class="text-center text-xs text-slate-500 dark:text-slate-400 py-6">No notifications</p>';
                        return;
                    }

                    var html = '';
                    data.notifications.forEach(function (n) {
                        var isUnread = !n.read_at;
                        var bgClass = isUnread ? 'bg-[#16283C]/5 dark:bg-[#16283C]/5' : '';
                        var indicator = isUnread ? '<span class="w-1.5 h-1.5 rounded-full bg-[#16283C] shrink-0"></span>' : '';
                        var link = n.link ? n.link : '#';

                        html += '<div class="px-4 py-3 hover:bg-slate-50 dark:hover:bg-slate-700/30 transition flex items-start justify-between gap-3 ' + bgClass + '" onclick="markNotificationRead(' + n.id + ', \'' + link + '\')">';
                        html += '<div class="flex-1 cursor-pointer">';
                        html += '<p class="text-xs font-bold text-slate-800 dark:text-slate-200">' + escapeHtml(n.title) + '</p>';
                        html += '<p class="text-[11px] text-slate-500 dark:text-slate-400 mt-0.5 leading-relaxed">' + escapeHtml(n.message) + '</p>';
                        html += '<span class="text-[9px] text-slate-500 dark:text-slate-400 mt-1 block">' + new Date(n.created_at).toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'}) + '</span>';
                        html += '</div>';
                        html += indicator;
                        html += '</div>';
                    });
                    list.innerHTML = html;
                });
        };
    })();

    function escapeHtml(str) {
        var div = document.createElement('div');
        div.appendChild(document.createTextNode(str));
        return div.innerHTML;
    }

    // Mark a notification as read and redirect
    function markNotificationRead(id, link) {
        fetch('/api/notifications/' + id + '/read', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'Content-Type': 'application/json'
            }
        }).then(function () {
            fetchNotifications();
            if (link && link !== '#') {
                window.location.href = link;
            }
        });
    }

    // Mark all as read
    function markAllNotificationsAsRead() {
        fetch('/api/notifications/read-all', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'Content-Type': 'application/json'
            }
        }).then(function () {
            fetchNotifications();
        });
    }

    // Register this fetch into a shared poller so other components
    // (e.g. negotiations-widget) reuse the same 10s interval.
    window.hhPollTasks = window.hhPollTasks || [];
    window.hhPollTasks.push(fetchNotifications);

    // Single combined poller: runs every registered task in parallel.
    function hhPollNotifications() {
        return Promise.all(window.hhPollTasks.map(function (fn) {
            try {
                return Promise.resolve(fn());
            } catch (e) {
                return Promise.resolve();
            }
        })).catch(function () {});
    }
    window.hhPollNotifications = hhPollNotifications;

    // Shared poll interval (guarded so no component starts a second one).
    window.hhPollInterval = window.hhPollInterval || null;
    document.addEventListener('DOMContentLoaded', function() {
        window.hhPollNotifications();
        if (!window.hhPollInterval) {
            window.hhPollInterval = setInterval(window.hhPollNotifications, 10000);
        }
    });
</script>
