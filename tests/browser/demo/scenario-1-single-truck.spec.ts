import { test, expect } from "@playwright/test";
import { runDemo, Scenario } from "./runner";
import { DEMO_LOGIN } from "./helpers";

const PASSWORD = DEMO_LOGIN.password;

test.describe.configure({ mode: "serial" });

/**
 * Scenario 1 — Single truck.
 * 3 coop farmers post (Mango 400kg, Banana 350kg, Pineapple 250kg = 1.0t);
 * the coop negotiates + finalizes all deals; one 3T truck carries the whole
 * load after Plan-all; the driver runs the route; the coop confirms receipt;
 * then the coop sells 1.0t to Robinsons on the 2T outbound truck and the
 * customer confirms the delivery through the public tracking link.
 */
test("Full demo — single-truck inbound + outbound to customer", async ({ browser }) => {
  test.setTimeout(1_700_000);

  const scenario: Scenario = {
    slug: "scenario-1-single-truck",
    label: "Single-truck day: 3 farms → 1 route → 1 customer delivery",
    farmers: [
      {
        email: "demo.outbound.farmer@harvesthaul.app",
        password: PASSWORD,
        name: "DEMO Farmer Rosa Dizon",
        crop: "Mango",
        variety: "Carabao",
        qty: "400",
        price: "55",
        rate: "2.50",
      },
      {
        email: "demo.outbound.farmer2@harvesthaul.app",
        password: PASSWORD,
        name: "DEMO Farmer Jocelyn Ramos",
        crop: "Banana",
        variety: "Cavendish",
        qty: "350",
        price: "28",
        rate: "2.50",
      },
      {
        email: "demo.outbound.farmer3@harvesthaul.app",
        password: PASSWORD,
        name: "DEMO Farmer Dante Mabuhay",
        crop: "Pineapple",
        variety: "MD2 Gold",
        qty: "250",
        price: "35",
        rate: "2.50",
      },
    ],
    planNotes: "E2E DEMO scenario 1 — one truck carries the full 1.0t load",
    expectedPlans: 1,
    outbound: {
      truckPlate: "DEMO-OB-001",
      driverEmail: "demo.outbound.driver@harvesthaul.app",
      driverName: "DEMO Driver Ely Guzman",
      lines: [
        { crop_type: "Banana", quantity_kg: "350", rate_per_kg: "30" },
        { crop_type: "Mango", quantity_kg: "400", rate_per_kg: "60" },
        { crop_type: "Pineapple", quantity_kg: "250", rate_per_kg: "40" },
      ],
      notes: "E2E DEMO scenario 1 — deliver to Robinsons before noon",
    },
  };

  const runLog = await runDemo(browser, scenario);

  expect(runLog.steps.length).toBeGreaterThan(25);
  expect(runLog.info.jobIds).toHaveLength(1);
  expect(runLog.info.orderStatus).toBe("completed");
  const truckFree = (runLog.info.trucksAtEnd as [string, string][]).every(([, s]) => s === "available");
  expect(truckFree).toBe(true);
}, { browser: "chromium" });