import { test, expect } from "@playwright/test";
import { runDemo, Scenario } from "./runner";
import { DEMO_LOGIN } from "./helpers";

const PASSWORD = DEMO_LOGIN.password;

test.describe.configure({ mode: "serial" });

/**
 * Scenario 2 — Multi-truck overflow.
 * 4 coop farmers post (Mango 1000kg, Banana 800kg, Pineapple 700kg,
 * Durian 600kg = 3.1t). No single inventory truck can carry all of it, so
 * Plan-all auto-splits the load across 2 trucks (3T + 2.5T) in one click.
 * Both drivers run their legs, the coop confirms both receipts, then the
 * coop sells 2.5t to Robinsons on the 3T truck and the customer confirms.
 */
test("Full demo — multi-truck auto-split + outbound to customer", async ({ browser }) => {
  test.setTimeout(1_900_000);

  const scenario: Scenario = {
    slug: "scenario-2-multi-truck",
    label: "Overflow day: 4 farms → 2 trucks auto-split → 1 customer delivery",
    farmers: [
      {
        email: "demo.outbound.farmer@harvesthaul.app",
        password: PASSWORD,
        name: "DEMO Farmer Rosa Dizon",
        crop: "Mango",
        variety: "Carabao",
        qty: "1000",
        price: "55",
        rate: "2.50",
      },
      {
        email: "demo.outbound.farmer2@harvesthaul.app",
        password: PASSWORD,
        name: "DEMO Farmer Jocelyn Ramos",
        crop: "Banana",
        variety: "Cavendish",
        qty: "800",
        price: "28",
        rate: "2.50",
      },
      {
        email: "demo.outbound.farmer3@harvesthaul.app",
        password: PASSWORD,
        name: "DEMO Farmer Dante Mabuhay",
        crop: "Pineapple",
        variety: "MD2 Gold",
        qty: "700",
        price: "35",
        rate: "2.50",
      },
      {
        email: "demo.outbound.farmer4@harvesthaul.app",
        password: PASSWORD,
        name: "DEMO Farmer Lito Salvador",
        crop: "Durian",
        variety: "Local",
        qty: "600",
        price: "120",
        rate: "2.50",
      },
    ],
    planNotes: "E2E DEMO scenario 2 — 3.1t load auto-splits across 2 trucks",
    expectedPlans: 2,
    outbound: {
      truckPlate: "DEMO-IB-002",
      driverEmail: "demo.outbound.driver@harvesthaul.app",
      driverName: "DEMO Driver Ely Guzman",
      lines: [
        { crop_type: "Mango", quantity_kg: "1000", rate_per_kg: "60" },
        { crop_type: "Banana", quantity_kg: "800", rate_per_kg: "30" },
        { crop_type: "Pineapple", quantity_kg: "700", rate_per_kg: "40" },
      ],
      notes: "E2E DEMO scenario 2 — deliver 2.5t to Robinsons before noon",
    },
  };

  const runLog = await runDemo(browser, scenario);

  expect(runLog.steps.length).toBeGreaterThan(30);
  expect(runLog.info.jobIds).toHaveLength(2);
  expect(runLog.info.inboundDrivers).toHaveLength(2);
  expect(runLog.info.orderStatus).toBe("completed");
  const truckFree = (runLog.info.trucksAtEnd as [string, string][]).every(([, s]) => s === "available");
  expect(truckFree).toBe(true);
}, { browser: "chromium" });