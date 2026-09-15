import { test, expect } from "@playwright/test";
import { execSync } from "child_process";
import * as fs from "fs";
import * as path from "path";
import { fileURLToPath } from "url";
import { guard, login, wireLogging, DEMO_LOGIN } from "./helpers";
import {
  Ctx,
  DemoFarm,
  OutboundLine,
  coopConfirmReceipts,
  coopOutboundProof,
  createOutboundOrder,
  customerTrackConfirm,
  dbFirstCol,
  dbRows,
  dispatchOutbound,
  farmersAccept,
  negotiateAll,
  planRouteAll,
  postHarvests,
  resolveDrivers,
  runInboundDriver,
  runOutboundDelivery,
  shotFor,
} from "./flows";

const PASSWORD = DEMO_LOGIN.password;
const E = DEMO_LOGIN;
const __dirname = path.dirname(fileURLToPath(import.meta.url));

test.describe.configure({ mode: "serial" });

/**
 * Scenario 3 — Combined day (the full farm→hub→customer story in ONE run).
 * Day 1: 3 farms post (Mango 400, Banana 350, Pineapple 250 = 1.0t); one truck
 *   fits it all → 1 rounded route → daily customer order #1 (partial, 800kg).
 * Day 2: 4 farms post (Mango 1000, Banana 800, Pineapple 700, Durian 600 =
 *   3.1t); no single truck fits it → Plan-all auto-splits onto 2 trucks →
 *   daily customer order #2 (2.3t).
 * After both days: one consolidated customer order #3 (1.0t) that combines the
 *   leftover Mango from both days + Day-2 Durian.
 * Incoming → Completed deliveries ends with 3 inbound routes; Customer Orders
 *   ends with 3 completed deliveries, each with a truck + driver.
 */
test("Combined demo — Day 1 single truck + Day 2 two-truck split + 3 customer deliveries", async ({ browser }) => {
  test.setTimeout(4_200_000);

  execSync("php artisan demo:full-seed --no-interaction", { stdio: "inherit" });

  const shotDir = path.join(__dirname, "screenshots", "scenario-3-combined");
  fs.rmSync(shotDir, { recursive: true, force: true });
  fs.mkdirSync(shotDir, { recursive: true });

  const runLog: any = {
    scenario: "Combined day: Day 1 → 1 truck; Day 2 → 2-truck split; 3 customer deliveries",
    startedAt: new Date().toISOString(),
    steps: [],
    errors: [],
    info: {},
  };
  const errLog = (...lines: (string | any)[]) => {
    runLog.errors.push(lines.map(String).join(" "));
    console.error(...lines);
  };

  let stepNo = 0;
  const step = (name: string) => `${String(++stepNo).padStart(2, "0")}-${name}`;
  const ctx: Ctx = { runLog, shotDir, step, errLog };

  const lpContext = await browser.newContext();
  const fpContext = await browser.newContext();
  const lp = guard(await lpContext.newPage());
  const fp = guard(await fpContext.newPage());
  wireLogging(lp, "lp", errLog);
  wireLogging(fp, "farmer", errLog);

  const day1Farmers: DemoFarm[] = [
    { email: E.farmer, password: PASSWORD, name: "DEMO Farmer Rosa Dizon", crop: "Mango", variety: "Carabao", qty: "400", price: "55", rate: "2.50" },
    { email: E.farmer2, password: PASSWORD, name: "DEMO Farmer Jocelyn Ramos", crop: "Banana", variety: "Cavendish", qty: "350", price: "28", rate: "2.50" },
    { email: E.farmer3, password: PASSWORD, name: "DEMO Farmer Dante Mabuhay", crop: "Pineapple", variety: "MD2 Gold", qty: "250", price: "35", rate: "2.50" },
  ];
  const day2Farmers: DemoFarm[] = [
    { email: E.farmer, password: PASSWORD, name: "DEMO Farmer Rosa Dizon", crop: "Mango", variety: "Carabao", qty: "1000", price: "55", rate: "2.50" },
    { email: E.farmer2, password: PASSWORD, name: "DEMO Farmer Jocelyn Ramos", crop: "Banana", variety: "Cavendish", qty: "800", price: "28", rate: "2.50" },
    { email: E.farmer3, password: PASSWORD, name: "DEMO Farmer Dante Mabuhay", crop: "Pineapple", variety: "MD2 Gold", qty: "700", price: "35", rate: "2.50" },
    { email: E.farmer4, password: PASSWORD, name: "DEMO Farmer Lito Salvador", crop: "Durian", variety: "Local", qty: "600", price: "120", rate: "2.50" },
  ];

  type OutboundPlan = {
    truckPlate: string;
    driverName: string;
    driverEmail: string;
    lines: OutboundLine[];
    notes: string;
  };

  const outbound1: OutboundPlan = {
    truckPlate: "DEMO-OB-001",
    driverName: "DEMO Driver Ely Guzman",
    driverEmail: E.driver,
    lines: [
      { crop_type: "Banana", quantity_kg: "350", rate_per_kg: "30" },
      { crop_type: "Pineapple", quantity_kg: "250", rate_per_kg: "40" },
      { crop_type: "Mango", quantity_kg: "200", rate_per_kg: "60" },
    ],
    notes: "E2E DEMO Day 1 — first customer delivery to Robinsons (leaving Mango 200kg at the hub)",
  };
  const outbound2: OutboundPlan = {
    truckPlate: "DEMO-IB-002",
    driverName: "DEMO Driver Rico Bartolome",
    driverEmail: E.driver2,
    lines: [
      { crop_type: "Mango", quantity_kg: "800", rate_per_kg: "60" },
      { crop_type: "Banana", quantity_kg: "800", rate_per_kg: "30" },
      { crop_type: "Pineapple", quantity_kg: "700", rate_per_kg: "40" },
    ],
    notes: "E2E DEMO Day 2 — second customer delivery to Robinsons (leaving Mango 200kg + Durian 600kg)",
  };
  const outbound3: OutboundPlan = {
    truckPlate: "DEMO-OB-001",
    driverName: "DEMO Driver Ely Guzman",
    driverEmail: E.driver,
    lines: [
      { crop_type: "Mango", quantity_kg: "400", rate_per_kg: "60" },
      { crop_type: "Durian", quantity_kg: "600", rate_per_kg: "125" },
    ],
    notes: "E2E DEMO after both days — consolidated order combining Day-1 + Day-2 hub leftovers to Robinsons",
  };

  async function inboundPhase(phaseTag: string, planNotes: string, farmers: DemoFarm[], expectedPlans: number) {
    ctx.phase = phaseTag;
    await postHarvests(ctx, farmers, fp);
    await negotiateAll(ctx, lp, fp, farmers);
    const { jobIds } = await planRouteAll(ctx, lp, farmers, farmers.length, planNotes, expectedPlans);
    if (jobIds.length !== expectedPlans) throw new Error(`${phaseTag}: got ${jobIds.length} inbound job(s), expected ${expectedPlans}`);
    await farmersAccept(ctx, fp, farmers);
    const drivers = resolveDrivers(expectedPlans);
    if (drivers.length !== expectedPlans) throw new Error(`${phaseTag}: expected ${expectedPlans} confirmed driver(s), got ${drivers.length}`);
    runLog.info[`${phaseTag}InboundDrivers`] = drivers;
    for (const d of drivers) {
      await runInboundDriver(ctx, browser, d.driverEmail);
    }
    await coopConfirmReceipts(ctx, lp, expectedPlans);
    return { jobIds, drivers };
  }

  async function outboundPhase(phaseTag: string, o: OutboundPlan) {
    ctx.phase = phaseTag;
    const orderId = await createOutboundOrder(ctx, lp, "1", o.lines, o.notes);
    const token = await dispatchOutbound(ctx, lp, orderId, { truckPlate: o.truckPlate, driverName: o.driverName });
    await runOutboundDelivery(ctx, browser, o.driverEmail);
    await customerTrackConfirm(ctx, browser, token);
    await coopOutboundProof(ctx, lp, orderId);
    const status = dbFirstCol(`SELECT status FROM outbound_orders WHERE id = ${orderId}`);
    if (status !== "completed") throw new Error(`Order #${orderId} ended as '${status}', expected 'completed'`);
    runLog.info.tokens = runLog.info.tokens ?? [];
    runLog.info.tokens.push(`/track-out/${token}`);
    return orderId;
  }

  const orders: number[] = [];

  try {
    // ── Stage 0: coop logistics account → dashboard ──────────────────────────
    await login(lp, E.coop, PASSWORD);

    // ── Day 1: 3 farms → 1 route → daily customer order ──────────────────────
    const day1 = await inboundPhase("day1", "E2E DEMO Day 1 — 1.0t fits a single truck, one route", day1Farmers, 1);
    orders.push(await outboundPhase("day1out", outbound1));
    runLog.info.totalInboundJobs = (runLog.info.totalInboundJobs ?? 0) + day1.jobIds.length;

    // ── Day 2: 4 farms → 2 trucks auto-split → daily customer order ──────────
    const day2 = await inboundPhase("day2", "E2E DEMO Day 2 — 3.1t can't fit one truck, auto-split onto 2", day2Farmers, 2);
    orders.push(await outboundPhase("day2out", outbound2));
    runLog.info.totalInboundJobs = (runLog.info.totalInboundJobs ?? 0) + day2.jobIds.length;

    // ── After both days: one consolidated customer order ─────────────────────
    orders.push(await outboundPhase("day3out", outbound3));

    // ── Final engine truth (from DB) ─────────────────────────────────────────
    const inboundCompleted = Number(dbFirstCol(
      "SELECT COUNT(DISTINCT pj.id) FROM pooling_jobs pj JOIN pooling_job_harvests pjh ON pjh.pooling_job_id = pj.id " +
      "WHERE pj.logistics_profile_id = 4 AND pj.leg_type = 'inbound' AND pj.status = 'completed'"
    ));
    if (inboundCompleted !== 3) throw new Error(`Expected 3 completed inbound routes, got ${inboundCompleted}`);

    const incomingCompleted = Number(dbFirstCol(
      "SELECT COUNT(DISTINCT pj.id) FROM pooling_jobs pj JOIN pooling_job_harvests pjh ON pjh.pooling_job_id = pj.id " +
      "JOIN harvests h ON h.id = pjh.harvest_id JOIN negotiations n ON n.harvest_id = h.id " +
      "WHERE pj.logistics_profile_id = 4 AND pj.status = 'completed' AND n.status = 'COMPLETED' " +
      "AND n.buyer_id = (SELECT id FROM users WHERE email = 'demo.outbound.coop@harvesthaul.app')"
    ));
    if (incomingCompleted !== 3) throw new Error(`Incoming completed deliveries show ${incomingCompleted} route(s), expected 3`);

    const doneOrders = Number(dbFirstCol("SELECT COUNT(*) FROM outbound_orders WHERE logistics_profile_id = 4 AND status = 'completed'"));
    if (doneOrders !== 3) throw new Error(`Expected 3 completed customer orders, got ${doneOrders}`);

    const trucks = dbRows("SELECT plate_number, status FROM trucks WHERE logistics_profile_id = 4 ORDER BY plate_number");
    const stuck = trucks.filter(([, s]) => s !== "available");
    if (stuck.length > 0) throw new Error(`Trucks not released back to 'available': ${JSON.stringify(stuck)}`);

    runLog.info.orders = orders;
    runLog.info.inboundCompleted = inboundCompleted;
    runLog.info.incomingCompleted = incomingCompleted;
    runLog.info.doneOrders = doneOrders;
    runLog.info.trucksAtEnd = trucks;

    // ── Final proof screenshots ──────────────────────────────────────────────
    ctx.phase = undefined;
    const finalShot = shotFor(ctx, lp);
    await lp.goto("/buyer/tracking");
    await lp.waitForLoadState("networkidle").catch(() => {});
    await finalShot("incoming-3-completed", "Incoming — 3 completed deliveries: Day 1 (1 route) + Day 2 (2 routes)");
    await lp.goto("/coop/outbound");
    await lp.waitForLoadState("networkidle").catch(() => {});
    await finalShot("customer-orders-3", "Customer Orders — 3 completed deliveries, each with truck + driver");

    // ── Log + noise-filter like the other scenarios ──────────────────────────
    runLog.finishedAt = new Date().toISOString();
    fs.writeFileSync(path.join(shotDir, "runLog.json"), JSON.stringify(runLog, null, 2));

    const mapNoise = /openstreetmap|tile|leaflet|Unexpected token '&'|openweathermap|driver\/tracking\/store|429 \(Too Many Requests\)|Cannot read properties of null \(reading 'innerText'\)/i;
    const realErrors = runLog.errors.filter((e: string) => !mapNoise.test(e));
    if (realErrors.length > 0) throw new Error(`Browser/console errors during the demo:\n${realErrors.join("\n")}`);

    expect(runLog.steps.length).toBeGreaterThan(70);
    expect(runLog.info.totalInboundJobs).toBe(3);

    console.log(`\n[PASS] Combined demo — ${runLog.steps.length} shots → ${shotDir}`);
    console.log(`       orders → #${orders.join(", #")}`);
    for (const t of runLog.info.tokens) console.log(`       link → http://127.0.0.1:8000${t}`);
  } catch (e: any) {
    runLog.failed = true;
    runLog.error = String(e?.message ?? e);
    fs.writeFileSync(path.join(shotDir, "runLog.json"), JSON.stringify(runLog, null, 2));
    throw e;
  } finally {
    await lpContext.close().catch(() => {});
    await fpContext.close().catch(() => {});
  }
}, { browser: "chromium" });