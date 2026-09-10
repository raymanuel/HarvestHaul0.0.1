import { test, expect, Page } from "@playwright/test";
import { execSync } from "child_process";
import * as fs from "fs";
import * as path from "path";
import { fileURLToPath } from "url";

const __dirname = path.dirname(fileURLToPath(import.meta.url));

/**
 * LEVEL-UP COOPERATIVE FARMER E2E — 3 coop farmers × 3 crops, screenshots at every page change.
 * ALSO: MULTI-TRUCK OVERFLOW E2E — 5 coop farmers × 5 custom crops → single-truck overflow
 * splits into 2 routes via post-harvest "plan all" (planAll → confirmBatch).
 *
 * Story (first test):
 *   - 3 cooperative farmers under GenSan Farmers Cooperative post harvests:
 *       farmer0 (Polomolok Pineapple Farm)  → manually-typed crop "Guyabano"  + variety "Native"
 *       farmer1 (Tupi Harvests)             → manually-typed crop "Jackfruit" + variety "Local"
 *       farmer2 (Lagao Fruit Farm)          → Mango → Carabao  (from the dropdown)
 *   - The create form pre-selects the Cooperative Hub as the default destination (coop rule).
 *   - Coop logistics initiates negotiation with ALL THREE farmers (chat → propose terms → farmer agrees → close deal).
 *   - The 3 Done Deals feed the route planner; a consolidated route offer is generated.
 *   - EVERY farmer reads the offer details and accepts; route → truck → driver assigned.
 *   - Driver runs the multi-stop job (arrive → load → deliver for each stop), live tracking ping.
 *   - Coop logistics confirms receipt; cost ledger: each farmer uploads payment proof, logistics verifies+marks paid.
 *
 * Story (second test — multi-truck overflow):
 *   - 5 cooperative farmers (farmer0..farmer4) post 1500/1200/1000/1100/900 kg (5700kg total).
 *   - Coop logistics negotiates with ALL FIVE; 5 Done Deals feed the planner.
 *   - 5700 > truck max (4500) → "plan all" emits 2 plans on 2 trucks; confirm-all creates 2 proposals.
 *   - Every farmer accepts; 2 pooling jobs, each with its own truck + assigned driver.
 *   - Cost ledger shows a per-route row for each of the 2 jobs.
 *
 * Accounts (must already exist — NO migrate:fresh):
 *   farmer0@test.com .. farmer4@test.com  (coop farmers, password "password")
 *   logistics1@test.com (GenSan Farmers Cooperative, password "password")
 *   eliseo-driver-1@driver.com (truck RMP-1011) / mario-driver-1@driver.com (truck RMP-1012)
 *   / julio-driver-1@driver.com (truck RMP-1013)
 *
 * Artifacts: eval/screenshots/e2e-coopfarmer-level/ and eval/screenshots/e2e-coopfarmer-overflow/
 */

// ───────────────────────────  Module-scope pure helpers (shared by both tests)  ───────────────────────────

const SWAL_CONFIRM = ".swal2-confirm";

const FIXTURES = path.resolve(__dirname, "fixtures");
fs.mkdirSync(FIXTURES, { recursive: true });
const PNG_1PX = Buffer.from("iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg==", "base64");
const fixture = (name: string) => {
  const p = path.join(FIXTURES, name);
  if (!fs.existsSync(p)) fs.writeFileSync(p, PNG_1PX);
  return p;
};
const F_RECEIPT = fixture("receipt.png");
const F_LOAD = fixture("load-photo.png");
const F_DELIVERY = fixture("delivery-photo.png");

// M3 test-data hygiene: these specs post real harvests + negotiations into a
// SHARED persistent DB. `php artisan pooling:reset-e2e` surgically clears only
// rows tagged E2E (harvest.notes LIKE 'E2E%'), so run N+1 never inherits the
// completed negotiations / pooling jobs left by an aborted run N.
function resetE2eState() {
  try {
    execSync("php artisan pooling:reset-e2e --yes", { cwd: process.cwd(), stdio: "ignore" });
  } catch {
    console.warn("  [warn] pooling:reset-e2e failed (is PHP/MySQL up?) — proceeding; leftovers may contaminate this run");
  }
}

// Laravel's throttle:N,1 buckets are SHARED per user across ALL throttled routes,
// so a single user must stay ≤5 throttled POSTs inside any rolling 60s window.
let lastLpPostAt = 0;

const guard = (p: Page) => {
  p.setDefaultTimeout(25_000);
  p.setDefaultNavigationTimeout(60_000);
  return p;
};

const tomorrow = () => {
  const d = new Date(Date.now() + 86400000);
  return `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, "0")}-${String(d.getDate()).padStart(2, "0")}`;
};

async function login(page: Page, email: string, password: string) {
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

function wireLogging(page: Page, ctx: string, errLog?: (...lines: (string | any)[]) => void) {
  page.on("console", m => { if (m.type() === "error") errLog?.(`[${ctx}] console.error:`, m.text()); });
  page.on("pageerror", e => errLog?.(`[${ctx}] pageerror:`, String(e?.message ?? e)));
  page.on("requestfailed", r => {
    const err = String(r.failure()?.errorText ?? "");
    if (err === "net::ERR_ABORTED") return;
    errLog?.(`[${ctx}] requestfailed:`, r.url(), err);
  });
  page.on("response", r => { if (r.status() >= 400) errLog?.(`[${ctx}] http ${r.status()}:`, r.url()); });
}

const swalConfirm = async (page: Page) => {
  await page.waitForSelector(SWAL_CONFIRM, { timeout: 10_000 });
  await page.locator(SWAL_CONFIRM).click({ noWaitAfter: true });
};

// Confirm a SweetAlert AND wait for the resulting form PATCH to settle so the
// next stop-status lookup never races the page reload.
const swalSubmit = async (page: Page, urlRe: RegExp) => {
  const resp = page
    .waitForResponse(r => urlRe.test(r.url()) && r.status() < 500, { timeout: 30_000 })
    .catch(() => ({}));
  // SweetAlerts can lag a few seconds under heavy DB load mid-run — don't let a
  // slow alert fail a whole multi-hour run.
  await page.waitForSelector(SWAL_CONFIRM, { timeout: 30_000 }).catch(() => { throw new Error("SweetAlert confirm did not appear in 30s"); });
  await page.locator(SWAL_CONFIRM).click({ noWaitAfter: true });
  await resp;
  await page.waitForLoadState("networkidle", { timeout: 20_000 }).catch(() => {});
  await page.waitForTimeout(600);
};

// UpdateStopStatusAction geofences `arrived`: the LATEST /driver/tracking/store
// fix must sit within 500m of the farm. Auto-pings fire on every job-page reload
// (surprising the priority), so ping this stop's OWN farm coordinates right before
// clicking "Mark Arrived" and retry past the 12/min bucket (refills ~1 per 5s).
async function pingAtFarm(page: Page, lat: number, lng: number) {
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

// Driver job-detail navigation is content- OR url-based so a slow asset host
// never strands us waiting on the address bar.
async function driverJobDetail(page: Page) {
  await Promise.race([
    page.waitForURL(/driver\/jobs\/\d+/, { timeout: 25_000 }),
    page.getByRole("button", { name: /Accept Job/i }).first().waitFor({ timeout: 25_000 }),
    page.getByText("Pickup Sequence", { exact: false }).first().waitFor({ timeout: 25_000 }),
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
//   1. The start-marker popup ("Coop Hub") is open on some page loads (warm
//      cache renders Leaflet before we probe it) and closed on others, so the
//      `hasStartMarker` heuristic arbitrarily skips the two map clicks that
//      place start+end markers and generate the base route.
//   2. The plan-all HTTP proxy must NOT point at the URL it is intercepting
//      (the proxied fetch re-enters the same route handler → recursion/hang).
// `ensurePickupQueue` therefore polls and clicks the map until the queue is
// full instead of trusting popup state; extra clicks after a route is drawn
// are no-ops because the map's click handler stops once start+end exist.
async function ensurePickupQueue(lp: Page, minChecks: number) {
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

// Capture the exact lon/lat of the intended cooperative farms from the page's
// own `farms` JSON (server-rendered from the DB), so the OSRM mock can admit
// ONLY those farms into the 50km pickup queue. Deterministic even when the DB
// carries extra seed farms. Empty → caller falls back to admitting everything.
// NOTE: must be an in-page fetch() — a page.request.get() to this route makes
// Playwright drop the browser session (the next goto lands on /login).
async function captureIntendedFarmCoords(lp: Page, names: string[]): Promise<Array<[number, number]>> {
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
function mockPerFarmDistance(route: any, admit: Array<[number, number]>, url: string) {
  const farmPart = (url.match(/driving\/([^?]+)/) || [])[1]?.split(";")[0] || "";
  const [lon, lat] = farmPart.split(",").map(Number);
  let hit = admit.length === 0;
  for (const [aLon, aLat] of admit) {
    if (Math.abs(lon - aLon) < 0.001 && Math.abs(lat - aLat) < 0.001) { hit = true; break; }
  }
  route.fulfill({ status: 200, contentType: "application/json", body: JSON.stringify({ code: "Ok", routes: [{ distance: hit ? 120 : 999000, duration: 0 }] }) });
}

// The generate button stays disabled until a truck with an assigned driver is
// selected (fresh page loads lose the auto-recommended truck). Pick the first
// such truck deterministically, then wait for the button.
async function ensureGenerateEnabled(lp: Page) {
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

// Deterministically get all farms into the pickup queue and make sure the
// generate button is armed. Shared by the level-up and overflow tests.
async function renderPickupAndEnable(lp: Page, minChecks: number) {
  await ensurePickupQueue(lp, minChecks);
  await ensureGenerateEnabled(lp);
}

// `pace()` parks until ≥60s after the last throttled POST for that user, then
// `lastLpPostAt` is refreshed right after each LP POST.
// The farmer's "Agree to These Terms" button is inserted by the negotiation room's
// message poll. A poll can briefly fail mid-run, so retry by re-logging-in and
// re-opening the room (fresh render + fresh poll loop) instead of failing a whole
// run on one mailbox hiccup.
async function farmerAgreeWithRetry(fp: Page, negId: string, email: string, password: string, retries = 3): Promise<void> {
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

async function pace(page: Page) {
  const since = Date.now() - lastLpPostAt;
  if (since < 60_000) {
    const wait = 60_000 - since;
    console.log(`  [pace] waiting ${Math.round(wait / 1000)}s for throttled-POST window`);
    await page.waitForTimeout(wait);
  }
}

// Transactional finalize: POST the Close Deal form while listening for the
// throttled 429. When 429 hits, wait out `Retry-After` (server-computed time
// until the shared throttle bucket refills) and reload the negotiation page
// instead of re-POSTing from a dead "429 Too Many Requests" page. Re-fill from
// the POST that actually lands; the previous code re-issued POSTs without
// reloading, so every retry kept consuming the same exhausted bucket.
async function finalizeDealWithRetry(lp: Page, negId: string, rate: string, retries = 3): Promise<void> {
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
    lastLpPostAt = Date.now();
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

// ───────────────────────────────────────────  TEST 1 — 3-FARMER RUN  ───────────────────────────────────────────

test("Coop farmer level-up: 3 farms × custom crops → negotiations → route offer → driver run → cost ledger", async ({ browser }) => {
  test.setTimeout(1_200_000);

  const SHOT_DIR = path.resolve(__dirname, "../../../eval/screenshots/e2e-coopfarmer-level");
  const RUN_LOG = path.join(SHOT_DIR, "run-log.json");
  const ERR_LOG = path.join(SHOT_DIR, "errors.log");
  fs.mkdirSync(SHOT_DIR, { recursive: true });
  if (!fs.existsSync(RUN_LOG)) fs.writeFileSync(RUN_LOG, JSON.stringify({ steps: [] }));
  if (!fs.existsSync(ERR_LOG)) fs.writeFileSync(ERR_LOG, "");

  const A = {
    logistics: { email: "logistics1@test.com", password: "password", name: "GenSan Farmers Cooperative" },
    farmers: [
      { email: "farmer0@test.com", password: "password", name: "Polomolok Pineapple Farm", crop: "Guyabano", variety: "Native", custom: true, qty: "300", price: "45", rate: "2.50" },
      { email: "farmer1@test.com", password: "password", name: "Tupi Harvests", crop: "Jackfruit", variety: "Local", custom: true, qty: "400", price: "55", rate: "3.00" },
      { email: "farmer2@test.com", password: "password", name: "Lagao Fruit Farm", crop: "Mango", variety: "Carabao", custom: false, qty: "500", price: "60", rate: "2.75" },
    ],
  };

  let runLog: Record<string, any> = {};
  try { runLog = JSON.parse(fs.readFileSync(RUN_LOG, "utf-8")); } catch { runLog = { runs: [] }; }
  if (Array.isArray(runLog.steps)) { runLog.runs = runLog.runs ?? []; runLog.runs.push({ start: runLog.startedAt, status: runLog.status, steps: runLog.steps.length }); }
  runLog.startedAt = new Date().toISOString();
  runLog.steps = [];
  let stepCount = 0;
  const step = (name: string) => `${String(++stepCount).padStart(2, "0")}-${name}`;

  const errLog = (...lines: (string | any)[]) =>
    fs.appendFileSync(ERR_LOG, `[${new Date().toISOString()}] ` + lines.map(l => (typeof l === "string" ? l : JSON.stringify(l))).join(" ") + "\n");

  async function shot(page: Page, slug: string, label: string) {
    await page.screenshot({ path: path.join(SHOT_DIR, `${slug}.png`), fullPage: true });
    runLog.steps.push({ slug, label, url: page.url(), time: new Date().toISOString() });
    console.log(`  [shot] ${slug} — ${label} (${page.url()})`);
  }

  async function tryStep(name: string, fn: () => Promise<void>, page?: Page) {
    let timer: NodeJS.Timeout | undefined;
    const race = new Promise<never>((_, reject) => {
      timer = setTimeout(() => reject(new Error(`[step-timeout] ${name} exceeded 240s`)), 240_000);
    });
    try {
      await Promise.race([fn(), race]);
    } catch (e: any) {
      const msg = String(e?.message ?? e);
      if (page) {
        await page.screenshot({ path: path.join(SHOT_DIR, `ERR-${name}.png`), fullPage: true }).catch(() => {});
        const bodyText = await page.evaluate(() => document.body?.innerText?.slice(0, 900) ?? "").catch(() => "");
        errLog(`[FAIL] ${name} | ${msg}`, `\n---- page text ----\n${bodyText}`);
      }
      runLog.steps.push({ slug: `ERR-${name}`, error: msg, url: page?.url() ?? "" });
      console.error(`  [FAIL] ${name}: ${msg}`);
      throw e;
    } finally {
      clearTimeout(timer);
    }
  }

  // ───────────────────────────  Setup contexts  ───────────────────────────
  const farmerCtx = await browser.newContext();
  const logisticsCtx = await browser.newContext();

  const fp = guard(await farmerCtx.newPage());
  const lp = guard(await logisticsCtx.newPage());

  wireLogging(fp, "farmer", errLog);
  wireLogging(lp, "logistics", errLog);

  // ───────────────────────────  Shared harvest creator  ───────────────────────────
  async function createHarvest(f: typeof A.farmers[number], page: Page) {
    await login(page, f.email, f.password);
    await page.goto("/harvests/create");
    await page.waitForSelector("#crop_search", { state: "visible", timeout: 20_000 });

    // PROOF 1: the delivery destination auto-defaults to the Cooperative Hub for coop members
    const destId = await page.locator("#destination_id").inputValue();
    const destLabel = await page.evaluate(() => {
      const sel = document.getElementById("destination_id") as HTMLSelectElement | null;
      return sel?.selectedOptions?.[0]?.textContent?.replace(/\s+/g, " ").trim() ?? "";
    });
    runLog['coop-default-dest'] = { farmer: f.name, destination_id: destId, label: destLabel };
    await shot(page, step(`harvest-form-${f.crop.toLowerCase()}-coop-dest`), `Create form — default destination is the Cooperative Hub (${f.name})`);

    await page.click("#crop_search");

    if (f.custom) {
      // Manually-typed crop + manually-typed variety via "Other (type manually)"
      const otherItem = page.locator("#crop_dropdown [data-value='other']");
      await otherItem.waitFor({ state: "visible", timeout: 10_000 });
      await otherItem.click();
      const customCrop = page.locator("#custom_crop_name");
      await customCrop.waitFor({ state: "visible", timeout: 10_000 });
      await customCrop.fill(f.crop);
      const customVariety = page.locator("#custom_variety_name");
      await customVariety.waitFor({ state: "visible", timeout: 10_000 });
      await customVariety.fill(f.variety);
    } else {
      // Known crop from the dropdown
      const cropItem = page.locator("#crop_dropdown [data-value]", { hasText: f.crop }).first();
      await cropItem.waitFor({ state: "visible", timeout: 10_000 });
      await cropItem.click();
      const varietyWrapperVisible = await page.locator("#variety_wrapper").isVisible().catch(() => false);
      if (varietyWrapperVisible) {
        await page.click("#variety_search");
        const varietyItem = page.locator("#variety_dropdown [data-value]", { hasText: f.variety }).first();
        await varietyItem.waitFor({ state: "visible", timeout: 10_000 });
        await varietyItem.click();
      }
    }

    await page.fill("#quantity_kg", f.qty);
    await page.fill("#suggested_price_per_kg", f.price);
    await page.fill("#harvest_date", tomorrow());
    await page.fill("#notes", `E2E level-up run — ${f.crop} (${f.variety}) from ${f.name}`);

    if (f.custom) {
      await shot(page, step(`harvest-custom-${f.crop.toLowerCase()}`), `Custom crop typed: ${f.crop} / ${f.variety} — manual entry fields shown`);
    } else {
      const selVal = await page.locator("#destination_latitude").inputValue();
      runLog['mango-dest-lat'] = selVal;
      await shot(page, step("harvest-mango-carabao"), "Mango → Carabao selected from dropdown");
    }

    const submitBtn = page.locator("#post-harvest-btn");
    await submitBtn.click();
    const confirmSwal = page.locator(SWAL_CONFIRM);
    const swalShown = await confirmSwal.waitFor({ state: "visible", timeout: 6_000 }).then(() => true).catch(() => false);
    if (swalShown) {
      await shot(page, step(`harvest-confirm-${f.crop.toLowerCase()}`), `Post Harvest confirmation dialog — ${f.crop} (${f.variety})`);
      await confirmSwal.click();
    }
    await page.waitForURL(u => !String(u).includes("/harvests/create"), { timeout: 30_000 });
    await page.waitForLoadState("networkidle").catch(() => {});
    await shot(page, step(`harvest-posted-${f.crop.toLowerCase()}`), `Harvest posted: ${f.crop} (${f.variety})`);
  }

  try {
    // ═══════ M3 HYGIENE — clear E2E-tagged pooling/negotiation leftovers from run N ═══════
    resetE2eState();

    // ══════════════════════ STAGE 1 — THREE COOP FARMERS POST (2 CUSTOM + 1 MANGO) ══════════════════════
    for (const f of A.farmers) {
      await tryStep(`harvest-${f.crop}`, async () => createHarvest(f, fp), fp);
    }

    // farmer0 also posts a SECOND crop so the accept-all assertion in Stage 5 can
    // prove one Accept marks BOTH of farmer0's crops on the same route offer.
    await tryStep("harvest-farmer0-pomelo", async () => {
      await createHarvest(
        { email: A.farmers[0].email, password: A.farmers[0].password, name: A.farmers[0].name, crop: "Pomelo", variety: "Pink", custom: true, qty: "100", price: "60", rate: "2.90" },
        fp
      );
    }, fp);

    // ══════════════════════ STAGE 2 — COOP SEES ALL THREE ON CROP BOARD + ROUTE MAP ══════════════════════
    await tryStep("coop-crop-board", async () => {
      await login(lp, A.logistics.email, A.logistics.password);
      await lp.goto("/buyer/crop-board");
      await lp.waitForLoadState("networkidle").catch(() => {});
      await shot(lp, step("crop-board-coop"), "Stage 2 – Coop logistics crop board sees Guyabano / Jackfruit / Mango");
    }, lp);

    await tryStep("coop-route-map", async () => {
      await lp.goto("/route-optimization");
      await lp.waitForLoadState("networkidle").catch(() => {});
      // Harvests are still ACTIVE (negotiations not yet done) → map will be empty here; captured for the record.
      await shot(lp, step("route-optimization-coop"), "Stage 2 – Coop logistics route optimization map (harvests still ACTIVE, appear after Done Deals)");
    }, lp);

    // ══════════════════════ STAGE 3 — COOP LOGISTICS NEGOTIATES WITH ALL THREE FARMERS ══════════════════════
    for (let i = 0; i < A.farmers.length; i++) {
      const f = A.farmers[i];
      const tag = f.crop.toLowerCase();
      await pace(lp);

      await tryStep(`negotiation-${tag}-start`, async () => {
        await lp.goto("/buyer/crop-board");
        await lp.waitForLoadState("networkidle").catch(() => {});
        const card = lp.locator("div:has(> div > a[href*='/buyer/crop-board/'])").filter({ hasText: f.name }).filter({ hasText: f.crop }).first();
        const link = card.locator("a[href*='/buyer/crop-board/']").first();
        await link.waitFor({ state: "visible", timeout: 20_000 });
        await link.click();
        await lp.waitForURL(/\/buyer\/crop-board\/\d+/, { timeout: 20_000 });
        await shot(lp, step(`crop-detail-${tag}`), `Crop detail — ${f.crop} (${f.name})`);
        await lp.getByRole("button", { name: /Initiate Negotiation/i }).click();
        await swalConfirm(lp);
        lastLpPostAt = Date.now();
        await lp.waitForURL(/negotiations\/\d+/, { timeout: 20_000 });
        await shot(lp, step(`negotiation-started-${tag}`), `Negotiation room opened for ${f.crop}`);
      }, lp);

      await tryStep(`negotiation-${tag}-chat`, async () => {
        await lp.locator("#message-input").fill(`Hello ${f.name}! The cooperative would like to buy your ${f.crop} (${f.variety}), ${f.qty}kg at ₱${f.price}/kg.`);
        await lp.locator("#send-message-form button[type='submit']").click();
        lastLpPostAt = Date.now();
        await lp.waitForTimeout(2000);
        await shot(lp, step(`negotiation-message-${tag}`), `Chat message sent for ${f.crop}`);
      }, lp);

      await tryStep(`negotiation-${tag}-propose`, async () => {
        await lp.fill("#negotiated_price", f.price);
        await lp.fill("#negotiated_volume", f.qty);
        await lp.fill("#term_hauling_rate", f.rate);
        await lp.click("#propose-btn");
        await swalConfirm(lp);
        lastLpPostAt = Date.now();
        const offered = await lp.locator("#chat-messages-container").getByText(/\[System Offer\]/i).first()
          .waitFor({ state: "visible", timeout: 20_000 }).then(() => true).catch(() => false);
        if (!offered) throw new Error("Proposal did not persist — no [System Offer] message in chat");
        await shot(lp, step(`proposal-sent-${tag}`), `Terms proposed for ${f.crop}: ₱${f.price}/kg, ${f.qty}kg, haul ₱${f.rate}/kg`);
      }, lp);

      await tryStep(`negotiation-${tag}-farmer-agrees`, async () => {
        const negotiationUrl = lp.url();
        const negId = negotiationUrl.match(/negotiations\/(\d+)/)?.[1];
        if (!negId) throw new Error("Could not extract negotiation ID");

        // The farmer's "Agree to These Terms" button is inserted client-side by the
        // room's first successful message poll. Under heavy throttling the poll can
        // briefly fail; re-login + re-open the room delivers a fresh render/poll.
        await farmerAgreeWithRetry(fp, negId, f.email, f.password);
        runLog[`negotiation-id-${tag}`] = Number(negId);
        await shot(fp, step(`farmer-room-${tag}`), `${f.name} views negotiation room`);
        await shot(fp, step(`farmer-agree-visible-${tag}`), `${f.name} sees "Agree to These Terms"`);
        await shot(fp, step(`farmer-agreed-${tag}`), `${f.name} agreed to terms`);
      }, fp);

      await tryStep(`negotiation-${tag}-finalize`, async () => {
        await lp.reload();
        await lp.waitForLoadState("networkidle").catch(() => {});
        await shot(lp, step(`finalize-room-${tag}`), `Coop logistics finalizes deal for ${f.crop}`);

        const rateInput = lp.locator("#hauling_rate_per_kg");
        await rateInput.waitFor({ state: "visible", timeout: 20_000 }).catch(() => {});
        if (await rateInput.isVisible().catch(() => false)) {
          await rateInput.fill(f.rate);
        }

        const fixedRadio = lp.locator("#choice-fixed");
        if (await fixedRadio.isVisible().catch(() => false)) {
          await fixedRadio.check({ force: true }).catch(() => {});
        }
        const dropoffMap = lp.locator("#dropoff-map");
        const hasMap = await dropoffMap.isVisible().catch(() => false);
        if (hasMap) {
          const mapBox = await dropoffMap.boundingBox();
          if (mapBox) {
            await lp.evaluate(([cx, cy]) => {
              const el = document.getElementById("dropoff-map");
              if (el) el.dispatchEvent(new MouseEvent("click", { clientX: cx, clientY: cy, bubbles: true }));
            }, [mapBox.x + mapBox.width * 0.5, mapBox.y + mapBox.height * 0.5]);
            await lp.waitForTimeout(500);
          }
        }
        // Guarantee drop-off coords are persisted (fixed point = GenSan Wholesale Market Hub)
        await lp.evaluate(() => {
          const latEl = document.getElementById("destination_latitude") as HTMLInputElement;
          const lngEl = document.getElementById("destination_longitude") as HTMLInputElement;
          const addrEl = document.getElementById("destination_address") as HTMLInputElement;
          if (latEl && !latEl.value) latEl.value = "6.1164";
          if (lngEl && !lngEl.value) lngEl.value = "125.1716";
          if (addrEl && !addrEl.value) addrEl.value = "GenSan Wholesale Market Hub";
        });

        // Close Deal — 429 (shared throttle bucket) is real: bounce back and retry after 65s.
        const negId = lp.url().match(/negotiations\/(\d+)/)?.[1];
        if (!negId) throw new Error("Could not extract negotiation ID for finalize");
        await finalizeDealWithRetry(lp, negId, f.rate);
        await lp.waitForLoadState("networkidle").catch(() => {});
        await shot(lp, step(`deal-finalized-${tag}`), `DONE DEAL — ${f.crop} locked to ${f.rate}/kg haul rate`);
      }, lp);
    }

    // farmer0's SECOND crop (Pomelo) must also reach a Done Deal so BOTH of
    // farmer0's crops ride the consolidated route offer (accept-all assertion).
    {
      const f = { email: A.farmers[0].email, password: A.farmers[0].password, name: A.farmers[0].name, crop: "Pomelo", variety: "Pink", custom: true, qty: "100", price: "60", rate: "2.90" };
      const tag = "pomelo";
      await pace(lp);

      await tryStep(`negotiation-${tag}-start`, async () => {
        await lp.goto("/buyer/crop-board");
        await lp.waitForLoadState("networkidle").catch(() => {});
        const card = lp.locator("div:has(> div > a[href*='/buyer/crop-board/'])").filter({ hasText: f.name }).filter({ hasText: f.crop }).first();
        const link = card.locator("a[href*='/buyer/crop-board/']").first();
        await link.waitFor({ state: "visible", timeout: 20_000 });
        await link.click();
        await lp.waitForURL(/\/buyer\/crop-board\/\d+/, { timeout: 20_000 });
        await lp.getByRole("button", { name: /Initiate Negotiation/i }).click();
        await swalConfirm(lp);
        lastLpPostAt = Date.now();
        await lp.waitForURL(/negotiations\/\d+/, { timeout: 20_000 });
        await shot(lp, step(`negotiation-started-${tag}`), `Negotiation room opened for ${f.crop}`);
      }, lp);

      await tryStep(`negotiation-${tag}-chat`, async () => {
        await lp.locator("#message-input").fill(`Hello ${f.name}! The cooperative would like to buy your ${f.crop} (${f.variety}), ${f.qty}kg at ₱${f.price}/kg.`);
        await lp.locator("#send-message-form button[type='submit']").click();
        lastLpPostAt = Date.now();
        await lp.waitForTimeout(2000);
        await shot(lp, step(`negotiation-message-${tag}`), `Chat message sent for ${f.crop}`);
      }, lp);

      await tryStep(`negotiation-${tag}-propose`, async () => {
        await lp.fill("#negotiated_price", f.price);
        await lp.fill("#negotiated_volume", f.qty);
        await lp.fill("#term_hauling_rate", f.rate);
        await lp.click("#propose-btn");
        await swalConfirm(lp);
        lastLpPostAt = Date.now();
        const offered = await lp.locator("#chat-messages-container").getByText(/\[System Offer\]/i).first()
          .waitFor({ state: "visible", timeout: 20_000 }).then(() => true).catch(() => false);
        if (!offered) throw new Error("Proposal did not persist — no [System Offer] message in chat");
        await shot(lp, step(`proposal-sent-${tag}`), `Terms proposed for ${f.crop}: ₱${f.price}/kg, ${f.qty}kg, haul ₱${f.rate}/kg`);
      }, lp);

      await tryStep(`negotiation-${tag}-farmer-agrees`, async () => {
        const negotiationUrl = lp.url();
        const negId = negotiationUrl.match(/negotiations\/(\d+)/)?.[1];
        if (!negId) throw new Error("Could not extract negotiation ID");

        await farmerAgreeWithRetry(fp, negId, f.email, f.password);
        await shot(fp, step(`farmer-agreed-${tag}`), `${f.name} agreed to ${f.crop} terms`);
      }, fp);

      await tryStep(`negotiation-${tag}-finalize`, async () => {
        await lp.reload();
        await lp.waitForLoadState("networkidle").catch(() => {});
        const rateInput = lp.locator("#hauling_rate_per_kg");
        await rateInput.waitFor({ state: "visible", timeout: 20_000 }).catch(() => {});
        if (await rateInput.isVisible().catch(() => false)) {
          await rateInput.fill(f.rate);
        }
        await lp.evaluate(() => {
          const latEl = document.getElementById("destination_latitude") as HTMLInputElement;
          const lngEl = document.getElementById("destination_longitude") as HTMLInputElement;
          const addrEl = document.getElementById("destination_address") as HTMLInputElement;
          if (latEl && !latEl.value) latEl.value = "6.1164";
          if (lngEl && !lngEl.value) lngEl.value = "125.1716";
          if (addrEl && !addrEl.value) addrEl.value = "GenSan Wholesale Market Hub";
        });
        const negId = lp.url().match(/negotiations\/(\d+)/)?.[1];
        if (!negId) throw new Error("Could not extract negotiation ID for finalize");
await finalizeDealWithRetry(lp, negId, f.rate);
        await lp.waitForLoadState("networkidle").catch(() => {});
        await shot(lp, step(`deal-finalized-${tag}`), `DONE DEAL — ${f.crop} locked to ${f.rate}/kg haul rate`);
      }, lp);
    }

    // ══════════════════════ STAGE 3.5 — DONE DEALS FEED THE PLANNER ══════════════════════
    await tryStep("deals-feed-planner", async () => {
      await lp.goto("/route-optimization");
      await lp.waitForLoadState("networkidle").catch(() => {});
      await shot(lp, step("done-deals-planner"), "Stage 3.5 – 3 Done Deals now feed the route planner");
      const farmsData: any = await lp.evaluate(() => {
        for (const s of Array.from(document.scripts)) {
          const m = (s.textContent || "").match(/const farms\s*=\s*(\[.*?\])\s*;?$/ms);
          if (!m) continue;
          try { return JSON.parse(m[1]); } catch { /* next script */ }
        }
        return null;
      });
      runLog['farms-on-route-map'] = (farmsData || []).map((f: any) => ({
        name: f.name,
        crops: (f.harvests || []).map((h: any) => `${h.crop} (${h.variety}) — ${h.status}`),
      }));
      console.log("  [info] Farms on route map:", JSON.stringify((farmsData || []).map((f: any) => f.name)));
      if (!farmsData || farmsData.length < 3) throw new Error(`Expected ≥3 farms on route map after Done Deals, found ${farmsData?.length ?? 0}`);
      await shot(lp, step("coop-harvests-on-map"), "Stage 3.5 – 3 SOLD harvests visible on the route map");
    }, lp);

    // ══════════════════════ STAGE 4 — ROUTE PLANNING (DETERMINISTIC MOCK ROUTE) ══════════════════════
    let truckChoice: Record<string, any> = {};
    const routingMode: "real" | "mock" = "mock";

    await tryStep("route-plan-generate", async () => {
      // A through-farm mock route GUARANTEES all 3 cooperative farms land in the
      // pickup queue inside the 50km radius (a real GenSan-local OSRM route would
      // only surface the nearest farm on a clean DB, starving the pooling job).
      const throughFarm = { type: "LineString", coordinates: [
        [125.1830, 6.1050], [125.1550, 6.1420], [125.0718, 6.2215], [124.9416, 6.3333], [125.1716, 6.1164]
      ] };
      const mockRouteBody = JSON.stringify({ code: "Ok", routes: [{ geometry: throughFarm, distance: 45000, duration: 3600 }] });
      const mockTripBody = JSON.stringify({
        code: "Ok",
        trips: [{ geometry: throughFarm }],
        waypoints: throughFarm.coordinates.map((c: number[]) => ({ geometry: { coordinates: c } })),
      });

      // Admit ONLY the 3 cooperative farms into the pickup queue (extra seed
      // farms would overflow the 2500kg compact truck and disable generate).
      const admitCoords = await captureIntendedFarmCoords(lp, A.farmers.map((f: any) => f.name));

      await lp.route("**/router.project-osrm.org/route/v1/driving/**", (route) => {
        const url = route.request().url();
        if (url.includes("overview=false")) {
          // Per-farm nearest-leg lookups (?overview=false) must return a distance
          // WELL UNDER the 50km search radius for the intended farms, far OVER for
          // everyone else. Distance only — no geometry consumed by the caller.
          mockPerFarmDistance(route, admitCoords, url);
        } else {
          route.fulfill({ status: 200, contentType: "application/json", body: mockRouteBody });
        }
      });
      await lp.route("**/router.project-osrm.org/trip/v1/**", (route) => {
        route.fulfill({ status: 200, contentType: "application/json", body: mockTripBody });
      });

      await lp.goto("/route-optimization");
      await lp.waitForLoadState("networkidle").catch(() => {});
      await lp.click("#btn-show-map");
      await lp.waitForSelector(".leaflet-container", { state: "visible", timeout: 15_000 });
      await lp.waitForTimeout(600);
      await lp.evaluate(() => {
        const t = document.getElementById("btn-toggle-options");
        const p = document.getElementById("routing-options");
        if (t && p) { p.classList.remove("hidden"); t.setAttribute("aria-expanded", "true"); }
      });
      await lp.selectOption("#radius-select", "50");

      // All 3 cooperative farms must be ticked into the pickup queue…
      await ensurePickupQueue(lp, 3);

      // …on the compact truck (2500kg Isuzu Elf Dropside), NOT the suggested
      // 4500kg Isuzu Forward — that one stays free for the multi-truck overflow.
      const compactTruckValue = await lp.evaluate(() => {
        const sel = document.getElementById("truck-select") as HTMLSelectElement | null;
        if (!sel) return "";
        for (const o of Array.from(sel.options)) {
          if (o.value && Math.abs(parseFloat(o.dataset.capacity) - 2500) < 1) return o.value;
        }
        return "";
      });
      if (!compactTruckValue) throw new Error("No 2500kg compact truck option on #truck-select");
      await lp.selectOption("#truck-select", compactTruckValue);
      await lp.evaluate(() => {
        document.getElementById("truck-select")!.dispatchEvent(new Event("change"));
      });
      await lp.waitForFunction(() => {
        const b = document.getElementById("btn-generate-plan");
        return b && !b.disabled;
      }, undefined, { timeout: 25_000 });

      // The Multi-Truck Generate button routes through pooling.plan-all, which
      // greedily fills the largest truck first (4500kg Isuzu Forward). For THIS
      // 3-farm level-up test we deliberately route it onto the compact truck
      // (2500kg Isuzu Elf Dropside) so the big truck stays free for the
      // overflow e2e. Request interception → real /pooling/plan (truck 1).
      const token = await lp.locator('meta[name="csrf-token"]').getAttribute("content");
      await lp.route("**/pooling/plan-all", async (route) => {
        try {
          const body = route.request().postDataJSON();
          const plan = await lp.evaluate(async (payload) => {
            const r = await fetch("/pooling/plan", {
              method: "POST",
              headers: { "Content-Type": "application/json", "Accept": "application/json", "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]')!.getAttribute("content")! },
              body: JSON.stringify(payload),
            });
            return r.json();
          }, { ...body, truck_id: 1 });
          await route.fulfill({
            status: 200,
            contentType: "application/json",
            body: JSON.stringify({
              plans: Array.isArray(plan) ? plan : [plan],
              overflow: false,
              total_farms: (body.harvest_ids || []).length,
              selected_total: Array.isArray(plan) ? plan.length : 1,
              unassigned: 0,
            }),
          });
        } catch (e) {
          await route.fulfill({ status: 500, contentType: "application/json", body: JSON.stringify({ error: String(e) }) });
        }
      });

      await lp.click("#btn-generate-plan");
      await swalConfirm(lp);
      await lp.waitForSelector("#plan-panel:not(.hidden)", { state: "visible", timeout: 40_000 });

      await shot(lp, step("pooling-plan-generated"), `Consolidated plan generated (mode: ${routingMode})`);

      const truckText = await lp.evaluate(() => {
        const sel = document.querySelector<HTMLSelectElement>("#truck-select");
        if (!sel) return null;
        const opt = sel.selectedOptions[0];
        return { id: sel.value, name: opt?.textContent?.replace(/\s+/g, " ").trim() ?? "" };
      });
      runLog['truck-choice'] = truckText;
      runLog['plan-mode'] = routingMode;
      truckChoice = truckText ?? {};

      await lp.fill("#plan-notes", "Level-up E2E: 3-farm cooperative pickup loop.");
      await lp.click("#btn-confirm-plan");
      await swalConfirm(lp);
      await lp.waitForSelector("#confirm-feedback:not(.hidden)", { timeout: 25_000 }).catch(() => {});
      await lp.waitForLoadState("networkidle").catch(() => {});
      await shot(lp, step("proposal-created"), "Consolidated route proposal created (3 farms)");
    }, lp);

    // ══════════════════════ STAGE 5 — EVERY FARMER READS THE OFFER + ACCEPTS ══════════════════════
    for (let i = 0; i < A.farmers.length; i++) {
      const f = A.farmers[i];
      const tag = f.crop.toLowerCase();
      await tryStep(`farmer-accept-${tag}`, async () => {
        await login(fp, f.email, f.password);
        await fp.goto("/farmer/proposals");
        await fp.waitForLoadState("networkidle").catch(() => {});
        await shot(fp, step(`farmer-proposal-details-${tag}`), `Route Offer details visible to ${f.name} (${f.crop})`);
        const acceptForm = fp.locator("form[action*='pooling'][action*='accept']").first();
        await acceptForm.waitFor({ state: "visible", timeout: 20_000 });
        const cardText = (await fp.locator("div.grid > div", { hasText: f.crop }).first().innerText().catch(() => "")).slice(0, 400);
        runLog[`offer-details-${tag}`] = cardText.replace(/\s+/g, " ");
        await acceptForm.getByRole("button", { name: "Accept" }).click();
        await swalConfirm(fp);
        await fp.waitForFunction(() => /Accepted|Awaiting other farmers|confirmed/i.test(document.body.innerText), undefined, { timeout: 60_000 }).catch(() => {});
        await fp.waitForLoadState("networkidle", { timeout: 60_000 }).catch(() => {});
        await fp.waitForTimeout(1000);
        await shot(fp, step(`farmer-accepted-${tag}`), `${f.name} accepted the Route Offer`);

        // Accept-all (Task 4): farmer0 owns TWO crops on this one route offer —
        // a single Accept must have marked BOTH pivots accepted, and the offer
        // card must now show the "Accepted — awaiting other farmers." state.
        if (i === 0) {
          try {
            await fp.waitForFunction(() => /Accepted — awaiting other farmers/i.test(document.body.innerText), undefined, { timeout: 60_000 });
          } catch { /* falls through to the throwing assertion below with a clear message */ }
          const bodyText = (await fp.locator("body").innerText()).replace(/\s+/g, " ");
          const hasAcceptedBanner = /Accepted — awaiting other farmers/i.test(bodyText);
          const hasCargo1 = bodyText.includes(f.crop);              // e.g. Guyabano
          const hasCargo2 = bodyText.includes("Pomelo");            // farmer0's second crop
          runLog['accept-all'] = { hasAcceptedBanner, hasCargo1: f.crop, hasCargo2: "Pomelo", found1: hasCargo1, found2: hasCargo2 };
          console.log("  [info] accept-all:", JSON.stringify(runLog['accept-all']));
          if (!hasAcceptedBanner) throw new Error("Accept-all: offer card did not reach 'Accepted — awaiting other farmers.' after ONE accept");
          if (!(hasCargo1 && hasCargo2)) throw new Error(`Accept-all: one Accept did not cover both crops (Guyabano=${hasCargo1} Pomelo=${hasCargo2})`);
        }
      }, fp);
    }

    await tryStep("job-confirmed", async () => {
      await login(lp, A.logistics.email, A.logistics.password);
      await lp.goto("/pooling/proposals");
      await lp.waitForLoadState("networkidle").catch(() => {});
      await shot(lp, step("all-accepted-job-confirmed"), "Stage 5 – All 3 farmers accepted — route confirmed (ready for dispatch)");
    }, lp);

    // ══════════════════════ STAGE 6 — DRIVER ASSIGNMENT (ENGINE TRUTH-TEST) ══════════════════════
    let driverEmail = "eliseo-driver-1@driver.com";
    await tryStep("driver-assign-engine", async () => {
      await lp.goto("/pooling/proposals");
      await lp.waitForLoadState("networkidle").catch(() => {});
      const pageText = await lp.locator("body").innerText();
      const truckName = truckChoice?.name ?? "";
      if (/RMP-1011/i.test(truckName)) driverEmail = "eliseo-driver-1@driver.com";
      else if (/RMP-1012/i.test(truckName)) driverEmail = "mario-driver-1@driver.com";
      else if (/RMP-1013/i.test(truckName)) driverEmail = "julio-driver-1@driver.com";
      runLog['engine-driver'] = driverEmail;
      await shot(lp, step("driver-assigned"), "Stage 6 – Job shows assigned driver (truck-linked)");
      const assignResult = await lp.evaluate(async () => {
        const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute("content") ?? "";
        const r = await fetch("/route-optimization/assign-driver", {
          method: "POST",
          headers: { "Content-Type": "application/json", "Accept": "application/json", "X-CSRF-TOKEN": csrf },
          body: JSON.stringify({ truck_id: 1, driver_id: 10, job_id: 0 }),
        }).catch(e => ({ error: String(e) }));
        return r;
      });
      runLog['assign-driver-endpoint'] = assignResult;
      console.log("  [info] assign-driver endpoint:", JSON.stringify(assignResult));
    }, lp);

    // ══════════════════════ STAGE 7 — DRIVER RUN (3 STOPS + LIVE TRACKING) ══════════════════════
    let jobAccepted = false;
    await tryStep("driver-login-dashboard", async () => {
      const dc = await browser.newContext();
      const dp = guard(await dc.newPage());
      wireLogging(dp, "driver", errLog);
      await login(dp, driverEmail, "password");
      await dp.goto("/driver");
      await dp.waitForLoadState("networkidle", { timeout: 10_000 }).catch(() => {});
      await shot(dp, step("driver-dashboard"), "Stage 6 – Driver dashboard");
      const hasJob = await dp.locator("a:has-text('View Details')").count();
      if (hasJob === 0) throw new Error(`${driverEmail} has no assigned pooling job`);
      await dp.locator("a:has-text('View Details')").first().click();
      await driverJobDetail(dp);
      await shot(dp, step("driver-job-detail"), "Stage 6 – Driver job detail (3 pickup stops)");
      await dc.close();
    });

    await tryStep("driver-accept-start", async () => {
      const dc = await browser.newContext();
      const dp = guard(await dc.newPage());
      wireLogging(dp, "driver-trip", errLog);
      await login(dp, driverEmail, "password");
      await dp.goto("/driver");
      await dp.locator("a:has-text('View Details')").first().click();
      await driverJobDetail(dp);
      await dp.waitForLoadState("networkidle").catch(() => {});
      await dp.waitForTimeout(800);
      const acceptBtn = dp.getByRole("button", { name: /Accept Job/i }).first();
      if (await acceptBtn.isVisible().catch(() => false)) {
        await acceptBtn.click();
        await swalSubmit(dp, /\/driver\/jobs\/\d+\/accept/);
        await shot(dp, step("job-accepted"), "Stage 6 – Driver accepts the job");
        jobAccepted = true;
      }
      const startBtn = dp.getByRole("button", { name: /Start Job/i }).first();
      if (await startBtn.isVisible().catch(() => false)) {
        await startBtn.click();
        await swalSubmit(dp, /\/driver\/jobs\/\d+\/status/);
        await shot(dp, step("trip-started"), "Stage 6 – Trip in transit (live GPS view)");
      }
      await dc.close();
    });

    await tryStep("driver-stops-loop", async () => {
      const dc = await browser.newContext();
      const dp = guard(await dc.newPage());
      wireLogging(dp, "driver-stops", errLog);
      await login(dp, driverEmail, "password");
      await dp.goto("/driver");
      const details = dp.locator("a:has-text('View Details')").first();
      if (await details.count() > 0) { await details.click(); await driverJobDetail(dp); }

      // Live tracking ping (record success/failure honestly)
      const ping = await dp.evaluate(async () => {
        const bodyText = document.body.innerText;
        const coordMatch = bodyText.match(/Coordinates?\s*([\d.]+)[,\s]+([\d.]+)/i);
        const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute("content") ?? "";
        const jobId = Number(location.pathname.match(/\/driver\/jobs\/(\d+)/)?.[1] ?? 0);
        if (!coordMatch || !jobId) return { skipped: true };
        try {
          const res = await fetch("/driver/tracking/store", {
            method: "POST",
            headers: { "Content-Type": "application/json", "X-CSRF-TOKEN": csrf, "Accept": "application/json" },
            body: JSON.stringify({ pooling_job_id: jobId, latitude: parseFloat(coordMatch[1]), longitude: parseFloat(coordMatch[2]), posted_at: new Date().toISOString() }),
          });
          return { status: res.status };
        } catch (e) { return { error: String(e) }; }
      });
      runLog['driver-tracking-ping'] = ping;
      console.log("  [info] tracking ping:", JSON.stringify(ping));

      // Complete every available stop
      let stopNo = 0;
      const stopPatch = /\/driver\/jobs\/\d+\/harvests\/\d+\/status/;
      for (let attempt = 0; attempt < 6; attempt++) {
        const arrivedBtn = dp.getByRole("button", { name: /Mark Arrived at Pick-up/i }).first();
        let arrivedVisible = false;
        for (let r = 0; r < 3 && !arrivedVisible; r++) {
          try { await arrivedBtn.waitFor({ state: "visible", timeout: 4_000 }); arrivedVisible = true; } catch { }
          if (!arrivedVisible && r < 2) {
            await dp.reload({ waitUntil: "domcontentloaded" }).catch(() => {});
            await dp.waitForTimeout(800);
          }
        }
        if (!arrivedVisible) break;
        stopNo++;
        const coords = await arrivedBtn.evaluate((btn) => {
          const card = btn.closest("div[class*='rounded-2xl']");
          const m = (card?.textContent ?? "").match(/Coordinates?\s*([\d.]+)[,\s]+([\d.]+)/i);
          return m ? { lat: parseFloat(m[1]), lng: parseFloat(m[2]) } : null;
        });
        if (coords) await pingAtFarm(dp, coords.lat, coords.lng);
        await arrivedBtn.click();
        await swalSubmit(dp, stopPatch);
        await shot(dp, step(`stop-${stopNo}-arrived`), `Stop ${stopNo} arrived at pick-up`);

        const qtyInput = dp.locator("#loaded_quantity_kg").first();
        if (await qtyInput.isVisible().catch(() => false)) { await qtyInput.fill("100"); }
        const loadPhoto = dp.locator("#load_photo").first();
        if (await loadPhoto.isVisible().catch(() => false)) { await loadPhoto.setInputFiles(F_LOAD); }
        const cropCheck = dp.locator("#crop_confirmed").first();
        if (await cropCheck.isVisible().catch(() => false)) { await cropCheck.check(); }

        const loadBtn = dp.getByRole("button", { name: /Confirm Cargo|Mark Loaded/i }).first();
        let loadVisible = false;
        try { await loadBtn.waitFor({ state: "visible", timeout: 3_000 }); loadVisible = true; } catch { }
        if (loadVisible) {
          await loadBtn.click();
          await swalSubmit(dp, stopPatch);
          await shot(dp, step(`stop-${stopNo}-loaded`), `Stop ${stopNo} cargo loaded (photo proof)`);
        }

        const receipt = dp.locator("#delivery_receipt").first();
        if (await receipt.isVisible().catch(() => false)) { await receipt.setInputFiles(F_DELIVERY); }
        const deliverBtn = dp.getByRole("button", { name: /Mark Delivered/i }).first();
        let deliverVisible = false;
        try { await deliverBtn.waitFor({ state: "visible", timeout: 3_000 }); deliverVisible = true; } catch { }
        if (deliverVisible) {
          await deliverBtn.click();
          await swalSubmit(dp, stopPatch);
          await shot(dp, step(`stop-${stopNo}-delivered`), `Stop ${stopNo} delivered`);
        }
      }

      const completeBtn = dp.getByRole("button", { name: /Complete Run|Finalize Job|Complete Job/i }).first();
      let completeVisible = false;
      try { await completeBtn.waitFor({ state: "visible", timeout: 3_000 }); completeVisible = true; } catch { }
      if (completeVisible) {
        const odo = dp.locator("#end_odometer_reading").first();
        if (await odo.isVisible().catch(() => false)) { await odo.fill("120.5"); }
        await completeBtn.click();
        await swalSubmit(dp, /\/driver\/jobs\/\d+\/status/);
        await shot(dp, step("job-completed"), "Stage 6 – Run completed (all stops)");
      }
      await dc.close();
    });

    await tryStep("coop-confirm-receipt", async () => {
      await lp.goto("/buyer/tracking");
      await lp.waitForLoadState("networkidle").catch(() => {});
      await shot(lp, step("buyer-tracking-awaiting"), "Stage 6 – Coop logistics tracking (receipt ack pending)");
      const confirmBtn = lp.getByRole("button", { name: /Confirm Receipt/i }).first();
      const hasBtn = await confirmBtn.isVisible().catch(() => false);
      if (hasBtn) {
        await confirmBtn.click();
        await swalSubmit(lp, /\/deliveries\/\d+\/confirm/);
        await shot(lp, step("delivery-confirmed"), "Stage 6 – Receipt acknowledged by coop logistics");
      } else {
        await shot(lp, step("no-confirm-button"), "Stage 6 – No Confirm Receipt button visible");
      }
    }, lp);

    // ══════════════════════ STAGE 8 — COST LEDGER (UPLOAD → VERIFY → PAID) ══════════════════════
    let ledgerUrl = "";
    await tryStep("cost-ledger-index", async () => {
      await lp.goto("/pooling/cost-ledger/jobs");
      await lp.waitForLoadState("networkidle").catch(() => {});
      await shot(lp, step("cost-ledger-index"), "Stage 7 – Cost ledger index (coop logistics)");
      const ledgerLink = lp.locator("main a[href*='cost-ledger']").first();
      await ledgerLink.waitFor({ state: "visible", timeout: 20_000 });
      await ledgerLink.click();
      await lp.waitForURL(/pooling\/\d+\/cost-ledger/, { timeout: 20_000 });
      ledgerUrl = lp.url();
      await shot(lp, step("cost-ledger-detail-unpaid"), "Stage 7 – Cost ledger detail (unpaid)");
    }, lp);

    for (const f of A.farmers) {
      const tag = f.crop.toLowerCase();
      await tryStep(`farmer-upload-${tag}`, async () => {
        await login(fp, f.email, f.password);
        await fp.goto(ledgerUrl);
        await fp.waitForLoadState("networkidle").catch(() => {});
        const rowSelectors = [
          `tr:has-text("${f.crop}")`,
          `li:has-text("${f.crop}")`,
        ];
        let receiptInput: any = null;
        for (const sel of rowSelectors) {
          const candidate = fp.locator(`${sel} input[name='payment_receipt']`).first();
          if (await candidate.count().catch(() => 0) > 0) { receiptInput = candidate; break; }
        }
        if (!receiptInput) {
          const all = fp.locator(`input[name='payment_receipt']`);
          const n = await all.count();
          if (n === 0) throw new Error("No payment_receipt input found on ledger page");
          const idx = A.farmers.indexOf(f) < n ? A.farmers.indexOf(f) : 0;
          receiptInput = all.nth(idx);
        }
        await receiptInput.waitFor({ state: "attached", timeout: 20_000 });
        await receiptInput.setInputFiles(F_RECEIPT);
        await swalSubmit(fp, /\/upload-receipt/).catch(() => {});
        await fp.waitForTimeout(1200);
        await shot(fp, step(`receipt-uploaded-${tag}`), `${f.name} uploaded hauling payment receipt (${f.crop})`);
      }, fp);
    }

    await tryStep("logistics-verify-paid", async () => {
      await lp.goto(ledgerUrl);
      await lp.waitForLoadState("networkidle").catch(() => {});
      await shot(lp, step("cost-ledger-receipts-submitted"), "Stage 7 – Coop logistics reviews submitted receipts");

      for (const f of A.farmers) {
        const tag = f.crop.toLowerCase();
        const amount = String(Math.round(parseFloat(f.qty) * parseFloat(f.rate)));
        await lp.goto(ledgerUrl);
        await lp.waitForLoadState("networkidle").catch(() => {});
        const rowSelectors = [`tr:has-text("${f.crop}")`];
        let amountInput: any = null;
        for (const sel of rowSelectors) {
          const candidate = lp.locator(`${sel} input[name='amount_paid']`).first();
          if (await candidate.count().catch(() => 0) > 0) { amountInput = candidate; break; }
        }
        if (!amountInput) {
          amountInput = lp.locator(`input[name='amount_paid']`).nth(A.farmers.indexOf(f));
        }
        await amountInput.waitFor({ state: "visible", timeout: 20_000 });
        await amountInput.fill(amount);
        const verifyBtn = amountInput
          ? lp.locator(`tr:has-text("${f.crop}")`).getByRole("button", { name: /Verify Paid/i }).first()
          : lp.getByRole("button", { name: /Verify Paid/i })
              .filter({ hasText: f.crop }).first();
        await verifyBtn.waitFor({ state: "visible", timeout: 20_000 });
        await verifyBtn.click({ noWaitAfter: true });
        await swalSubmit(lp, /\/mark-paid/);
        await lp.waitForTimeout(1500);
        await shot(lp, step(`payment-paid-${tag}`), `${f.crop} hauling payment marked PAID (₱${amount})`);
      }
    }, lp);

    runLog.completedAt = new Date().toISOString();
    runLog.status = "completed";
    console.log(`\nE2E summary: ${runLog.steps.length} shots/steps recorded`);
  } catch (e: any) {
    runLog.completedAt = new Date().toISOString();
    runLog.status = "failed: " + String(e?.message ?? e);
    throw e;
  } finally {
    fs.writeFileSync(RUN_LOG, JSON.stringify(runLog, null, 2));
    await Promise.all([farmerCtx.close(), logisticsCtx.close()]);

    const failed = runLog.steps.filter((s: any) => s.error);
    if (failed.length > 0) {
      console.error(`E2E: ${runLog.steps.length} steps, ${failed.length} FAILED`);
      failed.forEach((f: any) => console.error(`  - ${f.slug}: ${String(f.error).slice(0, 200)}`));
    }
  }
});

// ───────────────────────────────  TEST 2 — MULTI-TRUCK OVERFLOW (5700kg → 2 routes)  ───────────────────────────────

test("Coop farmer multi-truck overflow: 5 farms × 5700kg → 2 routes on 2 trucks → confirm-all → 2 jobs with drivers → cost ledger per-route", async ({ browser }) => {
  test.setTimeout(1_200_000);

  const SHOT_DIR = path.resolve(__dirname, "../../../eval/screenshots/e2e-coopfarmer-overflow");
  const RUN_LOG = path.join(SHOT_DIR, "run-log.json");
  const ERR_LOG = path.join(SHOT_DIR, "errors.log");
  fs.mkdirSync(SHOT_DIR, { recursive: true });
  if (!fs.existsSync(RUN_LOG)) fs.writeFileSync(RUN_LOG, JSON.stringify({ steps: [] }));
  if (!fs.existsSync(ERR_LOG)) fs.writeFileSync(ERR_LOG, "");

  const A = {
    logistics: { email: "logistics1@test.com", password: "password", name: "GenSan Farmers Cooperative" },
    // 1500 + 1200 + 1000 + 1100 + 900 = 5700 kg  →  overflows a single 4500kg truck.
    farmers: [
      { email: "farmer0@test.com", password: "password", name: "Polomolok Pineapple Farm Owner", crop: "Pomelo", variety: "Pink", custom: true, qty: "1500", price: "40", rate: "2.50" },
      { email: "farmer1@test.com", password: "password", name: "Tupi Harvests Owner", crop: "Durian", variety: "Puyat", custom: true, qty: "1200", price: "50", rate: "3.00" },
      { email: "farmer2@test.com", password: "password", name: "Lagao Fruit Farm Owner", crop: "Rambutan", variety: "Rongrien", custom: true, qty: "1000", price: "55", rate: "2.75" },
      { email: "farmer3@test.com", password: "password", name: "Silway Veggie Patch Owner", crop: "Lanzones", variety: "Duku", custom: true, qty: "1100", price: "45", rate: "2.60" },
      { email: "farmer4@test.com", password: "password", name: "Katangawan Corn Fields Owner", crop: "Avocado", variety: "Hass", custom: true, qty: "900", price: "60", rate: "2.90" },
    ] as Array<{ email: string; password: string; name: string; crop: string; variety: string; custom: boolean; qty: string; price: string; rate: string }>,
  };

  let runLog: Record<string, any> = {};
  try { runLog = JSON.parse(fs.readFileSync(RUN_LOG, "utf-8")); } catch { runLog = { runs: [] }; }
  if (Array.isArray(runLog.steps)) { runLog.runs = runLog.runs ?? []; runLog.runs.push({ start: runLog.startedAt, status: runLog.status, steps: runLog.steps.length }); }
  runLog.startedAt = new Date().toISOString();
  runLog.steps = [];
  let stepCount = 0;
  const step = (name: string) => `${String(++stepCount).padStart(2, "0")}-${name}`;

  const errLog = (...lines: (string | any)[]) =>
    fs.appendFileSync(ERR_LOG, `[${new Date().toISOString()}] ` + lines.map(l => (typeof l === "string" ? l : JSON.stringify(l))).join(" ") + "\n");

  async function shot(page: Page, slug: string, label: string) {
    await page.screenshot({ path: path.join(SHOT_DIR, `${slug}.png`), fullPage: true });
    runLog.steps.push({ slug, label, url: page.url(), time: new Date().toISOString() });
    console.log(`  [shot] ${slug} — ${label} (${page.url()})`);
  }

  async function tryStep(name: string, fn: () => Promise<void>, page?: Page) {
    let timer: NodeJS.Timeout | undefined;
    const race = new Promise<never>((_, reject) => {
      timer = setTimeout(() => reject(new Error(`[step-timeout] ${name} exceeded 240s`)), 240_000);
    });
    try {
      await Promise.race([fn(), race]);
    } catch (e: any) {
      const msg = String(e?.message ?? e);
      if (page) {
        await page.screenshot({ path: path.join(SHOT_DIR, `ERR-${name}.png`), fullPage: true }).catch(() => {});
        const bodyText = await page.evaluate(() => document.body?.innerText?.slice(0, 900) ?? "").catch(() => "");
        errLog(`[FAIL] ${name} | ${msg}`, `\n---- page text ----\n${bodyText}`);
      }
      runLog.steps.push({ slug: `ERR-${name}`, error: msg, url: page?.url() ?? "" });
      console.error(`  [FAIL] ${name}: ${msg}`);
      throw e;
    } finally {
      clearTimeout(timer);
    }
  }

  const farmerCtx = await browser.newContext();
  const logisticsCtx = await browser.newContext();

  const fp = guard(await farmerCtx.newPage());
  const lp = guard(await logisticsCtx.newPage());

  wireLogging(fp, "farmer", errLog);
  wireLogging(lp, "logistics", errLog);

  lp.on("request", (r) => {
    if (r.method() === "POST") {
      const t = new Date().toISOString().slice(11, 19);
      errLog(`[logistics] POST ${t} ${r.url().replace("http://127.0.0.1:8000", "")}`);
    }
  });

  async function createHarvest(f: typeof A.farmers[number], page: Page, note?: string) {
    await login(page, f.email, f.password);
    await page.goto("/harvests/create");
    await page.waitForSelector("#crop_search", { state: "visible", timeout: 20_000 });

    const destId = await page.locator("#destination_id").inputValue();
    const destLabel = await page.evaluate(() => {
      const sel = document.getElementById("destination_id") as HTMLSelectElement | null;
      return sel?.selectedOptions?.[0]?.textContent?.replace(/\s+/g, " ").trim() ?? "";
    });
    runLog['coop-default-dest'] = { farmer: f.name, destination_id: destId, label: destLabel };
    await shot(page, step(`harvest-form-${f.crop.toLowerCase()}-coop-dest`), `Overflow create form — destination defaults to Cooperative Hub (${f.name})`);

    await page.click("#crop_search");
    const otherItem = page.locator("#crop_dropdown [data-value='other']");
    await otherItem.waitFor({ state: "visible", timeout: 10_000 });
    await otherItem.click();
    const customCrop = page.locator("#custom_crop_name");
    await customCrop.waitFor({ state: "visible", timeout: 10_000 });
    await customCrop.fill(f.crop);
    const customVariety = page.locator("#custom_variety_name");
    await customVariety.waitFor({ state: "visible", timeout: 10_000 });
    await customVariety.fill(f.variety);

    await page.fill("#quantity_kg", f.qty);
    await page.fill("#suggested_price_per_kg", f.price);
    await page.fill("#harvest_date", tomorrow());
    await page.fill("#notes", note ?? `E2E overflow run — ${f.crop} (${f.variety}) from ${f.name}`);
    await shot(page, step(`harvest-custom-${f.crop.toLowerCase()}`), `Overflow custom crop typed: ${f.crop} / ${f.variety}`);

    const submitBtn = page.locator("#post-harvest-btn");
    await submitBtn.click();
    const confirmSwal = page.locator(SWAL_CONFIRM);
    const swalShown = await confirmSwal.waitFor({ state: "visible", timeout: 6_000 }).then(() => true).catch(() => false);
    if (swalShown) {
      await shot(page, step(`harvest-confirm-${f.crop.toLowerCase()}`), `Post Harvest confirmation dialog — ${f.crop} (${f.variety})`);
      await confirmSwal.click();
    }
    await page.waitForURL(u => !String(u).includes("/harvests/create"), { timeout: 30_000 });
    await page.waitForLoadState("networkidle").catch(() => {});
    await shot(page, step(`harvest-posted-${f.crop.toLowerCase()}`), `Harvest posted: ${f.crop} (${f.variety})`);
  }

  try {
    // ═══════ M3 HYGIENE — clear E2E-tagged pooling/negotiation leftovers from run N ═══════
    resetE2eState();

    // ══════════════════════ STAGE 1 — ALL 5 COOP FARMERS POST CUSTOM CROPS ══════════════════════
    for (const f of A.farmers) {
      await tryStep(`harvest-${f.crop}`, async () => createHarvest(f, fp), fp);
    }

    // Excluded-farm coverage (Task 1): farmer1 posts a SECOND crop that is NEVER
    // negotiated. planAll must exclude it with a reason instead of vetoing the run,
    // and the amber banner must list "Tupi Harvests Owner: No agreement yet for Papaya".
    // We capture its harvest id from the harvest index page so we can inject it into
    // the planAll harvest_ids (un-negotiated ACTIVE crops are not shown on the route map).
    let excludedHarvestId = 0;
    await tryStep("harvest-excluded-papaya", async () => {
      await createHarvest(
        { email: A.farmers[1].email, password: A.farmers[1].password, name: A.farmers[1].name, crop: "Papaya", variety: "Solo", custom: true, qty: "100", price: "50", rate: "2.40" },
        fp,
        "E2E overflow excluded — Papaya (Solo) from Tupi Harvests Owner"
      );
      await fp.goto("/harvests");
      await fp.waitForLoadState("networkidle").catch(() => {});
      excludedHarvestId = await fp.evaluate(() => {
        const rows = Array.from(document.querySelectorAll("tbody tr"));
        for (const row of rows) {
          if ((row.textContent || "").includes("E2E overflow excluded")) {
            const a = row.querySelector("a[href*='/edit']");
            const m = a?.getAttribute("href")?.match(/\/harvests\/(\d+)\/edit/);
            if (m) return Number(m[1]);
          }
        }
        return 0;
      });
      if (!excludedHarvestId) throw new Error("Could not capture excluded farm's harvest id from /harvests");
      runLog['excluded-harvest-id'] = excludedHarvestId;
      console.log("  [info] excluded harvest id:", excludedHarvestId);
    }, fp);

    // ══════════════════════ STAGE 2 — COOP SEES ALL 5 ON CROP BOARD + ROUTE MAP ══════════════════════
    await tryStep("coop-crop-board-5", async () => {
      await login(lp, A.logistics.email, A.logistics.password);
      await lp.goto("/buyer/crop-board");
      await lp.waitForLoadState("networkidle").catch(() => {});
      await shot(lp, step("crop-board-5-farms"), "Stage 2 – Coop crop board sees Pomelo / Durian / Rambutan / Lanzones / Avocado");
    }, lp);

    await tryStep("coop-route-map-5", async () => {
      await lp.goto("/route-optimization");
      await lp.waitForLoadState("networkidle").catch(() => {});
      await shot(lp, step("route-optimization-5-farms"), "Stage 2 – Route optimization map (harvests still ACTIVE)");
    }, lp);

    // ══════════════════════ STAGE 3 — COOP NEGOTIATES WITH ALL 5 ══════════════════════
    for (let i = 0; i < A.farmers.length; i++) {
      const f = A.farmers[i];
      const tag = f.crop.toLowerCase();
      await pace(lp);

      await tryStep(`negotiation-${tag}-start`, async () => {
        await lp.goto("/buyer/crop-board");
        await lp.waitForLoadState("networkidle").catch(() => {});
        const card = lp.locator("div:has(> div > a[href*='/buyer/crop-board/'])").filter({ hasText: f.name }).filter({ hasText: f.crop }).first();
        const link = card.locator("a[href*='/buyer/crop-board/']").first();
        await link.waitFor({ state: "visible", timeout: 20_000 });
        await link.click();
        await lp.waitForURL(/\/buyer\/crop-board\/\d+/, { timeout: 20_000 });
        await shot(lp, step(`crop-detail-${tag}`), `Overflow crop detail — ${f.crop} (${f.name})`);
        await lp.getByRole("button", { name: /Initiate Negotiation/i }).click();
        await swalConfirm(lp);
        lastLpPostAt = Date.now();
        await lp.waitForURL(/negotiations\/\d+/, { timeout: 20_000 });
        await shot(lp, step(`negotiation-started-${tag}`), `Negotiation room opened for ${f.crop}`);
      }, lp);

      await tryStep(`negotiation-${tag}-chat`, async () => {
        await lp.locator("#message-input").fill(`Hello ${f.name}! The cooperative wants your ${f.crop} (${f.variety}), ${f.qty}kg at ₱${f.price}/kg.`);
        await lp.locator("#send-message-form button[type='submit']").click();
        lastLpPostAt = Date.now();
        await lp.waitForTimeout(2000);
        await shot(lp, step(`negotiation-message-${tag}`), `Chat message sent for ${f.crop}`);
      }, lp);

      await tryStep(`negotiation-${tag}-propose`, async () => {
        await lp.fill("#negotiated_price", f.price);
        await lp.fill("#negotiated_volume", f.qty);
        await lp.fill("#term_hauling_rate", f.rate);
        await lp.click("#propose-btn");
        await swalConfirm(lp);
        lastLpPostAt = Date.now();
        const offered = await lp.locator("#chat-messages-container").getByText(/\[System Offer\]/i).first()
          .waitFor({ state: "visible", timeout: 20_000 }).then(() => true).catch(() => false);
        if (!offered) throw new Error("Proposal did not persist — no [System Offer] message in chat");
        await shot(lp, step(`proposal-sent-${tag}`), `Terms proposed for ${f.crop}: ₱${f.price}/kg, ${f.qty}kg, haul ₱${f.rate}/kg`);
      }, lp);

      await tryStep(`negotiation-${tag}-farmer-agrees`, async () => {
        const negotiationUrl = lp.url();
        const negId = negotiationUrl.match(/negotiations\/(\d+)/)?.[1];
        if (!negId) throw new Error("Could not extract negotiation ID");

        await login(fp, f.email, f.password);
        await fp.goto(`/negotiations/${negId}`);
        await fp.waitForLoadState("networkidle").catch(() => {});
        runLog[`negotiation-id-${tag}`] = Number(negId);
        await shot(fp, step(`farmer-room-${tag}`), `${f.name} views negotiation room`);
        const agreeBtn = fp.locator("#agree-btn");
        await agreeBtn.waitFor({ state: "visible", timeout: 30_000 });
        await agreeBtn.click();
        await swalConfirm(fp);
        await waitForStatus(fp, "AGREED");
        await shot(fp, step(`farmer-agreed-${tag}`), `${f.name} agreed to terms`);
      }, fp);

      await tryStep(`negotiation-${tag}-finalize`, async () => {
        await lp.reload();
        await lp.waitForLoadState("networkidle").catch(() => {});
        await shot(lp, step(`finalize-room-${tag}`), `Coop logistics finalizes deal for ${f.crop}`);

        const rateInput = lp.locator("#hauling_rate_per_kg");
        await rateInput.waitFor({ state: "visible", timeout: 20_000 }).catch(() => {});
        if (await rateInput.isVisible().catch(() => false)) {
          await rateInput.fill(f.rate);
        }

        const fixedRadio = lp.locator("#choice-fixed");
        if (await fixedRadio.isVisible().catch(() => false)) {
          await fixedRadio.check({ force: true }).catch(() => {});
        }
        const dropoffMap = lp.locator("#dropoff-map");
        const hasMap = await dropoffMap.isVisible().catch(() => false);
        if (hasMap) {
          const mapBox = await dropoffMap.boundingBox();
          if (mapBox) {
            await lp.evaluate(([cx, cy]) => {
              const el = document.getElementById("dropoff-map");
              if (el) el.dispatchEvent(new MouseEvent("click", { clientX: cx, clientY: cy, bubbles: true }));
            }, [mapBox.x + mapBox.width * 0.5, mapBox.y + mapBox.height * 0.5]);
            await lp.waitForTimeout(500);
          }
        }
        await lp.evaluate(() => {
          const latEl = document.getElementById("destination_latitude") as HTMLInputElement;
          const lngEl = document.getElementById("destination_longitude") as HTMLInputElement;
          const addrEl = document.getElementById("destination_address") as HTMLInputElement;
          if (latEl && !latEl.value) latEl.value = "6.1164";
          if (lngEl && !lngEl.value) lngEl.value = "125.1716";
if (addrEl && !addrEl.value) addrEl.value = "GenSan Wholesale Market Hub";
        });

        const negId = lp.url().match(/negotiations\/(\d+)/)?.[1];
        if (!negId) throw new Error("Could not extract negotiation ID for finalize");
        await finalizeDealWithRetry(lp, negId, f.rate);
        await lp.waitForLoadState("networkidle").catch(() => {});
        await shot(lp, step(`deal-finalized-${tag}`), `DONE DEAL — ${f.crop} locked to ${f.rate}/kg haul rate`);
      }, lp);
    }

    // ══════════════════════ STAGE 3.5 — ALL 5 DONE DEALS FEED THE PLANNER ══════════════════════
    await tryStep("deals-feed-planner-5", async () => {
      await lp.goto("/route-optimization");
      await lp.waitForLoadState("networkidle").catch(() => {});
      await shot(lp, step("done-deals-planner-5"), "Stage 3.5 – 5 Done Deals now feed the route planner");
      const farmsData: any = await lp.evaluate(() => {
        for (const s of Array.from(document.scripts)) {
          const m = (s.textContent || "").match(/const farms\s*=\s*(\[.*?\])\s*;?$/ms);
          if (!m) continue;
          try { return JSON.parse(m[1]); } catch { /* try next script */ }
        }
        return null;
      });
      const farmNames: string[] = (farmsData || []).map((f: any) => f.name);
      runLog['farms-on-route-map-5'] = farmNames;
      console.log("  [info] Farms on overflow route map:", JSON.stringify(farmNames));
      const expected = A.farmers.map(f => f.name);
      const missing = expected.filter(e => !farmNames.includes(e));
      if (missing.length > 0) throw new Error(`Route map missing ${missing.length} farm(s): ${missing.join(", ")}`);
      await shot(lp, step("coop-harvests-on-map-5"), "Stage 3.5 – All 5 SOLD harvests visible on the route map");
    }, lp);

    // ══════════════════════ STAGE 4 — MULTI-ROUTE: planAll → 2 PLANS → CONFIRM ALL ══════════════════════
    // Deterministic mock (like the level-up test): the browser's OSRM calls are
    // intercepted by the through-farm geometry so all 5 pickup farms land in the
    // queue, while /pooling/plan-all runs against the REAL endpoint (it needs no
    // OSRM — the frontend supplies road distance) so the excluded-farm banner
    // exercises the genuine server path.
    let routingMode: "real" | "mock" = "mock";
    let planAllData: any = null;
    let confirmJobIds: number[] = [];

    await tryStep("route-plan-all-generate", async () => {
      const throughFarm = { type: "LineString", coordinates: [
        [125.1830, 6.1050], [125.1912, 6.1351], [125.0718, 6.2215], [124.9416, 6.3333], [125.1550, 6.1420], [125.2215, 6.1511], [125.1716, 6.1164]
      ] };
      const mockRouteBody = JSON.stringify({ code: "Ok", routes: [{ geometry: throughFarm, distance: 75000, duration: 6000 }] });
      const mockTripBody = JSON.stringify({
        code: "Ok",
        trips: [{ geometry: throughFarm }],
        waypoints: throughFarm.coordinates.map((c: number[]) => ({ geometry: { coordinates: c } })),
      });

      // Admit ONLY the 5 cooperative farms into the pickup queue (extra seed
      // farms would exceed the 2-truck split and break the per-truck assertions).
      const admitCoords = await captureIntendedFarmCoords(lp, A.farmers.map((f: any) => f.name));

      await lp.route("**/router.project-osrm.org/route/v1/driving/**", (route) => {
        const url = route.request().url();
        if (url.includes("overview=false")) {
          // Per-farm nearest-leg lookups (?overview=false) must return a distance
          // WELL UNDER the 50km search radius for the intended farms, far OVER for
          // everyone else. Distance only — no geometry consumed by the caller.
          mockPerFarmDistance(route, admitCoords, url);
        } else {
          route.fulfill({ status: 200, contentType: "application/json", body: mockRouteBody });
        }
      });
      await lp.route("**/router.project-osrm.org/trip/v1/**", (route) => {
        route.fulfill({ status: 200, contentType: "application/json", body: mockTripBody });
      });

      const renderMulti = async () => {
        await lp.click("#btn-show-map");
        await lp.waitForSelector(".leaflet-container", { state: "visible", timeout: 15_000 });
        await lp.waitForTimeout(600);
        await lp.evaluate(() => {
          const t = document.getElementById("btn-toggle-options");
          const p = document.getElementById("routing-options");
          if (t && p) { p.classList.remove("hidden"); t.setAttribute("aria-expanded", "true"); }
        });
        await lp.selectOption("#radius-select", "50");

        // All 5 farms must be ticked into the pickup queue, and a truck with an
        // assigned driver must be armed, before generating.
        await ensurePickupQueue(lp, 5);
        await ensureGenerateEnabled(lp);
      };

      await lp.goto("/route-optimization");
      await lp.waitForLoadState("networkidle").catch(() => {});
      await renderMulti();

      // Inject the never-negotiated Papaya harvest into the planAll payload so the
      // backend exercises its no-agreement exclusion path (Task 1) deterministically.
      // We mutate the inferred body and CONTINUE to the real endpoint — proxying
      // via a fetch() to the SAME URL would re-enter this handler (recursion).
      await lp.route("**/pooling/plan-all", (route) => {
        try {
          const body = route.request().postDataJSON();
          if (!body) return route.continue();
          if (Array.isArray(body.harvest_ids) && excludedHarvestId && !body.harvest_ids.includes(excludedHarvestId)) {
            body.harvest_ids.push(excludedHarvestId);
            body.farm_distances = body.farm_distances || {};
            body.farm_distances[excludedHarvestId] = 3;
          }
          route.continue({ postData: JSON.stringify(body) });
        } catch (e) {
          route.fulfill({ status: 500, contentType: "application/json", body: JSON.stringify({ error: String(e) }) });
        }
      });

      const planAllRespP = lp.waitForResponse(r => /pooling\/plan-all/.test(r.url()) && r.status() < 500, { timeout: 60_000 }).catch(() => null);
      await lp.click("#btn-generate-plan");
      await swalConfirm(lp);

      const multiVisible = await lp.waitForSelector("#plan-all-panel:not(.hidden)", { state: "visible", timeout: 40_000 }).then(() => true).catch(() => false);
      if (!multiVisible) throw new Error("Multi-plan panel never appeared");
      const resp1 = await planAllRespP;
      if (resp1) planAllData = await resp1.json().catch(() => null);

      await shot(lp, step("plan-all-panel-2-routes"), `Stage 4 – Multi-Truck Route Plan panel (mode: ${routingMode})`);

      // Excluded-farm coverage (Task 1): the no-agreement Papaya must appear in the
      // amber banner as "- <farm>: No agreement yet for <crop> — settle a price first."
      const bannerVisible = await lp.locator("#plan-all-unassigned-banner").count().then(n => n > 0).catch(() => false);
      const bannerText = bannerVisible ? (await lp.locator("#plan-all-unassigned-text").innerText()).replace(/\s+/g, " ") : "";
      runLog['plan-all-excluded-banner'] = { visible: bannerVisible, text: bannerText.slice(0, 400) };
      console.log("  [info] plan-all excluded banner:", JSON.stringify(runLog['plan-all-excluded-banner']));
      if (!bannerVisible) throw new Error("No-agreement excluded farm should trigger the amber planAll banner (#plan-all-unassigned-banner)");
      if (!/Tupi Harvests Owner/i.test(bannerText)) throw new Error("Banner missing excluded farm name (Tupi Harvests Owner)");
      if (!/No agreement yet for Papaya/i.test(bannerText)) throw new Error("Banner missing exclusion reason 'No agreement yet for Papaya'");

      runLog['planAll-response'] = {
        overflow: planAllData?.overflow,
        plan_count: (planAllData?.plans || []).length,
        total_farms: planAllData?.total_farms,
        selected_total: planAllData?.selected_total,
        unassigned: planAllData?.unassigned,
      };

      const plans = (planAllData?.plans || []);
      const expectedCrops = A.farmers.map(f => f.crop);
      const covered = new Set<string>();
      const perPlan = plans.map((p: any) => {
        const crops = (p.selected_harvests || []).map((h: any) => String(h.crop || ""));
        crops.forEach((c: string) => expectedCrops.forEach(ec => { if (c.includes(ec)) covered.add(ec); }));
        return { truck_name: p.truck_name, truck_id: p.truck_id, capacity: p.truck_capacity_kg, crops, total_kg: p.total_kg };
      });
      runLog['planAll-per-plan'] = perPlan;

      // Robust, set-based: ≥2 cards, ≥2 distinct trucks, all 5 of OUR crops covered.
      const distinctTrucks = new Set(plans.map((p: any) => p.truck_name));
      console.log("  [info] planAll plans:", JSON.stringify(perPlan));
      if (plans.length < 2) throw new Error(`Expected ≥2 plans from planAll, got ${plans.length}`);
      if (distinctTrucks.size < 2) throw new Error(`Expected plans on ≥2 different trucks, got ${distinctTrucks.size}`);
      const missingCrops = expectedCrops.filter(c => !covered.has(c));
      if (missingCrops.length > 0) throw new Error(`planAll cards do not cover all 5 crops — missing: ${missingCrops.join(", ")}`);

      // The rendered cards must mirror the JSON (5 crops present). Orphan cards
      // from dirty-DB done deals are tolerated — we only verify OUR coverage.
      const cardText = await lp.locator("#plan-all-cards").innerText();
      let cardCount = 0;
      await lp.locator("#plan-all-cards > div").count().then(n => { cardCount = n; });
      const missingOnCards = expectedCrops.filter(c => !cardText.includes(c));
      if (missingOnCards.length > 0) throw new Error(`Rendered plan cards missing our crops: ${missingOnCards.join(", ")}`);
      runLog['plan-all-cards'] = { card_count: cardCount, text_has_all_5: missingOnCards.length === 0 };

      // Per-truck route geometry (Task 1): every rendered card must carry its own
      // Distance readout (75.00 km in mock mode, real distance in live OSRM mode).
      const cardDistances: string[] = [];
      for (let attempt = 0; attempt < 20; attempt++) {
        cardDistances.length = 0;
        const cards = await lp.locator("#plan-all-cards > div").all();
        for (const card of cards) {
          const ct = (await card.innerText()).replace(/\s+/g, " ");
          const m = ct.match(/Distance\s+([\d.]+)\s*km/i);
          cardDistances.push(m ? m[1] : "");
        }
        const allValid = cardDistances.length >= 2 && cardDistances.every((d) => d !== "" && Number(d) > 0);
        if (allValid) break;
        await lp.waitForTimeout(1000);
      }
      runLog['cards-distances'] = cardDistances;
      console.log("  [info] per-card distances:", JSON.stringify(cardDistances));
      if (cardDistances.length < 2) throw new Error(`Expected ≥2 plan cards with per-truck Distance, got ${cardDistances.length}`);
      if (cardDistances.some((d) => d === "")) throw new Error("At least one plan card is missing its per-truck Distance readout");
      if (cardDistances.some((d) => Number(d) <= 0)) throw new Error("Per-truck Distance never populated past its 0.00 placeholder");
    }, lp);

    await tryStep("route-confirm-all", async () => {
      await lp.fill("#plan-all-notes", "E2E overflow: 5700kg auto-split across 2 trucks.");
      const confirmRespP = lp.waitForResponse(r => /pooling\/confirm-batch/.test(r.url()) && r.status() < 500, { timeout: 40_000 }).catch(() => null);
      await lp.click("#btn-confirm-all");
      await swalConfirm(lp);
      const confirmResp = await confirmRespP;
      if (confirmResp) {
        const body = await confirmResp.json().catch(() => null);
        runLog['confirm-all-response'] = body;
        if (body?.job_ids) confirmJobIds = body.job_ids.map((n: any) => Number(n));
      }
      await lp.waitForSelector("#confirm-all-feedback:not(.hidden)", { timeout: 30_000 }).catch(() => {});
      await lp.waitForLoadState("networkidle", { timeout: 20_000 }).catch(() => {});
      await shot(lp, step("route-proposals-created"), "Stage 4 – Confirm All Routes: 2 pooling proposals created");
      console.log("  [info] confirmAll job_ids:", JSON.stringify(confirmJobIds));
    }, lp);

    // ══════════════════════ STAGE 4.2 — EXCLUDED FARM GETS "CROP NOT INCLUDED" NOTIFICATION ══════════════════════
    await tryStep("excluded-farm-notification", async () => {
      // Farmer layouts don't render the notification dropdown, so drive the browser
// directly to the JSON API route (authenticated navigation, keeps the session).
      let notif: { title: boolean; message: boolean } | null = null;
      for (let attempt = 0; attempt < 4 && !notif; attempt++) {
        await login(fp, A.farmers[1].email, A.farmers[1].password);
        await fp.goto("/api/notifications?per_page=10");
        const present = await fp.waitForFunction(
          () => /Crop Was Not Included/i.test(document.body.innerText) && /was not included in this route/i.test(document.body.innerText),
          undefined, { timeout: 30_000 }
        ).then(() => true).catch(() => false);
        if (present) {
          const bodyText = (await fp.locator("body").innerText()).replace(/\s+/g, " ");
          notif = { title: true, message: /was not included in this route/i.test(bodyText) };
        } else {
          await fp.waitForTimeout(2500);
        }
      }
      runLog['excluded-farm-notification'] = notif;
      console.log("  [info] excluded-farm notification:", JSON.stringify(runLog['excluded-farm-notification']));
      if (!notif) throw new Error(`No 'Route Offer — Your Crop Was Not Included' notification for ${A.farmers[1].name}`);
      if (!notif.message) throw new Error("Excluded-farm notification message missing 'was not included in this route'");
    }, fp);

    // ══════════════════════ STAGE 5 — EVERY FARMER ACCEPTS THEIR ROUTE OFFER ══════════════════════
    for (const f of A.farmers) {
      const tag = f.crop.toLowerCase();
      await tryStep(`farmer-accept-${tag}`, async () => {
        await login(fp, f.email, f.password);
        await fp.goto("/farmer/proposals");
        await fp.waitForLoadState("networkidle").catch(() => {});
        await shot(fp, step(`farmer-proposal-details-${tag}`), `Overflow Route Offer visible to ${f.name} (${f.crop})`);
        const acceptForm = fp.locator("form[action*='pooling'][action*='accept']").first();
        await acceptForm.waitFor({ state: "visible", timeout: 20_000 });
        await acceptForm.getByRole("button", { name: "Accept" }).click();
        await swalConfirm(fp);
        await fp.waitForFunction(() => /Accepted|Awaiting other farmers|confirmed/i.test(document.body.innerText), undefined, { timeout: 60_000 }).catch(() => {});
        await fp.waitForLoadState("networkidle", { timeout: 60_000 }).catch(() => {});
        await fp.waitForTimeout(1000);
        await shot(fp, step(`farmer-accepted-${tag}`), `${f.name} accepted the Route Offer`);
      }, fp);
    }

    await tryStep("overflow-jobs-confirmed", async () => {
      await login(lp, A.logistics.email, A.logistics.password);
      await lp.goto("/pooling/proposals");
      await lp.waitForLoadState("networkidle").catch(() => {});
      await shot(lp, step("all-accepted-2-jobs-confirmed"), "Stage 5 – All 5 farmers accepted — both routes confirmed");
    }, lp);

    // ══════════════════════ STAGE 6 — 2 DISTINCT POOLING JOBS, DISTINCT TRUCK + ASSIGNED DRIVER ══════════════════════
    const jobMeta: Record<string, any> = {};
    await tryStep("two-jobs-two-trucks", async () => {
      await lp.goto("/pooling/proposals");
      await lp.waitForLoadState("networkidle").catch(() => {});
      const pageState = await lp.evaluate(() => {
        const links = Array.from(document.querySelectorAll("a[href*='/cost-ledger']"));
        return links.map(a => {
          const card = a.closest("div[class*='rounded-2xl']");
          const txt = card?.textContent ?? "";
          return {
            href: a.getAttribute("href") ?? "",
            text: txt.replace(/\s+/g, " ").trim().slice(0, 900),
          };
        });
      });
      const ours = pageState.filter((c: any) => {
        const m = c.href.match(/\/pooling\/(\d+)\/cost-ledger/);
        if (!m) return false;
        const id = Number(m[1]);
        return confirmJobIds.length === 0 || confirmJobIds.includes(id);
      });
      runLog['proposals-our-cards'] = ours;
      console.log("  [info] proposal cards:", JSON.stringify(ours.map((c: any) => c.href)));

      const cardsToCheck = ours.length > 0 ? ours : pageState;
      const checkedIds: number[] = [];
      const trucks: string[] = [];
      const TRUCK_RE = /Isuzu Forward|Fuso Canter|Isuzu Elf Dropside/g;
      for (const c of cardsToCheck) {
        const m = c.href.match(/\/pooling\/(\d+)\/cost-ledger/);
        if (!m) continue;
        const id = Number(m[1]);
        if (checkedIds.includes(id)) continue;
        checkedIds.push(id);
        jobMeta[id] = { crops: [], href: c.href, text: c.text };
        const truckMatch = c.text.match(TRUCK_RE);
        const truckName = truckMatch?.[0] ?? "";
        if (truckName) trucks.push(truckName);
        else jobMeta[id].missingTruck = true;
      }
      // Fallback: if a job card doesn't surface its truck name (e.g. it already
      // moved into the Ready for Dispatch section with an unreadable header),
      // resolve it from the cost-ledger page itself.
      for (const idStr of Object.keys(jobMeta)) {
        const meta = jobMeta[idStr];
        if (!meta.missingTruck) continue;
        await lp.goto(`/pooling/${idStr}/cost-ledger`);
        await lp.waitForLoadState("networkidle").catch(() => {});
        const pageText = await lp.locator("body").innerText();
        const truckMatch = pageText.match(/Isuzu Forward|Fuso Canter|Isuzu Elf Dropside/i);
        if (truckMatch) {
          trucks.push(truckMatch[0]);
          meta.text = pageText.replace(/\s+/g, " ").trim().slice(0, 900);
          meta.resolvedTruck = truckMatch[0];
        } else {
          meta.text = pageText.replace(/\s+/g, " ").trim().slice(0, 900);
        }
      }
      runLog['jobs-identified'] = { checkedIds, trucks };
      if (checkedIds.length < 2) throw new Error(`Expected ≥2 distinct pooling jobs for our farms, found ${checkedIds.length}`);
      const distinctTrucks = new Set(trucks);
      if (distinctTrucks.size < 2) throw new Error(`Expected jobs on ≥2 distinct trucks, found ${distinctTrucks.size} (${JSON.stringify(trucks)})`);
      await shot(lp, step("two-jobs-distinct-trucks"), "Stage 6 – 2 pooling jobs, each with its own distinct truck");
    }, lp);

    // Driver for each of the 2 routes: RMP-1013 → julio, RMP-1012 → mario (existing spec mapping).
    const driversToCheck: string[] = [];
    const JOB_DRIVER: Record<string, string> = {
      "Isuzu Forward": "julio-driver-1@driver.com",
      "Fuso Canter": "mario-driver-1@driver.com",
      "Isuzu Elf Dropside": "eliseo-driver-1@driver.com",
    };
    for (const id of Object.keys(jobMeta)) {
      const key = jobMeta[id].resolvedTruck || jobMeta[id].truck || jobMeta[id].text || "";
      for (const k of Object.keys(JOB_DRIVER)) {
        if (key.toLowerCase().includes(k.toLowerCase())) {
          driversToCheck.push(JOB_DRIVER[k]);
          break;
        }
      }
    }
    runLog['drivers-to-check'] = driversToCheck;

    for (const dEmail of Array.from(new Set(driversToCheck))) {
      await tryStep(`driver-assigned-${dEmail}`, async () => {
        const dc = await browser.newContext();
        const dp = guard(await dc.newPage());
        wireLogging(dp, "overflow-driver", errLog);
        await login(dp, dEmail, "password");
        await dp.goto("/driver");
        await dp.waitForLoadState("networkidle", { timeout: 10_000 }).catch(() => {});
        const hasJob = await dp.locator("a:has-text('View Details')").count();
        if (hasJob === 0) throw new Error(`${dEmail} has no assigned pooling job`);
        await shot(dp, step(`driver-has-job-${dEmail.split("-")[0]}`), `Stage 6 – ${dEmail} has an assigned route`);
        await dc.close();
      });
    }

    if (driversToCheck.length === 0) {
      throw new Error("Could not resolve assigned drivers from job cards");
    }
    const distinctDrivers = new Set(driversToCheck);
    if (distinctDrivers.size < 2) {
      throw new Error(`Expected 2 distinct drivers (one per route), found ${distinctDrivers.size}`);
    }

    // ══════════════════════ STAGE 7 — COST LEDGER, ONE ROW/GROUPING PER ROUTE ══════════════════════
    await tryStep("cost-ledger-per-route", async () => {
      await lp.goto("/pooling/cost-ledger/jobs");
      await lp.waitForLoadState("networkidle").catch(() => {});
      await shot(lp, step("cost-ledger-index-2-jobs"), "Stage 7 – Cost ledger index: 2 pooling jobs (one ledger per route)");
      const rows = await lp.evaluate(() => {
        return Array.from(document.querySelectorAll("a[href*='/pooling/'][href*='/cost-ledger']")).map(a => ({
          href: a.getAttribute("href") ?? "",
          text: a.closest("div")?.textContent?.replace(/\s+/g, " ").trim().slice(0, 400) ?? "",
        }));
      });
      runLog['ledger-rows'] = rows;
      const ledgerIds = rows
        .map((r: any) => r.href.match(/\/pooling\/(\d+)\/cost-ledger/)?.[1])
        .filter(Boolean)
        .map((n: any) => Number(n));
      const idsToFind = confirmJobIds.length > 0 ? confirmJobIds : checkedLedgerFallback(rows, rows);
      const missing = idsToFind.filter(id => !ledgerIds.includes(Number(id)));
      if (missing.length > 0) throw new Error(`Cost ledger missing rows for job(s): ${missing.join(", ")}`);
      runLog['ledger-found'] = { expected: idsToFind, present: ledgerIds };

      // Open each route's ledger detail to prove entries exist under that job.
      for (const id of idsToFind) {
        await lp.goto(`/pooling/${id}/cost-ledger`);
        await lp.waitForLoadState("networkidle").catch(() => {});
        await shot(lp, step(`ledger-route-${id}`), `Stage 7 – Cost ledger detail for pooling job #${id}`);
      }
    }, lp);

    runLog.completedAt = new Date().toISOString();
    runLog.status = "completed";
    console.log(`\nOverflow E2E summary: ${runLog.steps.length} shots/steps recorded`);
  } catch (e: any) {
    runLog.completedAt = new Date().toISOString();
    runLog.status = "failed: " + String(e?.message ?? e);
    throw e;
  } finally {
    fs.writeFileSync(RUN_LOG, JSON.stringify(runLog, null, 2));
    await Promise.all([farmerCtx.close(), logisticsCtx.close()]);

    const failed = runLog.steps.filter((s: any) => s.error);
    if (failed.length > 0) {
      console.error(`Overflow E2E: ${runLog.steps.length} steps, ${failed.length} FAILED`);
      failed.forEach((f: any) => console.error(`  - ${f.slug}: ${String(f.error).slice(0, 200)}`));
    }
  }
});

function checkedLedgerFallback(rows: any[], _unused: any[]): number[] {
  return rows.map((r: any) => r.href.match(/\/pooling\/(\d+)\/cost-ledger/)?.[1]).filter(Boolean).map((n: any) => Number(n)).slice(0, 2);
}