/**
 * Seeds test users for Playwright browser tests.
 * Run: node tests/browser/seed.cjs
 */
const { execSync } = require("child_process");

console.log("Seeding browser test users...");

try {
  execSync("php artisan db:seed --class=BrowserTestSeeder", {
    cwd: process.cwd(),
    stdio: "inherit",
  });
  console.log("Done.");
} catch {
  console.error("Seed failed. Is the Laravel server running?");
  console.error("Try manually: php artisan db:seed --class=BrowserTestSeeder");
  process.exit(1);
}
