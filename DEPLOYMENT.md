# DEPLOYMENT.md

Notes for taking HarvestHaul online. This is the source of truth for deployment
decisions and procedures so any future session can pick up where we left off.

> Status: **NOT yet online.** We are still in development (capstone project,
> STI College General Santos). This file captures the plan for when we go live.

## 1. Deployment target

- **Hosting:** Hostinger (shared hosting, cPanel-style hPanel).
- **HTTPS:** Enable free SSL (Let's Encrypt) in hPanel for the site.
- **URL decision: UNDECIDED.** Leaning toward a **subdomain**
  (e.g. `harvesthaul.<yourdomain>.com`) because it is free, low commitment, and
  easy to migrate to a primary domain later. Actual domain not yet provided.

## 2. Launch model

- **Both:** demo / enrollment review now, and possibly a real rollout later.
- Demo-first: seed working accounts (logistics partner, farmer, buyer) so
  reviewers can log in and see each role.
- Gate unfinished features behind roles or a visible "beta" marker. Never show
  dead ends to real users, and keep copy truthful about actual scope (no fake
  testimonials/stats).

## 3. Pre-launch blockers (hard requirements)

1. On the live server `.env`:
   - `APP_ENV=production`
   - `APP_DEBUG=false` (currently `true` in local `.env` — flip before going online)
   - `APP_URL=https://<real-url>` (must match the actual domain/subdomain)
   - Real DB credentials.
2. `php artisan key:generate` (once, on the server).
3. Cache config/routes/views: `php artisan config:cache`, `route:cache`, `view:cache`.
4. Point the document root at Laravel's `public/` folder — **never** the repo
   root. This keeps `.env` and application code out of the public web root
   (security-critical).
5. Run `php artisan migrate --force`.

## 4. Ongoing development while live

- **Every DB change is a new migration** — test on a local DB copy, then
  `php artisan migrate --force` on live. Never hand-edit tables.
- **Backups:** enable Hostinger automatic daily backups (hPanel) and take a
  manual snapshot before any schema-changing deploy.
- **Standard deploy:** `git pull` → `migrate --force` → `cache:clear` (+
  `npm run build` if Vite/CSS assets changed).
- **Rollback:** `git revert` the breaking commit + restore the DB backup.
  (We use git + GitHub, so this is straightforward.)

## 5. Change-type risk reference (for edits while live)

| Change | Risk | Procedure |
|--------|------|-----------|
| Color scheme / CSS / Blade templates | Low | Safe. Just `app.css` + Blade. Verify across multiple pages (no visual gaps). No special deploy procedure. |
| New front-end pages | Low–med | Safe. Push, rebuild assets if needed. |
| DB schema / new tables/columns | Med–high | New migration + backup first + test on DB copy, then `migrate --force`. Never hand-alter tables. |
| Roles / permissions changes | Med | Gate carefully; affects live users. |
| Unfinished/new features | — | Hide behind roles or "beta" marker so real users don't hit dead ends. |

## 6. Queue note

- The queue runs synchronously (`QUEUE_CONNECTION=sync`) — no queue worker is needed in any environment.

## 7. Shared-hosting handoff checklist

Concrete build/upload steps for pushing the app to a Hostinger-style shared
account. Follow these in order after (or in place of) the notes above.

1. **Build the frontend locally and upload the compiled assets.** Run
   `npm run build` on the dev machine (Vite), then upload the `public/build`
   directory plus any static assets under `public/` that are not regenerated
   (fonts, images, favicons, vendor files). Never run npm/Vite on the shared
   host.
2. **Install PHP dependencies locally and upload `vendor/`.** Run
   `composer install --no-dev --optimize-autoloader` on the dev machine, then
   upload the whole `vendor/` directory. Shared hosting usually cannot run
   composer; if the host does offer SSH + composer, that path is acceptable too.
3. **Point the web root at `public/`** (hPanel "Document Root"), never the
   project root — this is the same security-critical rule as section 3.
4. **Server `.env` (production):**
   - `APP_ENV=production`
   - `APP_DEBUG=false`
   - `APP_URL=https://<real-url>` (must match the actual domain)
   - Real DB credentials.
   - `QUEUE_CONNECTION=sync` — no queue worker (or drop the line; the shipped
     default is already sync).
   - `SESSION_DRIVER`, `CACHE_DRIVER`, and `QUEUE_CONNECTION` all database-backed.
   - HTTPS-only.
5. **One-time server setup:** `php artisan key:generate`, then
   `php artisan storage:link`, then `php artisan migrate --force`, and finally
   `php artisan config:cache` + `route:cache` + `view:cache`.
6. **Cron for scheduled work (mandatory).** The scheduled tasks drive the
   hourly DA-AMAS Bantay Presyo price scrape (which also keeps prices fresh, so
   there is no background worker or polling process to babysit), the stale-price
   checks, invoice generation, overdue-marking, and auto-completions. From hPanel
   create a cron job on the documented 1-minute interval:

   ```
   * * * * * php /path/to/artisan schedule:run >> /dev/null 2>&1
   ```

   If the host restricts pass-through to PHP via cron, an alternative is to
   fetch the schedule URL via wget/curl with `php artisan schedule:run` behind a
   URL-governed route — treat this only as a fallback option, not a recommended
   setup.
7. **Reviewer verification checklist after upload:**
   - Market prices page loads and shows the source date and rows.
   - Live tracking page polls every 10 seconds (no WebSocket).
   - A "Refresh prices" click triggers a real scrape (verify via scraper status).
   - Inter-role notifications are delivered synchronously (in-request).
   - File uploads (identity, warehouse, invoice) work.

### Shared-hosting resource optimizations

These adjustments reduce load on a 1-core shared plan. No action
needed — shipped as code — but operators should know the trade-offs.

- **Weather cron tuned for shared hosting.** `routes/console.php` schedules
  `weather:check` hourly and `weather:check-active` every 30 minutes (previously
  30 min / 10 min). Cuts OpenWeatherMap API calls and CPU time. Trade-off:
  slightly less frequent weather and ETD alerts.
- **Client-side image compression.** Crop photos, driver load/delivery photos,
  and payment receipts are recompressed in the browser to max 1280 px longest
  edge at JPEG quality 0.7 before upload (`resources/js/image-compress.js`,
  wired into `harvests/create.blade.php`, `driver/driver-job-show.blade.php`,
  `logistics/cost-ledger.blade.php`). Reduces storage and server CPU. PDFs
  untouched. Server-side GD EXIF strip in `HarvestController` remains as
  fallback for clients without JS.
- **`.env.production.example` is the shared-hosting template.** Uses
  `CACHE_STORE=database`, `SESSION_DRIVER=database`, `QUEUE_CONNECTION=sync`
  (no worker), `BROADCAST_CONNECTION=log` (no WebSockets),
  `SESSION_SECURE_COOKIE=true`, and SMTP via `smtp.gmail.com:587`. No
  Redis/Memcached. AWS/Postmark/Resend/Slack config intentionally unset.

## 8. Future / when we make it "real"

- **Subdomain → primary domain migration:** buy a domain if desired, change
  `APP_URL` + vhost. ~30 min. Subdomain choice keeps this painless.
- **Stronger queue:** Horizon/supervisor, or consider a VPS if shared hosting is
  too limited.
- **Full security re-review** (incl. the HarvestHaul security audit checklist)
  before real users handle payment/GPS/location data.

## Open items (to fill in when going online)

- [ ] Actual Hostinger domain → exact `APP_URL` value.
- [ ] Final decision: subdomain vs primary domain.
- [ ] Whether deploys are done manually or via git hooks / CI.
