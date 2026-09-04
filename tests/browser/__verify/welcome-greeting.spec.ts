import { test, expect } from "@playwright/test";

async function login(page: any, email: string) {
  await page.goto("/login");
  await page.waitForSelector("#login-panel input[name='email']");
  await page.fill("#login-panel input[name='email']", email);
  await page.fill("#login-panel input[name='password']", "password");
  await page.locator("#login-panel button[type='submit']").click();
  await page.waitForURL(/dashboard|login/, { timeout: 10000 });
}

const ROLES = [
  { email: "farmer0@test.com", label: "farmer" },
  { email: "logistics1@test.com", label: "logistics" },
  { email: "buyer@test.com", label: "buyer" },
];

test.describe("Welcome greeting (time-aware)", () => {
  for (const role of ROLES) {
    test(`renders time-aware greeting for ${role.label}`, async ({ page }) => {
      await login(page, role.email);
      await expect(page).toHaveURL(/dashboard/);

      const h1 = page.locator("header h1").first();
      await expect(h1).toBeVisible();

      const text = (await h1.textContent()) ?? "";
      expect(text).toMatch(/^Good (morning|afternoon|evening), .+\.$/);

      const dateLine = page.locator("header p").first();
      await expect(dateLine).toBeVisible();
      expect(await dateLine.textContent()).toMatch(/\w+, \w+ \d+, \d{4}/);
    });
  }
});
