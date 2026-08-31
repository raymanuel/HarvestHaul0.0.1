const { chromium } = require('playwright');
(async () => {
  const b = await chromium.launch();
  const p = await b.newPage();
  const widths = [375, 500, 600, 700, 768, 800, 900, 1024, 1200];
  for (const w of widths) {
    await p.setViewportSize({width: w, height: 800});
    await p.goto('http://127.0.0.1:8000/');
    const burgerVisible = await p.locator('button[aria-label=Menu]').isVisible();
    const desktopNavVisible = await p.evaluate(() => {
      const nav = document.querySelector('header nav');
      return nav ? window.getComputedStyle(nav).display : 'no nav';
    });
    const desktopAuthVisible = await p.evaluate(() => {
      const authDiv = document.querySelector('header .hidden.md\\:flex');
      return authDiv ? window.getComputedStyle(authDiv).display : 'no auth div';
    });
    console.log('w=' + w + ' burger=' + burgerVisible + ' nav=' + desktopNavVisible + ' auth=' + desktopAuthVisible);
  }
  await b.close();
})().catch(e => { console.error(e.message); process.exit(1); });