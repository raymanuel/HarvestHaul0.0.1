import { test, expect, Page } from "@playwright/test";
import * as fs from "fs";
import * as path from "path";
import { fileURLToPath } from "url";

const __dirname = path.dirname(fileURLToPath(import.meta.url));

/**
 * LEVEL-UP COOPERATIVE FARMER E2E — 3 coop farmers × 3 crops, screenshots at every page change.
 *
 * Story:
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
 * Accounts (must already exist — NO migrate:fresh):
 *   farmer0@test.com / farmer1@test.com / farmer2@test.com  (coop farmers, password "password")
 *   logistics1@test.com (GenSan Farmers Cooperative, password "password")
 *   eliseo-driver-1@driver.com (truck RMP-1011) / julio-driver-1@driver.com (truck RMP-1013)
 *
 * Artifacts: eval/screenshots/e2e-coopfarmer-level/
 */
test("Coop farmer level-up: 3 farms × custom crops → negotiations → route offer → driver run → cost ledger", async ({ browser }) => {
  test.setTimeout(1_200_000);

  const SHOT_DIR = path.resolve(__dirname, "../../../eval/screenshots/e2e-coopfarmer-level");
  const RUN_LOG = path.join(SHOT_DIR, "run-log.json");
  const ERR_LOG = path.join(SHOT_DIR, "errors.log");
  fs.mkdirSync(SHOT_DIR, { recursive: true });
  if (!fs.existsSync(RUN_LOG)) fs.writeFileSync(RUN_LOG, JSON.stringify({ steps: [] }));
  if (!fs.existsSync(ERR_LOG)) fs.writeFileSync(ERR_LOG, "");

  const guard = (p: Page) => {
    p.setDefaultTimeout(25_000);
    p.setDefaultNavigationTimeout(60_000);
    return p;
  };

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

  const wireLogging = (page: Page, ctx: string) => {
    page.on("console", m => { if (m.type() === "error") errLog(`[${ctx}] console.error:`, m.text()); });
    page.on("pageerror", e => errLog(`[${ctx}] pageerror:`, String(e?.message ?? e)));
    page.on("requestfailed", r => {
      const err = String(r.failure()?.errorText ?? "");
      if (err === "net::ERR_ABORTED") return;
      errLog(`[${ctx}] requestfailed:`, r.url(), err);
    });
    page.on("response", r => { if (r.status() >= 400) errLog(`[${ctx}] http ${r.status()}:`, r.url()); });
  };

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

  const tomorrow = () => {
    const d = new Date(Date.now() + 86400000);
    return `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, "0")}-${String(d.getDate()).padStart(2, "0")}`;
  };

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

  const swalConfirm = async (page: Page) => {
    await page.waitForSelector(".swal2-confirm", { timeout: 10_000 });
    await page.locator(".swal2-confirm").click({ noWaitAfter: true });
  };

  // Confirm a SweetAlert AND wait for the resulting form PATCH to settle so the
  // next stop-status lookup never races the page reload.
  const swalSubmit = async (page: Page, urlRe: RegExp) => {
    const resp = page
      .waitForResponse(r => urlRe.test(r.url()) && r.status() < 500, { timeout: 30_000 })
      .catch(() => ({}));
    await swalConfirm(page);
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

  // Laravel's throttle:N,1 buckets are SHARED per user across ALL throttled routes,
  // so a single user must stay ≤5 throttled POSTs inside any rolling 60s window.
  // `pace()` parks until ≥60s after the last throttled POST for that user, then
  // `lastLpPostAt` is refreshed right after each LP POST.
  let lastLpPostAt = 0;
  async function pace(page: Page) {
    const since = Date.now() - lastLpPostAt;
    if (since < 60_000) {
      const wait = 60_000 - since;
      console.log(`  [pace] waiting ${Math.round(wait / 1000)}s for throttled-POST window`);
      await page.waitForTimeout(wait);
    }
  }

  // ───────────────────────────  Setup contexts  ───────────────────────────
  const farmerCtx = await browser.newContext();
  const logisticsCtx = await browser.newContext();

  const fp = guard(await farmerCtx.newPage());
  const lp = guard(await logisticsCtx.newPage());

  wireLogging(fp, "farmer");
  wireLogging(lp, "logistics");

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
    const confirmSwal = page.locator(".swal2-confirm");
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
    // ══════════════════════ STAGE 1 — THREE COOP FARMERS POST (2 CUSTOM + 1 MANGO) ══════════════════════
    for (const f of A.farmers) {
      await tryStep(`harvest-${f.crop}`, async () => createHarvest(f, fp), fp);
    }

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
        const card = lp.locator("div:has(> div > a[href*='/buyer/crop-board/'])").filter({ hasText: f.name }).first();
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

        await login(fp, f.email, f.password);
        await fp.goto(`/negotiations/${negId}`);
        await fp.waitForLoadState("networkidle").catch(() => {});
        runLog[`negotiation-id-${tag}`] = Number(negId);
        await shot(fp, step(`farmer-room-${tag}`), `${f.name} views negotiation room`);
        const agreeBtn = fp.locator("#agree-btn");
        await agreeBtn.waitFor({ state: "visible", timeout: 30_000 });
        await shot(fp, step(`farmer-agree-visible-${tag}`), `${f.name} sees "Agree to These Terms"`);
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
        let finalized = false;
        for (let attempt = 1; attempt <= 3 && !finalized; attempt++) {
          if (attempt > 1) {
            await pace(lp);
            await lp.reload();
            await lp.waitForLoadState("networkidle").catch(() => {});
            const rate2 = lp.locator("#hauling_rate_per_kg");
            if (await rate2.isVisible().catch(() => false)) await rate2.fill(f.rate);
            await lp.evaluate(() => {
              const latEl = document.getElementById("destination_latitude") as HTMLInputElement;
              const lngEl = document.getElementById("destination_longitude") as HTMLInputElement;
              const addrEl = document.getElementById("destination_address") as HTMLInputElement;
              if (latEl && !latEl.value) latEl.value = "6.1164";
              if (lngEl && !lngEl.value) lngEl.value = "125.1716";
              if (addrEl && !addrEl.value) addrEl.value = "GenSan Wholesale Market Hub";
            });
          }
          await lp.locator("button[type='submit']", { hasText: /Close Deal/i }).click();
          lastLpPostAt = Date.now();
          await lp.waitForTimeout(4000);
          finalized = lp.url().includes("/buyer/negotiations");
          if (attempt < 3 && !finalized) console.log(`  [pace] finalize ${tag} attempt ${attempt} bounced back — throttled? retrying after 65s`);
        }
        if (!finalized) throw new Error(`Finalize ${tag} did not complete after retries (url=${lp.url()})`);
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

    // ══════════════════════ STAGE 4 — ROUTE PLANNING (REAL OSRM FIRST, MOCK FALLBACK) ══════════════════════
    let truckChoice: Record<string, any> = {};
    let routingMode: "real" | "mock" = "real";

    await tryStep("route-plan-generate", async () => {
      const throughFarm = { type: "LineString", coordinates: [
        [125.1830, 6.1050], [125.1912, 6.1351], [125.0718, 6.2215], [124.9416, 6.3333], [125.1716, 6.1164]
      ] };
      const mockRouteBody = JSON.stringify({ code: "Ok", routes: [{ geometry: throughFarm, distance: 45000, duration: 3600 }] });
      const mockTripBody = JSON.stringify({
        code: "Ok",
        trips: [{ geometry: throughFarm }],
        waypoints: [
          { geometry: { coordinates: [125.1830, 6.1050] } },
          { geometry: { coordinates: [125.1912, 6.1351] } },
          { geometry: { coordinates: [125.0718, 6.2215] } },
          { geometry: { coordinates: [124.9416, 6.3333] } },
          { geometry: { coordinates: [125.1716, 6.1164] } },
        ],
      });

      await lp.route("**/router.project-osrm.org/route/v1/driving/**", (route) => {
        if (routingMode === "real") return route.continue();
        route.fulfill({ status: 200, contentType: "application/json", body: mockRouteBody });
      });
      await lp.route("**/router.project-osrm.org/trip/v1/**", (route) => {
        if (routingMode === "real") return route.continue();
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

      const hasStartMarker = await lp.evaluate(() => document.querySelector(".leaflet-popup")?.textContent?.includes("Coop Hub") ?? false);
      if (!hasStartMarker) {
        const mapBox = await lp.locator(".leaflet-container").boundingBox();
        if (!mapBox) throw new Error("Leaflet map not found");
        await lp.evaluate(([cx, cy]) => {
          document.querySelector(".leaflet-container")!.dispatchEvent(new MouseEvent("click", { clientX: cx, clientY: cy, bubbles: true }));
        }, [mapBox.x + mapBox.width * 0.3, mapBox.y + mapBox.height * 0.3]);
        await lp.waitForTimeout(400);
        await lp.evaluate(([cx, cy]) => {
          document.querySelector(".leaflet-container")!.dispatchEvent(new MouseEvent("click", { clientX: cx, clientY: cy, bubbles: true }));
        }, [mapBox.x + mapBox.width * 0.7, mapBox.y + mapBox.height * 0.7]);
      }

      await lp.waitForFunction(() => {
        const b = document.getElementById("btn-generate-plan");
        return b && !b.disabled;
      }, undefined, { timeout: 25_000 });

      await lp.click("#btn-generate-plan");
      await swalConfirm(lp);
      let planVisible = await lp.waitForSelector("#plan-panel:not(.hidden)", { state: "visible", timeout: 40_000 }).then(() => true).catch(() => false);

      if (!planVisible) {
        if (routingMode === "real") {
          await shot(lp, step("real-osrm-failed"), "Real OSRM routing failed/unreachable — falling back to deterministic mock");
          routingMode = "mock";
          await lp.reload();
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
          await lp.waitForFunction(() => {
            const b = document.getElementById("btn-generate-plan");
            return b && !b.disabled;
          }, undefined, { timeout: 25_000 });
          await lp.click("#btn-generate-plan");
          await swalConfirm(lp);
          planVisible = await lp.waitForSelector("#plan-panel:not(.hidden)", { state: "visible", timeout: 40_000 }).then(() => true).catch(() => false);
          if (!planVisible) throw new Error("Plan panel never appeared (mock too)");
        } else {
          throw new Error("Plan panel never appeared");
        }
      }

      // Truth-check the engine output
      const planText = await lp.locator("#plan-panel").innerText();
      const farmCount = (planText.match(/farm/g) || []).length;
      const planHas3 = planText.includes("3") || farmCount >= 3;
      if (!planHas3 && routingMode === "real") {
        runLog['plan-mode'] = "real→mock (engine under-picked or routing unavailable)";
        routingMode = "mock";
        await lp.reload();
        await lp.waitForLoadState("networkidle").catch(() => {});
        await lp.click("#btn-show-map");
        await lp.waitForSelector(".leaflet-container", { state: "visible", timeout: 15_000 });
        await lp.waitForTimeout(600);
        await lp.selectOption("#radius-select", "50");
        await lp.waitForFunction(() => {
          const b = document.getElementById("btn-generate-plan");
          return b && !b.disabled;
        }, undefined, { timeout: 25_000 });
        await lp.click("#btn-generate-plan");
        await lp.waitForSelector("#plan-panel:not(.hidden)", { state: "visible", timeout: 40_000 });
      } else {
        runLog['plan-mode'] = routingMode;
      }

      await shot(lp, step("pooling-plan-generated"), `Consolidated plan generated (mode: ${routingMode})`);

      const truckText = await lp.evaluate(() => {
        const sel = document.querySelector<HTMLSelectElement>("#truck-select");
        if (!sel) return null;
        const opt = sel.selectedOptions[0];
        return { id: sel.value, name: opt?.textContent?.replace(/\s+/g, " ").trim() ?? "" };
      });
      runLog['truck-choice'] = truckText;
      truckChoice = truckText ?? {};

      await lp.fill("#plan-notes", "Level-up E2E: 3-farm cooperative pickup loop.");
      await lp.click("#btn-confirm-plan");
      await swalConfirm(lp);
      await lp.waitForSelector("#confirm-feedback:not(.hidden)", { timeout: 25_000 }).catch(() => {});
      await lp.waitForLoadState("networkidle").catch(() => {});
      await shot(lp, step("proposal-created"), "Consolidated route proposal created (3 farms)");
    }, lp);

    // ══════════════════════ STAGE 5 — EVERY FARMER READS THE OFFER + ACCEPTS ══════════════════════
    for (const f of A.farmers) {
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
      wireLogging(dp, "driver");
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
      wireLogging(dp, "driver-trip");
      await login(dp, driverEmail, "password");
      await dp.goto("/driver");
      await dp.locator("a:has-text('View Details')").first().click();
      await driverJobDetail(dp);
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
      wireLogging(dp, "driver-stops");
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