# Scenario 3 — Combined Two-Day Demo Walkthrough

A full, continuous two-day story in a single run — no data reset between days — from farm
to customer. Day 1: three farmers sell to the coop and one truck carries the whole 1.0t
load to the hub. Day 2: four farmers post a bigger 3.1t harvest that no single truck can
fit, so the route planner auto-splits it onto two trucks. The coop ships to Robinsons
Place General Santos every day, and once more after both days with a consolidated order
that combines leftover crops from both days.

Every step below has a matching screenshot in this folder. Screenshots are phase-prefixed
(`day1-`, `day2-`, `day1out-`, `day2out-`, `day3out-`) so the two farming days never
overwrite each other; within each prefix they are in alphabetical order, and the story
you should read them in is the order below.

## Actors

| Role | Account | What they do |
| --- | --- | --- |
| Logistics partner (coop) | `demo.outbound.coop@harvesthaul.app` | buys from farmers, plans routes, dispatches customer orders, confirms receipts |
| Farmer Rosa Dizon | `demo.outbound.farmer@harvesthaul.app` | Day 1: posts 400 kg Mango — Day 2: posts 1000 kg Mango |
| Farmer Jocelyn Ramos | `demo.outbound.farmer2@harvesthaul.app` | Day 1: posts 350 kg Banana — Day 2: posts 800 kg Banana |
| Farmer Dante Mabuhay | `demo.outbound.farmer3@harvesthaul.app` | Day 1: posts 250 kg Pineapple — Day 2: posts 700 kg Pineapple |
| Farmer Lito Salvador | `demo.outbound.farmer4@harvesthaul.app` | Day 2 only: posts 600 kg Durian |
| Driver Rico Bartolome | `demo.outbound.driver2@harvesthaul.app` | Day 1 inbound route (3T truck) + Day 2 inbound route (3T) + Day 2 customer delivery |
| Driver Ely Guzman | `demo.outbound.driver@harvesthaul.app` | Day 2 inbound route (2.5T) + Day 1 and Day 3 customer deliveries (outbound truck) |
| Customer | — | Robinsons Place General Santos receives all three orders |

All passwords are `demo1234`.

## Day 1 — 3 farms, 1 truck

### Part 1a — Farmers post their harvest (Day 1)

- `day1-harvest-form-mango` / `day1-harvest-confirm-mango` / `day1-harvest-posted-mango` — Rosa Dizon posts 400 kg of Mango; the destination defaults to the Cooperative Hub.
- `day1-harvest-form-banana` / `day1-harvest-confirm-banana` / `day1-harvest-posted-banana` — Jocelyn Ramos posts 350 kg of Banana.
- `day1-harvest-form-pineapple` / `day1-harvest-confirm-pineapple` / `day1-harvest-posted-pineapple` — Dante Mabuhay posts 250 kg of Pineapple.

### Part 2a — Negotiations and deals (Day 1)

- `day1-crop-detail-mango` — the Mango listing on the crop board.
- `day1-negotiation-started-mango` → `day1-negotiation-message-mango` → `day1-proposal-sent-mango` — the coop opens a negotiation, chats, and proposes PHP 55/kg for 400 kg.
- `day1-farmer-agree-mango` → `day1-farmer-agreed-mango` — Rosa agrees to the terms.
- `day1-finalize-room-mango` → `day1-deal-finalized-mango` — the coop locks the deal.
- The same flow repeats for Banana (PHP 28/kg) and Pineapple (PHP 35/kg).

### Part 3a — Route planning (Day 1)

- `day1-route-map-trucks` — three farms in the pickup queue, a truck selected.
- `day1-plan-single-panel` — the 1.0t load fits one truck, so a single route is offered (with the road-distance hauling rate suggestion).
- `day1-route-proposals-created` — one route offer is created.
- `day1-farmer-proposal-mango` / `day1-farmer-accepted-mango` (and the Banana + Pineapple pairs) — each farmer accepts the Route Offer.

### Part 4a — The truck does the pickup run (Day 1)

- `day1-driver-dashboard-driver2` → `day1-driver-job-detail-driver2` — driver Rico sees the single inbound route (3T truck) and the pickup sequence.
- `day1-driver-accepted-driver2` → `day1-trip-started-driver2` — accepted, Route In Transit, live GPS on.
- `day1-stop-1-arrived-driver2` / `-loaded-` / `-delivered-` (and stops 2 and 3) — the truck visits each farm, loads cargo with photo proof, and delivers to the hub.
- `day1-run-completed-driver2` — inbound run complete, awaiting receipt confirmation.
- `day1-buyer-tracking-awaiting` → `day1-receipts-confirmed` — the coop confirms receipt; Day-1 inbound leg done.

### Part 5a — Daily customer delivery (Order #1)

- `day1out-outbound-order-form` → `day1out-outbound-order-saved` — customer order for Robinsons: Banana 350 + Pineapple 250 + Mango 200 kg (the coop keeps 200 kg Mango at the hub for later).
- `day1out-order-drafted-dispatch` → `day1out-order-dispatched-tracking-link` — dispatched on the outbound truck (DEMO-OB-001) with driver Ely; a tracking link is generated.
- `day1out-outbound-job-detail-driver` → `day1out-outbound-delivered-driver` → `day1out-outbound-finalized-driver` — Ely delivers and finalizes the trip.
- `day1out-customer-track-page` → `day1out-customer-confirm-prompt` → `day1out-customer-confirmed-complete` — the customer follows the public link and confirms receipt.
- `day1out-coop-order-completed` — order #1 is Completed.

## Day 2 — 4 farms, no single truck fits, 2 trucks

### Part 1b — Farmers post another harvest (Day 2)

- `day2-harvest-form-mango` / `-confirm-` / `-posted-` — 1000 kg Mango.
- `day2-harvest-form-banana` / `-confirm-` / `-posted-` — 800 kg Banana.
- `day2-harvest-form-pineapple` / `-confirm-` / `-posted-` — 700 kg Pineapple.
- `day2-harvest-form-durian` / `-confirm-` / `-posted-` — Lito Salvador posts 600 kg Durian.

### Part 2b — Negotiations and deals (Day 2)

- `day2-crop-detail-mango` … `day2-deal-finalized-mango` — Rosa's 1000 kg Mango deal closed.
- Repeat for Banana, Pineapple, and Durian (PHP 120/kg).

### Part 3b — Route planning: the overflow split (Day 2)

- `day2-route-map-trucks` — four farms in the pickup queue.
- `day2-plan-all-panel` — 3.1t is more than any one truck's capacity, so the planner **auto-splits onto 2 trucks** in one click: the 3T truck takes the Banana + Mango + Pineapple (2.5t) and the 2.5T truck takes the Durian (0.6t).
- `day2-route-proposals-created` — two route offers created (one per truck).
- `day2-farmer-proposal-*` / `day2-farmer-accepted-*` for all four crops — every farmer accepts.

### Part 4b — Both trucks run their legs (Day 2)

- `day2-driver-dashboard-driver2` → `day2-run-completed-driver2` — Rico drives the 3T route (stops 1–3, load + delivered shots through `day2-stop-3-delivered-driver2`).
- `day2-driver-dashboard-driver` → `day2-run-completed-driver` — Ely drives the 2.5T route with the Durian (stops 1–1, `day2-driver-accepted-driver` … `day2-stop-1-delivered-driver`).
- `day2-buyer-tracking-awaiting` → `day2-receipts-confirmed` — the coop confirms both receipts; Day-2 inbound leg done. Two routes now sit in the hub.

### Part 5b — Daily customer delivery (Order #2)

- `day2out-outbound-order-form` → `day2out-outbound-order-saved` — second Robinsons order: Mango 800 + Banana 800 + Pineapple 700 kg (leaving Mango 200 kg + Durian 600 kg at the hub).
- `day2out-order-drafted-dispatch` → `day2out-order-dispatched-tracking-link` — dispatched on the 3T truck with driver Rico.
- `day2out-outbound-job-detail-driver2` … `day2out-customer-confirmed-complete` — Rico delivers, the customer confirms via the public link.
- `day2out-coop-order-completed` — order #2 is Completed.

## After both days — one consolidated order (Order #3)

- `day3out-outbound-order-form` — final Robinsons order that **combines both days' leftovers**: Mango 400 kg (200 kg left from Day 1 + 200 kg left from Day 2) + Durian 600 kg.
- `day3out-outbound-order-saved` → `day3out-order-drafted-dispatch` → `day3out-order-dispatched-tracking-link` — dispatched on the outbound truck (DEMO-OB-001) with driver Ely.
- `day3out-outbound-job-detail-driver` → `day3out-outbound-delivered-driver` → `day3out-outbound-finalized-driver` — Ely delivers.
- `day3out-customer-track-page` → `day3out-customer-confirmed-complete` — customer confirms.
- `day3out-coop-order-completed` — order #3 is Completed.

## The whole story at a glance

- `incoming-3-completed` — the coop's **Incoming** page: **3 completed deliveries** (Day 1's 1 route + Day 2's 2 routes), exactly what the two-day story should show.
- `customer-orders-3` — the **Customer Orders** list: **3 completed orders**, each showing its truck and driver.

## How to re-run

From the repo root, with the app, Vite (port 5173), and MySQL running:

```
npx playwright test -c playwright.demo.config.ts scenario-3-combined.spec.ts
```

The seed resets all demo data once at the start, so the run is always reproducible. The
full two-day demo takes roughly 40 minutes because the demo environment intentionally
throttles GPS pings and payments; progress is logged to `runLog.json` in this folder.