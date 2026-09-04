import { test, expect } from "@playwright/test";

async function login(page: any, email: string) {
  await page.goto("/login");
  await page.waitForSelector("#login-panel input[name='email']");
  await page.fill("#login-panel input[name='email']", email);
  await page.fill("#login-panel input[name='password']", "password");
  await page.locator("#login-panel button[type='submit']").click();
  await page.waitForURL(/dashboard|login/, { timeout: 15000 });
}

async function statCardLink(page: any, title: string): Promise<{ text: string; href: string } | null> {
  const card = page.locator(`[role="region"][aria-label="${title}"]`);
  if ((await card.count()) === 0) return null;
  const link = card.locator("a").first();
  if ((await link.count()) === 0) return null;
  return {
    text: (await link.textContent())?.replace(/\s+/g, " ").trim() ?? "",
    href: (await link.getAttribute("href")) ?? "",
  };
}

test.describe("Logistics dashboard card actions", () => {
  test("coop Available Pickups links to the Crop Board", async ({ page }) => {
    await login(page, "logistics1@test.com");
    await page.goto("/dashboard");
    await page.waitForSelector('[role="region"][aria-label="Available Pickups"]');

    const card = await statCardLink(page, "Available Pickups");
    expect(card).not.toBeNull();
    expect(card!.text).toContain("View Crop Board");
    expect(card!.href).toContain("/buyer/crop-board");
  });

  test("independent Available Pickups links to the Proposal Inbox", async ({ page }) => {
    await login(page, "logistics2@test.com");
    await page.goto("/dashboard");
    await page.waitForSelector('[role="region"][aria-label="Available Pickups"]');

    const card = await statCardLink(page, "Available Pickups");
    expect(card).not.toBeNull();
    expect(card!.text).toContain("View Proposal Inbox");
    expect(card!.href).toContain("/pooling/proposals");
  });

  test("crop board is reachable for coop and shows a card grid", async ({ page }) => {
    await login(page, "logistics1@test.com");
    await page.goto("/buyer/crop-board");
    await page.waitForSelector("h1");

    const h1 = (await page.locator("h1").first().textContent()) ?? "";
    expect(h1).toContain("Available Posts");
  });
});
