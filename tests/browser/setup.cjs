/**
 * Browser test setup helper.
 * Seeds test users before running Playwright tests.
 *
 * Usage: node tests/browser/setup.cjs
 */
const { execSync } = require("child_process");

const TEST_PASSWORD = "Password123!";

const users = [
  {
    name: "Test Farmer",
    email: "testfarmer@example.com",
    role: "farmer",
  },
  {
    name: "Test Buyer",
    email: "testbuyer@example.com",
    role: "buyer",
  },
  {
    name: "Test Logistics",
    email: "testlogistics@example.com",
    role: "logistics_partner",
  },
];

console.log("Seeding browser test users...");

for (const user of users) {
  const php = `<?php
require '${process.cwd()}/vendor/autoload.php';
\\$app = require_once '${process.cwd()}/bootstrap/app.php';
\\$app->make(\\Illuminate\Contracts\Console\\Kernel::class)->bootstrap();

use App\\Models\\User;

User::updateOrCreate(
    ['email' => '${user.email}'],
    [
        'name' => '${user.name}',
        'password' => '${TEST_PASSWORD}',
        'role' => '${user.role}',
        'status' => 'active',
        'email_verified_at' => now(),
        'affiliation_type' => '${user.role === "farmer" ? "independent" : null}',
    ]
);

echo "Created: ${user.email}\\n";
`;

  try {
    execSync(`php -r "${php.replace(/"/g, '\\"').replace(/\n/g, '\\n')}"`, {
      cwd: process.cwd(),
      stdio: "inherit",
    });
  } catch {
    console.error(`Failed to create ${user.email}`);
  }
}

console.log("Browser test users seeded.");
