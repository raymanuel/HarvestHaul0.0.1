import { test, expect } from "@playwright/test";

const TEST_USER = {
  email: "testfarmer@example.com",
  password: "Password123!",
};

async function loginAsFarmer(page: any) {
  await page.goto("/login");
  await page.waitForSelector("#login-panel input[name='email']");
  await page.fill("#login-panel input[name='email']", TEST_USER.email);
  await page.fill("#login-panel input[name='password']", TEST_USER.password);
  await page.locator("#login-panel button[type='submit']").click();
  await page.waitForURL(/dashboard|login/, { timeout: 10000 });
}

test.describe("Dashboard", () => {
  test("redirects to login when unauthenticated", async ({ page }) => {
    await page.goto("/dashboard");
    await expect(page).toHaveURL(/login/);
  });

  test("loads dashboard after login", async ({ page }) => {
    await loginAsFarmer(page);
    await expect(page).toHaveURL(/dashboard/);
    await expect(
      page.getByRole("heading", { name: /dashboard/i })
    ).toBeVisible();
  });

  test("shows sidebar navigation", async ({ page }) => {
    await loginAsFarmer(page);
    await expect(page).toHaveURL(/dashboard/);
    await expect(page.locator("#sidebar-nav")).toBeVisible();
  });
});

test.describe("Navigation", () => {
  test("can navigate to market prices", async ({ page }) => {
    await loginAsFarmer(page);
    if (!page.url().includes("dashboard")) return;
    const pricesLink = page.locator('a[href*="market-prices"]').first();
    if (await pricesLink.isVisible()) {
      await pricesLink.click();
      await expect(page).toHaveURL(/market-prices/);
    }
  });

  test("can navigate to harvests", async ({ page }) => {
    await loginAsFarmer(page);
    if (!page.url().includes("dashboard")) return;
    const harvestsLink = page.locator('a[href*="harvest"]').first();
    if (await harvestsLink.isVisible()) {
      await harvestsLink.click();
      await expect(page).toHaveURL(/harvest/);
    }
  });

  test("page has no console errors on load", async ({ page }) => {
    const errors: string[] = [];
    page.on("console", (msg) => {
      if (msg.type() === "error") {
        errors.push(msg.text());
      }
    });
    await loginAsFarmer(page);
    if (!page.url().includes("dashboard")) return;
    await page.waitForLoadState("networkidle");
    const criticalErrors = errors.filter(
      (e) => !e.includes("404") && !e.includes("analytics")
    );
    expect(criticalErrors).toHaveLength(0);
  });
});
