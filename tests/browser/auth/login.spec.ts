import { test, expect } from "@playwright/test";

test.describe("Login Page", () => {
  test("loads login page", async ({ page }) => {
    await page.goto("/login");
    await expect(page).toHaveTitle(/HarvestHaul/);
    await expect(page.locator("#login-panel input[name='email']")).toBeVisible();
    await expect(
      page.locator("#login-panel input[name='password']")
    ).toBeVisible();
    await expect(
      page.locator("#login-panel button[type='submit']")
    ).toBeVisible();
  });

  test("shows validation errors on empty submit", async ({ page }) => {
    await page.goto("/login");
    await page.locator("#login-panel button[type='submit']").click();
    const emailField = page.locator("#login-panel input[name='email']");
    await expect(emailField).toBeFocused();
  });

  test("shows error on invalid credentials", async ({ page }) => {
    await page.goto("/login");
    await page.fill("#login-panel input[name='email']", "nonexistent@example.com");
    await page.fill("#login-panel input[name='password']", "wrongpassword123");
    await page.locator("#login-panel button[type='submit']").click();
    await expect(
      page.locator("text=provided credentials do not match")
    ).toBeVisible();
  });

  test("has create account link", async ({ page }) => {
    await page.goto("/login");
    const registerLink = page.locator("#login-panel a:has-text('Create account')");
    await expect(registerLink).toBeVisible();
  });

  test("has forgot password link", async ({ page }) => {
    await page.goto("/login");
    const forgotLink = page.locator(
      "#login-panel a:has-text('Forgot password')"
    );
    await expect(forgotLink).toBeVisible();
  });
});

test.describe("Login Flow", () => {
  test("successful login redirects to dashboard", async ({ page }) => {
    await page.goto("/login");
    await page.fill("#login-panel input[name='email']", "testfarmer@example.com");
    await page.fill(
      "#login-panel input[name='password']",
      "Password123!"
    );
    await page.locator("#login-panel button[type='submit']").click();
    await expect(page).toHaveURL(/dashboard/);
  });

  test("logged-in user can log out", async ({ page }) => {
    await page.goto("/login");
    await page.fill("#login-panel input[name='email']", "testfarmer@example.com");
    await page.fill(
      "#login-panel input[name='password']",
      "Password123!"
    );
    await page.locator("#login-panel button[type='submit']").click();
    await expect(page).toHaveURL(/dashboard/);

    const logoutButton = page.locator(
      'a:has-text("Log Out"), button:has-text("Log Out")'
    );
    if (await logoutButton.isVisible()) {
      await logoutButton.click();
      await expect(page).toHaveURL("/");
    }
  });
});
