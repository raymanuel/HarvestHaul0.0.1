# Scenario 2 — Multi-Truck Auto-Split Demo Walkthrough

A full day-in-the-life demo of HarvestHaul from farm to customer with enough volume
that one truck cannot carry it all: four farmers sell their harvest to the coop, the
route planner **automatically splits the 3.1-ton load across two trucks**, both drivers
run their own routes, the coop confirms both receipts, then packs a customer order for
Robinsons Place General Santos, dispatches it, and the customer confirms they received it.

Every step below has a matching screenshot in this folder. The screenshots are in
alphabetical order; the story you should read them in is the order below.

## Actors

| Role | Account | What they do |
| --- | --- | --- |
| Logistics partner (coop) | `demo.outbound.coop@harvesthaul.app` | buys from farmers, plans the route, dispatches the customer order, confirms receipts |
| Farmer Rosa Dizon | `demo.outbound.farmer@harvesthaul.app` | posts 1,000 kg Mango |
| Farmer Jocelyn Ramos | `demo.outbound.farmer2@harvesthaul.app` | posts 800 kg Banana |
| Farmer Dante Mabuhay | `demo.outbound.farmer3@harvesthaul.app` | posts 700 kg Pineapple |
| Farmer Lito Salvador | `demo.outbound.farmer4@harvesthaul.app` | posts 600 kg Durian |
| Driver Rico (driver2) | `demo.outbound.driver2@harvesthaul.app` | runs the 3-stop route on DEMO-IB-002 (3-ton truck) |
| Driver Ely Guzman | `demo.outbound.driver@harvesthaul.app` | runs the 1-stop route on DEMO-IB-001 (2.5-ton truck) and the outbound delivery (DEMO-OB-001) |
| Customer | — | Robinsons Place General Santos receives the order |

All passwords are `demo1234`.

## Part 1 — Farmers post their harvest

Four farmers post their harvests. Coop members' destination automatically defaults to
the Cooperative Hub.

- `harvest-form-mango` / `harvest-confirm-mango` / `harvest-posted-mango` — Rosa Dizon posts 1,000 kg Mango.
- `harvest-form-banana` / `harvest-confirm-banana` / `harvest-posted-banana` — Jocelyn Ramos posts 800 kg Banana.
- `harvest-form-pineapple` / `harvest-confirm-pineapple` / `harvest-posted-pineapple` — Dante Mabuhay posts 700 kg Pineapple.
- `harvest-form-durian` / `harvest-confirm-durian` / `harvest-posted-durian` — Lito Salvador posts 600 kg Durian.

## Part 2 — Negotiations and deals

The coop buys each crop: open the listing, negotiate in the chat, the farmer agrees,
and the coop finalizes. Price and hauling rate per kg are locked into each deal.

- `crop-detail-mango` → `negotiation-started-mango` → `negotiation-message-mango` → `proposal-sent-mango` (PHP 55/kg, 1,000 kg, haul PHP 2.50/kg) → `farmer-agree-mango` → `farmer-agreed-mango` → `finalize-room-mango` → `deal-finalized-mango`.
- Banana (PHP 28/kg), Pineapple (PHP 35/kg), and Durian (PHP 120/kg) follow the same flow.

## Part 3 — Route planning: one load, two trucks

The coop sees all four farms in the pickup queue and plans the route. The combined
3.1 tons exceeds any single truck, so the planner **auto-splits the load across two
trucks** and creates two route offers (one per truck).

- `route-map-trucks` — the four farms on the map, a truck selected.
- `plan-all-panel` — the route panel: 2,500 kg goes to truck DEMO-IB-002 (Banana, Mango, Pineapple) and 600 kg to truck DEMO-IB-001 (Durian).
- `route-proposals-created` — "Confirm All" creates 2 route offers, one per truck.
- `farmer-proposal-mango` / `farmer-accepted-mango` — Rosa Dizon accepts.
- `farmer-proposal-banana` / `farmer-accepted-banana` — Jocelyn Ramos accepts.
- `farmer-proposal-pineapple` / `farmer-accepted-pineapple` — Dante Mabuhay accepts.
- `farmer-proposal-durian` / `farmer-accepted-durian` — Lito Salvador accepts.

## Part 4 — Two trucks, two pickup runs

Each driver gets their own inbound route, accepts it, and works the stops with photo
proof at every load. GPS tracking runs on both trucks at the same time.

- `driver-dashboard-driver2` — driver Rico sees his inbound route waiting.
- `driver-job-detail-driver2` / `driver-accepted-driver2` / `trip-started-driver2` — he accepts and starts the trip.
- `stop-1-arrived-driver2` / `stop-1-loaded-driver2` / `stop-1-delivered-driver2` — Stop 1 at the first farm.
- `stop-2-arrived-driver2` / `stop-2-loaded-driver2` / `stop-2-delivered-driver2` — Stop 2.
- `stop-3-arrived-driver2` / `stop-3-loaded-driver2` / `stop-3-delivered-driver2` — Stop 3.
- `run-completed-driver2` — Rico's 3-stop run is complete (2,500 kg at the hub).
- `driver-dashboard-driver` / `driver-job-detail-driver` / `driver-accepted-driver` / `trip-started-driver` — Ely Guzman accepts his 1-stop route.
- `stop-1-arrived-driver` / `stop-1-loaded-driver` / `stop-1-delivered-driver` — his single stop for the Durian.
- `run-completed-driver` — Ely's run is complete (600 kg at the hub).
- `buyer-tracking-awaiting` — the coop sees 2 routes awaiting receipt confirmation.
- `receipts-confirmed` — the coop confirms receipt for both routes; the inbound leg is complete.

## Part 5 — Customer order and outbound delivery

The coop packs a customer order for Robinsons Place General Santos from the crops now
at the hub, dispatches it on a dedicated outbound truck, and a public tracking link is
generated for the customer.

- `outbound-order-form` — 3 crop line items for Robinsons Place General Santos.
- `outbound-order-saved` — the customer order is saved as Drafted.
- `order-drafted-dispatch` — the dispatch form: pick truck + driver + start point.
- `order-dispatched-tracking-link` — shipment dispatched; a tracking link was generated for the customer.
- `outbound-job-detail-driver` — the driver sees the customer delivery on his portal.
- `outbound-in-transit-driver` — the customer delivery is In Transit, GPS tracking the truck to the store.
- `outbound-delivered-driver` — the driver marks the delivery at the customer location (with odometer).
- `outbound-finalized-driver` — the trip is finalized; waiting for the customer to confirm receipt.

## Part 6 — Customer confirms

The customer follows the public tracking link (no login) to follow the truck live and
confirm the delivery.

- `customer-track-page` — the tracking page shows the live truck position and the delivery items.
- `customer-confirm-prompt` — on arrival the customer sees "Arrived? Confirm Received".
- `customer-confirmed-complete` — the customer confirms; the order shows as Complete.
- `coop-order-completed` — the coop sees the customer order marked Completed.

## How to re-run

From the repo root, with the app, Vite (port 5173), and MySQL running:

```
npx playwright test -c playwright.demo.config.ts scenario-2-multi-truck.spec.ts
```

The seed resets all demo data first, so the run is always reproducible. The full demo
takes roughly 40 minutes because the demo environment intentionally throttles GPS
pings and payments; progress is logged to `runLog.json` in this folder.