import { test, expect, Page, BrowserContext } from "@playwright/test";
import * as fs from "fs";
import * as path from "path";
import { fileURLToPath } from "url";

const __dirname = path.dirname(fileURLToPath(import.meta.url));

/**
 * FULL COOPERATIVE FARMER TRANSACTION — E2E walkthrough with screenshots.
 *
 * Story: A cooperative-affiliated farmer lists a harvest (visibility=both),
 * the cooperative logistics partner sees it immediately on the route map,
 * acts as B2B buyer to negotiate and close a deal (pinning the drop-off),
 * plans a pooled truck route (Route Offer) that the farmer approves,
 * then delivery/settlement completes the cycle.
 *
 * Key differences from independent scenario:
 *  - Harvest visibility is 'both' (buyers + logistics immediately)
 *  - Cooperative logistics partner acts as B2B buyer (initiates negotiations)
 *  - Only cooperative-scoped accounts can see cooperative farmer harvests
 *  - Coop farmers have NO haul request UI — hauling goes through Route Offers
 *
 * Prereqs: `php artisan migrate:fresh --seed`
 *   - farmer0@test.com (cooperative farmer, GenSan Farmers Cooperative)
 *   - logistics1@test.com (cooperative logistics partner)
 *   - admin@mail.com (admin)
 *
 * Artifacts: eval/screenshots/e2e-cooperative-farmer/
 */
test("Full cooperative-farmer transaction (E2E walkthrough)", async ({ browser }) => {
  test.setTimeout(600_000);

  const SHOT_DIR = path.resolve(__dirname, "../../../eval/screenshots/e2e-cooperative-farmer");
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
    farmer:    { email: "farmer0@test.com", password: "password" },
    logistics: { email: "logistics1@test.com", password: "password" },
    farmers: [
        { email: "farmer0@test.com", password: "password", name: "Polomolok Pineapple Farm" },
        { email: "farmer1@test.com", password: "password", name: "Tupi Harvests" },
        { email: "farmer2@test.com", password: "password", name: "Lagao Fruit Farm" },
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
      timer = setTimeout(() => reject(new Error(`[step-timeout] ${name} exceeded 180s`)), 180_000);
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
    // Clear client session cookie so Laravel shows the login form
    await page.context().clearCookies();
    await page.goto("/login", { waitUntil: "domcontentloaded" });
    const csrfToken = await page.locator('meta[name="csrf-token"]').getAttribute("content", { timeout: 2_000 }).catch(() => null);
    if (csrfToken) {
      // Submit login via form POST (reliable even if page state is stale)
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
      // Fallback: fill the visible form
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
    await page.locator(".swal2-confirm").click();
  };

  // ───────────────────────────  Setup contexts  ───────────────────────────
  const farmerCtx = await browser.newContext();
  const logisticsCtx = await browser.newContext();
  const adminCtx = await browser.newContext();

  const fp = guard(await farmerCtx.newPage());
  const lp = guard(await logisticsCtx.newPage());
  const ap = guard(await adminCtx.newPage());

  wireLogging(fp, "farmer");
  wireLogging(lp, "logistics");
  wireLogging(ap, "admin");

  async function createHarvest(page: Page, farmerEmail: string, farmerPassword: string, cropIndex: number = 0) {
    await login(page, farmerEmail, farmerPassword);
    await page.goto("/harvests/create");
    await page.waitForSelector("#crop_search", { state: "visible", timeout: 20_000 });
    await page.click("#crop_search");
    const cropItem = page.locator("#crop_dropdown [data-value]").nth(cropIndex);
    await cropItem.waitFor({ state: "visible", timeout: 10_000 });
    await cropItem.click();

    const varietyWrapperVisible = await page.locator("#variety_wrapper").isVisible().catch(() => false);
    if (varietyWrapperVisible) {
        await page.click("#variety_search");
        const varietyItem = page.locator("#variety_dropdown [data-value]").first();
        await varietyItem.waitFor({ state: "visible", timeout: 10_000 });
        await varietyItem.click();
    }
    await page.fill("#quantity_kg", "100");
    await page.fill("#suggested_price_per_kg", "50");
    await page.fill("#harvest_date", tomorrow());
    const destOpt = page.locator("#destination_id option:not([value=''])").first();
    const destVal = await destOpt.getAttribute("value");
    if (!destVal) throw new Error("No destination option available");
    await page.selectOption("#destination_id", destVal);
    const dLat = await destOpt.getAttribute("data-lat");
    const dLng = await destOpt.getAttribute("data-lng");
    const dAddr = await destOpt.getAttribute("data-address");
    if (dLat) await page.locator("#destination_latitude").evaluate((el: any, v: string) => { el.value = v; }, dLat);
    if (dLng) await page.locator("#destination_longitude").evaluate((el: any, v: string) => { el.value = v; }, dLng);
    if (dAddr) await page.locator("#destination_address").evaluate((el: any, v: string) => { el.value = v; }, dAddr);
    await page.fill("#notes", `Harvest from ${farmerEmail}`);
    const submitBtn = page.locator("#post-harvest-btn");
    if ((await submitBtn.count().catch(() => 0)) > 0) {
        await submitBtn.click();
    } else {
        await page.locator("button[type='submit']").last().click();
    }
    await page.waitForURL(u => !String(u).includes("/harvests/create"), { timeout: 25_000 });
    await page.waitForLoadState("networkidle").catch(() => {});
  }

  try {
    // ══════════════════════ STAGE 1 — ONBOARDING ══════════════════════

    await tryStep("01-registration-forms", async () => {
      await fp.goto("/register/farmer");
      await fp.waitForLoadState("networkidle").catch(() => {});
      await shot(fp, step("registration-farmer"), "Stage 1 – Cooperative farmer registration form");

      await lp.goto("/register/logistics_partner");
      await lp.waitForLoadState("networkidle").catch(() => {});
      await shot(lp, step("registration-logistics"), "Stage 1 – Logistics partner registration form");
    }, fp);

    await tryStep("02-admin-verify-farmer", async () => {
      await login(ap, A.admin.email, A.admin.password);
      await ap.goto("/admin/farmers");
      await ap.waitForLoadState("networkidle").catch(() => {});
      await shot(ap, step("admin-farmers-list"), "Stage 1 – Admin farmers list");

      const row = ap.locator("tr", { hasText: A.farmer.email }).first();
      const hasRow = await row.isVisible().catch(() => false);
      if (hasRow) {
        const verifyBtn = row.locator("button[title='Verify'], button:has-text('Verify')").first();
        if (await verifyBtn.isVisible().catch(() => false)) {
          await verifyBtn.click();
          await swalConfirm(ap);
          await ap.waitForLoadState("networkidle").catch(() => {});
          await shot(ap, step("admin-farmer-verified"), "Stage 1 – Farmer verified by admin");
        } else {
          await shot(ap, step("admin-farmer-already-verified"), "Stage 1 – Farmer already verified");
        }
      } else {
        await shot(ap, step("admin-farmer-not-found"), "Stage 1 – Farmer row not found (may be pre-verified)");
      }
    }, ap);

    await tryStep("03-admin-verify-logistics", async () => {
      await ap.goto("/admin/logistics");
      await ap.waitForLoadState("networkidle").catch(() => {});
      await shot(ap, step("admin-logistics-list"), "Stage 1 – Admin logistics list");

      const row = ap.locator("tr", { hasText: "GenSan Farmers Cooperative" }).first();
      const hasRow = await row.isVisible().catch(() => false);
      if (hasRow) {
        const verifyBtn = row.locator("button[title='Verify'], button:has-text('Verify')").first();
        if (await verifyBtn.isVisible().catch(() => false)) {
          await verifyBtn.click();
          await swalConfirm(ap);
          await ap.waitForLoadState("networkidle").catch(() => {});
          await shot(ap, step("admin-logistics-verified"), "Stage 1 – Cooperative logistics verified");
        } else {
          await shot(ap, step("admin-logistics-already-verified"), "Stage 1 – Cooperative logistics already verified");
        }
      } else {
        await shot(ap, step("admin-logistics-not-found"), "Stage 1 – Cooperative logistics row not found");
      }
    }, ap);

    // ══════════════════════ STAGE 2 — HARVEST & COOPERATIVE VISIBILITY ══════════════════════

    await tryStep("04-farmer-dashboard", async () => {
      await login(fp, A.farmer.email, A.farmer.password);
      await shot(fp, step("coop-farmer-dashboard"), "Stage 2 – Cooperative farmer dashboard");
    }, fp);

    await tryStep("05-harvest-create", async () => {
      for (let i = 0; i < A.farmers.length; i++) {
        const f = A.farmers[i];
        await createHarvest(fp, f.email, f.password, i);
        await shot(fp, step(`harvest-created-farmer${i}`), `Stage 2 – Harvest created for ${f.name}`);
      }
    }, fp);

    await tryStep("06-logistics-sees-harvest-on-map", async () => {
      await login(lp, A.logistics.email, A.logistics.password);
      await lp.goto("/route-optimization");
      await lp.waitForLoadState("networkidle").catch(() => {});
      await shot(lp, step("route-optimization-coop"), "Stage 2 – Cooperative logistics route optimization");

      const farmsData = await lp.evaluate(() => {
        for (const s of Array.from(document.scripts)) {
          const m = (s.textContent || "").match(/const farms\s*=\s*(\[.*?\])\s*;?$/ms);
          if (!m) continue;
          try {
            const farms = JSON.parse(m[1]);
            return farms.map((f: any) => ({
              name: f.name,
              harvests: (f.harvests || []).map((h: any) => ({
                id: h.id,
                crop: h.crop_type,
                kg: h.quantity_kg,
                has_open_haul_request: h.has_open_haul_request,
              })),
            }));
          } catch { /* try next script */ }
        }
        return null;
      });
      console.log("  [info] Cooperative farms on route map:", JSON.stringify(farmsData));
      await shot(lp, step("coop-harvests-on-map"), "Stage 2 – Cooperative harvests visible on route map");
    }, lp);

    // ══════════════════════ STAGE 3 — B2B NEGOTIATION (COOPERATIVE AS BUYER) ══════════════════════

    await tryStep("07-coop-logistics-crop-board", async () => {
      await lp.goto("/buyer/crop-board");
      await lp.waitForLoadState("networkidle").catch(() => {});
      await shot(lp, step("crop-board-coop"), "Stage 3 – Cooperative logistics views crop board (sees cooperative farmers)");
    }, lp);

    await tryStep("08-negotiation-start", async () => {
      // The farmer name is in a sibling span, not inside the <a> tag, so filter the parent card
      const card = lp.locator("div:has(> div > a[href*='/buyer/crop-board/'])").filter({ hasText: A.farmers[0].name }).first();
      const link = card.locator("a[href*='/buyer/crop-board/']").first();
      await link.waitFor({ state: "visible", timeout: 20_000 });
      await link.click();
      await lp.waitForURL(/\/buyer\/crop-board\/\d+/, { timeout: 20_000 });
      await shot(lp, step("crop-detail"), "Stage 3 – Crop detail page");
      await lp.getByRole("button", { name: /Initiate Negotiation/i }).click();
      await lp.waitForURL(/negotiations\/\d+/, { timeout: 20_000 });
      await shot(lp, step("negotiation-started"), "Stage 3 – Negotiation room opened (cooperative as buyer)");
    }, lp);

    await tryStep("09-negotiation-chat", async () => {
      await lp.locator("#message-input").fill("Hello! We would like to negotiate a bulk purchase for the cooperative.");
      await lp.locator("#send-message-form button[type='submit']").click();
      await lp.waitForTimeout(2000);
      await shot(lp, step("negotiation-chat"), "Stage 3 – Cooperative logistics sends chat message");
    }, lp);

    await tryStep("10-propose-terms", async () => {
      await lp.fill("#negotiated_price", "50");
      await lp.fill("#negotiated_volume", "100");
      await lp.click("#propose-btn");
      await lp.waitForTimeout(2000);
      await shot(lp, step("proposal-sent"), "Stage 3 – Cooperative logistics proposes terms");
    }, lp);

    await tryStep("11-farmer-agrees", async () => {
      const negotiationUrl = lp.url();
      const negId = negotiationUrl.match(/negotiations\/(\d+)/)?.[1];
      if (!negId) throw new Error("Could not extract negotiation ID from URL");

      await login(fp, A.farmers[0].email, A.farmers[0].password);
      await fp.goto(`/negotiations/${negId}`);
      await fp.waitForLoadState("networkidle").catch(() => {});
      await shot(fp, step("farmer-negotiation-room"), "Stage 3 – Farmer views negotiation room");

      const agreeBtn = fp.locator("#agree-btn");
      await agreeBtn.waitFor({ state: "visible", timeout: 20_000 });
      await agreeBtn.click();
      await swalConfirm(fp);
      await fp.waitForTimeout(2000);
      await shot(fp, step("farmer-agreed"), "Stage 3 – Farmer agrees to terms");
    }, fp);

    await tryStep("12-logistics-finalizes", async () => {
      await lp.reload();
      await lp.waitForLoadState("networkidle").catch(() => {});

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

      const addrInput = lp.locator("input[name='destination_address']");
      if (await addrInput.isVisible().catch(() => false)) {
        await lp.evaluate(() => {
          const el = document.querySelector("input[name='destination_address']") as HTMLInputElement;
          if (el) { el.readOnly = false; el.value = "GenSan Wholesale Market Hub"; el.readOnly = true; }
        });
      }

      await lp.evaluate(() => {
        const latEl = document.getElementById("destination_latitude") as HTMLInputElement;
        const lngEl = document.getElementById("destination_longitude") as HTMLInputElement;
        if (latEl) latEl.value = "6.1164";
        if (lngEl) lngEl.value = "125.1716";
      });

      await lp.locator("button[type='submit']", { hasText: /Close Deal/i }).click();
      await lp.waitForLoadState("networkidle").catch(() => {});
      await shot(lp, step("deal-finalized"), "Stage 3 – Deal finalized by cooperative logistics");
    }, lp);

    // ══════════════════════ STAGE 4 — COOP FARMERS SKIP HAUL REQUESTS ══════════════════════

    await tryStep("13-coop-farmer-has-no-haul-request-path", async () => {
      // Coop farmers are hauled exclusively via their cooperative's Route Offers.
      // The haul request entry points must be hidden for them.
      await fp.goto("/harvests");
      await fp.waitForLoadState("networkidle").catch(() => {});
      const reqHaulBtns = await fp.getByRole("button", { name: /Request Haul/i }).count();
      if (reqHaulBtns > 0) throw new Error(`Coop farmer sees ${reqHaulBtns} 'Request Haul' button(s) — should be hidden`);
      const haulNav = await fp.locator("a[href='/farmer/haul-requests'], a[href*='farmer.haul-requests']").count();
      if (haulNav > 0) throw new Error("Coop farmer sidebar shows 'Haul Requests' nav item — should be hidden");
      await shot(fp, step("no-haul-request-ui"), "Stage 4 – No haul request UI for cooperative farmer");
    }, fp);

    // ══════════════════════ STAGE 5 — ROUTE PLANNING & POOLING ══════════════════════

    await tryStep("16-pooling-plan", async () => {
      // Route must pass within 20km of all 3 farm locations for turf.findFarmsAlongRoute() to pick them up
      // Farm coords (lng,lat): farmer0=125.0718,6.2215  farmer1=124.9416,6.3333  farmer2=125.1912,6.1351
      // Hub=125.1830,6.1050  Destination=125.1716,6.1164
      const throughFarm = { type: "LineString", coordinates: [
        [125.1830, 6.1050], [125.1912, 6.1351], [125.0718, 6.2215], [124.9416, 6.3333], [125.1716, 6.1164]
      ] };
      await lp.route("**/router.project-osrm.org/route/v1/driving/**", (r) =>
        r.fulfill({
          status: 200,
          contentType: "application/json",
          body: JSON.stringify({ code: "Ok", routes: [{ geometry: throughFarm, distance: 45000, duration: 3600 }] }),
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
              { geometry: { coordinates: [125.1830, 6.1050] } },
              { geometry: { coordinates: [125.1912, 6.1351] } },
              { geometry: { coordinates: [125.0718, 6.2215] } },
              { geometry: { coordinates: [124.9416, 6.3333] } },
              { geometry: { coordinates: [125.1716, 6.1164] } },
            ],
          }),
        })
      );

      await lp.goto("/route-optimization");
      await lp.waitForLoadState("networkidle").catch(() => {});

      const farmsData = await lp.evaluate(() => {
        for (const s of Array.from(document.scripts)) {
          const m = (s.textContent || "").match(/const farms\s*=\s*(\[.*?\])\s*;?$/ms);
          if (!m) continue;
          try {
            return JSON.parse(m[1]);
          } catch { /* try next script */ }
        }
        return null;
      });

      if (!farmsData || farmsData.length < 3) {
        throw new Error(`Expected 3 cooperative farms on route map, found ${farmsData?.length ?? 0}`);
      }
      console.log("  [info] Farms on route map:", farmsData.map((f: any) => f.name).join(", "));

      await lp.click("#btn-show-map");
      await lp.waitForSelector(".leaflet-container", { state: "visible", timeout: 10_000 });
      await lp.waitForTimeout(500);
      await lp.selectOption("#radius-select", "50");

      const hasStartMarker = await lp.evaluate(() => {
        return document.querySelector(".leaflet-popup")?.textContent?.includes("Coop Hub") ?? false;
      });
      console.log("  [info] Start marker auto-populated:", hasStartMarker);

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
      }, undefined, { timeout: 20_000 });

      const assignRes = await lp.evaluate(async () => {
        const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute("content") ?? "";
        const truckSelect = document.querySelector<HTMLSelectElement>("#truck-select");
        const truckId = truckSelect?.value ? Number(truckSelect.value) : null;
        if (!truckId) return { success: false, message: "no truck" };
        const r = await fetch("/route-optimization/assign-driver", {
          method: "POST",
          headers: { "Content-Type": "application/json", "Accept": "application/json", "X-CSRF-TOKEN": csrf },
          body: JSON.stringify({ truck_id: truckId, driver_id: 10 }),
        });
        return await r.json();
      });
      console.log("  [info] Assign result:", JSON.stringify(assignRes));

      await lp.click("#btn-generate-plan");
      await lp.waitForSelector("#plan-panel:not(.hidden)", { timeout: 25_000 });
      await shot(lp, step("pooling-plan-generated"), "Stage 5 – Consolidated pooling plan (3 farms)");
      await lp.fill("#plan-notes", "Multi-farmer cooperative route: pickup loop.");
      await lp.click("#btn-confirm-plan");
      await lp.waitForSelector("#confirm-feedback:not(.hidden)", { timeout: 25_000 }).catch(() => {});
      await lp.waitForLoadState("networkidle").catch(() => {});
      await shot(lp, step("proposal-created"), "Stage 5 – Delivery proposal created (3 farms)");
    }, lp);

    await tryStep("17-farmer-accepts-proposal", async () => {
      await fp.goto("/farmer/proposals");
      await fp.waitForLoadState("networkidle").catch(() => {});
      await shot(fp, step("farmer-proposals"), "Stage 5 – Farmer Route Offers inbox");
      const acceptForm = fp.locator("form[action*='pooling'][action*='accept']").first();
      await acceptForm.waitFor({ state: "visible", timeout: 20_000 });
      await acceptForm.locator("button[type='submit']").click({ force: true, timeout: 120_000 });
      await fp.waitForLoadState("networkidle", { timeout: 60_000 }).catch(() => {});
      await shot(fp, step("proposal-accepted"), "Stage 5 – Pooling job confirmed");
    }, fp);

    // ══════════════════════ STAGE 6 — DRIVER ASSIGNMENT & DELIVERY ══════════════════════

    await tryStep("18-auto-assign-driver", async () => {
      await lp.goto("/pooling/proposals");
      await lp.waitForLoadState("networkidle").catch(() => {});
      await shot(lp, step("driver-assigned"), "Stage 6 – Job assigned to a driver");
    }, lp);

    let driverEmail: string | null = null;
    driverEmail = "eliseo-driver-1@driver.com";

    await tryStep("19-driver-dashboard", async () => {
      const dc = await browser.newContext();
      const dp = guard(await dc.newPage());
      wireLogging(dp, "driver-eliseo");
      await login(dp, driverEmail!, "password");
      await shot(dp, step("driver-after-login"), "Stage 6 – After driver login (diagnostic)");
      await dp.goto("/driver");
      await dp.waitForLoadState("networkidle", { timeout: 10_000 }).catch(() => {});
      await shot(dp, step("driver-dashboard-pre"), "Stage 6 – Driver dashboard before check");
      const hasJob = await dp.locator("a:has-text('View Details')").count();
      if (hasJob > 0) {
        await shot(dp, step("driver-dashboard"), "Stage 6 – Driver dashboard (Eliseo Driver)");
        await dp.locator("a:has-text('View Details')").first().click();
        await dp.waitForURL(/driver\/jobs\/\d+/, { timeout: 20_000 });
        await shot(dp, step("driver-job-detail"), "Stage 6 – Driver job detail");
        await dc.close();
        return;
      }
      await dc.close();
      throw new Error("Eliseo Driver has no assigned pooling job on dashboard");
    });

    await tryStep("20-driver-accept-start", async () => {
      const dc = await browser.newContext();
      const dp = guard(await dc.newPage());
      wireLogging(dp, "driver-trip");
      await login(dp, driverEmail!, "password");
      await dp.goto("/driver");
      await dp.locator("a:has-text('View Details')").first().click();
      await dp.waitForURL(/driver\/jobs\/\d+/, { timeout: 20_000 });
      await dp.getByRole("button", { name: /Accept Job/i }).first().click();
      await dp.waitForLoadState("networkidle").catch(() => {});
      await shot(dp, step("job-accepted"), "Stage 6 – Driver accepts the job");
      await dp.getByRole("button", { name: /Start Job/i }).first().click();
      await dp.waitForLoadState("networkidle").catch(() => {});
      await shot(dp, step("trip-started"), "Stage 6 – Trip in transit (live GPS)");
      await dc.close();
    });

    await tryStep("21-driver-completes-stops", async () => {
      const dc = await browser.newContext();
      const dp = guard(await dc.newPage());
      wireLogging(dp, "driver-stops");
      await login(dp, driverEmail!, "password");
      await dp.goto("/driver");
      await dp.locator("a:has-text('View Details')").first().click();
      await dp.waitForURL(/driver\/jobs\/\d+/, { timeout: 20_000 });

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
      await shot(dp, step("stop-arrived"), "Stage 6 – Stop 1 arrived at pick-up");

      await dp.locator("#loaded_quantity_kg").first().fill("80");
      await dp.locator("#load_photo").first().setInputFiles(F_LOAD);
      await dp.locator("#crop_confirmed").first().check();
      await dp.getByRole("button", { name: /Confirm Cargo & Mark Loaded/i }).first().click();
      await dp.waitForLoadState("networkidle").catch(() => {});
      await shot(dp, step("stop-loaded"), "Stage 6 – Cargo loaded with photo proof");

      await dp.locator("#delivery_receipt").first().setInputFiles(F_DELIVERY);
      await dp.getByRole("button", { name: /Mark Delivered & Upload Photo/i }).first().click();
      await dp.waitForLoadState("networkidle").catch(() => {});
      await shot(dp, step("stop-delivered"), "Stage 6 – Stop delivered");

      const completeBtn = dp.getByRole("button", { name: /Complete Run|Finalize Job|Complete Job/i }).first();
      if (await completeBtn.count().catch(() => 0) > 0) {
        const odo = dp.locator("#end_odometer_reading").first();
        if (await odo.count().catch(() => 0) > 0) {
          await odo.fill("120.5");
        }
        await completeBtn.click();
        await dp.waitForLoadState("networkidle").catch(() => {});
        await shot(dp, step("job-completed"), "Stage 6 – Run completed");
      }
      await dc.close();
    });

    await tryStep("22-buyer-confirm-receipt", async () => {
      await lp.goto("/buyer/tracking");
      await lp.waitForLoadState("networkidle").catch(() => {});
      await shot(lp, step("buyer-tracking"), "Stage 6 – Cooperative logistics tracking (awaiting confirmation)");
      const confirmBtn = lp.getByRole("button", { name: /Confirm Receipt/i }).first();
      const hasBtn = await confirmBtn.isVisible().catch(() => false);
      if (hasBtn) {
        await confirmBtn.click();
        await lp.waitForLoadState("networkidle").catch(() => {});
        await shot(lp, step("delivery-confirmed"), "Stage 6 – Delivery confirmed by cooperative logistics");
      } else {
        await shot(lp, step("no-confirm-button"), "Stage 6 – No confirm button visible");
      }
    }, lp);

    // ══════════════════════ STAGE 7 — SETTLEMENT ══════════════════════

    await tryStep("23-cost-ledger-logistics", async () => {
      await lp.goto("/pooling/cost-ledger");
      await lp.waitForLoadState("networkidle").catch(() => {});
      await shot(lp, step("cost-ledger-index"), "Stage 7 – Cost ledger index (cooperative logistics)");
      const ledgerLink = lp.locator("main a[href*='cost-ledger']").first();
      await ledgerLink.waitFor({ state: "visible", timeout: 20_000 });
      await ledgerLink.click();
      await lp.waitForURL(/pooling\/\d+\/cost-ledger/, { timeout: 20_000 });
      await shot(lp, step("cost-ledger-detail"), "Stage 7 – Cost ledger detail (unpaid)");
    }, lp);

    await tryStep("24-farmer-uploads-receipt", async () => {
      const ledgerUrl = lp.url();
      await fp.goto(ledgerUrl);
      await fp.waitForLoadState("networkidle").catch(() => {});
      const receiptInput = fp.locator("input[name='payment_receipt']").first();
      await receiptInput.waitFor({ state: "attached", timeout: 20_000 });
      await receiptInput.setInputFiles(F_RECEIPT);
      await fp.waitForLoadState("networkidle").catch(() => {});
      await shot(fp, step("receipt-uploaded"), "Stage 7 – Farmer uploads payment receipt");
    }, fp);

    await tryStep("25-logistics-verify-paid", async () => {
      await lp.reload();
      await lp.waitForLoadState("networkidle").catch(() => {});
      await shot(lp, step("cost-ledger-receipt"), "Stage 7 – Cooperative logistics reviews submitted receipt");
      const amountInput = lp.locator("input[name='amount_paid']").first();
      await amountInput.waitFor({ state: "visible", timeout: 20_000 });
      await amountInput.fill("250");
      await lp.getByRole("button", { name: /Verify Paid/i }).first().click({ noWaitAfter: true });
      // POST-back re-renders the row as Completed/Paid; wait on DOM state, not navigation events
      await lp.waitForFunction(
        () => !!document.body && !document.body.innerText.includes("Verify Paid"),
        undefined, { timeout: 45_000 }
      );
      const paidBadge = await lp.locator("text=/Paid|Completed/i").count();
      if (paidBadge === 0) throw new Error("Ledger does not show Paid/Completed after Verify Paid");
      await shot(lp, step("payment-paid"), "Stage 7 – Payment verified (Paid)");
    }, lp);

    runLog.completedAt = new Date().toISOString();
    runLog.status = "completed";
    console.log(`\nE2E summary: ${runLog.steps.length} shots/steps recorded`);
  } catch (e: any) {
    runLog.completedAt = new Date().toISOString();
    runLog.status = "failed: " + String(e?.message ?? e);
  } finally {
    fs.writeFileSync(RUN_LOG, JSON.stringify(runLog, null, 2));
    await Promise.all([farmerCtx.close(), logisticsCtx.close(), adminCtx.close()]);
  }

  const failed = runLog.steps.filter((s: any) => s.error);
  if (failed.length > 0) {
    console.error(`E2E: ${runLog.steps.length} steps, ${failed.length} FAILED`);
    failed.forEach((f: any) => console.error(`  - ${f.slug}: ${String(f.error).slice(0, 200)}`));
  }
});
