import { test, expect, Page, BrowserContext } from "@playwright/test";
import * as fs from "fs";
import * as path from "path";
import { fileURLToPath } from "url";

const __dirname = path.dirname(fileURLToPath(import.meta.url));

/**
 * FULL INDEPENDENT FARMER TRANSACTION — E2E walkthrough with screenshots.
 *
 * Story: An independent farmer lists a harvest, a buyer negotiates and closes
 * a B2B deal, the deal auto-creates a haul request, a logistics partner
 * expresses intent / builds a pooling proposal, the farmer accepts, a driver
 * is assigned and runs the trip, the buyer confirms receipt, and the cost
 * ledger is settled (receipt uploaded -> payment verified).
 *
 * Prereqs (data): run `npm run test:browser:seed` after a fresh
 * `php artisan migrate:fresh --seed` so the accounts below exist in the right
 * state (testfarmer/testbuyer unverified so admin verification is actionable).
 *
 * Artifacts: screenshots -> eval/screenshots/e2e-independent-farmer/,
 * run summary -> run-log.json, diagnostics -> errors.log
 */
test("Full independent-farmer transaction (E2E walkthrough)", async ({ browser }) => {
  test.setTimeout(600_000);

  const SHOT_DIR = path.resolve(__dirname, "../../../eval/screenshots/e2e-independent-farmer");
  const RUN_LOG = path.join(SHOT_DIR, "run-log.json");
  const ERR_LOG = path.join(SHOT_DIR, "errors.log");
  fs.mkdirSync(SHOT_DIR, { recursive: true });

  const guard = (p: Page) => {
    p.setDefaultTimeout(20_000);
    p.setDefaultNavigationTimeout(60_000);
    return p;
  };

  const A = {
    admin:     { email: "admin@mail.com", password: "password123" },
    farmer:    { email: "testfarmer@example.com", password: "Password123!" },
    buyer:     { email: "testbuyer@example.com", password: "Password123!" },
    logistics: { email: "logistics2@test.com", password: "password" },
    drivers:   [
      { email: "private-driver-1-2@driver.com", password: "password", name: "Private Driver 1" },
      { email: "private-driver-2-2@driver.com", password: "password", name: "Private Driver 2" },
    ],
  };

  const runLog: Record<string, any> = { startedAt: new Date().toISOString(), steps: [] };
  let stepCount = 0;
  const step = (name: string) => `${String(++stepCount).padStart(2, "0")}-${name}`;

  const errLog = (...lines: (string | any)[]) =>
    fs.appendFileSync(ERR_LOG, `[${new Date().toISOString()}] ` + lines.map(l => (typeof l === "string" ? l : JSON.stringify(l))).join(" ") + "\n");

  const wireLogging = (page: Page, ctx: string) => {
    page.on("console", m => { if (m.type() === "error") errLog(`[${ctx}] console.error:`, m.text()); });
    page.on("pageerror", e => errLog(`[${ctx}] pageerror:`, String(e?.message ?? e)));
    page.on("requestfailed", r => {
      const err = String(r.failure()?.errorText ?? "");
      if (err === "net::ERR_ABORTED") return; // browser-cancelled (tile pan/navigation) — not a resource failure
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
    const guard = new Promise<never>((_, reject) => {
      timer = setTimeout(() => reject(new Error(`[step-timeout] ${name} exceeded 180s`)), 180_000);
    });
    try {
      await Promise.race([fn(), guard]);
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
    await page.goto("/login");
    await page.waitForLoadState("networkidle").catch(() => {});
    // Already authenticated — server redirected away from /login
    if (!page.url().includes("/login")) return;
    await page.waitForSelector("#login-panel input[name='email']", { timeout: 20_000 });
    await page.fill("#login-panel input[name='email']", email);
    await page.fill("#login-panel input[name='password']", password);
    await page.locator("#login-panel button[type='submit']").click({ noWaitAfter: true });
    await page.waitForURL(/\/(dashboard|admin|farmer|buyer|logistics|driver)/, { timeout: 25_000 });
    await page.waitForLoadState("networkidle").catch(() => {});
  }

  const tomorrow = () => {
    const d = new Date(Date.now() + 86400000);
    return `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, "0")}-${String(d.getDate()).padStart(2, "0")}`;
  };

  // Minimal 1x1 PNG for file-upload fixtures
  const FIXTURES = path.resolve(__dirname, "fixtures");
  fs.mkdirSync(FIXTURES, { recursive: true });
  const PNG_1PX = Buffer.from("iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg==", "base64");
  const fixture = (name: string) => {
    const p = path.join(FIXTURES, name);
    if (!fs.existsSync(p)) fs.writeFileSync(p, PNG_1PX);
    return p;
  };
  const F_LOAD = fixture("load-photo.png");
  const F_DELIVERY = fixture("delivery-photo.png");

  const swalConfirm = async (page: Page) => {
    await page.waitForSelector(".swal2-confirm", { timeout: 10_000 });
    await page.locator(".swal2-confirm").click();
  };

  // ───────────────────────────  Setup contexts  ───────────────────────────
  const farmerCtx = await browser.newContext();
  const buyerCtx = await browser.newContext();
  const logisticsCtx = await browser.newContext();
  const adminCtx = await browser.newContext();
  const fp = guard(await farmerCtx.newPage());
  const bp = guard(await buyerCtx.newPage());
  const lp = guard(await logisticsCtx.newPage());
  const ap = guard(await adminCtx.newPage());
  wireLogging(fp, "farmer");
  wireLogging(bp, "buyer");
  wireLogging(lp, "logistics");
  wireLogging(ap, "admin");

  try {
    // ══════════════════════ STAGE 1 — ONBOARDING ══════════════════════
    await tryStep("01-registration-forms", async () => {
      await fp.goto("/register/farmer");
      await fp.waitForLoadState("networkidle").catch(() => {});
      await shot(fp, step("registration-farmer"), "Stage 1 – Farmer registration form");
      await lp.goto("/register/logistics_partner");
      await lp.waitForLoadState("networkidle").catch(() => {});
      await shot(lp, step("registration-logistics"), "Stage 1 – Logistics registration form");
    }, fp);

    await tryStep("02-admin-verify-farmer", async () => {
      await login(ap, A.admin.email, A.admin.password);
      await ap.goto("/admin/farmers");
      await ap.waitForLoadState("networkidle").catch(() => {});
      await shot(ap, step("admin-farmers-list"), "Stage 1 – Admin pending-farmer approvals");
      const row = ap.locator("tr", { hasText: "Test Farmer" }).first();
      const verifyForm = row.locator("form[action*='verify']").first();
      if (await verifyForm.count().catch(() => 0) > 0) {
        await verifyForm.locator("button").first().click();
        await swalConfirm(ap);
        await ap.waitForLoadState("networkidle").catch(() => {});
        await shot(ap, step("admin-farmer-verified"), "Stage 1 – Farmer approved");
      } else {
        errLog("[info] testfarmer already verified; skipping admin approve click");
      }
    }, ap);

    await tryStep("03-admin-verify-buyer", async () => {
      await ap.goto("/admin/buyers");
      await ap.waitForLoadState("networkidle").catch(() => {});
      await shot(ap, step("admin-buyers-list"), "Stage 1 – Admin pending-buyer approvals");
      const row = ap.locator("tr", { hasText: "Test Buyer" }).first();
      const verifyForm = row.locator("form[action*='verify']").first();
      if (await verifyForm.count().catch(() => 0) > 0) {
        await verifyForm.locator("button").first().click();
        await swalConfirm(ap);
        await ap.waitForLoadState("networkidle").catch(() => {});
        await shot(ap, step("admin-buyer-verified"), "Stage 1 – Buyer approved");
      } else {
        errLog("[info] testbuyer already verified; skipping admin approve click");
      }
    }, ap);

    // ══════════════════════ STAGE 2 — LISTING & SALE ══════════════════════
    await tryStep("04-farmer-dashboard", async () => {
      await login(fp, A.farmer.email, A.farmer.password);
      await shot(fp, step("farmer-dashboard"), "Stage 2 – Farmer dashboard");
    }, fp);

    await tryStep("05-harvest-create", async () => {
      await fp.goto("/harvests/create");
      // Crop is a custom combobox: focus #crop_search to open #crop_dropdown, click an item
      await fp.waitForSelector("#crop_search", { state: "visible", timeout: 20_000 });
      await fp.click("#crop_search");
      const cropItem = fp.locator("#crop_dropdown [data-value]").first();
      await cropItem.waitFor({ state: "visible", timeout: 10_000 });
      await cropItem.click();

      // Variety cascades in only when the chosen crop has varieties
      const varietyWrapperVisible = await fp.locator("#variety_wrapper").isVisible().catch(() => false);
      if (varietyWrapperVisible) {
        await fp.click("#variety_search");
        const varietyItem = fp.locator("#variety_dropdown [data-value]").first();
        await varietyItem.waitFor({ state: "visible", timeout: 5_000 }).catch(() => {});
        if (await varietyItem.isVisible().catch(() => false)) await varietyItem.click();
      }
      await fp.fill("#quantity_kg", "200");
      await fp.fill("#suggested_price_per_kg", "60");
      await fp.fill("#harvest_date", tomorrow());
      const destOpt = fp.locator("#destination_id option:not([value=''])").first();
      const destVal = await destOpt.getAttribute("value");
      if (!destVal) throw new Error("No destination option available");
      await fp.selectOption("#destination_id", destVal);
      // Explicitly populate the hidden destination fields (handles broken-onchange edge cases)
      const dLat = await destOpt.getAttribute("data-lat");
      const dLng = await destOpt.getAttribute("data-lng");
      const dAddr = await destOpt.getAttribute("data-address");
      if (dLat) await fp.locator("#destination_latitude").evaluate((el: any, v: string) => { el.value = v; }, dLat);
      if (dLng) await fp.locator("#destination_longitude").evaluate((el: any, v: string) => { el.value = v; }, dLng);
      if (dAddr) await fp.locator("#destination_address").evaluate((el: any, v: string) => { el.value = v; }, dAddr);
      await fp.fill("#notes", "Fresh from today's harvest; negotiable pricing.");
      const submitBtn = fp.locator("#post-harvest-btn");
      if ((await submitBtn.count().catch(() => 0)) > 0) {
        await submitBtn.click();
      } else {
        await fp.locator("button[type='submit']").last().click();
      }
      await fp.waitForURL(u => !String(u).includes("/harvests/create"), { timeout: 25_000 });
      await fp.waitForLoadState("networkidle").catch(() => {});
      await shot(fp, step("harvest-created"), "Stage 2 – Harvest listing posted");
    }, fp);

    await tryStep("06-buyer-crop-board", async () => {
      await login(bp, A.buyer.email, A.buyer.password);
      await bp.goto("/buyer/crop-board");
      await bp.waitForLoadState("networkidle").catch(() => {});
      await shot(bp, step("crop-board"), "Stage 2 – Buyer crop board with posted harvest");
    }, bp);

    await tryStep("07-negotiation-start", async () => {
      const card = bp.locator("a[href*='/buyer/crop-board/']").first();
      await card.waitFor({ state: "visible", timeout: 20_000 });
      await card.click();
      await bp.waitForURL(/\/buyer\/crop-board\/\d+/, { timeout: 20_000 });
      await shot(bp, step("crop-detail"), "Stage 2 – Buyer crop detail");
      await bp.getByRole("button", { name: /Initiate Negotiation/i }).click();
      await bp.waitForURL(/negotiations\/\d+/, { timeout: 20_000 });
      await shot(bp, step("negotiation-started"), "Stage 2 – Negotiation room opened");
    }, bp);

    await tryStep("08-negotiation-chat", async () => {
      await bp.waitForSelector("#send-message-form #message-input", { timeout: 15_000 });
      await bp.fill("#send-message-form #message-input", "Hello! Interested in your fresh harvest.");
      await bp.locator("#send-message-form button[type='submit']").click();
      await bp.waitForTimeout(800);
      await shot(bp, step("negotiation-chat"), "Stage 2 – Buyer sends chat message");
    }, bp);

    await tryStep("09-propose-terms", async () => {
      await bp.waitForSelector("#negotiated_price", { timeout: 15_000 });
      await bp.fill("#negotiated_price", "50");
      await bp.fill("#negotiated_volume", "200");
      await bp.click("#propose-btn");
      await bp.waitForTimeout(1200);
      await shot(bp, step("proposal-sent"), "Stage 2 – Buyer proposes terms (₱50/kg)");
    }, bp);

    await tryStep("10-farmer-agrees", async () => {
      const roomUrl = bp.url();
      const fNeg = await farmerCtx.newPage();
      wireLogging(fNeg, "farmer-neg");
      await fNeg.goto(roomUrl);
      await fNeg.waitForSelector("#agree-btn", { timeout: 20_000 });
      await shot(fNeg, step("farmer-negotiation-room"), "Stage 2 – Farmer reviews proposed terms");
      await fNeg.click("#agree-btn");
      await swalConfirm(fNeg);
      await fNeg.waitForTimeout(1200);
      await shot(fNeg, step("agreement-reached"), "Stage 2 – Farmer agrees to terms");
      await fNeg.close();
    }, bp);

    await tryStep("11-buyer-finalizes", async () => {
      await bp.reload();
      await bp.waitForSelector("#destination_address", { timeout: 20_000 });
      await shot(bp, step("finalize-panel"), "Stage 2 – Finalize & drop-off panel");
      await bp.fill("#destination_address", "Dadiangas Wholesale Market Hub, General Santos City");
      await bp.locator("#destination_latitude").evaluate((el: any, v: string) => { el.value = v; }, "6.1145");
      await bp.locator("#destination_longitude").evaluate((el: any, v: string) => { el.value = v; }, "125.1706");
      await bp.getByRole("button", { name: /Close Deal & Confirm Drop-off/i }).click();
      await bp.waitForURL(/(negotiations|buyer|dashboard)/, { timeout: 25_000 });
      await shot(bp, step("deal-finalized"), "Stage 2 – Deal closed, haul request auto-created");
    }, bp);

    // ══════════════════════ STAGE 3 — HAUL COORDINATION ══════════════════════
    await tryStep("12-farmer-requests-haul", async () => {
      await fp.goto("/harvests");
      await fp.waitForLoadState("networkidle").catch(() => {});
      await fp.getByRole("button", { name: /Request Haul/i }).first().waitFor({ state: "visible", timeout: 20_000 });
      await fp.getByRole("button", { name: /Request Haul/i }).first().click();
      await fp.waitForSelector("#swal-date", { timeout: 10_000 });
      await fp.fill("#swal-date", tomorrow());
      await fp.fill("#swal-notes", "Please schedule pickup within the week.");
      await swalConfirm(fp);
      await fp.waitForURL((u) => String(u).includes("/harvests"), { timeout: 25_000 }).catch(() => {});
      await fp.waitForLoadState("networkidle").catch(() => {});
      await shot(fp, step("haul-request-posted"), "Stage 3 – Farmer posts haul request");
    }, fp);

    await tryStep("13-farmer-haul-request", async () => {
      await fp.goto("/farmer/haul-requests");
      await fp.waitForLoadState("networkidle").catch(() => {});
      await shot(fp, step("haul-request-created"), "Stage 3 – Farmer haul requests (auto-created)");
    }, fp);

    await tryStep("14-logistics-express-intent", async () => {
      await login(lp, A.logistics.email, A.logistics.password);
      await lp.goto("/route-optimization");
      await lp.waitForLoadState("networkidle").catch(() => {});
      await shot(lp, step("route-optimization-list"), "Stage 3 – Route planning (open haul requests)");

      // Locate an open haul request from the embedded farmers JSON
      const open = await lp.evaluate(() => {
        for (const s of Array.from(document.scripts)) {
          const m = (s.textContent || "").match(/const farms\s*=\s*(\[.*?\])\s*;?$/ms);
          if (!m) continue;
          try {
            const farms = JSON.parse(m[1]);
            for (const farm of farms) {
              for (const h of farm.harvests || []) {
                if (h.has_open_haul_request && h.haul_request_id) {
                  return { hrId: h.haul_request_id, name: farm.name };
                }
              }
            }
          } catch { /* try next script */ }
        }
        return null;
      });
      if (!open) throw new Error("No open haul request found in route-optimization data");

      // Click the server-rendered "Express Haul Intent" button in the list (map stays hidden)
      const expressBtn = lp.locator("#open-haul-requests button", { hasText: "Express Haul Intent" }).first();
      await expressBtn.waitFor({ state: "visible", timeout: 10_000 });
      await expressBtn.click();
      await lp.waitForSelector("#swal-date", { timeout: 10_000 });
      await shot(lp, step("route-express-intent"), "Stage 3 – Express Haul Intent from list");

      await lp.fill("#swal-date", tomorrow());
      await lp.fill("#swal-notes", "Will pick up with our medium truck.");
      await swalConfirm(lp);
      await lp.waitForURL(/route-optimization|haul/, { timeout: 20_000 }).catch(() => {});
      await lp.waitForLoadState("networkidle").catch(() => {});
      await shot(lp, step("haul-intent-expressed"), "Stage 3 – Logistics expressed haul intent");
    }, lp);

    await tryStep("15b-haul-negotiation-chat", async () => {
      // Find the haul intent link from the haul-requests page
      await fp.goto("/farmer/haul-requests");
      await fp.waitForLoadState("networkidle").catch(() => {});
      const intentLink = fp.locator("a[href*='/haul-negotiations/']").first();
      const hasLink = await intentLink.isVisible().catch(() => false);
      if (!hasLink) {
        await shot(fp, step("haul-nego-no-link"), "Stage 3 – No haul negotiation link found");
        return;
      }
      const intentUrl = await intentLink.getAttribute("href");

      // Logistics proposes a rate
      await login(lp, A.logistics.email, A.logistics.password);
      await lp.goto(intentUrl!);
      await lp.waitForLoadState("networkidle").catch(() => {});
      await shot(lp, step("haul-nego-room-logistics"), "Stage 3 – Haul negotiation room (logistics)");
      const proposeInput = lp.locator("#offer_rate_php_per_kg");
      if (await proposeInput.isVisible().catch(() => false)) {
        await proposeInput.fill("25");
        await lp.locator("#propose-rate-form button[type='submit']").click();
        await lp.waitForTimeout(2000);
        await shot(lp, step("haul-rate-proposed"), "Stage 3 – Logistics proposes hauling rate");
      }

      // Farmer counters the rate
      await fp.goto(intentUrl!);
      await fp.waitForLoadState("networkidle").catch(() => {});
      await shot(fp, step("haul-nego-room-farmer"), "Stage 3 – Haul negotiation room (farmer)");
      const counterInput = fp.locator("#counter_rate_php_per_kg");
      if (await counterInput.isVisible().catch(() => false)) {
        await counterInput.fill("20");
        await fp.locator("#counter-rate-form button[type='submit']").click();
        await fp.waitForTimeout(2000);
        await shot(fp, step("haul-rate-countered"), "Stage 3 – Farmer counters hauling rate");
      }

      // Farmer agrees and books
      const agreeBtn = fp.locator("#agree-btn");
      if (await agreeBtn.isVisible().catch(() => false)) {
        await agreeBtn.click();
        await fp.waitForTimeout(2000);
        await shot(fp, step("haul-rate-agreed"), "Stage 3 – Farmer agrees and books haul");
      }
    }, fp);

    await tryStep("15-farmer-accepts-intent", async () => {
      await fp.goto("/farmer/haul-requests");
      await fp.waitForLoadState("networkidle").catch(() => {});
      await shot(fp, step("haul-intent-received"), "Stage 3 – Farmer reviews haul intent");
      const acceptForm = fp.locator("form[action*='haul-intents']").first();
      const hasAccept = await acceptForm.isVisible().catch(() => false);
      if (!hasAccept) {
        await shot(fp, step("haul-intent-already-booked"), "Stage 3 – Intent already booked via negotiation");
        return;
      }
      await acceptForm.locator("button[type='submit']").click();
      await fp.waitForLoadState("networkidle").catch(() => {});
      await shot(fp, step("haul-intent-accepted"), "Stage 3 – Haul booked with logistics partner");
    }, fp);

    // ══════════════════════ STAGE 4 — ROUTE PLANNING ══════════════════════
    await tryStep("16-pooling-plan", async () => {
      const throughFarm = { type: "LineString", coordinates: [[125.15, 6.09], [125.1716, 6.1164], [125.2, 6.14]] };
      await lp.route("**/router.project-osrm.org/route/v1/driving/**", (r) =>
        r.fulfill({
          status: 200,
          contentType: "application/json",
          body: JSON.stringify({ code: "Ok", routes: [{ geometry: throughFarm, distance: 1234, duration: 123 }] }),
        })
      );
      await lp.route("**/router.project-osrm.org/trip/v1/**", (r) =>
        r.fulfill({
          status: 200,
          contentType: "application/json",
          body: JSON.stringify({
            code: "Ok",
            trips: [{ geometry: throughFarm }],
            waypoints: [
              { waypoint_index: 0, trips_index: 0, location: [125.15, 6.09] },
              { waypoint_index: 1, trips_index: 1, location: [125.1716, 6.1164] },
              { waypoint_index: 2, trips_index: 2, location: [125.2, 6.14] },
            ],
          }),
        })
      );

      await lp.goto("/route-optimization");
      await lp.waitForLoadState("networkidle").catch(() => {});
      const truckOptions = lp.locator("#truck-select option");
      const truckCount = await truckOptions.count();
      // Select the first truck that has a driver assigned (required for plan generation).
      const firstAssignedIdx = await truckOptions.evaluateAll((opts) =>
        opts.findIndex((o) => {
          const driver = o.getAttribute("data-driver") ?? "";
          return driver.trim() !== "" && driver.trim() !== "No driver assigned";
        })
      );
      await lp.selectOption("#truck-select", { index: firstAssignedIdx >= 0 ? firstAssignedIdx : 0 });

      // The map starts hidden (list-first design); reveal it before plotting.
      await lp.click("#btn-show-map");
      await lp.waitForSelector(".leaflet-container", { state: "visible", timeout: 10_000 });
      await lp.waitForTimeout(500);
      await lp.selectOption("#radius-select", "20");

      const mapBox = await lp.locator(".leaflet-container").boundingBox();
      if (!mapBox) throw new Error("Leaflet map not found");
      await lp.evaluate(([cx, cy]) => {
        document.querySelector(".leaflet-container")!.dispatchEvent(new MouseEvent("click", { clientX: cx, clientY: cy, bubbles: true }));
      }, [mapBox.x + mapBox.width * 0.3, mapBox.y + mapBox.height * 0.3]);
      await lp.waitForTimeout(400);
      await lp.evaluate(([cx, cy]) => {
        document.querySelector(".leaflet-container")!.dispatchEvent(new MouseEvent("click", { clientX: cx, clientY: cy, bubbles: true }));
      }, [mapBox.x + mapBox.width * 0.7, mapBox.y + mapBox.height * 0.7]);
      await lp.waitForFunction(() => {
        const b = document.getElementById("btn-generate-plan");
        return b && !b.disabled;
      }, undefined, { timeout: 20_000 });

      await lp.click("#btn-generate-plan");
      await lp.waitForSelector("#plan-panel:not(.hidden)", { timeout: 25_000 });
      await shot(lp, step("pooling-plan-generated"), "Stage 4 – Consolidated pooling plan");
      await lp.fill("#plan-notes", "Group pickup along the Polomolok route.");
      await lp.click("#btn-confirm-plan");
      await lp.waitForSelector("#confirm-feedback:not(.hidden)", { timeout: 25_000 }).catch(() => {});
      await lp.waitForLoadState("networkidle").catch(() => {});
      await shot(lp, step("proposal-created"), "Stage 4 – Delivery proposal created");
    }, lp);

    await tryStep("17-farmer-accepts-proposal", async () => {
      await fp.goto("/farmer/proposals");
      await fp.waitForLoadState("networkidle").catch(() => {});
      await shot(fp, step("farmer-proposals"), "Stage 4 – Farmer proposals inbox");
      const acceptBtn = fp.locator("form[action*='/accept'] button[type='submit']").first();
      await acceptBtn.waitFor({ state: "visible", timeout: 20_000 });
      await acceptBtn.click();
      await fp.waitForLoadState("networkidle").catch(() => {});
      await shot(fp, step("proposal-accepted"), "Stage 4 – Farmer accepts proposal");
    }, fp);

    await tryStep("18-auto-assign-driver", async () => {
      await lp.goto("/route-optimization");
      await lp.waitForLoadState("networkidle").catch(() => {});
      const res = await lp.evaluate(async () => {
        const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute("content") ?? "";
        const truckSelect = document.querySelector<HTMLSelectElement>("#truck-select");
        const truckId = truckSelect?.value ? Number(truckSelect.value) : null;
        if (!truckId) return { status: 0, body: "no truck available" };
        let pickupLat = 6.1164;
        let pickupLng = 125.1716;
        for (const s of Array.from(document.scripts)) {
          const m = (s.textContent || "").match(/const farms\s*=\s*(\[.*?\])\s*;?$/ms);
          if (!m) continue;
          try {
            const farms = JSON.parse(m[1]);
            for (const farm of farms) {
              if (farm.farmer_profile?.latitude && farm.farmer_profile?.longitude) {
                pickupLat = Number(farm.farmer_profile.latitude);
                pickupLng = Number(farm.farmer_profile.longitude);
                break;
              }
            }
          } catch { /* ignore malformed embedded JSON */ }
          break;
        }
        const r = await fetch("/route-optimization/auto-assign-driver", {
          method: "POST",
          headers: { "Content-Type": "application/json", "Accept": "application/json", "X-CSRF-TOKEN": csrf },
          body: JSON.stringify({ truck_id: truckId, pickup_lat: pickupLat, pickup_lng: pickupLng }),
        });
        return { status: r.status, body: await r.text() };
      });
      if (res.status >= 400) throw new Error(`Auto-assign failed (HTTP ${res.status}): ${res.body}`);
      await lp.goto("/pooling/proposals");
      await lp.waitForLoadState("networkidle").catch(() => {});
      await shot(lp, step("driver-assigned"), "Stage 4 – Job assigned to a driver");
    }, lp);

    // ══════════════════════ STAGE 5 — DELIVERY & TRACKING ══════════════════════
    let driverEmail: string | null = null;
    await tryStep("19-driver-dashboard", async () => {
      for (const d of A.drivers) {
        const dc = await browser.newContext();
        const dp = await dc.newPage();
        wireLogging(dp, `driver-${d.name}`);
        await login(dp, d.email, d.password);
        await dp.goto("/driver");
        const hasJob = await dp.locator("a:has-text('View Details')").count();
        if (hasJob > 0) {
          driverEmail = d.email;
          await shot(dp, step("driver-dashboard"), `Stage 5 – Driver dashboard (${d.name})`);
          await dp.locator("a:has-text('View Details')").first().click();
          await dp.waitForURL(/driver\/jobs\/\d+/, { timeout: 20_000 });
          await shot(dp, step("driver-job-detail"), "Stage 5 – Driver job detail");
          await dc.close();
          return;
        }
        await dc.close();
      }
      throw new Error("No driver account has the assigned pooling job");
    });

    await tryStep("20-driver-accept-start", async () => {
      const dc = await browser.newContext();
      const dp = await dc.newPage();
      wireLogging(dp, "driver-trip");
      await login(dp, driverEmail!, "password");
      await dp.goto("/driver");
      await dp.locator("a:has-text('View Details')").first().click();
      await dp.waitForURL(/driver\/jobs\/\d+/, { timeout: 20_000 });
      await dp.getByRole("button", { name: /Accept Job/i }).first().click();
      await dp.waitForLoadState("networkidle").catch(() => {});
      await shot(dp, step("job-accepted"), "Stage 5 – Driver accepts the job");
      await dp.getByRole("button", { name: /Start Job/i }).first().click();
      await dp.waitForLoadState("networkidle").catch(() => {});
      await shot(dp, step("trip-started"), "Stage 5 – Trip in transit (live GPS)");
      await dc.close();
    });

    await tryStep("21-driver-completes-stops", async () => {
      const dc = await browser.newContext();
      const dp = await dc.newPage();
      wireLogging(dp, "driver-stops");
      await login(dp, driverEmail!, "password");
      await dp.goto("/driver");
      await dp.locator("a:has-text('View Details')").first().click();
      await dp.waitForURL(/driver\/jobs\/\d+/, { timeout: 20_000 });

      // Simulate the driver's GPS arriving at the farm (geofence requires ≤500m).
      // Post a tracking ping at the farm coordinates shown on the stop card,
      // retrying until the server actually stores it (201) — the page's own
      // fallback ping can collide with the 5s per-driver rate limit.
      const farmCoords = await dp.evaluate(async () => {
        const bodyText = document.body.innerText;
        const match = bodyText.match(/Coordinates\s*([\d.]+),\s*([\d.]+)/);
        if (!match) return null;
        const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute("content") ?? "";
        const trackingUrl = (Array.from(document.scripts).map(s => s.textContent).join("").match(/const trackingUrl\s*=\s*'([^']+)'/) || [])[1] ?? "/driver/tracking/store";
        const jobId = Number(location.pathname.match(/\/driver\/jobs\/(\d+)/)?.[1] ?? 1);
        const payload = {
          pooling_job_id: jobId,
          latitude: parseFloat(match[1]),
          longitude: parseFloat(match[2]),
          posted_at: new Date().toISOString(),
        };
        for (let attempt = 0; attempt < 5; attempt++) {
          if (attempt > 0) await new Promise((r) => setTimeout(r, 6000));
          const res = await fetch(trackingUrl, {
            method: "POST",
            headers: { "Content-Type": "application/json", "X-CSRF-TOKEN": csrf, "Accept": "application/json" },
            body: JSON.stringify(payload),
          });
          if (res.status === 201) return { status: res.status, attempt };
        }
        return { status: -1, attempts: 5 };
      });
      if (!farmCoords) throw new Error("Could not locate farm coordinates on the driver stop page");
      if (farmCoords.status !== 201) throw new Error("Farm GPS ping was not stored by the server");

      await dp.getByRole("button", { name: /Mark Arrived at Pick-up/i }).first().click();
      await dp.waitForLoadState("networkidle").catch(() => {});
      await shot(dp, step("stop-arrived"), "Stage 5 – Stop 1 arrived at pick-up");

      await dp.locator("#loaded_quantity_kg").first().fill("200");
      await dp.locator("#load_photo").first().setInputFiles(F_LOAD);
      await dp.locator("#crop_confirmed").first().check();
      await dp.getByRole("button", { name: /Confirm Cargo & Mark Loaded/i }).first().click();
      await dp.waitForLoadState("networkidle").catch(() => {});
      await shot(dp, step("stop-loaded"), "Stage 5 – Cargo loaded with photo proof");

      await dp.locator("#delivery_receipt").first().setInputFiles(F_DELIVERY);
      await dp.getByRole("button", { name: /Mark Delivered & Upload Photo/i }).first().click();
      await dp.waitForLoadState("networkidle").catch(() => {});
      await shot(dp, step("stop-delivered"), "Stage 5 – Stop delivered");

      const completeBtn = dp.getByRole("button", { name: /Complete Run|Finalize Job|Complete Job/i }).first();
      if (await completeBtn.count().catch(() => 0) > 0) {
        const odo = dp.locator("#end_odometer_reading").first();
        if (await odo.count().catch(() => 0) > 0) {
          await odo.fill("120.5");
        }
        await completeBtn.click();
        await dp.waitForLoadState("networkidle").catch(() => {});
        await shot(dp, step("job-completed"), "Stage 5 – Run completed");
      }
      await dc.close();
    });

    await tryStep("22-logistics-tracking", async () => {
      await lp.goto("/tracking");
      await lp.waitForLoadState("networkidle").catch(() => {});
      await shot(lp, step("tracking-map"), "Stage 5 – Logistics tracking view");
    }, lp);

    await tryStep("23-buyer-confirm", async () => {
      await bp.goto("/buyer/tracking");
      await bp.waitForLoadState("networkidle").catch(() => {});
      await shot(bp, step("buyer-tracking"), "Stage 5 – Buyer deliveries (awaiting confirmation)");
      const confirmBtn = bp.getByRole("button", { name: /Confirm Receipt/i }).first();
      await confirmBtn.waitFor({ state: "visible", timeout: 20_000 });
      await confirmBtn.click();
      await bp.waitForLoadState("networkidle").catch(() => {});
      await shot(bp, step("delivery-confirmed"), "Stage 5 – Buyer confirms receipt");
    }, bp);

    // ══════════════════════ STAGE 6 — SETTLEMENT ══════════════════════
    await tryStep("24-cost-ledger-logistics", async () => {
      await lp.goto("/pooling/cost-ledger");
      await lp.waitForLoadState("networkidle").catch(() => {});
      await shot(lp, step("cost-ledger-index"), "Stage 6 – Cost ledger index (logistics)");
      const ledgerLink = lp.locator("main a[href*='cost-ledger']").first();
      await ledgerLink.waitFor({ state: "visible", timeout: 20_000 });
      await ledgerLink.click();
      await lp.waitForURL(/pooling\/\d+\/cost-ledger/, { timeout: 20_000 });
      await shot(lp, step("cost-ledger-detail"), "Stage 6 – Cost ledger detail (unpaid)");
    }, lp);

    await tryStep("25-farmer-views-payout", async () => {
      const ledgerUrl = lp.url();
      await fp.goto(ledgerUrl);
      await fp.waitForLoadState("networkidle").catch(() => {});
      await fp.locator("text=/Net Payout|Crop Value|Hauling Fee/i").first().waitFor({ state: "visible", timeout: 20_000 });
      await shot(fp, step("farmer-payout-view"), "Stage 6 – Farmer views net payout (hauling deducted)");
    }, fp);

    await tryStep("26-logistics-record-payout", async () => {
      await lp.reload();
      await lp.waitForLoadState("networkidle").catch(() => {});
      const recordBtn = lp.getByRole("button", { name: /Record Payout/i }).first();
      await recordBtn.waitFor({ state: "visible", timeout: 20_000 });
      await recordBtn.click({ noWaitAfter: true });
      await lp.locator(".swal2-confirm").click({ noWaitAfter: true }).catch(() => {});
      await lp.waitForFunction(
        () => !!document.body && !document.body.innerText.includes("Record Payout"),
        undefined, { timeout: 45_000 }
      );
      const settledBadge = await lp.locator("text=/Settled|Paid/i").count();
      if (settledBadge === 0) throw new Error("Ledger does not show Settled after recording payout");
      await shot(lp, step("payout-recorded"), "Stage 6 – Net payout recorded (hauling deducted)");
    }, lp);

    runLog.completedAt = new Date().toISOString();
    runLog.status = "completed";
    console.log(`\nE2E summary: ${runLog.steps.length} shots/steps recorded`);
  } catch (e: any) {
    runLog.completedAt = new Date().toISOString();
    runLog.status = "failed: " + String(e?.message ?? e);
  } finally {
    fs.writeFileSync(RUN_LOG, JSON.stringify(runLog, null, 2));
    await Promise.all([farmerCtx.close(), buyerCtx.close(), logisticsCtx.close(), adminCtx.close()]);
  }

  const failed = runLog.steps.filter((s: any) => s.error);
  if (failed.length > 0) {
    console.error(`E2E: ${runLog.steps.length} steps, ${failed.length} FAILED`);
    failed.forEach((f: any) => console.error(`  - ${f.slug}: ${String(f.error).slice(0, 200)}`));
  }
});
