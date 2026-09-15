import { Browser } from "@playwright/test";
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
} from "./flows";

const __dirname = path.dirname(fileURLToPath(import.meta.url));

export type Scenario = {
  slug: string;
  label: string;
  farmers: DemoFarm[];
  planNotes: string;
  expectedPlans: number;
  outbound: {
    truckPlate: string;
    driverEmail: string;
    driverName: string;
    lines: OutboundLine[];
    notes: string;
  };
};

export async function runDemo(browser: Browser, scenario: Scenario): Promise<Record<string, any>> {
  console.log(`\n==== DEMO SCENARIO: ${scenario.label} ====`);
  execSync("php artisan demo:full-seed --no-interaction", { stdio: "inherit" });

  const shotDir = path.join(__dirname, "screenshots", scenario.slug);
  fs.rmSync(shotDir, { recursive: true, force: true });
  fs.mkdirSync(shotDir, { recursive: true });

  const runLog: any = { scenario: scenario.label, startedAt: new Date().toISOString(), steps: [], errors: [], info: {} };
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

  try {
    // ── Stage 0: coop logistics account → dashboard ──────────────────────────
    await login(lp, DEMO_LOGIN.coop, DEMO_LOGIN.password);

    // ── Stage 1: farmers post their harvests ─────────────────────────────────
    await postHarvests(ctx, scenario.farmers, fp);
    runLog.info.harvestCount = scenario.farmers.length;

    // ── Stage 2: cooperative negotiates + finalizes every deal ───────────────
    const negIds = await negotiateAll(ctx, lp, fp, scenario.farmers);
    runLog.info.negotiationIds = negIds;

    // ── Stage 3: route planner auto-splits the load across trucks ────────────
    const { planAllData, jobIds } = await planRouteAll(ctx, lp, scenario.farmers, scenario.farmers.length, scenario.planNotes, scenario.expectedPlans);
    if (jobIds.length !== scenario.expectedPlans) {
      throw new Error(`confirmBatch returned ${jobIds.length} job(s), expected ${scenario.expectedPlans}`);
    }
    runLog.info.jobIds = jobIds;

    // ── Stage 4: farmers accept their Route Offers ───────────────────────────
    await farmersAccept(ctx, fp, scenario.farmers);

    // ── Stage 5: drivers run the inbound routes ──────────────────────────────
    const drivers = resolveDrivers(scenario.expectedPlans);
    if (drivers.length !== scenario.expectedPlans) {
      throw new Error(`Expected ${scenario.expectedPlans} confirmed pooling job(s) with a driver, got ${drivers.length}`);
    }
    runLog.info.inboundDrivers = drivers;
    for (const d of drivers) {
      await runInboundDriver(ctx, browser, d.driverEmail);
    }

    // ── Stage 6: coop confirms every inbound receipt ─────────────────────────
    await coopConfirmReceipts(ctx, lp, scenario.expectedPlans);

    // ── Stage 7: outbound customer order + dispatch + delivery + confirm ─────
    const orderId = await createOutboundOrder(ctx, lp, "1", scenario.outbound.lines, scenario.outbound.notes);
    runLog.info.outboundOrderId = orderId;

    const token = await dispatchOutbound(ctx, lp, orderId, {
      truckPlate: scenario.outbound.truckPlate,
      driverName: scenario.outbound.driverName,
    });
    runLog.info.trackingToken = token;

    await runOutboundDelivery(ctx, browser, scenario.outbound.driverEmail);

    await customerTrackConfirm(ctx, browser, token);
    await coopOutboundProof(ctx, lp, orderId);

    // ── Final verification (engine truth, from DB) ───────────────────────────
    const status = dbFirstCol(`SELECT status FROM outbound_orders WHERE id = ${orderId}`);
    if (status !== "completed") throw new Error(`Outbound order #${orderId} ended as '${status}', expected 'completed'`);

    const doneJobs = Number(dbFirstCol(`SELECT COUNT(*) FROM pooling_jobs WHERE logistics_profile_id = 4 AND status = 'completed'`));
    if (doneJobs < scenario.expectedPlans) throw new Error(`Only ${doneJobs}/${scenario.expectedPlans} inbound route(s) reached 'completed'`);

    const trucks = dbRows(`SELECT plate_number, status FROM trucks WHERE logistics_profile_id = 4 ORDER BY plate_number`);
    const notFree = trucks.filter(([p, s]) => s !== "available");
    if (notFree.length > 0) throw new Error(`Trucks not released back to 'available': ${JSON.stringify(notFree)}`);

    runLog.info.trucksAtEnd = trucks;
    runLog.info.orderStatus = status;
    runLog.info.trackingLink = `/track-out/${token}`;
    runLog.finishedAt = new Date().toISOString();

    // ── Screenshot + log artifacts ───────────────────────────────────────────
    fs.writeFileSync(path.join(shotDir, "runLog.json"), JSON.stringify(runLog, null, 2));

    const mapNoise = /openstreetmap|tile|leaflet|Unexpected token '&'|openweathermap|driver\/tracking\/store|429 \(Too Many Requests\)|Cannot read properties of null \(reading 'innerText'\)/i;
    const realErrors = runLog.errors.filter((e: string) => !mapNoise.test(e));
    if (realErrors.length > 0) {
      throw new Error(`Browser/console errors during the demo:\n${realErrors.join("\n")}`);
    }

    console.log(`\n[PASS] ${scenario.label} — ${runLog.steps.length} shots → ${shotDir}`);
    console.log(`       tracking link → http://127.0.0.1:8000/track-out/${token}`);
  } catch (e) {
    runLog.failed = true;
    runLog.error = String((e as Error)?.message ?? e);
    throw e;
  } finally {
    runLog.finishedAt = runLog.finishedAt ?? new Date().toISOString();
    runLog.success = !runLog.failed;
    fs.writeFileSync(path.join(shotDir, "runLog.json"), JSON.stringify(runLog, null, 2));
    await lpContext.close().catch(() => {});
    await fpContext.close().catch(() => {});
  }

  return runLog;
}