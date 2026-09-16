# Deploying HarvestHaul to Hostinger (Premium Web Hosting) — First Time

This guide walks you through putting HarvestHaul live on your **Premium** Hostinger
subscription, served from a **subdomain** (e.g. `harvesthaul.yourbrand.com`).

Your Premium plan **includes SSH**, and HarvestHaul is configured shared-hosting
friendly (database sessions, database cache, no queue worker, no websockets), so
this is straightforward.

**Remember the mental model:** your site has three separate parts —

1. **The recipe book** = the app code (`laravel_app/`). Safe to replace anytime.
2. **The stockroom** = your MySQL database. Never touched by a code upload.
3. **The fridge** = farmers' uploaded photos & documents (`storage/`). Untouched.

Uploading new code never wipes 2 or 3.

---

## Part 0 — Before you start

- [ ] You have your hPanel login (Hostinger account password).
- [ ] You know the subdomain you want to use, e.g. `harvesthaul.yourbrand.com`.
      (Because your domain is registered at Hostinger, no DNS work is needed.)
- [ ] On your PC: [Node.js](https://nodejs.org) installed (for building the design
      assets) and `php` available on the command line (the same PHP 8.2 used locally).

---

## Part 1 — Build the release zip on your PC

Open **PowerShell** in the project folder and run:

```powershell
powershell -ExecutionPolicy Bypass -File scripts/build-release.ps1
```

This:
- re-builds the design assets (CSS/JS),
- clears stale caches,
- packs the app into `dist/harvesthaul-release.zip` — about **36 MB**.
  `vendor/` (the ready-made code libraries) is included, so the server needs no
  Composer step on a fresh extract,
- **verifies** that secrets, `public/hot`, local logs/uploads, and node_modules
  are all excluded (it refuses to build if `.env` or `public/hot` sneak in).

When it finishes it prints a green "Release zip ready" message.

> Later, when you've made changes, run the same script with `-Update` to get a
> small "only what changed" zip instead (see Part 11).

---

## Part 2 — Set up the subdomain, database, and PHP in hPanel

1. **hPanel → Websites → Add Website → Create a subdomain.**
   - Subdomain: `harvesthaul`
   - Choose your domain. Hostinger creates `https://harvesthaul.yourbrand.com`.
2. **hPanel → Databases → MySQL → Create a MySQL database.**
   - Create a *new user* here too. Write down three things somewhere safe:
     - the **database name** (e.g. `u123456_harvesthaul`),
     - the **username** (e.g. `u123456_hhuser`),
     - the **password** you set.
3. **hPanel → Websites → your subdomain → PHP Configuration.**
   - Set **PHP version to 8.2** (or 8.3) and Save.

---

## Part 3 — Upload and extract the zip (no SSH needed for this part)

> **Heads-up:** File Manager's Extract has been seen to *silently drop files* on
> big uploads (a 10,000+ file app can lose whole folders — `app/`, `config/`,
> even `vendor/composer/` — with no error). It happens rarely, but when it does
> the site looks broken with no obvious cause. **Always verify** after extracting
> that `laravel_app/` contains `app/`, `config/`, `database/`, `public/`,
> `routes/`, `resources/`, `storage/`, and `vendor/`. If any is missing, delete
> and re-extract (the symptoms below in "If the site still fails the check" are
> what a silent drop looks like).

1. **hPanel → Websites → your subdomain → File Manager** (or a new browser tab to
   the File Manager for that domain).
2. Inside `~/domains/harvesthaul.yourbrand.com/`, create a folder named
   **`laravel_app`**.
3. Upload `harvesthaul-release.zip` into `laravel_app/` (File Manager → Upload →
   doesn't matter where it downloaded to on your PC).
4. Select the zip in File Manager → **Extract**. When done, `laravel_app/` contains
   `app/`, `config/`, `database/`, `public/`, `routes/`, `resources/`, `storage/`,
   `artisan`, `composer.json`, etc. Delete the zip.

> If File Manager struggles with the size, use **FileZilla** (free) instead:
> connect with your hPanel FTP credentials (hPanel → FTP Accounts), navigate the
> right panel into `~/domains/harvesthaul.yourbrand.com/laravel_app`, and drag the
> project files across. FileZilla handles 10,000+ files reliably.

---

## Part 4 — Set up the `.env` file

Still in File Manager, inside `laravel_app/`:

1. Find **`.env.production.example`** → click the `···` menu → **Copy** → name the
   copy **`.env`**.
2. Click the new **`.env`** → **Edit**, and change these lines:

| Key | What to type |
|---|---|
| `APP_NAME` | `HarvestHaul` (or your brand name) |
| `APP_ENV` | `production` (already set) |
| `APP_DEBUG` | `false` (already set — never `true` on live) |
| `APP_URL` | `https://harvesthaul.yourbrand.com` (your real subdomain) |
| `APP_KEY` | leave empty — Server Setup creates it |
| `DB_HOST` | `127.0.0.1` |
| `DB_DATABASE` | the **database name** from Part 2 |
| `DB_USERNAME` | the **username** from Part 2 |
| `DB_PASSWORD` | the **password** from Part 2 |
| `OPENWEATHER_API_KEY` | `f919b4cb28937972ee0447b4796f2b6d` |
| `MAIL_MAILER` | change `smtp` → **`log`** (no email breaks during the pilot) |
| `SESSION_DOMAIN` | leave as `null` |
| `MAIL_FROM_ADDRESS` | can stay; not used while MAIL_MAILER=log |

Save. Double check the DB lines — a typo here is the #1 cause of a white screen.

3. Set permissions so the site can write where it needs to (File Manager → right
   click folder → **Permissions → 755**):
   - `laravel_app/storage`
   - `laravel_app/bootstrap/cache`

---

## Part 5 — Connect via SSH (first remote command line)

Your Premium plan has SSH. Enable and connect:

1. **hPanel → Websites → your subdomain → SSH Access → Enable.**
2. Copy the **SSH Command** it shows (something like
   `ssh u123456789@harvesthaul.yourbrand.com -p 65002`).
3. Paste it into **PowerShell** (Windows) and press Enter. It asks for your
   **cPanel/hPanel password** — that's your Hostinger account password.
   - You should land on a screen with a `$` and the folder `~/domains/...`.

If the connection closes right away: disable SSH, re-enable it, and connect again.

Every command below is typed **inside that SSH window**.

---

## Part 6 — Server-side one-time setup (in SSH)

Run these one line at a time:

```bash
cd ~/domains/harvesthaul.yourbrand.com/laravel_app
php artisan key:generate
php artisan storage:link
php artisan migrate --force
php artisan optimize
php artisan db:seed --class=DemoSeeder --force
```

What each does, in plain words:
- `key:generate` — creates the site's secret key (fills the empty APP_KEY).
- `storage:link` — makes farmers' crop photos publicly viewable. **If this fails
  with "Call to undefined function ... exec()"**, the server blocks that helper;
  create the shortcut by hand instead:
  `ln -s ../storage/app/public public/storage`.
- `migrate --force` — builds all the database tables (the empty "record book").
- `optimize` — pre-loads routes/config/views so pages load fast.
- `db:seed --class=DemoSeeder --force` — loads **labeled DEMO accounts** and a few
  sample harvests so the boards aren't empty while you test.

> Skip the seed line if you'd rather start completely empty.
> To fully remove the demo data later (when real users come), run:
> `php artisan migrate:fresh --force` — this empties every table and rebuilds the
> empty record book. Nothing demo is left behind.

If `php` isn't found, first run: `alias php=/opt/alt/php82/usr/bin/php` (ask
hPanel which PHP version you chose; adjust `php82` to `php83` if needed).

**If any command prints nothing and just drops you back to `$`** — the most
common cause is a silent-extract dropped `vendor/composer/autoload_real.php`.
Quick fix:

```bash
cd ~/domains/harvesthaul.yourbrand.com/laravel_app
php /usr/local/bin/composer install --no-dev --no-interaction --prefer-dist --optimize-autoloader
```

Then re-run the five artisan commands above. (This is also needed if you
deliberately use the code-only zip build that excludes `vendor/` to keep uploads
small.)

---

## Part 7 — Point the website at the app (one clever shortcut)

Hostinger's web root is `public_html`, but Laravel's entry point is inside
`laravel_app/public`. We replace `public_html` with a **shortcut** that points at
the app. In SSH, from the subdomain's folder:

```bash
cd ~/domains/harvesthaul.yourbrand.com
rm -rf public_html
ln -s laravel_app/public public_html
```

That's it — the site now serves only the `public/` folder, and everything private
(`.env`, `app/`, `vendor/`) stays safely behind the scenes.

---

## Part 8 — Make background chores run on a clock (cron)

HarvestHaul has automatic chores (finishing deliveries on time, logging
impending-delayment reminders, running invoices, checking weather). Put them on a
schedule:

1. **hPanel → Websites → your subdomain → Advanced → Cron Jobs → Add cron job.**
2. Interval: **every minute** (`*/1 * * * *` or the UI's "Every minute").
3. Command:

```bash
cd /home/UUUUUUUUU/domains/harvesthaul.yourbrand.com/laravel_app && /usr/bin/php artisan schedule:run >> storage/logs/cron.log 2>&1
```

Replace `UUUUUUUUU` with your cPanel username (hPanel shows the correct full
path when you create the job).

> Even if cron is off, the site keeps working — the app quietly wakes up its own
> chores on visitor traffic. Real cron just makes them run on time.

---

## Part 9 — Free SSL + force HTTPS

1. **hPanel → Websites → your subdomain → Security → SSL → Install free SSL**
   (Let's Encrypt). Wait for it to issue.
2. Reload the site with `https://harvesthaul.yourbrand.com` — it should show a
   padlock. `.env` already has `SESSION_SECURE_COOKIE=true`.

---

## Part 10 — First-launch checklist

- [ ] `https://harvesthaul.yourbrand.com` loads (not a directory listing, no 403).
- [ ] Login as a demo account: `demo.admin@harvesthaul.app` / `demo1234`.
- [ ] Login as `demo.farmer1@harvesthaul.app`, post a harvest, upload a photo —
      the photo shows up (proves `storage:link` worked).
- [ ] Login as `demo.coop@harvesthaul.app`, `demo.driver@harvesthaul.app`,
      `demo.buyer@harvesthaul.app` — each lands on its own dashboard.
- [ ] Open a PDF invoice (works, no email dependence).
- [ ] Weather card shows live data (your OpenWeather key is in `.env`).
- [ ] `https://harvesthaul.yourbrand.com/.env` gives a **404** (not a download).

If you see a white page: 90% of the time it's a typo in `.env` (re-check the DB
lines) or PHP version not set to 8.2 in hPanel. Check
`laravel_app/storage/logs/laravel.log` for the actual error.

**If `php artisan anything` silently prints nothing and exits** (you just get
your `$` prompt back with no "INFO ..." line), it's almost always the File
Manager silent-drop: `vendor/composer/autoload_real.php` (or whole backend
folders) went missing during Extract. Re-upload the zip and re-extract, or use
FileZilla. A quick tell: `ls laravel_app/vendor/composer` must list files like
`autoload_real.php` and `ClassLoader.php`.

---

## Part 11 — Updating HarvestHaul later (the app is still in development)

You never re-send the whole site. Because your **database and uploaded photos live
on Hostinger**, replacing code is always safe.

**Kind of change → what you send → command to run afterwards (SSH):**

| You changed… | You re-send… | Then run |
|---|---|---|
| PHP code / pages / logic | only those files | `cd laravel_app && php artisan optimize:clear` |
| Design (CSS/JS) | `public/build/` (built fresh on your PC) | `php artisan view:cache` |
| The record structure (a new thing harvests keep) | the new migration file | `php artisan migrate --force` |
| A new package you added | nothing extra (it's in the zip's `composer.json`) | `php /usr/local/bin/composer install --no-dev` then `php artisan optimize` |

**Easiest way — let the tool pack it for you.** On your PC:

```powershell
powershell -ExecutionPolicy Bypass -File scripts/build-release.ps1 -Update
```

- It rebuilds assets, then compares against the previous `dist/release-snapshot.json`
  and packs **only the changed files** into a small zip
  (`dist/harvesthaul-update-<date>.zip`).
- It even tells you if you've added a migration (so you remember to run
  `migrate --force`).
- Upload that small zip into `laravel_app/` and Extract (overwrite files), then run
  the SSH command it prints. Done in minutes.

Rule of thumb: **never delete `laravel_app/storage` or your database** to "clean up" —
everything else can be replaced freely.

---

## Optional (later) — real email

For password resets / invites you can switch `.env` to a real mailbox later:
- `MAIL_MAILER=smtp`, `MAIL_HOST=smtp.gmail.com`, `MAIL_PORT=587`,
- `MAIL_USERNAME=you@gmail.com`, `MAIL_PASSWORD=<gmail App Password>` (16-char
  code from Google → App passwords),
- `MAIL_FROM_ADDRESS=you@gmail.com`, then `php artisan config:clear`.

Not needed for the pilot — HarvestHaul's inbox-style notifications are all
in-app (database-based), so nothing breaks without email.