# Scenario 1 — Single-Truck Demo Walkthrough

A full day-in-the-life demo of HarvestHaul from farm to customer: three farmers sell
their harvest to the coop, one truck picks everything up and brings it to the coop hub,
the coop packs a customer order for Robinsons Place General Santos, dispatches it, and
the customer confirms they received it.

Every step below has a matching screenshot in this folder. The screenshots are in
alphabetical order; the story you should read them in is the order below.

## Actors

| Role | Account | What they do |
| --- | --- | --- |
| Logistics partner (coop) | `demo.outbound.coop@harvesthaul.app` | buys from farmers, plans the route, dispatches the customer order, confirms receipts |
| Farmer Rosa Dizon | `demo.outbound.farmer@harvesthaul.app` | posts 400 kg Mango |
| Farmer Jocelyn Ramos | `demo.outbound.farmer2@harvesthaul.app` | posts 350 kg Banana |
| Farmer Dante Mabuhay | `demo.outbound.farmer3@harvesthaul.app` | posts 250 kg Pineapple |
| Driver Ely Guzman | `demo.outbound.driver@harvesthaul.app` | runs the inbound pickup route (DEMO-IB-001) and the outbound delivery (DEMO-OB-001) |
| Customer | — | Robinsons Place General Santos receives the order |

All passwords are `demo1234`.

## Part 1 — Farmers post their harvest

Each farmer posts a harvest on the crop board. Because they are coop members, the
destination automatically defaults to the Cooperative Hub.

- `harvest-form-mango` — Rosa Dizon posts 400 kg of Mango.
- `harvest-confirm-mango` — confirmation dialog before the post is saved.
- `harvest-posted-mango` — the harvest is live on the crop board.
- `harvest-form-banana` / `harvest-confirm-banana` / `harvest-posted-banana` — Jocelyn Ramos posts 350 kg of Banana.
- `harvest-form-pineapple` / `harvest-confirm-pineapple` / `harvest-posted-pineapple` — Dante Mabuhay posts 250 kg of Pineapple.

## Part 2 — Negotiations and deals

The coop buyer opens each crop on the crop board, starts a negotiation, sends terms,
the farmer agrees, and the coop finalizes the deal. The agreed price and a hauling
rate per kg are locked into the deal.

- `crop-detail-mango` — the Mango listing from Rosa Dizon.
- `negotiation-started-mango` — negotiation room opened.
- `negotiation-message-mango` — the coop introduces itself to the farmer in the chat.
- `proposal-sent-mango` — terms proposed: PHP 55/kg for 400 kg, haul PHP 2.50/kg.
- `farmer-agree-mango` — Rosa Dizon sees the proposed terms in her deals page.
- `farmer-agreed-mango` — she agrees.
- `finalize-room-mango` — the coop finalizes the deal in the negotiation room.
- `deal-finalized-mango` — the deal is locked ("DONE DEAL").
- Same flow repeats for Banana (PHP 28/kg) and Pineapple (PHP 35/kg) with Jocelyn and Dante.

## Part 3 — Route planning and pickup

The coop sees the three farms (all destined for the hub) in the pickup queue and plans
the route. With a 1 ton load and a 2,500 kg truck, one truck is enough — a single route
is offered.

- `route-map-trucks` — the three farms on the map, a truck selected.
- `plan-single-panel` — the route panel: one truck carries all three farms, with the road-distance hauling rate suggestion.
- `route-proposals-created` — a route offer is created.
- `farmer-proposal-mango` / `farmer-accepted-mango` — Rosa Dizon sees and accepts the route offer for her Mango.
- `farmer-proposal-banana` / `farmer-accepted-banana` — Jocelyn Ramos accepts.
- `farmer-proposal-pineapple` / `farmer-accepted-pineapple` — Dante Mabuhay accepts.

## Part 4 — The truck picks everything up

Driver Ely Guzman opens his driver portal, sees the inbound route, accepts it, and
starts the trip. GPS tracking runs in the background.

- `driver-dashboard-driver2` — the driver portal shows an inbound route waiting.
- `driver-job-detail-driver2` — the pickup sequence with each farm stop.
- `driver-accepted-driver2` — Ely accepts the route.
- `trip-started-driver2` — the route is In Transit, live GPS tracking active.
- `stop-1-arrived-driver2` / `stop-1-loaded-driver2` / `stop-1-delivered-driver2` — Stop 1: truck arrives, loads the cargo with photo proof, delivers it to the hub.
- `stop-2-arrived-driver2` / `stop-2-loaded-driver2` / `stop-2-delivered-driver2` — Stop 2 at the second farm.
- `stop-3-arrived-driver2` / `stop-3-loaded-driver2` / `stop-3-delivered-driver2` — Stop 3 at the third farm.
- `run-completed-driver2` — the inbound run is complete, awaiting receipt confirmation.
- `buyer-tracking-awaiting` — the coop sees 1 route awaiting receipt confirmation on its Deliveries page.
- `receipts-confirmed` — the coop confirms receipt; the inbound leg is complete.

## Part 5 — Customer order and outbound delivery

The coop creates a customer order for Robinsons Place General Santos from the crops now
at the hub, dispatches it on a dedicated outbound truck, and a public tracking link is
generated for the customer.

- `outbound-order-form` — 3 crop line items: Mango, Banana, Pineapple for Robinsons Place General Santos.
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
npx playwright test -c playwright.demo.config.ts scenario-1-single-truck.spec.ts
```

The seed resets all demo data first, so the run is always reproducible. The full demo
takes roughly 40 minutes because the demo environment intentionally throttles GPS
pings and payments; progress is logged to `runLog.json` in this folder.