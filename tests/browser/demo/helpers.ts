import { expect, Page } from "@playwright/test";
import * as fs from "fs";
import * as path from "path";
import { fileURLToPath } from "url";

/**
 * Shared helpers for the demo browser specs. Ported from
 * tests/browser/e2e/coopfarmer-level.spec.ts so the demo runs behave like the
 * proven E2E runs (same throttle pacing, same SweetAlert handling, same
 * deterministic OSRM mocking).
 */

export const SWAL_CONFIRM = ".swal2-confirm";

const __dirname = path.dirname(fileURLToPath(import.meta.url));

export const DEMO_LOGIN = {
  coop:   "demo.outbound.coop@harvesthaul.app",
  farmer: "demo.outbound.farmer@harvesthaul.app",
  farmer2: "demo.outbound.farmer2@harvesthaul.app",
  farmer3: "demo.outbound.farmer3@harvesthaul.app",
  farmer4: "demo.outbound.farmer4@harvesthaul.app",
  driver: "demo.outbound.driver@harvesthaul.app",
  driver2: "demo.outbound.driver2@harvesthaul.app",
  password: "demo1234",
};

export function shot(page: Page) {
  return async (slug: string, label: string, runLog: Record<string, any>, shotDir: string) => {
    await page.screenshot({ path: `${shotDir}\\${slug}.png`, fullPage: true });
    runLog.steps.push({ slug, label, url: page.url(), time: new Date().toISOString() });
    console.log(`  [shot] ${slug} — ${label} (${page.url()})`);
  };
}

export function wireLogging(page: Page, ctx: string, errLog?: (...lines: (string | any)[]) => void) {
  page.on("console", m => { if (m.type() === "error") errLog?.(`[${ctx}] console.error:`, m.text()); });
  page.on("pageerror", e => errLog?.(`[${ctx}] pageerror:`, String(e?.message ?? e)));
  page.on("requestfailed", r => {
    const err = String(r.failure()?.errorText ?? "");
    if (err === "net::ERR_ABORTED") return;
    errLog?.(`[${ctx}] requestfailed:`, r.url(), err);
  });
  page.on("response", r => { if (r.status() >= 400) errLog?.(`[${ctx}] http ${r.status()}:`, r.url()); });
}

// Laravel's throttle:N,1 buckets are SHARED per user across ALL throttled routes,
// so a single user must stay ≤5 throttled POSTs inside any rolling 60s window.
export let lastLpPostAt = 0;

export function bumpLpPostAt(t = Date.now()) {
  lastLpPostAt = t;
}

export const guard = (p: Page) => {
  p.setDefaultTimeout(25_000);
  p.setDefaultNavigationTimeout(60_000);
  return p;
};

export const tomorrow = () => {
  const d = new Date(Date.now() + 86400000);
  return `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, "0")}-${String(d.getDate()).padStart(2, "0")}`;
};

export async function login(page: Page, email: string, password: string) {
  await page.context().clearCookies();
  await page.goto("/login", { waitUntil: "domcontentloaded" });
  const csrfToken = await page.locator('meta[name="csrf-token"]').getAttribute("content", { timeout: 2_000 }).catch(() => null);
  if (csrfToken) {
    await page.evaluate(({ email, password, token }) => {
      const form = document.createElement("form");
      form.method = "POST";
      form.action = "/login";
      form.innerHTML =
        '<input type="hidden" name="_token" value="' + token + '">' +
        '<input type="hidden" name="email" value="' + email + '">' +
        '<input type="hidden" name="password" value="' + password + '">';
      document.body.appendChild(form);
      form.submit();
    }, { email, password, token: csrfToken }).catch(() => {});
  } else {
    await page.waitForSelector("#login-panel input[name='email']", { timeout: 20_000 });
    await page.fill("#login-panel input[name='email']", email);
    await page.fill("#login-panel input[name='password']", password);
    await page.locator("#login-panel button[type='submit']").click();
  }
  await page.waitForURL(/\/(dashboard|admin|farmer|buyer|logistics|driver)/, { timeout: 25_000 });
  await page.waitForLoadState("networkidle", { timeout: 5_000 }).catch(() => {});
}

export const swalConfirm = async (page: Page) => {
  await page.waitForSelector(SWAL_CONFIRM, { timeout: 10_000 });
  await page.locator(SWAL_CONFIRM).click({ noWaitAfter: true });
};

// Dismiss any lingering SweetAlert info-modal (e.g. the auto "next steps"
// popup). An open modal shields the page background from getByRole lookups,
// so it must be gone before role-based locators can see the page again.
export const dismissSwal = async (page: Page) => {
  for (let t = 0; t < 6; t++) {
    const swalBtn = page.locator(".swal2-container .swal2-confirm").first();
    try {
      await swalBtn.waitFor({ state: "visible", timeout: 1_500 });
    } catch {
      break;
    }
    await swalBtn.click({ noWaitAfter: true }).catch(() => {});
    await page.waitForTimeout(400);
    const stillOpen = await page.locator(".swal2-container.swal2-shown").first().isVisible().catch(() => false);
    if (!stillOpen) break;
  }
};

// Submit a page form by the NATIVE form.submit() path, bypassing any inline
// `onsubmit` that calls the app's `swalConfirm` helper. Deterministic for the
// demo even when a full page reload leaves `window.swalConfirm` momentarily
// out of scope. The button is used only to locate its owning <form>.
export const jsSubmitForm = async (page: Page, btn: any, urlRe: RegExp) => {
  const resp = page
    .waitForResponse(r => urlRe.test(r.url()) && r.status() < 500, { timeout: 30_000 })
    .catch(() => ({}));
  const ok = await btn.evaluate((el: HTMLElement) => {
    const form = el.closest("form") as HTMLFormElement | null;
    if (!form) return false;
    form.submit();
    return true;
  }).catch(() => false);
  if (!ok) throw new Error("No owning <form> found for the submit button");
  await resp;
  await page.waitForLoadState("networkidle", { timeout: 20_000 }).catch(() => {});
  await page.waitForTimeout(600);
  // The app auto-fires an informational "next steps" SweetAlert after these
  // submits. An open SweetAlert shields the page background from getByRole
  // lookups, so dismiss any lingering modal before returning.
  await dismissSwal(page);
};

// Confirm a SweetAlert AND wait for the resulting form PATCH/POST to settle so the
// next lookup never races the page reload.
export const swalSubmit = async (page: Page, urlRe: RegExp) => {
  const resp = page
    .waitForResponse(r => urlRe.test(r.url()) && r.status() < 500, { timeout: 30_000 })
    .catch(() => ({}));
  await page.waitForSelector(SWAL_CONFIRM, { timeout: 30_000 }).catch(() => { throw new Error("SweetAlert confirm did not appear in 30s"); });
  await page.locator(SWAL_CONFIRM).click({ noWaitAfter: true });
  await resp;
  await page.waitForLoadState("networkidle", { timeout: 20_000 }).catch(() => {});
  await page.waitForTimeout(600);
};

// UpdateStopStatusAction geofences `arrived`: the latest /driver/tracking/store
// fix must sit within 500m of the farm. Auto-pings fire on job-page reload, so
// ping this stop's OWN farm coordinates right before clicking "Mark Arrived".
export async function pingAtFarm(page: Page, lat: number, lng: number) {
  const jobId = Number(page.url().match(/\/driver\/jobs\/(\d+)/)?.[1] ?? 0);
  const csrf = await page.locator('meta[name="csrf-token"]').getAttribute("content").catch(() => "");
  if (!jobId || !csrf || !lat || !lng) return { skipped: true };
  for (let t = 0; t < 8; t++) {
    const status = await page.evaluate(async ({ jobId, csrf, lat, lng }) => {
      try {
        const res = await fetch("/driver/tracking/store", {
          method: "POST",
          headers: { "Content-Type": "application/json", "X-CSRF-TOKEN": csrf, "Accept": "application/json" },
          body: JSON.stringify({ pooling_job_id: jobId, latitude: lat, longitude: lng, posted_at: new Date().toISOString() }),
        });
        return res.status;
      } catch (e) { return 0; }
    }, { jobId, csrf, lat, lng });
    if (status === 200 || status === 201) return { status };
    await page.waitForTimeout(5_000);
  }
  return { status: null };
}

// Inbound driver job-detail navigation is content- OR url-based so a slow asset
// host never strands us waiting on the address bar.
export async function driverJobDetail(page: Page) {
  await Promise.race([
    page.waitForURL(/driver\/jobs\/\d+/, { timeout: 25_000 }),
    page.getByRole("button", { name: /Accept Job/i }).first().waitFor({ timeout: 25_000 }),
    page.getByText("Pickup Sequence", { exact: false }).first().waitFor({ timeout: 25_000 }),
  ]);
  await page.waitForLoadState("domcontentloaded").catch(() => {});
}

export async function outboundJobDetail(page: Page) {
  await Promise.race([
    page.waitForURL(/driver\/jobs\/\d+/, { timeout: 25_000 }),
    page.getByRole("button", { name: /Accept Customer Delivery/i }).first().waitFor({ timeout: 25_000 }),
    page.getByText("Delivery Items", { exact: false }).first().waitFor({ timeout: 25_000 }),
  ]);
  await page.waitForLoadState("domcontentloaded").catch(() => {});
}

async function waitForStatus(page: Page, label: string, timeout = 25_000) {
  const deadline = Date.now() + timeout;
  while (Date.now() < deadline) {
    const txt = await page.locator("#deal-status-badge").textContent().catch(() => "");
    if (txt && txt.includes(label)) return true;
    await page.waitForTimeout(1_000);
  }
  throw new Error(`Deal status badge did not reach "${label}"`);
}

// Route-optimization helpers. Two traps make the naive render flow flaky:
//   1. The start-marker popup ("Coop Hub") is open on some page loads and closed
//      on others, so clicking state can't be trusted blindly.
//   2. The plan-all HTTP proxy must NOT point at the URL it is intercepting.
// `ensurePickupQueue` therefore polls and clicks the map until the queue is full.
export async function ensurePickupQueue(lp: Page, minChecks: number) {
  for (let i = 0; i < 40; i++) {
    const satisfied = await lp
      .evaluate((minCheck: number) => {
        const checks = Array.from(document.querySelectorAll("#pickup-queue input[type='checkbox'][data-farm-id]"));
        return checks.length >= minCheck && checks.every(c => (c as HTMLInputElement).checked);
      }, minChecks)
      .catch(() => false);
    if (satisfied) return;
    const mapBox = await lp.locator(".leaflet-container").boundingBox().catch(() => null);
    if (mapBox) {
      const fx = i % 2 === 0 ? 0.35 : 0.65;
      const fy = i % 2 === 0 ? 0.3 : 0.7;
      await lp.evaluate(([cx, cy]) => {
        const el = document.querySelector(".leaflet-container");
        if (el) el.dispatchEvent(new MouseEvent("click", { clientX: cx, clientY: cy, bubbles: true }));
      }, [mapBox.x + mapBox.width * fx, mapBox.y + mapBox.height * fy]).catch(() => {});
    }
    await lp.waitForTimeout(800);
  }
  throw new Error(`Pickup queue never satisfied (needed ≥${minChecks} checked farms)`);
}

// Capture the exact lon/lat of the intended demo farms from the page's own
// `farms` JSON, so the OSRM mock can admit ONLY those farms into the pickup
// queue. Deterministic even when the DB carries extra seed farms. Empty → caller
// falls back to admitting everything. NOTE: must be an in-page fetch() — a
// page.request.get() to this route makes Playwright drop the browser session.
export async function captureIntendedFarmCoords(lp: Page, names: string[]): Promise<Array<[number, number]>> {
  try {
    const raw: string = await lp.evaluate(async () => await (await fetch("/route-optimization")).text());
    const scriptRe = /<script[^>]*>([\s\S]*?)<\/script>/gi;
    const jsonRe = /const farms\s*=\s*(\[[\s\S]*?\])/;
    let m: RegExpExecArray | null;
    while ((m = scriptRe.exec(raw))) {
      const j = m[1].match(jsonRe);
      if (!j) continue;
      try {
        const farmsJson = JSON.parse(j[1]);
        return (farmsJson as any[])
          .filter((f: any) => names.includes(f.name))
          .map((f: any) => [Number(f.farmer_profile.longitude), Number(f.farmer_profile.latitude)] as [number, number]);
      } catch { /* try next script */ }
    }
  } catch { /* fall through to empty → admit-all */ }
  return [];
}

// Per-farm nearest-leg lookup (?overview=false): farms we recognize get a
// sub-radius distance, everyone else is pushed past the 50km filter.
export function mockPerFarmDistance(route: any, admit: Array<[number, number]>, url: string) {
  const farmPart = (url.match(/driving\/([^?]+)/) || [])[1]?.split(";")[0] || "";
  const [lon, lat] = farmPart.split(",").map(Number);
  let hit = admit.length === 0;
  for (const [aLon, aLat] of admit) {
    if (Math.abs(lon - aLon) < 0.001 && Math.abs(lat - aLat) < 0.001) { hit = true; break; }
  }
  route.fulfill({ status: 200, contentType: "application/json", body: JSON.stringify({ code: "Ok", routes: [{ distance: hit ? 120 : 999000, duration: 0 }] }) });
}

// The generate button stays disabled until a truck with an assigned driver is
// selected. Pick the first such truck deterministically, then wait for the button.
export async function ensureGenerateEnabled(lp: Page) {
  const pick = await lp.evaluate(() => {
    const btn = document.getElementById("btn-generate-plan") as HTMLButtonElement | null;
    if (btn && !btn.disabled) return { ready: true, index: -1 };
    const sel = document.getElementById("truck-select") as HTMLSelectElement | null;
    if (!sel) return { ready: false, index: -2 };
    for (let i = 0; i < sel.options.length; i++) {
      const d = (sel.options[i].dataset.driver || "").trim();
      if (sel.options[i].value && d && d !== "No driver assigned") return { ready: false, index: i };
    }
    return { ready: false, index: -3 };
  });
  if (pick.ready) return;
  if (pick.index < 0) throw new Error("No truck with an assigned driver is available for route generation");
  await lp.selectOption("#truck-select", { index: pick.index });
  await lp.evaluate(() => {
    document.getElementById("truck-select")!.dispatchEvent(new Event("change"));
  });
  await lp.waitForFunction(() => {
    const b = document.getElementById("btn-generate-plan");
    return b && !b.disabled;
  }, undefined, { timeout: 25_000 });
}

// `pace()` parks until ≥60s after the last throttled POST for that user, then
// `lastLpPostAt` is refreshed right after each LP POST.
export async function pace(page: Page) {
  const since = Date.now() - lastLpPostAt;
  if (since < 60_000) {
    const wait = 60_000 - since;
    console.log(`  [pace] waiting ${Math.round(wait / 1000)}s for throttled-POST window`);
    await page.waitForTimeout(wait);
  }
}

// The farmer's "Agree to These Terms" button is inserted by the negotiation room's
// message poll. A poll can briefly fail mid-run, so retry by re-logging-in and
// re-opening the room instead of failing a whole run on one mailbox hiccup.
export async function farmerAgreeWithRetry(fp: Page, negId: string, email: string, password: string, retries = 3): Promise<void> {
  for (let attempt = 1; attempt <= retries; attempt++) {
    await login(fp, email, password);
    await fp.goto(`/negotiations/${negId}`);
    await fp.waitForLoadState("networkidle").catch(() => {});
    const agreeBtn = fp.locator("#agree-btn");
    const ok = await agreeBtn.waitFor({ state: "visible", timeout: 30_000 }).then(() => true).catch(() => false);
    if (ok) {
      await agreeBtn.click();
      await swalConfirm(fp);
      await waitForStatus(fp, "AGREED");
      return;
    }
    console.log(`  [pace] farmer agree attempt ${attempt} missed #agree-btn — re-opening room`);
    await fp.waitForTimeout(2500);
  }
  throw new Error(`Farmer agree did not render after ${retries} attempts (negotiation ${negId})`);
}

// Transactional finalize: POST the Close Deal form while listening for the
// throttled 429. When 429 hits, wait out `Retry-After` and reload the negotiation
// page instead of re-POSTing from a dead "429 Too Many Requests" page.
export async function finalizeDealWithRetry(lp: Page, negId: string, rate: string, retries = 3): Promise<void> {
  let finalized = false;
  for (let attempt = 1; attempt <= retries && !finalized; attempt++) {
    if (attempt > 1) {
      await lp.goto(`/negotiations/${negId}`);
      await lp.waitForLoadState("networkidle").catch(() => {});
      const rate2 = lp.locator("#hauling_rate_per_kg");
      if (await rate2.isVisible().catch(() => false)) await rate2.fill(rate);
      await lp.evaluate(() => {
        const latEl = document.getElementById("destination_latitude") as HTMLInputElement;
        const lngEl = document.getElementById("destination_longitude") as HTMLInputElement;
        const addrEl = document.getElementById("destination_address") as HTMLInputElement;
        if (latEl && !latEl.value) latEl.value = "6.1164";
        if (lngEl && !lngEl.value) lngEl.value = "125.1716";
        if (addrEl && !addrEl.value) addrEl.value = "GenSan Wholesale Market Hub";
      });
    }
    const finResp = lp.waitForResponse(r => /\/negotiations\/\d+\/finalize$/.test(r.url()), { timeout: 30_000 }).catch(() => null);
    await lp.waitForLoadState("networkidle").catch(() => {});
    await lp.waitForTimeout(600);
    await lp.locator("button[type='submit']", { hasText: /Close Deal/i }).click();
    bumpLpPostAt();
    const res = await finResp;
    const status = res?.status();
    if (status === 429) {
      const h = res?.headers() ?? {};
      const retryAfter = Number(h["retry-after"]) || 0;
      const backoff = Math.max(retryAfter + 5, 65_000);
      console.log(`  [throttle] finalize #${negId} attempt ${attempt} 429 (limit=${h["x-ratelimit-limit"]} remaining=${h["x-ratelimit-remaining"]} reset=${h["x-ratelimit-reset"]} retryAfter=${retryAfter}s) — waiting ${Math.round(backoff / 1000)}s`);
      await lp.waitForTimeout(backoff);
      continue;
    }
    if (status && status < 400) finalized = true;
    if (!status && lp.url().includes("/buyer/negotiations")) finalized = true;
    if (!finalized) await lp.waitForTimeout(4000);
    finalized = finalized || lp.url().includes("/buyer/negotiations");
  }
  if (!finalized) throw new Error(`Finalize #${negId} did not complete after ${retries} attempts`);
  await lp.waitForLoadState("networkidle").catch(() => {});
}

// Small screenshot fixture generator + shared pickups for driver stop photos.
export function makeFixtures() {
  const FIXTURES = path.resolve(__dirname, "fixtures");
  fs.mkdirSync(FIXTURES, { recursive: true });
  const PNG_1PX = Buffer.from("iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg==", "base64");
  const fixture = (name: string) => {
    const p = `${FIXTURES}\\${name}`;
    if (!fs.existsSync(p)) fs.writeFileSync(p, PNG_1PX);
    return p;
  };
  return {
    receipt: fixture("receipt.png"),
    load: fixture("load-photo.png"),
    delivery: fixture("delivery-photo.png"),
  };
}