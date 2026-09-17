import { Browser, Page } from "@playwright/test";
import { execSync } from "child_process";
import {
  bumpLpPostAt,
  captureIntendedFarmCoords,
  dismissSwal,
  driverJobDetail,
  ensureGenerateEnabled,
  ensurePickupQueue,
  farmerAgreeWithRetry,
  finalizeDealWithRetry,
  guard,
  jsSubmitForm,
  login,
  makeFixtures,
  mockPerFarmDistance,
  outboundJobDetail,
  pace,
  pingAtFarm,
  shot as shotHelper,
  swalConfirm,
  swalSubmit,
  today,
  wireLogging,
} from "./helpers";

export type DemoFarm = {
  email: string;
  password: string;
  name: string;
  crop: string;
  variety: string;
  qty: string;
  price: string;
  rate: string;
};

export type OutboundLine = { crop_type: string; quantity_kg: string; rate_per_kg: string };

export type Ctx = {
  runLog: Record<string, any>;
  shotDir: string;
  step: (name: string) => string;
  errLog: (...lines: (string | any)[]) => void;
  /** Optional phase tag (e.g. "day1", "day2") prefixed to shot filenames so a
   *  multi-phase run never overwrites an earlier phase's screenshots. */
  phase?: string;
};

export function shotFor(ctx: Ctx, page: Page) {
  return (slug: string, label: string) => {
    const prefix = ctx.phase ? `${ctx.phase}-` : "";
    return shotHelper(page)(`${prefix}${slug}`, label, ctx.runLog, ctx.shotDir);
  };
}

const MYSQL = "C:\\xampp\\mysql\\bin\\mysql.exe";

export function dbFirstCol(sql: string): string {
  try {
    const out = execSync(`"${MYSQL}" -uroot harvesthaul -N -B -e "${sql.replace(/"/g, '\\"')}"`, { encoding: "utf8" });
    return out.trim().split(/\r?\n/).filter(Boolean)[0] ?? "";
  } catch {
    return "";
  }
}

export function dbRows(sql: string): string[][] {
  try {
    const out = execSync(`"${MYSQL}" -uroot harvesthaul -N -B -e "${sql.replace(/"/g, '\\"')}"`, { encoding: "utf8" });
    return out
      .trim()
      .split(/\r?\n/)
      .filter(Boolean)
      .map(row => row.split("\t"));
  } catch {
    return [];
  }
}

// tryStep with 240s hard timeout + error screenshot (same contract as the e2e spec).
export function makeTryStep(ctx: Ctx, page?: Page) {
  return async (name: string, fn: () => Promise<void>) => {
    let timer: NodeJS.Timeout | undefined;
    const race = new Promise<never>((_, reject) => {
      timer = setTimeout(() => reject(new Error(`[step-timeout] ${name} exceeded 240s`)), 240_000);
    });
    try {
      await Promise.race([fn(), race]);
    } catch (e: any) {
      const msg = String(e?.message ?? e);
      if (page) {
        await page.screenshot({ path: `${ctx.shotDir}\\ERR-${name}.png`, fullPage: true }).catch(() => {});
        const bodyText = await page.evaluate(() => document.body?.innerText?.slice(0, 900) ?? "").catch(() => "");
        ctx.errLog(`[FAIL] ${name} | ${msg}`, `\n---- page text ----\n${bodyText}`);
      }
      ctx.runLog.steps.push({ slug: `ERR-${name}`, error: msg, url: page?.url() ?? "" });
      console.error(`  [FAIL] ${name}: ${msg}`);
      throw e;
    } finally {
      clearTimeout(timer);
    }
  };
}

// ───────────────────────────── STAGE 1 — FARMERS POST HARVESTS ─────────────────────────────

export async function postHarvests(ctx: Ctx, farmers: DemoFarm[], fp: Page) {
  const tryStep = makeTryStep(ctx, fp);
  const shot = shotFor(ctx, fp);
  for (const f of farmers) {
    const tag = f.crop.toLowerCase();
    await tryStep(`harvest-${tag}`, async () => {
      await login(fp, f.email, f.password);
      await fp.goto("/harvests/create");
      await fp.waitForSelector("#crop_search", { state: "visible", timeout: 20_000 });

      const destLabel = await fp.evaluate(() => {
        const sel = document.getElementById("destination_id") as HTMLSelectElement | null;
        return sel?.selectedOptions?.[0]?.textContent?.replace(/\s+/g, " ").trim() ?? "";
      });
      ctx.runLog[`dest-${tag}`] = destLabel;
      await shot(`harvest-form-${tag}`, `${f.name} posts ${f.qty}kg of ${f.crop} — destination defaults to the Cooperative Hub for coop members`);

      await fp.click("#crop_search");
      const cropItem = fp.locator("#crop_dropdown [data-value]", { hasText: f.crop }).first();
      await cropItem.waitFor({ state: "visible", timeout: 10_000 });
      await cropItem.click();
      const varietyWrapperVisible = await fp.locator("#variety_wrapper").isVisible().catch(() => false);
      if (varietyWrapperVisible) {
        await fp.click("#variety_search");
        const varietyItem = fp.locator("#variety_dropdown [data-value]", { hasText: f.variety }).first();
        await varietyItem.waitFor({ state: "visible", timeout: 10_000 });
        await varietyItem.click();
      }

      await fp.fill("#quantity_kg", f.qty);
      await fp.fill("#suggested_price_per_kg", f.price);
      await fp.fill("#harvest_date", today());
      await fp.fill("#notes", `E2E DEMO ${f.crop} (${f.variety}) from ${f.name}`);

      await fp.locator("#post-harvest-btn").click();
      const swalShown = await fp.locator(".swal2-confirm").waitFor({ state: "visible", timeout: 6_000 }).then(() => true).catch(() => false);
      if (swalShown) {
        await shot(`harvest-confirm-${tag}`, `Post harvest confirmation dialog — ${f.crop}`);
        await swalConfirm(fp);
      }
      await fp.waitForURL(u => !String(u).includes("/harvests/create"), { timeout: 30_000 });
      await fp.waitForLoadState("networkidle").catch(() => {});
      await shot(`harvest-posted-${tag}`, `Harvest posted: ${f.crop} (${f.variety}), ${f.qty}kg`);
    });
  }
}

// ───────────────────────────── STAGE 3 — NEGOTIATE EVERY HARVEST ─────────────────────────────

export async function negotiateAll(ctx: Ctx, lp: Page, fp: Page, farmers: DemoFarm[]): Promise<Record<string, number>> {
  const tryStep = makeTryStep(ctx);
  const shotLp = shotFor(ctx, lp);
  const shotFp = shotFor(ctx, fp);
  const negIds: Record<string, number> = {};
  for (const f of farmers) {
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
      await shotLp(`crop-detail-${tag}`, `Crop board detail — ${f.crop} from ${f.name}`);
      await lp.getByRole("button", { name: /Initiate Negotiation/i }).click();
      await swalConfirm(lp);
      bumpLpPostAt();
      await lp.waitForURL(/negotiations\/\d+/, { timeout: 20_000 });
      await shotLp(`negotiation-started-${tag}`, `Negotiation room opened for ${f.crop}`);
    }, lp);

    await tryStep(`negotiation-${tag}-chat`, async () => {
      await lp.locator("#message-input").fill(`Hello ${f.name}! The cooperative would like to buy your ${f.crop} (${f.variety}), ${f.qty}kg at ₱${f.price}/kg.`);
      await lp.locator("#send-message-form button[type='submit']").click();
      bumpLpPostAt();
      await lp.waitForTimeout(2000);
      await shotLp(`negotiation-message-${tag}`, `${f.name} receives the coop team's first message — negotiation chat`);
    }, lp);

    await tryStep(`negotiation-${tag}-propose`, async () => {
      await lp.fill("#negotiated_price", f.price);
      await lp.fill("#negotiated_volume", f.qty);
      await lp.fill("#term_hauling_rate", f.rate);
      await lp.click("#propose-btn");
      await swalConfirm(lp);
      bumpLpPostAt();
      const offered = await lp.locator("#chat-messages-container").getByText(/\[System Offer\]/i).first()
        .waitFor({ state: "visible", timeout: 20_000 }).then(() => true).catch(() => false);
      if (!offered) throw new Error("Proposal did not persist — no [System Offer] message in chat");
      await shotLp(`proposal-sent-${tag}`, `Terms proposed for ${f.crop}: ₱${f.price}/kg, ${f.qty}kg, haul ₱${f.rate}/kg`);
    }, lp);

    await tryStep(`negotiation-${tag}-farmer-agrees`, async () => {
      const negId = lp.url().match(/negotiations\/(\d+)/)?.[1];
      if (!negId) throw new Error("Could not extract negotiation ID");
      negIds[tag] = Number(negId);
      await farmerAgreeWithRetry(fp, negId, f.email, f.password);
      await shotFp(`farmer-agree-${tag}`, `${f.name} sees the agreed terms in the negotiation room`);
      await shotFp(`farmer-agreed-${tag}`, `${f.name} agreed to the coop's terms for ${f.crop}`);
    }, fp);

    await tryStep(`negotiation-${tag}-finalize`, async () => {
      await lp.reload();
      await lp.waitForLoadState("networkidle").catch(() => {});
      await shotLp(`finalize-room-${tag}`, `Coop logistics finalizes the deal for ${f.crop}`);

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
      if (await dropoffMap.isVisible().catch(() => false)) {
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
      await shotLp(`deal-finalized-${tag}`, `DONE DEAL — ${f.crop} locked at ₱${f.price}/kg, haul ₱${f.rate}/kg`);
    }, lp);
  }
  return negIds;
}

// ───────────────────────────── STAGE 4 — ROUTE PLANNING ─────────────────────────────

export async function planRouteAll(
  ctx: Ctx,
  lp: Page,
  farmers: DemoFarm[],
  minChecks: number,
  planNotes: string,
  expectedPlans: number
): Promise<{ planAllData: any; jobIds: number[] }> {
  const tryStep = makeTryStep(ctx, lp);
  const shot = shotFor(ctx, lp);
  let planAllData: any = null;
  let jobIds: number[] = [];

  await tryStep("route-plan-all-generate", async () => {
    const throughFarm = { type: "LineString", coordinates: [
      [125.1830, 6.1050], [125.1912, 6.1351], [125.1550, 6.1420], [125.0718, 6.2215], [124.9416, 6.3333], [125.2215, 6.1511], [125.1716, 6.1164]
    ] };
    const mockRouteBody = JSON.stringify({ code: "Ok", routes: [{ geometry: throughFarm, distance: 75000, duration: 6000 }] });
    const mockTripBody = JSON.stringify({
      code: "Ok",
      trips: [{ geometry: throughFarm }],
      waypoints: throughFarm.coordinates.map((c: number[]) => ({ geometry: { coordinates: c } })),
    });

    const admitCoords = await captureIntendedFarmCoords(lp, farmers.map((f) => f.name));

    await lp.route("**/router.project-osrm.org/route/v1/driving/**", (route) => {
      const url = route.request().url();
      if (url.includes("overview=false")) {
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

    await ensurePickupQueue(lp, minChecks);
    await ensureGenerateEnabled(lp);

    await shot(`route-map-trucks`, `${farmers.length} farms in the pickup queue, truck selected`);

    const planAllRespP = lp.waitForResponse(r => /pooling\/plan-all/.test(r.url()) && r.status() < 500, { timeout: 60_000 }).catch(() => null);
    await lp.click("#btn-generate-plan");
    await swalConfirm(lp);

    const panelSel = expectedPlans > 1 ? "#plan-all-panel:not(.hidden)" : "#plan-panel:not(.hidden)";
    const panelName = expectedPlans > 1 ? "Multi-plan" : "Single-plan";
    const panelVisible = await lp.waitForSelector(panelSel, { state: "visible", timeout: 40_000 }).then(() => true).catch(() => false);
    if (!panelVisible) throw new Error(`${panelName} panel never appeared`);
    const resp1 = await planAllRespP;
    if (resp1) planAllData = await resp1.json().catch(() => null);

    await shot(expectedPlans > 1 ? `plan-all-panel` : `plan-single-panel`, expectedPlans > 1 ? "Route plan panel — the 3.1t crop load auto-splits across 2 trucks" : "Route plan panel — one truck carries all 3 farms");

    const plans = (planAllData?.plans || []);
    ctx.runLog['planAll-response'] = {
      overflow: planAllData?.overflow,
      plan_count: plans.length,
      total_farms: planAllData?.total_farms,
      selected_total: planAllData?.selected_total,
      unassigned: planAllData?.unassigned,
    };
    const perPlan = plans.map((p: any) => ({
      truck_name: p.truck_name,
      truck_id: p.truck_id,
      capacity: p.truck_capacity_kg,
      crops: (p.selected_harvests || []).map((h: any) => `${h.crop} (${h.variety})`),
      total_kg: p.total_kg,
    }));
    ctx.runLog['planAll-per-plan'] = perPlan;
    console.log("  [info] planAll plans:", JSON.stringify(perPlan));

    if (plans.length !== expectedPlans) {
      throw new Error(`Expected ${expectedPlans} plan(s) from planAll, got ${plans.length}`);
    }
    const distinctTrucks = new Set(plans.map((p: any) => p.truck_name));
    if (distinctTrucks.size < expectedPlans) {
      throw new Error(`Expected plans on ${expectedPlans} different truck(s), got ${distinctTrucks.size}: ${JSON.stringify(perPlan)}`);
    }
    const expectedCrops = farmers.map(f => f.crop);
    const covered = new Set<string>();
    plans.forEach((p: any) => (p.selected_harvests || []).forEach((h: any) => {
      expectedCrops.forEach(ec => { if (String(h.crop || "").includes(ec)) covered.add(ec); });
    }));
    const missingCrops = expectedCrops.filter(c => !covered.has(c));
    if (missingCrops.length > 0) throw new Error(`planAll cards do not cover all crops — missing: ${missingCrops.join(", ")}`);
  }, lp);

  await tryStep("route-confirm-all", async () => {
    if (expectedPlans > 1) {
      await lp.fill("#plan-all-notes", planNotes);
      const confirmRespP = lp.waitForResponse(r => /pooling\/confirm-batch/.test(r.url()) && r.status() < 500, { timeout: 40_000 }).catch(() => null);
      await lp.click("#btn-confirm-all");
      await swalConfirm(lp);
      const confirmResp = await confirmRespP;
      if (confirmResp) {
        const body = await confirmResp.json().catch(() => null);
        ctx.runLog['confirm-all-response'] = body;
        if (body?.job_ids) jobIds = body.job_ids.map((n: any) => Number(n));
      }
      await lp.waitForSelector("#confirm-all-feedback:not(.hidden)", { timeout: 30_000 }).catch(() => {});
    } else {
      await lp.fill("#plan-notes", planNotes);
      const confirmRespP = lp.waitForResponse(r => /pooling\/confirm\b/.test(r.url()) && r.status() < 500, { timeout: 40_000 }).catch(() => null);
      await lp.click("#btn-confirm-plan");
      await swalConfirm(lp);
      const confirmResp = await confirmRespP;
      if (confirmResp) {
        const body = await confirmResp.json().catch(() => null);
        ctx.runLog['confirm-single-response'] = body;
        if (body?.pooling_job_id) jobIds = [Number(body.pooling_job_id)];
      }
      await lp.waitForSelector("#confirm-feedback:not(.hidden)", { timeout: 30_000 }).catch(() => {});
    }
    await lp.waitForLoadState("networkidle", { timeout: 20_000 }).catch(() => {});
    await shot(`route-proposals-created`, expectedPlans > 1 ? "Confirm All — 2 route offers (one per truck) created" : "Route offer created for the single-truck route");
    console.log("  [info] confirmAll job_ids:", JSON.stringify(jobIds));
  }, lp);

  return { planAllData, jobIds };
}

// ───────────────────────────── STAGE 5 — FARMERS ACCEPT ─────────────────────────────

export async function farmersAccept(ctx: Ctx, fp: Page, farmers: DemoFarm[]) {
  const tryStep = makeTryStep(ctx, fp);
  const shot = shotFor(ctx, fp);
  for (const f of farmers) {
    const tag = f.crop.toLowerCase();
    await tryStep(`farmer-accept-${tag}`, async () => {
      await login(fp, f.email, f.password);
      await fp.goto("/farmer/proposals");
      await fp.waitForLoadState("networkidle").catch(() => {});
      await shot(`farmer-proposal-${tag}`, `Route Offer visible to ${f.name} for ${f.crop}`);
      const acceptForm = fp.locator("form[action*='pooling'][action*='accept']").first();
      await acceptForm.waitFor({ state: "visible", timeout: 20_000 });
      await acceptForm.getByRole("button", { name: "Accept" }).click();
      await swalConfirm(fp);
      await fp.waitForFunction(() => /Accepted|Awaiting other farmers|confirmed/i.test(document.body.innerText), undefined, { timeout: 60_000 }).catch(() => {});
      await fp.waitForLoadState("networkidle", { timeout: 60_000 }).catch(() => {});
      await fp.waitForTimeout(1000);
      await shot(`farmer-accepted-${tag}`, `${f.name} accepted the Route Offer`);
    });
  }
}

// Resolve each pooling job → assigned truck → driver email (engine truth, from DB).
export function resolveDrivers(expectedJobs: number): { jobId: string; plate: string; driverEmail: string }[] {
  return dbRows(
    "SELECT pj.id, t.plate_number, u.email FROM pooling_jobs pj JOIN trucks t ON t.id = pj.truck_id JOIN users u ON u.id = pj.driver_id WHERE pj.logistics_profile_id = 4 AND pj.status = 'confirmed' ORDER BY pj.id"
  )
    .map(([jobId, plate, email]) => ({ jobId, plate, driverEmail: email ?? "" }))
    .slice(-expectedJobs);
}

// ───────────────────────────── STAGE 6 — DRIVER RUNS THE INBOUND ROUTE ─────────────────────────────

export async function runInboundDriver(ctx: Ctx, browser: Browser, driverEmail: string) {
  const dc = await browser.newContext();
  const dp = guard(await dc.newPage());
  wireLogging(dp, "driver", ctx.errLog);
  const tryStep = makeTryStep(ctx, dp);
  const shot = shotFor(ctx, dp);
  const shortName = driverEmail.split("@")[0].replace("demo.outbound.", "");

  await tryStep(`driver-${shortName}-accept-start`, async () => {
    await login(dp, driverEmail, "demo1234");
    await dp.goto("/driver");
    await dp.waitForLoadState("networkidle", { timeout: 10_000 }).catch(() => {});
    await shot(`driver-dashboard-${shortName}`, `Driver portal — ${shortName} has an inbound route waiting`);
    await dp.locator("a:has-text('View Details')").first().click();
    await driverJobDetail(dp);
    await shot(`driver-job-detail-${shortName}`, "Driver job detail — pickup sequence with each farm stop");
    await dp.waitForTimeout(800);
    const acceptBtn = dp.getByRole("button", { name: /Accept Job/i }).first();
    if (await acceptBtn.isVisible().catch(() => false)) {
      await jsSubmitForm(dp, acceptBtn, /\/driver\/jobs\/\d+\/accept/);
      await shot(`driver-accepted-${shortName}`, `Driver ${shortName} accepted the inbound route`);
    }
    const startBtn = dp.getByRole("button", { name: /Start Job/i }).first();
    if (await startBtn.isVisible().catch(() => false)) {
      await jsSubmitForm(dp, startBtn, /\/driver\/jobs\/\d+\/status/);
      await shot(`trip-started-${shortName}`, "Route In Transit — live GPS tracking active");
    }
  }, dp);

  await tryStep(`driver-${shortName}-stops`, async () => {
    const fixtures = makeFixtures();
    let stopNo = 0;
    const stopPatch = /\/driver\/jobs\/\d+\/harvests\/\d+\/status/;
    // Pass A — pickups: arrive + load each farm, any order (server allows it).
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
      await jsSubmitForm(dp, arrivedBtn, stopPatch);
      await shot(`stop-${stopNo}-arrived-${shortName}`, `Stop ${stopNo} — truck arrives at the farm`);

      const qtyInput = dp.locator("#loaded_quantity_kg").first();
      if (await qtyInput.isVisible().catch(() => false)) { await qtyInput.fill("100"); }
      const loadPhoto = dp.locator("#load_photo").first();
      if (await loadPhoto.isVisible().catch(() => false)) { await loadPhoto.setInputFiles(fixtures.load); }
      const cropCheck = dp.locator("#crop_confirmed").first();
      if (await cropCheck.isVisible().catch(() => false)) { await cropCheck.check(); }

      const loadBtn = dp.getByRole("button", { name: /Confirm Cargo|Mark Loaded/i }).first();
      let loadVisible = false;
      try { await loadBtn.waitFor({ state: "visible", timeout: 3_000 }); loadVisible = true; } catch { }
      if (loadVisible) {
        await jsSubmitForm(dp, loadBtn, stopPatch);
        await shot(`stop-${stopNo}-loaded-${shortName}`, `Stop ${stopNo} — cargo loaded with photo proof`);
      }
    }
    if (stopNo === 0) throw new Error("No pickup stops were completed by the driver");

    // Pass B — deliveries: allowed only after every stop is loaded (server-enforced).
    for (let attempt = 0; attempt <= stopNo; attempt++) {
      const receipt = dp.locator("#delivery_receipt").first();
      if (await receipt.isVisible().catch(() => false)) { await receipt.setInputFiles(fixtures.delivery); }
      const deliverBtn = dp.getByRole("button", { name: /Mark Delivered/i }).first();
      let deliverVisible = false;
      try { await deliverBtn.waitFor({ state: "visible", timeout: 3_000 }); deliverVisible = true; } catch { }
      if (!deliverVisible) break;
      await jsSubmitForm(dp, deliverBtn, stopPatch);
      await shot(`stop-${attempt + 1}-delivered-${shortName}`, `Stop ${attempt + 1} — crop delivered to the coop hub`);
    }

    const completeBtn = dp.getByRole("button", { name: /Complete Run|Finalize Job|Complete Job/i }).first();
    let completeVisible = false;
    try { await completeBtn.waitFor({ state: "visible", timeout: 3_000 }); completeVisible = true; } catch { }
    if (completeVisible) {
      const odo = dp.locator("#end_odometer_reading").first();
      if (await odo.isVisible().catch(() => false)) { await odo.fill("120.5"); }
      await jsSubmitForm(dp, completeBtn, /\/driver\/jobs\/\d+\/status/);
      await shot(`run-completed-${shortName}`, "Inbound run completed — receipt confirmation pending");
    }
  }, dp);

  await dc.close();
}

// ───────────────────────────── STAGE 6.5 — COOP CONFIRMS RECEIPTS ─────────────────────────────

export async function coopConfirmReceipts(ctx: Ctx, lp: Page, expected: number) {
  const tryStep = makeTryStep(ctx, lp);
  const shot = shotFor(ctx, lp);
  await tryStep("coop-confirm-receipts", async () => {
    await lp.goto("/buyer/tracking");
    await lp.waitForLoadState("networkidle").catch(() => {});
    await shot(`buyer-tracking-awaiting`, `${expected} route(s) awaiting coop receipt confirmation`);
    let confirmed = 0;
    for (let i = 0; i < expected; i++) {
      // Each confirm redirect can auto-fire an informational "next steps"
      // modal that shields the background buttons from getByRole. Dismiss it,
      // then give the button time to render before deciding it's gone.
      const confirmBtn = lp.getByRole("button", { name: /Confirm Receipt/i }).first();
      let hasBtn = false;
      for (let r = 0; r < 5 && !hasBtn; r++) {
        try {
          await confirmBtn.waitFor({ state: "visible", timeout: 2_500 });
          hasBtn = true;
        } catch {
          await dismissSwal(lp);
        }
      }
      if (!hasBtn) break;
      await confirmBtn.click();
      await swalSubmit(lp, /\/deliveries\/\d+\/confirm/);
      confirmed++;
    }
    if (confirmed < expected) throw new Error(`Confirmed only ${confirmed}/${expected} receipts`);
    await shot(`receipts-confirmed`, "Coop logistics confirmed receipt for every route — inbound leg complete");
  }, lp);
}

// ───────────────────────────── STAGE 7 — OUTBOUND LEG ─────────────────────────────

export async function createOutboundOrder(ctx: Ctx, lp: Page, customerId: string, lines: OutboundLine[], notes: string): Promise<number> {
  const tryStep = makeTryStep(ctx, lp);
  const shot = shotFor(ctx, lp);
  await tryStep("outbound-create-order", async () => {
    await lp.goto("/coop/outbound/create");
    await lp.waitForLoadState("networkidle").catch(() => {});
    await lp.selectOption("#customer_card_id", customerId);

    for (let i = 0; i < lines.length; i++) {
      const line = lines[i];
      if (i > 0) {
        await lp.locator("#outbound-form button").filter({ hasText: /Add line/i }).click();
      }
      const row = lp.locator(".line-row").nth(i);
      await row.locator("input[name$='[crop_type]']").fill(line.crop_type);
      await row.locator("input[name$='[quantity_kg]']").fill(line.quantity_kg);
      await row.locator("input[name$='[rate_per_kg]']").fill(line.rate_per_kg);
    }

    await lp.fill("#notes", notes);
    await shot(`outbound-order-form`, `${lines.length} crop line(s) for Robinsons Place General Santos`);
    await lp.locator("#outbound-form button[type='submit']").click();
    await lp.waitForURL(/\/coop\/outbound/, { timeout: 30_000 });
    await lp.waitForLoadState("networkidle").catch(() => {});
    await shot(`outbound-order-saved`, "Customer order saved (Drafted)");
  }, lp);
  const id = Number(dbFirstCol(`SELECT id FROM outbound_orders WHERE logistics_profile_id = 4 AND status = 'drafted' ORDER BY id DESC LIMIT 1`));
  if (!id) throw new Error("Could not resolve the drafted outbound order id from the DB");
  return id;
}

export async function dispatchOutbound(
  ctx: Ctx,
  lp: Page,
  orderId: number,
  io: { truckPlate: string; driverName: string }
): Promise<string> {
  const tryStep = makeTryStep(ctx, lp);
  const shot = shotFor(ctx, lp);
  let token = "";
  await tryStep("outbound-dispatch", async () => {
    await lp.goto(`/coop/outbound/${orderId}`);
    await lp.waitForLoadState("networkidle").catch(() => {});
    await shot(`order-drafted-dispatch`, "Drafted customer order — Dispatch form (truck + driver + start point)");

    await lp.evaluate(({ truck, driver }) => {
      const pick = (sel: string, needle: string) => {
        const el = document.getElementById(sel) as HTMLSelectElement | null;
        for (let i = 0; el && i < el.options.length; i++) {
          if (el.options[i].textContent && el.options[i].textContent!.includes(needle)) {
            el.selectedIndex = i;
            return;
          }
        }
      };
      pick("truck_id", truck);
      pick("driver_id", driver);
    }, { truck: io.truckPlate, driver: io.driverName });
    await lp.fill("#start_latitude", "6.0533");
    await lp.fill("#start_longitude", "125.1321");

    const dispatchResp = lp.waitForResponse(r => /\/coop\/outbound\/\d+\/dispatch/.test(r.url()) && r.status() < 500, { timeout: 30_000 }).catch(() => null);
    await lp.getByRole("button", { name: /Dispatch Order/i }).click();
    await dispatchResp;
    await lp.waitForLoadState("networkidle", { timeout: 30_000 }).catch(() => {});
    await lp.waitForTimeout(800);
    await shot(`order-dispatched-tracking-link`, "Shipment dispatched — tracking link generated for the customer");

    token = await lp.locator("input[readonly]").inputValue().then(v => v.match(/track-out\/([A-Za-z0-9]+)/)?.[1] ?? "").catch(() => "");
    console.log("  [info] tracking token:", token || "(not resolved)");
  }, lp);
  if (!token) throw new Error("Tracking token not found on the dispatched order page");
  return token;
}

export async function runOutboundDelivery(ctx: Ctx, browser: Browser, driverEmail: string): Promise<string> {
  const dc = await browser.newContext();
  const dp = guard(await dc.newPage());
  wireLogging(dp, "outbound-driver", ctx.errLog);
  const tryStep = makeTryStep(ctx, dp);
  const shot = shotFor(ctx, dp);
  const shortName = driverEmail.split("@")[0].replace("demo.outbound.", "");

  await tryStep("outbound-driver-delivers", async () => {
    await login(dp, driverEmail, "demo1234");
    await dp.goto("/driver");
    await dp.waitForLoadState("networkidle", { timeout: 10_000 }).catch(() => {});
    const deliveryLink = dp.locator(".glass-card:has-text('Customer Delivery') a:has-text('View Details')").first();
    await deliveryLink.waitFor({ state: "visible", timeout: 20_000 });
    await deliveryLink.click();
    await outboundJobDetail(dp);
    await shot(`outbound-job-detail-${shortName}`, "Driver sees the customer delivery — Robinsons Place General Santos");

    const acceptBtn = dp.getByRole("button", { name: /Accept Customer Delivery/i }).first();
    if (await acceptBtn.isVisible().catch(() => false)) {
      await jsSubmitForm(dp, acceptBtn, /\/driver\/jobs\/\d+\/accept/);
    }
    const startBtn = dp.getByRole("button", { name: /Start Job/i }).first();
    if (await startBtn.isVisible().catch(() => false)) {
      await jsSubmitForm(dp, startBtn, /\/driver\/jobs\/\d+\/status/);
      await shot(`outbound-in-transit-${shortName}`, "Customer delivery In Transit — GPS tracking the truck to the store");
    }
    // CSS anchor, not getByRole: an open SweetAlert info-modal shields the page
    // background from the accessibility tree, so role lookups can time out.
    const deliveredBtn = dp.locator("form[action*='outbound-delivered'] button[type='submit']").first();
    await deliveredBtn.waitFor({ state: "visible", timeout: 30_000 });
    await jsSubmitForm(dp, deliveredBtn, /\/driver\/jobs\/\d+\/outbound-delivered/);
    await shot(`outbound-delivered-${shortName}`, "Driver marked the delivery at the customer location");

    const finalBtn = dp.locator("button:has-text('Finalize Job — Mark Completed')").first();
    const odo = dp.locator("#end_odometer_reading").first();
    if (await odo.isVisible().catch(() => false)) { await odo.fill("96.4"); }
    if (await finalBtn.isVisible().catch(() => false)) {
      await jsSubmitForm(dp, finalBtn, /\/driver\/jobs\/\d+\/status/);
      await shot(`outbound-finalized-${shortName}`, "Driver finalized the trip — waiting for the customer to confirm receipt");
    }
  }, dp);
  await dc.close();

  const token = dbFirstCol("SELECT o.tracking_token FROM outbound_orders o WHERE o.logistics_profile_id = 4 AND o.status = 'awaiting_confirmation' AND o.tracking_token IS NOT NULL ORDER BY o.id DESC LIMIT 1");
  return token;
}

export async function customerTrackConfirm(ctx: Ctx, browser: Browser, token: string) {
  const cc = await browser.newContext();
  const cp = guard(await cc.newPage());
  wireLogging(cp, "customer", ctx.errLog);
  const tryStep = makeTryStep(ctx, cp);
  const shot = shotFor(ctx, cp);
  await tryStep("customer-tracks-delivery", async () => {
    await cp.goto(`/track-out/${token}`);
    await cp.waitForLoadState("networkidle").catch(() => {});
    await cp.locator("#liveMap").waitFor({ state: "visible", timeout: 20_000 }).catch(() => {});
    await shot(`customer-track-page`, "Customer tracking page — live truck position + delivery items for Robinsons Place");

    const btn = cp.getByRole("button", { name: /Confirm Received/i }).first();
    await btn.waitFor({ state: "visible", timeout: 30_000 });
    await shot(`customer-confirm-prompt`, "Customer sees 'Arrived? Confirm Received' on arrival");
    await btn.click();
    await cp.waitForURL(/\/track-out\/complete/, { timeout: 30_000 });
    await cp.waitForLoadState("networkidle").catch(() => {});
    await shot(`customer-confirmed-complete`, "Order complete — the customer confirmed receipt");
  }, cp);
  await cc.close();
}

export async function coopOutboundProof(ctx: Ctx, lp: Page, orderId: number) {
  const tryStep = makeTryStep(ctx, lp);
  const shot = shotFor(ctx, lp);
  await tryStep("coop-outbound-completed", async () => {
    await lp.goto(`/coop/outbound/${orderId}`);
    await lp.waitForLoadState("networkidle").catch(() => {});
    await shot(`coop-order-completed`, "Coop logistics sees the customer order marked Completed");
  }, lp);
}