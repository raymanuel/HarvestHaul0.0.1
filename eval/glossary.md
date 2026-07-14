# HarvestHaul — Glossary of Technical Terms

**How to use**: Each term includes a plain-English definition, then exactly how/where it's used in the HarvestHaul system (file paths + line references).

---

## A

### Algorithm
- **Definition**: Step-by-step procedure for solving a problem. Like a cooking recipe, but for data.
- **In HarvestHaul**: Route optimization uses 3 algorithms chained together: knapsack selects which farms to visit, nearest neighbor orders the stops, haversine calculates distances.

### Audit Log
- **Definition**: Immutable record of who did what and when. Used for compliance, debugging, and dispute resolution.
- **In HarvestHaul**: `app/Models/AuditLog.php` — every driver status change, admin action, invoice generation, and payment update is logged. Example: "Admin verified Farmer Juan at 2026-07-09 14:30".

---

## B

### Baseline Price
- **Definition**: Reference price per kg for a crop. Used as starting point for negotiations and route cost estimates.
- **In HarvestHaul**: `crops.baseline_price_per_kg` column. Admin sets via `AdminController@updateBaselinePrice`. Route optimization uses this in the price reference formula: `(distance × 15) + (total_kg × 0.50) + 250`.

### Bearing
- **Definition**: Compass direction (0-360 degrees) a vehicle is moving. 0 = north, 90 = east, 180 = south, 270 = west.
- **In HarvestHaul**: `TrackingController.php:234-239` — calculated from two consecutive GPS points using standard azimuth formula. Stored in `tracking_records.bearing`. Used to show truck heading on Leaflet map.

### Blade
- **Definition**: Laravel's templating engine. Allows PHP logic inside HTML files with `{{ }}` syntax.
- **In HarvestHaul**: All views are Blade files in `resources/views/`. Example: `logistics/route-optimization.blade.php` renders the Leaflet map with farmer markers.

---

## C

### Cron
- **Definition**: Time-based job scheduler. Runs commands at specified intervals (every 15min, hourly, daily).
- **In HarvestHaul**: **4 scheduled commands** defined in `routes/console.php`:
  - `delays:check` — Every 15 minutes. Detects driver stalls and stop delays.
  - `weather:check` — Every 30 minutes. Checks OpenWeatherMap for active routes.
  - `invoices:generate` — Hourly. Generates invoices for completed jobs.
  - `deliveries:auto-complete` — Hourly. Auto-completes jobs awaiting confirmation >48h.

### Controller
- **Definition**: Laravel component that handles HTTP requests. Receives input, calls services/models, returns a response (view or JSON).
- **In HarvestHaul**: `app/Http/Controllers/` contains 20+ controllers. Each user role has dedicated controllers (e.g., `DriverController` for drivers, `BuyerController` for buyers).

### Cost Share / Cost Allocation
- **Definition**: Splitting a shared cost fairly among participants.
- **In HarvestHaul**: `ResourcePoolingService.php:134-153` — each farmer's share = `(their_kg × their_haul_distance) / total_score × price_reference`. Farmers who ship more weight over longer distance pay more. Stored in `pooling_job_harvests.cost_share`.

---

## D

### DB Transaction
- **Definition**: Group of database operations that succeed or fail as one unit. If any step fails, ALL changes are rolled back (no partial saves).
- **In HarvestHaul**: `ResourcePoolingService.php:236-294` — `confirm()` wraps creation of PoolingJob + attaching harvests + updating statuses + reserving truck in a single DB transaction. If truck reservation fails, the job is not created.

### DP (Dynamic Programming)
- **Definition**: Algorithm technique that breaks a problem into smaller overlapping subproblems, solves each once, and reuses results. More efficient than brute force.
- **In HarvestHaul**: Not yet used. Proposed as replacement for greedy knapsack (Gap #15). 0/1 Knapsack DP would compute exact optimal harvest-to-truck assignment.

### Deduplication
- **Definition**: Removing duplicate entries. Prevents redundant data.
- **In HarvestHaul**: Two types:
  1. Destination dedup: `ResourcePoolingService.php:377-383` — if 3 farmers drop at same market, it's 1 stop, not 3.
  2. GPS dedup: Not yet implemented (Gap #20) — should ignore identical GPS pings within 10s window.

### Delay Detection
- **Definition**: Automated system that checks if a delivery is running behind schedule.
- **In HarvestHaul**: `DelayDetectionService.php` — two checks:
  - **Stall**: Driver moving <1 km/h for >15 minutes → warning. >30 min → critical alert.
  - **Stop delay**: Driver "arrived" at pickup for >20 minutes without loading → delay alert.
  - Runs every 15 minutes via cron.

---

## E

### ETA (Estimated Time of Arrival)
- **Definition**: Predicted time when a vehicle will reach its destination.
- **In HarvestHaul**: `ETAService.php` — computes remaining distance through remaining waypoints, divides by current speed (or 30 km/h default), converts to human-friendly format like "arriving at 3:45 PM". Exposed at `GET /tracking/{job}/eta`.

### Enum
- **Definition**: A data type with predefined allowed values. Restricts a field to specific options.
- **In HarvestHaul**: Used extensively in the database:
  - `users.role`: admin, farmer, logistics_partner, driver, buyer
  - `users.status`: active, inactive
  - `harvests.status`: active, negotiating, sold, assigned, in_progress, completed
  - `pooling_jobs.status`: pending, confirmed, in_progress, completed, cancelled
  - `payment_status`: unpaid, submitted, paid

---

## G

### GPS (Global Positioning System)
- **Definition**: Satellite-based system that provides location (latitude, longitude) anywhere on Earth.
- **In HarvestHaul**: Every farmer has GPS (farm location), every tracking record has lat/lng, routes have start/end coordinates. The entire logistics system is geospatial.

### Greedy Algorithm
- **Definition**: Algorithm that makes the best immediate choice at each step without considering future consequences. Fast but may miss optimal solutions.
- **In HarvestHaul**: Two greedy algorithms:
  1. **Greedy Knapsack** (`ResourcePoolingService.php:311-327`) — picks heaviest harvest first, then next heaviest that fits. Fast but suboptimal (Gap #15).
  2. **Greedy Nearest Neighbor** (`ResourcePoolingService.php:338-366`) — visits closest unvisited farm next. Fast TSP approximation.

---

## H

### Haversine Formula
- **Definition**: Math formula that calculates the great-circle distance between two GPS coordinates on a sphere (Earth). Accounts for Earth's curvature.
- **Formula**: `a = sin²(Δlat/2) + cos(lat1)·cos(lat2)·sin²(Δlng/2)` then `distance = 2·R·atan2(√a, √(1-a))` where R = 6371 km.
- **In HarvestHaul**: The most-used function in the system. Appears in 5+ files:
  - `ResourcePoolingService.php:422-428` — route distances, cost scores
  - `DriverAssignmentService.php:77-83` — nearest driver search
  - `ETAService.php:106-112` — remaining distance for ETA
  - `TrackingController.php:222-229` — speed calculation between GPS pings

### Heartbeat
- **Definition**: Periodic signal from a device to indicate it's active and connected.
- **In HarvestHaul**: Not yet implemented (Gap #7). Proposed: when driver has no active job, PWA sends GPS location every 60 seconds to `driver_heartbeats` table, so system knows where idle drivers are for nearest-driver assignment.

---

## I

### IndexedDB
- **Definition**: Browser-side NoSQL database. Stores structured data locally in the user's browser. Larger storage than localStorage.
- **In HarvestHaul**: Not yet used. Proposed for offline GPS queue (Gap #19): when driver loses signal, GPS pings accumulate in IndexedDB and flush to server when reconnected.

### Invoice
- **Definition**: Commercial document itemizing products/services provided and amount due.
- **In HarvestHaul**: `InvoiceService.php` generates invoices for completed pooling jobs. Format: `HH-INV-YYYYMMDD-XXXXX`. Currently HTML file stored on disk. Contains: company name, route number, farmer list with crops/qty/cost, total footer.

---

## K

### K-Means
- **Definition**: Clustering algorithm that partitions n data points into k groups. Each point belongs to the group with the nearest mean (centroid).
- **In HarvestHaul**: Not yet implemented (Gap #6). Proposed for fleet-level optimization: cluster all sold harvests into k groups (k = available trucks), assign each cluster to one truck.

### Knapsack (Algorithm)
- **Definition**: Optimization problem: given items with weights and values, select subset that fits in a knapsack (capacity limit) while maximizing total value. "0/1 Knapsack" = each item taken or left entirely (no fractions).
- **In HarvestHaul**: `ResourcePoolingService.php:311-327` — selects which harvests (items) fit in a truck (capacity, 1000-5000kg). Currently uses greedy (heaviest-first) heuristic. Proposed upgrade: DP 0/1 Knapsack for exact optimal selection.

---

## L

### Leaflet
- **Definition**: Open-source JavaScript library for interactive maps. Lightweight, no API key needed (unlike Google Maps).
- **In HarvestHaul**: Used in `logistics/route-optimization.blade.php` and `tracking/index.blade.php`. Shows farmers as markers, truck position, route lines. Uses OpenStreetMap tiles.

### Linear Regression
- **Definition**: Statistical method modeling relationship between variables as a straight line: `y = mx + b`. Given past data, predicts future values.
- **In HarvestHaul**: Not yet implemented. Proposed improvement for Predictor (Gap #5): use past harvest dates and quantities to predict next harvest window and expected yield per crop.

---

## M

### Middleware
- **Definition**: Software layer that filters HTTP requests before they reach the controller. Checks authentication, roles, account status.
- **In HarvestHaul**: 6 custom middleware files in `app/Http/Middleware/`:
  - `EnsureAccountIsActive` — blocks inactive/suspended users
  - `EnsureUserIsFarmer/Driver/Logistics/Buyer` — role-based access
  - `CheckRole` — generic role check
  - Applied in `routes/web.php` to protect route groups.

### Migration
- **Definition**: Version-controlled database schema changes in Laravel. Like git for your database structure.
- **In HarvestHaul**: Uses `doctrine/dbal` for migrations. Schema defined in `database/` directory. The full schema snapshot is in `harvesthaul.sql` (20 tables, 1406 lines).

### Model
- **Definition**: Laravel class representing a database table. Provides methods to query, create, update, and relate data.
- **In HarvestHaul**: `app/Models/` contains 18+ models including: User, Harvest, PoolingJob, TrackingRecord, Invoice, Notification, Truck, DriverProfile, FarmerProfile, Crop, Negotiation, etc.

---

## N

### Nearest Neighbor (TSP)
- **Definition**: Algorithm that starts at a point, then repeatedly visits the closest unvisited point. Simple TSP heuristic.
- **In HarvestHaul**: `ResourcePoolingService.php:338-366` — stops (farms) are ordered starting from depot. Each step picks the nearest unvisited farm. Result: a route sequence like "Depot → Farm C (2km) → Farm A (3km) → Farm B (4km)".

### Notification
- **Definition**: Alert message informing a user about an event.
- **In HarvestHaul**: `app/Models/Notification.php` — stored in `notifications` DB table. Current: database-only (pull model). Proposed upgrade: add email/SMS/push layers (Gap #9). Sent for: delay alerts, new proposals, negotiation messages, invoice ready.

---

## O

### OSRM (Open Source Routing Machine)
- **Definition**: C++ routing engine that calculates driving directions using OpenStreetMap road data. Returns road distance + turn-by-turn instructions. More accurate than straight-line distance.
- **In HarvestHaul**: Not yet integrated (Gap #1). `pooling_jobs.route_geometry` column exists but is always null. Would replace haversine with actual road distance and provide route polyline for map display.

### OTP (One-Time Password)
- **Definition**: Temporary numeric code sent via email/SMS for verification. Expires after single use or time window.
- **In HarvestHaul**: Email OTP for account email verification. `VerifyOtpController` handles send/verify/resend. Resend throttled to 3 per minute.

---

## P

### Pivot Table
- **Definition**: Database table that links two other tables in a many-to-many relationship. Contains foreign keys plus extra data about the relationship.
- **In HarvestHaul**: `pooling_job_harvests` pivots between `pooling_jobs` and `harvests`. Extra columns: `pickup_order`, `quantity_kg`, `cost_share`, `status`, `payment_status`, `loaded_quantity_kg`, `delivery_receipt_path`.

### Polyline
- **Definition**: Series of connected line segments drawn on a map. Represents the path a vehicle took or will take.
- **In HarvestHaul**: `pooling_jobs.route_geometry` column stores JSON polyline data. Currently always null (no OSRM yet). Would be drawn on Leaflet maps to show planned route to drivers and viewers.

### Pooling Job
- **Definition**: A consolidated delivery route grouping multiple harvests into one truck trip.
- **In HarvestHaul**: `app/Models/PoolingJob.php` — the central logistics entity. Status lifecycle: `pending → confirmed → in_progress → awaiting_confirmation → completed`. Links logistics partner, truck, driver, buyer, and multiple harvests via pivot.

### PWA (Progressive Web App)
- **Definition**: Website that behaves like a native mobile app. Key features: installable on home screen, works offline (service worker), uses device hardware (GPS, camera).
- **In HarvestHaul**: Driver portal is a PWA. `public/sw.js` (service worker) + `public/manifest.json`. Driver uses HTML5 Geolocation API for GPS. Offline support not yet customized (Gap #19).

---

## Q

### Queue
- **Definition**: Background job processing system. Time-consuming tasks (email, PDF generation) are pushed to a queue and processed asynchronously by a worker. HTTP response returns immediately.
- **In HarvestHaul**: Configured for database-backed queue in Laravel. Currently unused. Would be used for email notifications and PDF invoice generation once implemented.

---

## R

### Rate Limiting / Throttle
- **Definition**: Restricts how many requests a client can make within a time window. Prevents abuse, accidental DDOS, and runaway costs.
- **In HarvestHaul**: OTP resend throttled to `3 per minute`. GPS tracking store NOT throttled (Gap #20). Proposed: `throttle:60,1` on tracking endpoint.

### Route Geometry
- **Definition**: GPS coordinate path representing a planned or driven route. Usually encoded as JSON array of [lat, lng] pairs.
- **In HarvestHaul**: `pooling_jobs.route_geometry` column. Schema exists, data always null (no OSRM). Once populated, would render truck route on Leaflet maps.

---

## S

### Scheduler
- **Definition**: Laravel's task scheduling system. Defines commands that run at specified intervals using cron syntax. Defined in `routes/console.php`.
- **In HarvestHaul**: 4 scheduled tasks:
  - `delays:check` — every 15 min
  - `weather:check` — every 30 min
  - `invoices:generate` — hourly
  - `deliveries:auto-complete` — hourly

### Service
- **Definition**: Laravel class containing business logic. Keeps controllers thin (handling only HTTP concerns) and models focused on data.
- **In HarvestHaul**: `app/Services/` contains 6 services:
  - `ResourcePoolingService` — route planning, knapsack, TSP, cost allocation
  - `DriverAssignmentService` — nearest driver finder
  - `ETAService` — ETA computation
  - `DelayDetectionService` — stall/stop delay detection
  - `WeatherService` — OpenWeatherMap integration
  - `InvoiceService` — invoice generation

### Service Worker
- **Definition**: JavaScript file that runs in the browser background, separate from the web page. Enables offline caching, push notifications, background syncing.
- **In HarvestHaul**: `public/sw.js` — exists as Laravel default scaffold. Not yet customized (Gap #19).

### Stale GPS
- **Definition**: Location data that is outdated and no longer reflects the current position.
- **In HarvestHaul**: `DriverAssignmentService` reads GPS from `driver_profiles` table (static, only updates on profile edit). A driver may have moved 10km since last update (Gap #7).

### Stall Detection
- **Definition**: Identifying when a vehicle has stopped moving for an abnormally long time.
- **In HarvestHaul**: `DelayDetectionService.php:50-81` — checks if speed <1 km/h for >15 minutes (warning) or >30 minutes (critical). Uses last 3 tracking records.

---

## T

### Throttle (see Rate Limiting)

### TSP (Traveling Salesman Problem)
- **Definition**: Classic optimization problem: "Given a list of cities and distances, what's the shortest possible route visiting each exactly once and returning to origin?" NP-hard (no known efficient exact solution for large n).
- **In HarvestHaul**: Approximated via Greedy Nearest Neighbor algorithm. For 5-15 farms, typically within 15-20% of optimal. Good enough for rural PH logistics.

### Telemetry
- **Definition**: Automatic measurement and transmission of data from remote sources. In logistics: GPS position, speed, bearing.
- **In HarvestHaul**: `POST /driver/tracking/store` ingests telemetry. `GET /tracking/{job}/latest` serves latest position. WebSocket broadcasts to viewers in real-time.

---

## W

### Webhook
- **Definition**: HTTP callback. When an external service has new data, it sends an HTTP POST to a predefined URL. Used for async notifications from third parties.
- **In HarvestHaul**: Not yet implemented. Proposed for payment gateway integration: PayMongo sends webhook when payment succeeds, which auto-updates `payment_status` to `paid`.

### WebSocket
- **Definition**: Protocol enabling persistent, bidirectional communication between browser and server. Unlike HTTP (request-response), server can push data anytime.
- **In HarvestHaul**: `WebSocketServer.php` — custom raw PHP socket server on port 8080. Listens for GPS telemetry from drivers and broadcasts to connected map viewers. Current implementation is single-threaded and not production-grade (Gap #2).

### Waypoint
- **Definition**: Intermediate point along a route. A stop to pick up or drop off cargo.
- **In HarvestHaul**: Each farm in a pooling job is a waypoint with a `pickup_order`. ETA calculation iterates through remaining waypoints to compute remaining distance.

---

## Y

### Yield Prediction
- **Definition**: Estimating future crop output based on historical data.
- **In HarvestHaul**: `PredictorController@farmerPredict` — computes average cycle days between past harvests per crop type and estimates next harvest date. Current implementation is simple average (Gap #5 — no ML).

---

*Generated July 9, 2026 — based on full code review of HarvestHaul 0.0.1*
