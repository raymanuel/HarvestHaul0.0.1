/**
 * HarvestHaul Driver Service Worker — Offline Telemetry Sync
 * Queues GPS tracking pings locally when offline, retries when online.
 * Version: 1.0
 */

const SW_VERSION = 'hh-telemetry-v1';
const TRACKING_URL_PATTERN = /\/tracking\/store|\/tracking\/stream/;

// Install — skip waiting to activate immediately
self.addEventListener('install', () => {
    self.skipWaiting();
});

// Activate — claim clients immediately
self.addEventListener('activate', event => {
    event.waitUntil(self.clients.claim());
});

/**
 * Fetch handler — intercept tracking POST requests.
 * If the network fails, the payload is stored in IndexedDB and
 * the SW broadcasts a 'telemetry-queued' message back to the client.
 */
self.addEventListener('fetch', event => {
    const url = new URL(event.request.url);

    // Only intercept tracking POST requests
    if (event.request.method === 'POST' && TRACKING_URL_PATTERN.test(url.pathname)) {
        event.respondWith(handleTrackingRequest(event.request.clone()));
    }
});

async function handleTrackingRequest(request) {
    try {
        const response = await fetch(request);
        return response;
    } catch (networkError) {
        // Network failed — queue the payload offline
        try {
            const body = await request.json();
            await queueOfflinePing(body);
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

async function queueOfflinePing(payload) {
    const db    = await openDB();
    const tx    = db.transaction('pings', 'readwrite');
    const store = tx.objectStore('pings');
    store.add({ ...payload, queued_at: new Date().toISOString() });
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
            const { id, queued_at, ...payload } = ping;
            const res = await fetch('/driver/tracking/store', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json',
                },
                body: JSON.stringify(payload),
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
