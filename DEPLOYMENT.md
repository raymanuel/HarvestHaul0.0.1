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

## 7. Future / when we make it "real"

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
