import { test, expect } from "@playwright/test";

async function login(page: any, email: string) {
  await page.goto("/login");
  await page.waitForSelector("#login-panel input[name='email']");
  await page.fill("#login-panel input[name='email']", email);
  await page.fill("#login-panel input[name='password']", "password");
  await page.locator("#login-panel button[type='submit']").click();
  await page.waitForURL(/dashboard|login/, { timeout: 15000 });
}

async function allNavLabels(page: any): Promise<string[]> {
  const labels = await page
    .locator('nav[aria-label="Sidebar navigation"] .nav-label')
    .allTextContents();
  return labels.map((t: string) => t.trim()).filter(Boolean);
}

// Assert items appear in relative order (allowing interspersed child labels).
function expectOrderedSubsequence(haystack: string[], needles: string[]) {
  const idxs = needles.map((n) => {
    const i = haystack.indexOf(n);
    expect(i, `expected "${n}" in ${JSON.stringify(haystack)}`).toBeGreaterThan(-1);
    return i;
  });
  for (let k = 1; k < idxs.length; k++) {
    expect(idxs[k], `"${needles[k]}" out of order`).toBeGreaterThan(idxs[k - 1]);
  }
}

async function profileDropdownLabels(page: any): Promise<string[]> {
  await page.locator("#profile-menu-btn").click();
  const labels = await page
    .locator("#profile-dropdown a")
    .allTextContents();
  return labels.map((t: string) => t.trim()).filter(Boolean);
}

test.describe("Logistics sidebar reorganization", () => {
  test("coop sidebar order + moved items", async ({ page }) => {
    await login(page, "logistics1@test.com");

    const labels = await allNavLabels(page);
    expectOrderedSubsequence(labels, [
      "Dashboard",
      "Harvests",
      "Proposal Inbox",
      "Operations",
      "Incoming",
      "Customers",
      "Customer Orders",
      "Transport",
    ]);

    expect(labels).not.toContain("Deliveries");
    expect(labels).not.toContain("Reference");
    expect(labels).not.toContain("Market Prices");
    expect(labels).not.toContain("Business License Docs");
    expect(labels).toContain("Members");

    const dropdown = await profileDropdownLabels(page);
    expect(dropdown).toContain("Business Docs");
    expect(dropdown).toContain("Members");
  });

  test("independent sidebar keeps order minus Reference", async ({ page }) => {
    await login(page, "logistics2@test.com");

    const labels = await allNavLabels(page);
    expectOrderedSubsequence(labels, [
      "Dashboard",
      "Proposal Inbox",
      "Operations",
      "Transport",
      "Route Pricing",
      "Live Tracking",
      "Haul Negotiations",
    ]);

    expect(labels).not.toContain("Reference");
    expect(labels).not.toContain("Market Prices");
    expect(labels).not.toContain("Harvests");
    expect(labels).not.toContain("Deliveries");
    expect(labels).not.toContain("Members");

    const dropdown = await profileDropdownLabels(page);
    expect(dropdown).toContain("Business Docs");
    expect(dropdown).not.toContain("Members");
  });
});
