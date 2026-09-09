const { chromium } = require("@playwright/test");

(async () => {
  const browser = await chromium.launch({ headless: true });
  const ctx = await browser.newContext();
  const page = await ctx.newPage();
  page.setDefaultTimeout(25000);

  const login = async (email, password) => {
    await page.context().clearCookies();
    await page.goto("http://127.0.0.1:8000/login", { waitUntil: "domcontentloaded" });
    const token = await page.locator('meta[name="csrf-token"]').getAttribute("content").catch(() => null);
    if (token) {
      await page.evaluate(({ email, password, token }) => {
        const form = document.createElement("form");
        form.method = "POST";
        form.action = "/login";
        form.innerHTML =
          '<input type="hidden" name="_token" value="' + token + '">' +
          '<input type="hidden" name="email" value="' + email + '">' +
          '<input type="hidden" name="password" value="' + password + '">';
        document.body.appendChild(form);
        form.submit();
      }, { email, password, token });
    } else {
      await page.fill("#login-panel input[name='email']", email);
      await page.fill("#login-panel input[name='password']", password);
      await page.locator("#login-panel button[type='submit']").click();
    }
    await page.waitForURL(/\/(dashboard|admin|farmer|buyer|logistics|driver)/, { timeout: 25000 });
  };

  await login("julio-driver-1@driver.com", "password");
  await page.goto("http://127.0.0.1:8000/driver");
  await page.waitForLoadState("networkidle").catch(() => {});
  console.log("URL after /driver:", page.url());

  const links = await page.locator("a:has-text('View Details')").evaluateAll(as =>
    as.map(a => ({ text: a.textContent.trim(), href: a.getAttribute("href") }))
  );
  console.log("View Details anchors:", JSON.stringify(links, null, 2));

  if (links.length === 0) {
    const body = await page.locator("body").innerText();
    console.log("BODY:", body.slice(0, 1200));
    await browser.close();
    return;
  }

  await page.locator("a:has-text('View Details')").first().click();
  await page.waitForTimeout(4000);
  console.log("URL after click:", page.url());
  console.log("location.href:", await page.evaluate(() => location.href));
  console.log("has Accept Job:", await page.getByRole("button", { name: /Accept Job/i }).count());
  const h1 = await page.locator("h1, .heading-font").first().innerText().catch(() => "");
  console.log("h1:", h1);

  await browser.close();
})();