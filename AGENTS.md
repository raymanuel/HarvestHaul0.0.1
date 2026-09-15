# AGENTS.md

## Dev workflow rules

- **Dev pages need Vite running.** Laravel's `@vite()` directive injects asset URLs pointing at the Vite dev server on port 5173. If Vite is stopped, every Blade page loses its CSS/JS (broken styling, broken scripts) even though HTML still returns 200. Before session-cleanup, never stop a running Vite dev server — the app depends on it in local dev.   Start it with:
  `node node_modules/vite/bin/vite.js --host localhost --port 5173`
  (Bare `--host` makes the Vite plugin write `http://[::]:5173` into `public/hot`, which nothing can connect to — every page then loses CSS/JS. Always pass a string host like `localhost`.)
  Verify with `netstat -ano | findstr ":5173"` or a 200 on `http://localhost:5173/@vite/client`, and confirm `Get-Content public/hot` shows `http://localhost:5173`.
- **MySQL runs manually.** XAMPP MySQL is not running by default; start it with `C:\xampp\mysql\bin\mysqld.exe` before artisan/tinker DB work. A `SQLSTATE[HY000] [2002] Unknown error while connecting` on host 127.0.0.1:3306 means it's down.

## Project knowledge

- **Billing model (hauling):** farmers are billed rate × kg. The source of truth is the per-farmer `hauling_rate_per_kg` agreed in the negotiation chat; else the logistics-quoted flat `hauling_rate_per_kg`; else the distance fallback (`base_rate_per_km` × km + `base_rate_per_kg` × kg + `base_trip_fee`). Distance/terrain are folded into the quoted per-kg rate by the hauler, never billed separately.
- **Road-distance rate suggestion (advisory only, never billed):** `HaulingRateCalculator::suggest()` = (fuel + maintenance + driver per km) × road km × terrain multiplier + trip fee, then ÷ total kg. `ResourcePoolingService::plan()` returns `suggested_rate_per_kg`, `road_distance_km`, `terrain`, `rate_sanity` (a banner the logistics UI shows). The frontend sends `route_distance_km` + `terrain` to `/pooling/plan` and `/pooling/confirm`; `PoolingJobService` prefers road distance when no rate is set. Inputs and source URLs live in `config/harvesthaul.php` → `hauling` (fuel price = DOE Oil Price Watch, light-truck economy ≈ 7.4 km/L = ICCT, terrain ratios = World Bank/FHWA).

<!-- antislop:start -->
## antislop (skill routing)

For UI, copy, or mobile layout work, read `antislop.md` (core) and then the skill for the task:
- UI / visual: `skills/antislop-ui/SKILL.md`
- Copy & text: `skills/antislop-copywriting/SKILL.md`
- People: `skills/antislop-human/SKILL.md`
- Mobile / responsive: `skills/antislop-layoutmobile/SKILL.md`
- Code comments: `skills/antislop-code/SKILL.md`

Precedence with impeccable:
- **impeccable + designing-frontend-interfaces own the direction** (what to build, how bold). They are the builder.
- **antislop is a review-only filter** with no directional opinion. It never tells you what to build; it only censors lessons already built for "AI-slop" patterns.
- **The user's explicit request wins over both.** A pattern the user intentionally asked for (e.g. a specific glow or gradient) is never censored by antislop — its own rules already say "do not flag things the user intentionally asked for."
- **antislop runs DURING the work** (default, already chosen; never block UI work to ask the usage-mode question). antislop-ui loads for the finish/review pass over impeccable's output.
- The core's Delivery Gate is the reviewer; run it as a single lightweight checklist line at finish. Em-dash ban applies to new UI copy only, never retroactively to approved content.
<!-- antislop:end -->
