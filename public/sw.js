/**
 * HarvestHaul Driver Service Worker — Offline Telemetry Sync
 * Queues GPS tracking pings locally when offline, retries when online.
 * Handles all fetch events so browsers detect SW page control (PWA installability).
 * Version: 2.0
 */

const SW_VERSION = 'hh-v2';
const TRACKING_URL_PATTERN = /\/delivery\/trips\/\d+\/location/;

// Install — skip waiting to activate immediately
self.addEventListener('install', () => {
    self.skipWaiting();
});

// Activate — claim clients immediately
self.addEventListener('activate', event => {
    event.waitUntil(self.clients.claim());
});

/**
 * Fetch handler.
 * Tracking POSTs: queue offline via IndexedDB on network failure (retried on reconnect).
 * Everything else: passes straight through to the network (no SW interception),
 * so a down server surfaces the browser's native error instead of a misleading "Offline" stub.
 */
self.addEventListener('fetch', event => {
    const url = new URL(event.request.url);

    if (event.request.method === 'POST' && TRACKING_URL_PATTERN.test(url.pathname)) {
        event.respondWith(handleTrackingRequest(event.request.clone(), url.pathname));
    }
    // Everything else goes straight to the network (no SW interception).
    // Removes the misleading bare "Offline" 503 stub while preserving
    // offline GPS telemetry + PWA installability.
});

async function handleTrackingRequest(request, pathname) {
    try {
        const response = await fetch(request);
        return response;
    } catch (networkError) {
        // Network failed — queue the payload offline
        try {
            const body = await request.json();
            await queueOfflinePing(pathname, body);
            broadcastToClients({ type: 'telemetry-queued', payload: body });
        } catch (e) {
            console.error('[SW] Failed to queue offline ping:', e);
        }

        // Return a fake 202 so the client doesn't crash
        return new Response(JSON.stringify({
            status: 'queued',
            message: 'Offline — ping queued for sync.',
        }), {
            status: 202,
            headers: { 'Content-Type': 'application/json' },
        });
    }
}

/**
 * Background sync on reconnect — flush all queued pings to server.
 */
self.addEventListener('message', async event => {
    if (event.data?.type === 'flush-offline-queue') {
        await flushOfflineQueue(event.data.csrfToken);
    }
});

// ─────────────────────────────────────────────
// IndexedDB helpers
// ─────────────────────────────────────────────

function openDB() {
    return new Promise((resolve, reject) => {
        const req = indexedDB.open('hh_telemetry', 1);
        req.onupgradeneeded = e => {
            const db = e.target.result;
            if (!db.objectStoreNames.contains('pings')) {
                db.createObjectStore('pings', { keyPath: 'id', autoIncrement: true });
            }
        };
        req.onsuccess = e => resolve(e.target.result);
        req.onerror   = e => reject(e.target.error);
    });
}

async function queueOfflinePing(url, payload) {
    const db    = await openDB();
    const tx    = db.transaction('pings', 'readwrite');
    const store = tx.objectStore('pings');
    store.add({ url, payload, queued_at: new Date().toISOString() });
    return new Promise(r => (tx.oncomplete = r));
}

async function getQueuedPings() {
    const db    = await openDB();
    const tx    = db.transaction('pings', 'readonly');
    const store = tx.objectStore('pings');
    return new Promise((resolve, reject) => {
        const req  = store.getAll();
        req.onsuccess = e => resolve(e.target.result);
        req.onerror   = e => reject(e.target.error);
    });
}

async function deletePing(id) {
    const db    = await openDB();
    const tx    = db.transaction('pings', 'readwrite');
    const store = tx.objectStore('pings');
    store.delete(id);
    return new Promise(r => (tx.oncomplete = r));
}

async function flushOfflineQueue(csrfToken) {
    const pings = await getQueuedPings();
    if (!pings.length) return;

    broadcastToClients({ type: 'sync-started', count: pings.length });

    let synced = 0;
    for (const ping of pings) {
        try {
            const res = await fetch(ping.url, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json',
                },
                body: JSON.stringify(ping.payload),
            });

            if (res.ok || res.status === 422) {
                // 422 = already invalid (job finished etc.) — delete anyway
                await deletePing(id);
                synced++;
            }
        } catch (e) {
            // Still offline — stop trying
            break;
        }
    }

    broadcastToClients({ type: 'sync-complete', synced, remaining: pings.length - synced });
}

function broadcastToClients(message) {
    self.clients.matchAll({ type: 'window', includeUncontrolled: true }).then(clients => {
        clients.forEach(client => client.postMessage(message));
    });
}
