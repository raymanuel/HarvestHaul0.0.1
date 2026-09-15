import { defineConfig, devices } from "@playwright/test";

// Demo-only runner: executes just the two full-demo browser scenarios
// (single-truck + multi-truck overflow) and writes screenshots under
// tests/browser/demo/screenshots/<scenario>. Each scenario reseeds itself via
// `php artisan demo:full-seed`, so runs are reproducible end-to-end.
export default defineConfig({
  testDir: "./tests/browser/demo",
  fullyParallel: false,
  forbidOnly: !!process.env.CI,
  retries: process.env.CI ? 1 : 0,
  workers: 1,
  reporter: "list",
  timeout: 30_000,

  use: {
    baseURL: "http://127.0.0.1:8000",
    trace: "on-first-retry",
    screenshot: "only-on-failure",
  },

  projects: [
    {
      name: "chromium",
      use: { ...devices["Desktop Chrome"] },
    },
  ],

  webServer: {
    command: "php artisan serve",
    port: 8000,
    reuseExistingServer: !process.env.CI,
    timeout: 120_000,
  },
});