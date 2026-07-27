import { test, expect } from "@playwright/test";

test.describe("Registration Page", () => {
  test("loads role selection page", async ({ page }) => {
    await page.goto("/register");
    await expect(page.locator("text=Join the Dispatch Network")).toBeVisible();
    await expect(
      page.locator('a:has-text("Register as Farmer")')
    ).toBeVisible();
    await expect(
      page.locator('a:has-text("Register as Coordinator")')
    ).toBeVisible();
    await expect(
      page.locator('a:has-text("Register as Buyer")')
    ).toBeVisible();
  });

  test("farmer registration form loads", async ({ page }) => {
    await page.goto("/register/farmer");
    await expect(page.locator('input[name="name"]')).toBeVisible();
    await expect(page.locator('input[name="email"]')).toBeVisible();
    await expect(page.locator('input[name="password"]')).toBeVisible();
    await expect(
      page.locator('input[name="password_confirmation"]')
    ).toBeVisible();
  });

  test("buyer registration form loads", async ({ page }) => {
    await page.goto("/register/buyer");
    await expect(page.locator('input[name="name"]')).toBeVisible();
    await expect(page.locator('input[name="email"]')).toBeVisible();
    await expect(page.locator('input[name="password"]')).toBeVisible();
  });

  test("logistics registration form loads", async ({ page }) => {
    await page.goto("/register/logistics_partner");
    await expect(page.locator('input[name="name"]')).toBeVisible();
    await expect(page.locator('input[name="email"]')).toBeVisible();
    await expect(page.locator('input[name="password"]')).toBeVisible();
  });
});

test.describe("Registration Validation", () => {
  test("farmer form rejects short password", async ({ page }) => {
    await page.goto("/register/farmer");
    await page.fill('input[name="name"]', "Test Farmer");
    await page.fill('input[name="email"]', `test${Date.now()}@example.com`);
    await page.fill('input[name="phone"]', "09123456789");
    await page.fill('input[name="password"]', "short");
    await page.fill('input[name="password_confirmation"]', "short");
    await page.locator('input[name="accepted_terms"]').check({ force: true });
    await page.locator('button[type="submit"]').click();
    await expect(page.locator("ul.text-red-600")).toBeVisible();
  });

  test("farmer form rejects mismatched passwords", async ({ page }) => {
    await page.goto("/register/farmer");
    await page.fill('input[name="name"]', "Test Farmer");
    await page.fill('input[name="email"]', `test${Date.now()}@example.com`);
    await page.fill('input[name="phone"]', "09123456789");
    await page.fill('input[name="password"]', "Password123!");
    await page.fill('input[name="password_confirmation"]', "Different123!");
    await page.locator('input[name="accepted_terms"]').check({ force: true });
    await page.locator('button[type="submit"]').click();
    await expect(page.locator("ul.text-red-600")).toBeVisible();
  });

  test("farmer form rejects duplicate email", async ({ page }) => {
    await page.goto("/register/farmer");
    await page.fill('input[name="name"]', "Test Farmer");
    await page.fill('input[name="email"]', "testfarmer@example.com");
    await page.fill('input[name="phone"]', "09123456789");
    await page.fill('input[name="password"]', "Password123!");
    await page.fill('input[name="password_confirmation"]', "Password123!");
    await page.locator('input[name="accepted_terms"]').check({ force: true });
    await page.locator('button[type="submit"]').click();
    await expect(page.locator("ul.text-red-600")).toBeVisible();
  });
});
