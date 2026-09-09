const { chromium } = require("@playwright/test");

(async () => {
  const browser = await chromium.launch({ headless: true, viewport: { width: 400, height: 800 } });
  const ctx = await browser.newContext();
  const page = await ctx.newPage();
  page.setDefaultTimeout(25000);

  const login = async (email, password) => {
    await page.context().clearCookies();
    await page.goto("http://127.0.0.1:8000/login", { waitUntil: "domcontentloaded" });
    await page.waitForSelector("input[name='email']", { timeout: 15000 });
    await page.fill("input[name='email']", email);
    await page.fill("input[name='password']", password);
    await page.locator("button[type='submit']").first().click();
    await page.waitForURL(/\/(dashboard|driver)/, { timeout: 25000 });
  };

  await login("mario-driver-1@driver.com", "password");
  await page.goto("http://127.0.0.1:8000/driver");
  await page.waitForLoadState("networkidle").catch(() => {});
  console.log("dashboard url:", page.url());
  const links = await page.locator("a:has-text('View Details')").evaluateAll(as => as.map(a => a.getAttribute("href")));
  console.log("detail links:", JSON.stringify(links));
  await page.locator("a:has-text('View Details')").first().click();
  await page.waitForURL(/driver\/jobs\/\d+/, { timeout: 25000 });
  await page.waitForLoadState("networkidle").catch(() => {});

  const cards = await page.locator("main div.space-y-4 > div, .grid, [class*='rounded-2xl']").count().catch(() => 0);
  const btns = await page.getByRole("button").all();
  console.log("ALL BUTTONS:");
  for (const b of btns) {
    const txt = (await b.innerText().catch(() => "")).trim().replace(/\n+/g, " | ").slice(0, 80);
    const vis = await b.isVisible().catch(() => false);
    const formHas = await b.evaluate(el => {
      const f = el.closest("form");
      if (!f) return "no-form";
      const names = Array.from(f.querySelectorAll("[name]")).map(i => i.name + "=" + i.value).join(",");
      return "form[" + names.slice(0, 100) + "]";
    }).catch(() => "");
    console.log(`  vis=${vis} ${JSON.stringify(txt)} ${formHas}`);
  }
  await browser.close();
})();