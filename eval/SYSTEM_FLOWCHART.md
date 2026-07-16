# HarvestHaul — Complete System Flowchart

> Updated: 2026-07-16 | Laravel 12 | PHP 8.2+ | MySQL

---

## Table of Contents

1. [System Overview](#system-overview)
2. [User Roles & Authentication Flow](#user-roles--authentication-flow)
3. [Admin Flow](#admin-flow)
4. [Farmer Flow](#farmer-flow)
5. [Logistics Partner Flow](#logistics-partner-flow)
6. [Buyer Flow](#buyer-flow)
7. [Driver Flow](#driver-flow)
8. [Harvest Lifecycle](#harvest-lifecycle)
9. [B2B Negotiation Flow (Buyer ↔ Farmer)](#b2b-negotiation-flow-buyer--farmer)
10. [Resource Pooling & Freight Negotiation Flow](#resource-pooling--freight-negotiation-flow)
11. [Delivery Execution Flow](#delivery-execution-flow)
12. [Cost Ledger & Payment Flow](#cost-ledger--payment-flow)
13. [Invoice Generation Flow](#invoice-generation-flow)
14. [Real-Time Tracking Flow](#real-time-tracking-flow)
15. [Scheduled Tasks Flow](#scheduled-tasks-flow)
16. [Entity Relationship Diagram](#entity-relationship-diagram)
17. [Security & Vulnerability Summary](#security--vulnerability-summary)

---

## System Overview

```
┌─────────────────────────────────────────────────────────────────────────┐
│                         HARVESTHAUL PLATFORM                            │
├─────────────────────────────────────────────────────────────────────────┤
│                                                                         │
│  ┌──────────┐  ┌──────────┐  ┌──────────┐  ┌──────────┐               │
│  │  FARMER  │  │ LOGISTICS│  │  BUYER   │  │  DRIVER  │               │
│  │          │  │ PARTNER  │  │          │  │          │               │
│  └────┬─────┘  └────┬─────┘  └────┬─────┘  └────┬─────┘               │
│       │              │              │              │                     │
│       ▼              ▼              ▼              ▼                     │
│  ┌─────────────────────────────────────────────────────────────────┐   │
│  │                    LARAVEL 12 WEB APP                            │   │
│  │  ┌─────────┐ ┌──────────┐ ┌──────────┐ ┌──────────────────┐   │   │
│  │  │Harvests │ │ Pooling  │ │B2B Negot │ │  Cost Ledger     │   │   │
│  │  │  CRUD   │ │  Jobs +  │ │  Chat +  │ │  + Payment       │   │   │
│  │  │         │ │Freight   │ │  Deals   │ │  Tracking        │   │   │
│  │  └─────────┘ │Negotiate │ └──────────┘ └──────────────────┘   │   │
│  │  ┌─────────┐ └──────────┘ ┌──────────┐ ┌──────────────────┐   │   │
│  │  │Invoices │ │ Weather    │ │ Predictor│ │  Documents      │   │   │
│  │  │  (PDF)  │ │  (API)     │ │(Scraper) │ │   (Upload)      │   │   │
│  │  └─────────┘ └────────────┘ └──────────┘ └──────────────────┘   │   │
│  └─────────────────────────────────────────────────────────────────┘   │
│       │                    │                │                           │
│       ▼                    ▼                ▼                           │
│  ┌──────────┐     ┌──────────────┐   ┌──────────────────┐             │
│  │ MySQL DB │     │  WebSocket   │   │  OpenWeatherMap  │             │
│  │ 46+ Tables│    │  Server:8080 │   │     API          │             │
│  └──────────┘     │ (DB-polling) │   └──────────────────┘             │
│                   └──────────────┘                                     │
└─────────────────────────────────────────────────────────────────────────┘
```

### Services Layer

```
┌─────────────────────────────────────────────────────────────────────┐
│  Service                    │  Purpose                              │
├─────────────────────────────┼───────────────────────────────────────┤
│  ResourcePoolingService     │  Knapsack + nearest-neighbor TSP     │
│  InvoiceService             │  PDF generation via DomPDF           │
│  WeatherService             │  OpenWeatherMap integration          │
│  ETAService                 │  Real-time ETA with confidence       │
│  DelayDetectionService      │  Stall/GPS/stop/ETA alerting         │
│  DriverAssignmentService    │  Nearest available driver matching   │
│  GeometryHelper (trait)     │  Haversine distance formula          │
└─────────────────────────────┴───────────────────────────────────────┘
```

---

## User Roles & Authentication Flow

```
                    ┌─────────────────┐
                    │   NEW VISITOR   │
                    └────────┬────────┘
                             │
                             ▼
                    ┌─────────────────┐
                    │   LANDING PAGE  │
                    │      (/)        │
                    └────────┬────────┘
                             │
              ┌──────────────┼──────────────┐
              ▼              ▼              ▼
        ┌──────────┐  ┌──────────┐  ┌──────────┐
        │  LOGIN   │  │ REGISTER │  │  GUEST   │
        │ /login   │  │          │  │  BROWSE  │
        └────┬─────┘  └────┬─────┘  └──────────┘
             │              │
             │              ▼
             │     ┌─────────────────┐
             │     │ ROLE SELECTION  │
             │     │ /register/{role}│
             │     └───┬───┬───┬─────┘
             │         │   │   │
             │    ┌────┘   │   └────┐
             │    ▼        ▼        ▼
             │ ┌──────┐ ┌──────┐ ┌──────┐
             │ │FARMER│ │BUYER │ │LOGIST│
             │ │ REG  │ │ REG  │ │ REG  │
             │ └──┬───┘ └──┬───┘ └──┬───┘
             │    │        │        │
             │    ▼        ▼        ▼
             │ ┌──────────────────────────┐
             │ │   CREATE USER + PROFILE  │
             │ │   status = 'active'      │
             │ │   email_verified_at=NULL │
             │ └──────────┬───────────────┘
             │            │
             │            ▼
             │ ┌──────────────────────────┐
             │ │  AUTO LOGIN (Auth::login)│
             │ └──────────┬───────────────┘
             │            │
             │            ▼
             │ ┌──────────────────────────┐
             │ │  OTP VERIFICATION PAGE   │
             │ │  /email/verify-otp       │
             │ │  (6-digit, 10min expiry) │
             │ │  throttle:5,1            │
             │ └──────────┬───────────────┘
             │            │
             │            ▼
             │ ┌──────────────────────────┐
             │ │  EMAIL VERIFIED ✓        │
             │ └──────────┬───────────────┘
             │            │
             ▼            ▼
    ┌─────────────────────────────────────┐
    │         LOGIN (email+password)      │
    │         throttle:5,1                │
    └────────────────┬────────────────────┘
                     │
                     ▼
    ┌─────────────────────────────────────┐
    │      DASHBOARD SWITCHER             │
    │      /dashboard                     │
    │                                     │
    │  role=admin     → /admin            │
    │  role=farmer    → /harvests         │
    │  role=buyer     → /buyer            │
    │  role=driver    → /driver           │
    │  role=logistics → /pooling          │
    └─────────────────────────────────────┘
```

### Middleware Stack

```
Request
  │
  ▼
┌─────────────────────┐
│   auth              │ ← Must be authenticated (session-based, NOT Sanctum)
└──────────┬──────────┘
           │
           ▼
┌─────────────────────┐
│ EnsureAccountIsActive│ ← Force-logout if status='inactive'
└──────────┬──────────┘
           │
           ▼
┌─────────────────────┐
│     verified        │ ← Email must be verified (OTP)
└──────────┬──────────┘
           │
           ▼
┌─────────────────────┐
│  Role Middleware     │ ← EnsureUserIsAdmin / Farmer / Logistics / Driver / Buyer
│  (per route group)  │   Note: EnsureUserIsBuyer also allows cooperative LPs
└──────────┬──────────┘
           │
           ▼
┌─────────────────────┐
│  CheckRole           │ ← Parameterized: middleware('role:admin,farmer')
│  (optional)         │   Accepts variadic role list
└──────────┬──────────┘
           │
           ▼
      Controller
```

### Rate Limiting

```
┌──────────────────────────────────────────────────────┐
│  Endpoint                    │  Throttle              │
├──────────────────────────────┼────────────────────────┤
│  POST /login                 │  5 attempts / 1 min    │
│  POST /email/verify-otp      │  5 attempts / 1 min    │
│  POST /email/resend-otp      │  3 attempts / 1 min    │
│  POST /negotiations/start    │ 10 attempts / 1 min    │
│  POST /negotiations/{id}/msg │ 15 attempts / 1 min    │
│  POST /negotiations/{id}/*   │ 10 attempts / 1 min    │
│  POST /pooling/*/accept      │ 30 attempts / 1 min    │
│  POST /pooling/*/reject      │ 30 attempts / 1 min    │
│  POST /pooling/*/counter     │ 30 attempts / 1 min    │
│  POST /driver/tracking/store │ 12 attempts / 1 min    │
│  POST /tracking/stream       │ 12 attempts / 1 min    │
└──────────────────────────────┴────────────────────────┘
```

---

## Admin Flow

```
                    ┌───────────────────┐
                    │  ADMIN DASHBOARD  │
                    │     /admin        │
                    └─────────┬─────────┘
                              │
    ┌──────────┬──────────┬───┴────┬──────────┬──────────┬──────────┐
    ▼          ▼          ▼        ▼          ▼          ▼          ▼
┌───────┐┌───────┐┌────────┐┌────────┐┌────────┐┌────────┐┌────────┐
│ USERS ││FARMERS││LOGISTICS││ BUYERS ││DRIVERS ││HARVESTS││ ANALYTICS│
│ manage││verify ││ verify ││ verify ││verify  ││ browse ││ + EXPORT │
└───┬───┘└───┬───┘└───┬────┘└───┬────┘└───┬────┘└────────┘└────────┘
    │        │        │         │         │
    ▼        ▼        ▼         ▼         ▼
```

### User Management

```
┌──────────────────────────────────────────────────────┐
│  USER CRUD                                           │
├──────────────────────────────────────────────────────┤
│  GET  /admin/users          → User list              │
│  POST /admin/users          → Create user + profile  │
│  PUT  /admin/users/{user}   → Update user            │
│  POST /admin/users/{user}/status → Toggle active/     │
│                                    inactive           │
│                                                      │
│  Each action → AuditLog::create()                    │
└──────────────────────────────────────────────────────┘
```

### Verification Management

```
┌──────────────────────────────────────────────────────┐
│  FARMER VERIFICATION                                 │
│  GET  /admin/farmers            → List pending/verified│
│  POST /admin/farmers/{user}/verify   → Mark verified  │
│  POST /admin/farmers/{user}/reject   → Mark rejected  │
├──────────────────────────────────────────────────────┤
│  LOGISTICS VERIFICATION                              │
│  GET  /admin/logistics          → List pending/verified│
│  POST /admin/logistics/{user}/verify                 │
│  POST /admin/logistics/{user}/reject                 │
├──────────────────────────────────────────────────────┤
│  BUYER VERIFICATION                                  │
│  GET  /admin/buyers             → List pending/verified│
│  POST /admin/buyers/{user}/verify                   │
│  POST /admin/buyers/{user}/reject                   │
├──────────────────────────────────────────────────────┤
│  DRIVER IDENTITY VERIFICATION                        │
│  GET  /admin/drivers            → List all drivers    │
│  POST /admin/drivers/{user}/verify-identity          │
│  POST /admin/drivers/{user}/reject-identity          │
│  DriverProfile: identity_verified, id_photo_path,    │
│                 selfie_path                          │
└──────────────────────────────────────────────────────┘
```

### Document Approval

```
┌──────────────────────────────────────────────────────┐
│  FARMER DOCUMENTS                                    │
│  GET  /admin/farmer-documents                         │
│  PATCH /admin/farmer-documents/{doc}/approve          │
│  PATCH /admin/farmer-documents/{doc}/reject           │
│  Fields: status, admin_notes, reviewed_by             │
├──────────────────────────────────────────────────────┤
│  LOGISTICS DOCUMENTS                                 │
│  GET  /admin/logistics-documents                      │
│  PATCH /admin/logistics-documents/{doc}/approve       │
│  PATCH /admin/logistics-documents/{doc}/reject        │
│  Fields: status, business_permit_match_confirmed      │
└──────────────────────────────────────────────────────┘
```

### Crop Management

```
┌──────────────────────────────────────────────────────┐
│  CROP MATRIX (3-level hierarchy)                     │
│  /admin/crops → CropManagerController                │
│                                                      │
│  CropCategory ──┬── Crop ──┬── CropVariety           │
│  (Fruits)       │  (Mango) │  (Carabao, Pico)       │
│  (Vegetables)   │  (Banana)│  (Cavendish, Saba)     │
│  (Specialty)    │  (Coffee)│  (Robusta, Arabica)     │
│                                                      │
│  CRUD for Categories, Crops, Varieties               │
│  POST /admin/crops/{crop}/baseline-price             │
│  Updates baseline_price_per_kg on Crop               │
└──────────────────────────────────────────────────────┘
```

### Data Export

```
┌──────────────────────────────────────────────────────┐
│  GET /admin/export/users      → Export user data     │
│  GET /admin/export/harvests   → Export harvest data  │
└──────────────────────────────────────────────────────┘
```

### Analytics & Audit

```
┌──────────────────────────────────────────────────────┐
│  GET /admin/analytics  → Aggregated platform stats   │
│  GET /admin/audit-logs → Full audit trail            │
└──────────────────────────────────────────────────────┘
```

---

## Farmer Flow

```
                    ┌───────────────────┐
                    │ FARMER DASHBOARD  │
                    │     /harvests     │
                    └─────────┬─────────┘
                              │
    ┌──────────┬──────────┬───┴────┬──────────┬──────────┬──────────┐
    ▼          ▼          ▼        ▼          ▼          ▼          ▼
┌────────┐┌────────┐┌────────┐┌────────┐┌────────┐┌────────┐┌────────┐
│PROFILE ││HARVESTS││DOCUMENTS││PROPOSALS││NEGOTIA-││TRACKING││PREDICT │
│        ││  CRUD  ││(upload)││ FROM LP ││TIONS   ││        ││OR      │
└────────┘└───┬────┘└────────┘└───┬────┘└───┬────┘└────────┘└────────┘
              │                   │         │
              ▼                   ▼         ▼
```

### Harvest Management

```
┌─────────────────────────────────────────────────────────────────┐
│                    HARVEST MANAGEMENT                            │
├─────────────────────────────────────────────────────────────────┤
│                                                                 │
│  ┌─────────────┐    ┌─────────────┐    ┌─────────────┐        │
│  │   CREATE    │    │   EDIT      │    │   DELETE    │        │
│  │ POST/harvests│   │PUT/harvests │    │DELETE/harv. │        │
│  └──────┬──────┘    └──────┬──────┘    └──────┬──────┘        │
│         │                  │                  │                │
│         ▼                  ▼                  ▼                │
│  ┌─────────────┐    ┌─────────────┐    ┌─────────────┐        │
│  │Validate:    │    │Check:       │    │Check:       │        │
│  │• crop_cat   │    │• ownership  │    │• ownership  │        │
│  │• crop_var   │    │• LOCKED     │    │• LOCKED     │        │
│  │• quantity   │    │  status     │    │  status     │        │
│  │• price/kg   │    │• is_verified│    │             │        │
│  │• location   │    │             │    │             │        │
│  │• image      │    │             │    │             │        │
│  │• destination│    │             │    │             │        │
│  └──────┬──────┘    └──────┬──────┘    └──────┬──────┘        │
│         │                  │                  │                │
│         ▼                  ▼                  ▼                │
│  ┌──────────────────────────────────────────────────┐         │
│  │  Fields: crop_category_id, crop_id, crop_variety │         │
│  │  quantity_kg, remaining_quantity_kg, visibility,  │         │
│  │  harvest_date, quality_grade, packaging_type,     │         │
│  │  crop_photos[], latitude, longitude,              │         │
│  │  destination_id, destination_address              │         │
│  └──────────────────────────────────────────────────┘         │
└─────────────────────────────────────────────────────────────────┘
```

### Harvest Status State Machine (9 statuses)

```
                    ┌──────────┐
          create    │          │
    ───────────────►│ pending  │
                    │          │
                    └────┬─────┘
                         │ approve
                         ▼
                    ┌──────────┐  buyer starts   ┌────────────┐
                    │          │  negotiation    │            │
                    │  active  ├────────────────►│negotiating │
                    │          │                 │            │
                    └──┬───┬───┘                 └──┬────┬────┘
                       │   │                        │    │
                       │   │ direct sale            │    │ cancel
                       │   │                        │    │ (no other
                       │   ▼                        │    │  deals)
          partial sale│ ┌──────────┐                │    │
          ┌───────────┤ │  sold    │◄───────────────┘    │
          │           │ └──────────┘  full sale          │
          ▼           │       │                          │
   ┌──────────────┐   │       │ assign                   │
   │partially_sold│   │       │ to pool                  │
   │              │   │       ▼                          │
   └──────┬───────┘   │  ┌──────────┐                   │
          │            │  │ assigned │                   │
          │ full sale  │  └────┬─────┘                   │
          └────────────┤       │ driver starts           │
                       │       ▼                         │
                       │  ┌──────────────┐               │
                       │  │ in_progress  │               │
                       │  └──────┬───────┘               │
                       │         │ delivery done         │
                       │         ▼                       │
                       │  ┌──────────┐                   │
                       │  │completed │                   │
                       │  └──────────┘                   │
                       │                                  │
                       │  ┌──────────┐                   │
                       └─►│cancelled │◄──────────────────┘
                          └──────────┘

  LOCKED statuses (cannot edit harvest):
  negotiating, partially_sold, sold, assigned, in_progress, completed, cancelled

  BUYER_AVAILABLE statuses (visible on crop board):
  active, partially_sold

  LOGISTICS_VISIBLE statuses (visible on routing map):
  sold, partially_sold
```

### Harvest Visibility Rules

```
┌─────────────────────────────────────────────────────────┐
│  VISIBILITY: 'both'                                     │
│  → Shown to ALL logistics partners + ALL buyers         │
├─────────────────────────────────────────────────────────┤
│  VISIBILITY: 'buyers_only'                              │
│  → Shown to buyers in same cooperative                  │
│  → Hidden from logistics partners until negotiation     │
└─────────────────────────────────────────────────────────┘
```

### Cooperative / Independent Scoping

```
┌─────────────────────────────────────────────────────────┐
│  Farmers belong to a cooperative OR are independent      │
│  FarmerProfile.affiliation_type: 'cooperative'|'independent'│
│  FarmerProfile.cooperative_id → LogisticsProfile        │
│                                                         │
│  Crop board scoping:                                    │
│  • Cooperative buyer → sees cooperative farmers only    │
│  • Independent buyer → sees independent farmers only    │
│  • Cooperative LP → sees cooperative farmers only       │
│  • Company LP → sees all sold harvests                  │
└─────────────────────────────────────────────────────────┘
```

### Document Management

```
┌─────────────────────────────────────────────────────────┐
│  /my-documents                                          │
│  ├── Create: Upload document (file + type)              │
│  ├── Store: Save to disk + DB with status='pending'     │
│  ├── Delete: Remove file + DB record                    │
│  └── Admin reviews → approve/reject                     │
└─────────────────────────────────────────────────────────┘
```

### Crop Price Predictor

```
┌─────────────────────────────────────────────────────────┐
│  /farmer/predictor                                      │
│  ├── Select crop + quantity                             │
│  ├── POST /predictor/analyze                            │
│  ├── PredictorController scrapes DA prices              │
│  ├── Returns price forecast + recommendation           │
│  └── Uses cached data (15-min TTL)                      │
│                                                         │
│  Background: crops:scrape command updates               │
│  baseline_price_per_kg from DA portal                   │
│  Fallback: hardcoded Mindanao price bulletin values     │
└─────────────────────────────────────────────────────────┘
```

### Farmer Proposal Actions (Counter-Offer)

```
┌─────────────────────────────────────────────────────────┐
│  /farmer/proposals                                      │
│  ├── View pooling job proposals from logistics partners │
│  ├── Accept proposal → pivot.status = 'accepted'       │
│  ├── Reject proposal → pivot.status = 'rejected'       │
│  └── Counter-offer:                                     │
│      • Submit counter_price (25%-175% of reference)     │
│      • Max 5 negotiation_rounds                         │
│      • Only updates this farmer's cost_share pivot      │
│      • Notifies logistics partner                       │
│      • Route: POST /pooling/{poolingJob}/counter        │
└─────────────────────────────────────────────────────────┘
```

---

## Logistics Partner Flow

```
                    ┌───────────────────────────┐
                    │ LOGISTICS PARTNER DASH    │
                    │      /pooling             │
                    └─────────────┬─────────────┘
                                  │
    ┌──────────┬──────────┬───────┼──────┬──────────┬──────────┐
    ▼          ▼          ▼       ▼      ▼          ▼          ▼
┌────────┐┌────────┐┌────────┐┌──────┐┌────────┐┌────────┐┌────────┐
│PROFILE ││VEHICLES││DRIVERS ││ROUTE ││DOCS    ││ANALYTICS││COST    │
│        ││(Trucks)││(fleet) ││OPTIM.││        ││(Fleet) ││LEDGER  │
└────────┘└────────┘└────────┘└──┬───┘└────────┘└────────┘└────────┘
                                 │
                                 ▼
```

### Vehicle (Truck) Management

```
┌─────────────────────────────────────────────────────────┐
│  /vehicles                                               │
│  ├── CRUD: plate_number, truck_name, capacity_kg,       │
│  │         vehicle_type, status, notes                  │
│  ├── Status: available / reserved / maintenance         │
│  ├── Linked to logistics_profile_id                     │
│  └── Scopes: available(), forPartner(), withDriver()    │
└─────────────────────────────────────────────────────────┘
```

### Driver Fleet Management

```
┌─────────────────────────────────────────────────────────┐
│  /drivers                                                │
│  ├── CRUD: first_name, last_name, license_number, phone │
│  ├── Linked to logistics_profile_id + user_id           │
│  ├── Driver gets login credentials (role=driver)        │
│  └── Auto-assign: POST /route-optimization/auto-assign  │
│      Uses DriverAssignmentService                       │
│      → Nearest available driver (haversine)             │
│      → Real-time heartbeat GPS or profile fallback      │
│      → 8-hour rest period enforcement                   │
│      → Best available truck matched                     │
└─────────────────────────────────────────────────────────┘
```

### Route Optimization & Pooling

```
┌─────────────────────────────────────────────────────────────────┐
│                  RESOURCE POOLING (ROUTES)                       │
├─────────────────────────────────────────────────────────────────┤
│                                                                 │
│  ┌─────────────┐                                               │
│  │  BROWSE MAP │  View harvests on Leaflet map                 │
│  │ (crop board)│ Filter by crop, location, status              │
│  └──────┬──────┘                                               │
│         │                                                       │
│         ▼                                                       │
│  ┌─────────────┐                                               │
│  │ PLAN ROUTE  │  POST /pooling/plan (AJAX)                    │
│  │             │  Select harvests (harvest_ids[])               │
│  │             │  Select truck + driver                         │
│  │             │  Set start/end coordinates + radius            │
│  └──────┬──────┘                                               │
│         │                                                       │
│         ▼                                                       │
│  ┌─────────────────────────────────────────────────┐           │
│  │  ResourcePoolingService::plan()                  │           │
│  │                                                   │           │
│  │  1. Fetch sold/partially_sold harvests in radius  │           │
│  │  2. Validate: single buyer, no conflicts,         │           │
│  │     no pending pooling jobs on same harvests      │           │
│  │  3. Knapsack: fit harvests to truck capacity      │           │
│  │     (brute-force n≤20, greedy n>20)               │           │
│  │  4. Nearest-neighbor: optimize pickup order       │           │
│  │  5. Sequence dropoffs (deduplicated destinations) │           │
│  │  6. Haversine distance calculation                │           │
│  │  7. Reference price: (dist×₱15) + (weight×₱0.50) │           │
│  │     + ₱250 base fee                              │           │
│  │  8. Per-farmer cost: proportional by weight ×     │           │
│  │     haul distance allocation score                │           │
│  │  9. Weather check per waypoint                    │           │
│  │ 10. Fuel sufficiency warning                      │           │
│  └──────┬──────────────────────────────────────────┘           │
│         │                                                       │
│         ▼                                                       │
│  ┌─────────────┐                                               │
│  │ CONFIRM     │  POST /pooling/confirm                        │
│  │             │  Server recalculates all costs                 │
│  │             │  DB::transaction + lockForUpdate on truck      │
│  │             │  Creates PoolingJob + pivot records            │
│  │             │  Harvests → 'assigned', Truck → 'reserved'    │
│  │             │  Status: pending                               │
│  │             │  proposal_expires_at = now() + 48h             │
│  └──────┬──────┘                                               │
│         │                                                       │
│         ▼                                                       │
│  ┌─────────────────────────────────────────────────┐           │
│  │  PROPOSALS SENT TO FARMERS                       │           │
│  │  Each harvest gets pivot:                         │           │
│  │  • proposed_cost_per_kg                           │           │
│  │  • acceptance_status = 'pending'                  │           │
│  │  • pickup_order, quantity_kg, cost_share           │           │
│  │  Notification sent to each farmer                 │           │
│  └──────────────────────────────────────────────────┘           │
│         │                                                       │
│    ┌────┴──────────────────────────┐                           │
│    ▼                               ▼                            │
│ ┌──────┐  ┌──────┐  ┌────────────────────┐                    │
│ │ACCEPT│  │REJECT│  │COUNTER (25-175%)   │  ← Farmer actions  │
│ └──┬───┘  └──┬───┘  └─────────┬──────────┘                    │
│    │         │                 │                                │
│    │         │                 ▼                                │
│    │         │    ┌────────────────────────┐                   │
│    │         │    │ LP can:                 │                   │
│    │         │    │ • Accept counter        │                   │
│    │         │    │ • Counter-bid to ALL    │                   │
│    │         │    │   (resets all farmers   │                   │
│    │         │    │    to pending)           │                   │
│    │         │    │ Max 5 rounds            │                   │
│    │         │    └────────────────────────┘                   │
│    │         │                                                  │
│    ▼         ▼                                                  │
│  ┌─────────────────────────────────────────────────┐           │
│  │  ALL ACCEPTED?                                   │           │
│  │  ├── YES: status → 'confirmed'                   │           │
│  │  │         Driver assigned (auto or manual)      │           │
│  │  │         Notify driver                         │           │
│  │  └── NO:  Wait for other farmers                 │           │
│  │            (48h timeout → auto-reject)            │           │
│  └─────────────────────────────────────────────────┘           │
└─────────────────────────────────────────────────────────────────┘
```

### Fleet Analytics

```
┌─────────────────────────────────────────────────────────┐
│  GET /logistics/analytics → CostLedgerController        │
│                                                         │
│  Per-truck metrics:                                     │
│  • Total fuel (liters), total fuel cost                 │
│  • KPL (kilometers per liter)                           │
│  • Completed trips, revenue, net income                 │
│                                                         │
│  Fleet-wide summary:                                    │
│  • Total refuels, fuel cost, fuel liters                │
│  • Total revenue                                        │
└─────────────────────────────────────────────────────────┘
```

---

## Buyer Flow

```
                    ┌───────────────────┐
                    │  BUYER DASHBOARD  │
                    │     /buyer        │
                    └─────────┬─────────┘
                              │
          ┌──────────┬────────┼────────┬──────────┐
          ▼          ▼        ▼        ▼          ▼
    ┌──────────┐┌──────────┐┌──────────┐┌──────────┐┌──────────┐
    │  PROFILE ││CROP BOARD││NEGOTIATE ││ TRACKING ││DELIVERY  │
    │          ││          ││          ││          ││CONFIRM   │
    └──────────┘└────┬─────┘└────┬─────┘└────┬─────┘└────┬─────┘
                     │           │           │           │
                     ▼           ▼           ▼           ▼
```

### Buyer Dashboard

```
┌─────────────────────────────────────────────────────────┐
│  Buyer Dashboard shows:                                  │
│  • Active negotiations (OPEN/AGREED)                    │
│  • Completed deal count                                 │
│  • Recent crop board posts (6 items, scoped)            │
│  • Pending delivery confirmations (awaiting_confirm)    │
└─────────────────────────────────────────────────────────┘
```

### Crop Board

```
┌─────────────────────────────────────────────────────────┐
│  /buyer/crop-board                                       │
│  ├── Paginated (12 per page)                            │
│  ├── Scoped by buyer affiliation:                       │
│  │   • Cooperative buyer → cooperative farmers only     │
│  │   • Independent buyer → independent farmers only     │
│  ├── Status filters: active, negotiating, sold          │
│  ├── Under Negotiation: grayed out + badge              │
│  ├── Leaflet map integration                            │
│  └── Click harvest → /buyer/crop-board/{harvest}        │
│                                                         │
│  Crop Detail page:                                      │
│  ├── Full product detail                                │
│  ├── Blocks if negotiating by another buyer             │
│  ├── Shows if this buyer has active negotiation         │
│  └── Start Negotiation button                           │
└─────────────────────────────────────────────────────────┘
```

### Delivery Tracking

```
┌─────────────────────────────────────────────────────────┐
│  /buyer/tracking                                         │
│  ├── Active deliveries: in_progress + awaiting_confirm  │
│  ├── Completed deliveries: last 10                      │
│  ├── Live GPS tracking (WebSocket + Leaflet)            │
│  ├── ETA display with confidence score                  │
│  └── Full eager loading: truck, crops, farmer, driver   │
└─────────────────────────────────────────────────────────┘
```

### Confirm Receipt

```
┌─────────────────────────────────────────────────────────┐
│  POST /buyer/deliveries/{poolingJob}/confirm             │
│  ├── Transitions: awaiting_confirmation → completed     │
│  ├── Marks buyer_confirmed_at on all pivot entries      │
│  ├── Audit logged                                       │
│  └── Logistics partner notified                         │
│                                                         │
│  Auto-complete: 48h no confirmation → auto-completed    │
└─────────────────────────────────────────────────────────┘
```

---

## Driver Flow

```
                    ┌───────────────────┐
                    │  DRIVER DASHBOARD │
                    │     /driver       │
                    └─────────┬─────────┘
                              │
          ┌──────────┬────────┼────────┬──────────┐
          ▼          ▼        ▼        ▼          ▼
    ┌──────────┐┌──────────┐┌──────────┐┌──────────┐┌──────────┐
    │ ACTIVE   ││JOB DETAIL││GPS TRACK ││FUEL LOG  ││IDENTITY  │
    │ JOBS     ││(stops)   ││(stream)  ││          ││UPLOAD    │
    └──────────┘└──────────┘└──────────┘└──────────┘└──────────┘
```

### Driver Dashboard

```
┌─────────────────────────────────────────────────────────┐
│  GET /driver/                                             │
│  ├── Lists jobs assigned to this driver                  │
│  │   (confirmed + in_progress only)                      │
│  ├── Completed job count                                 │
│  └── Accept job button (for new confirmed jobs)          │
└─────────────────────────────────────────────────────────┘
```

### Job Acceptance

```
┌─────────────────────────────────────────────────────────┐
│  POST /driver/jobs/{poolingJob}/accept                   │
│  ├── Driver explicitly accepts confirmed job            │
│  ├── Sets accepted_at timestamp                         │
│  └── Validates: job must be 'confirmed', driver assigned│
└─────────────────────────────────────────────────────────┘
```

### Per-Stop Status Management

```
┌─────────────────────────────────────────────────────────┐
│  Sequential stop workflow per harvest:                   │
│                                                         │
│  ┌──────────┐    ┌──────────┐    ┌──────────┐    ┌──────────┐
│  │ assigned │───►│ arrived  │───►│  loaded  │───►│ delivered│
│  └──────────┘    └──────────┘    └──────────┘    └──────────┘
│                       │                │                │
│                  Geofence ≤500m   Load photo +     Delivery receipt│
│                  around farm GPS  Crop confirm +   + quantity check│
│                                   Qty ≤ allocation                 │
│                                                         │
│  Routes:                                                │
│  PATCH /driver/jobs/{poolingJob}/harvests/{harvest}/status│
│                                                         │
│  Side effects:                                          │
│  • arrived → timestamp recorded                         │
│  • loaded → Harvest status → in_progress                │
│  • delivered → Harvest status → completed               │
│  • delivered → Buyer notified (B2B purchases)           │
└─────────────────────────────────────────────────────────┘
```

### Status Checkpoint (Job-Level)

```
┌─────────────────────────────────────────────────────────┐
│  PATCH /driver/jobs/{poolingJob}/status                  │
│                                                         │
│  confirmed → in_progress                                │
│  (requires at least 1 GPS ping)                         │
│                                                         │
│  in_progress → awaiting_confirmation                    │
│  (requires end_odometer_reading AND                     │
│   all stops must be 'delivered')                        │
│  Calculates actual_distance_km from GPS records         │
│  Notifies: LP, all farmers, buyer                       │
└─────────────────────────────────────────────────────────┘
```

### GPS Telemetry

```
┌─────────────────────────────────────────────────────────┐
│  POST /driver/tracking/store (throttle:12,1)             │
│  ├── Validates: job exists, driver assigned, in_progress│
│  ├── GPS accuracy filter: rejects if > 500 meters      │
│  ├── Deduplication: skips if same coords within 30s    │
│  ├── Computes speed (km/h) from previous position      │
│  ├── Computes bearing from previous position           │
│  └── Saves to tracking_records table                    │
│                                                         │
│  Alternative: POST /tracking/stream (cross-domain)     │
└─────────────────────────────────────────────────────────┘
```

### Fuel Logging

```
┌─────────────────────────────────────────────────────────┐
│  POST /driver/jobs/{poolingJob}/fuel-log                 │
│  ├── Fields: fuel_liters, cost, odometer_reading        │
│  ├── Duplicate odometer prevention per truck            │
│  ├── Audit trail for every entry                        │
│  └── Linked to driver_id + truck_id                     │
└─────────────────────────────────────────────────────────┘
```

### Identity Verification

```
┌─────────────────────────────────────────────────────────┐
│  POST /driver/identity-upload                            │
│  ├── Upload ID photo + selfie                           │
│  ├── Sets identity_verified = false (pending review)    │
│  ├── Admin verifies via POST /admin/drivers/{id}/verify │
│  └── DriverProfile: id_photo_path, selfie_path          │
└─────────────────────────────────────────────────────────┘
```

---

## Harvest Lifecycle

See [Harvest Status State Machine](#harvest-status-state-machine-9-statuses) in Farmer Flow section.

Key transitions:
```
pending → active           (admin/farmer approves draft)
active → negotiating       (buyer starts B2B negotiation)
active → sold              (direct sale, no negotiation)
active → partially_sold    (partial sale finalized)
negotiating → active       (cancelled, no other deals)
negotiating → partially_sold (cancelled, other deals exist)
negotiating → sold         (full sale via B2B deal)
partially_sold → sold      (remaining quantity sold)
sold → assigned            (pooling job confirmed)
assigned → in_progress     (driver starts trip)
in_progress → completed    (delivery finished)
any active → cancelled     (farmer/admin cancels)
```

---

## B2B Negotiation Flow (Buyer ↔ Farmer)

```
┌─────────────────────────────────────────────────────────────────┐
│                    NEGOTIATION FLOW                              │
├─────────────────────────────────────────────────────────────────┤
│                                                                 │
│  ┌──────────────┐                                              │
│  │ START NEGOTIATION                                           │
│  │ POST /negotiations/start (throttle:10,1)                    │
│  │ (Buyer initiates from crop detail)                          │
│  └──────┬───────┘                                              │
│         │                                                       │
│         ▼                                                       │
│  ┌──────────────────────────────────────────────┐              │
│  │  NegotiationController::start()               │              │
│  │  1. Check harvest status in BUYER_AVAILABLE   │              │
│  │     (active or partially_sold)                │              │
│  │  2. Check buyer != farmer (no self-deal)      │              │
│  │  3. Check no existing OPEN negotiation        │              │
│  │  4. Check cooperative/independent scoping     │              │
│  │  5. Create Negotiation (status=OPEN)          │              │
│  │  6. Harvest status → negotiating              │              │
│  │  7. Notify farmer                             │              │
│  │  8. DB::transaction + lockForUpdate           │              │
│  └──────┬───────────────────────────────────────┘              │
│         │                                                       │
│         ▼                                                       │
│  ┌──────────────────────────────────────────────┐              │
│  │  CHAT ROOM                                    │              │
│  │  GET /negotiations/{id}                        │              │
│  │  Real-time messaging via AJAX polling          │              │
│  │  Max 10 offer rounds (system message prefix)  │              │
│  │                                                │              │
│  │  ┌──────────┐  ┌──────────┐  ┌──────────┐    │              │
│  │  │ SEND MSG │  │ PROPOSE  │  │  COUNTER │    │              │
│  │  │ /message │  │ /propose │  │ /propose │    │              │
│  │  └──────────┘  └──────────┘  └──────────┘    │              │
│  └──────────────────────────────────────────────┘              │
│         │                                                       │
│    ┌────┴──────────────────────┐                               │
│    ▼                          ▼                                │
│ ┌──────────┐           ┌──────────┐                            │
│ │ AGREE    │           │ CANCEL   │                            │
│ │ /agree   │           │ /cancel  │                            │
│ └────┬─────┘           └────┬─────┘                            │
│      │                      │                                  │
│      ▼                      ▼                                  │
│ ┌──────────────┐    ┌──────────────┐                          │
│ │ Status →     │    │ Status →     │                          │
│ │ AGREED       │    │ CANCELLED    │                          │
│ └──────┬───────┘    │ Harvest →    │                          │
│        │            │ active (or   │                          │
│        ▼            │ partially_   │                          │
│ ┌──────────────┐    │ sold)        │                          │
│ │ FINALIZE DEAL│    └──────────────┘                          │
│ │ /finalize    │                                              │
│ │              │                                              │
│ │ • agr_price  │                                              │
│ │ • quantity   │                                              │
│ │ • drop-off   │                                              │
│ │   coordinates│                                              │
│ │ • harvest    │                                              │
│ │   → sold     │                                              │
│ │ • status     │                                              │
│ │   → COMPLETED│                                              │
│ └──────────────┘                                              │
└─────────────────────────────────────────────────────────────────┘
```

### Negotiation Status State Machine

```
  ┌────────┐  buyer/farmer   ┌────────┐  agree    ┌──────────┐
  │        │   initiates     │        │  terms    │          │
  │ (none) ├────────────────►│  OPEN  ├──────────►│ AGREED   │
  │        │                 │        │           │          │
  └────────┘                 └────┬───┘           └────┬─────┘
                                 │                     │
                                 │ cancel              │ finalize
                                 ▼                     ▼
                          ┌──────────┐         ┌──────────┐
                          │CANCELLED │         │COMPLETED │
                          └──────────┘         └──────────┘

  Status values: OPEN | AGREED | COMPLETED | CANCELLED

  Guards:
  • proposeTerms: blocks if AGREED or COMPLETED
  • agreeTerms: requires OPEN
  • finalizeDeal: requires AGREED (under lock)
  • cancelDeal: allows OPEN or AGREED
  • sendMessage: blocks if COMPLETED or CANCELLED

  Auto-close: 48h inactivity → CANCELLED (daily cron)
  Throttle: 10-15 req/min on all mutation endpoints
```

---

## Resource Pooling & Freight Negotiation Flow

```
┌─────────────────────────────────────────────────────────────────┐
│              COMPLETE POOLING LIFECYCLE                          │
├─────────────────────────────────────────────────────────────────┤
│                                                                 │
│  1. PLAN (LP)                                                   │
│  ┌─────────────────────────────────────────┐                   │
│  │ Select harvests → knapsack optimization │                   │
│  │ Select truck + driver                   │                   │
│  │ Set start/end coordinates               │                   │
│  │ → ResourcePoolingService::plan()        │                   │
│  │ → Route + cost estimates                │                   │
│  └───────────────────┬─────────────────────┘                   │
│                      │                                          │
│  2. CONFIRM (LP)                                                │
│  ┌───────────────────▼─────────────────────┐                   │
│  │ Recalculate all costs server-side        │                   │
│  │ DB::transaction + lockForUpdate (truck)  │                   │
│  │ Create PoolingJob + pivot records         │                   │
│  │ Harvests → assigned, Truck → reserved    │                   │
│  │ Send proposals to farmers                │                   │
│  │ Status: pending                           │                   │
│  │ proposal_expires_at = now() + 48h        │                   │
│  └───────────────────┬─────────────────────┘                   │
│                      │                                          │
│  3. FARMER ACCEPT / REJECT / COUNTER                            │
│  ┌───────────────────▼─────────────────────┐                   │
│  │ Each farmer reviews proposed_cost_per_kg │                   │
│  │ Accept → pivot.status = 'accepted'       │                   │
│  │ Reject → pivot.status = 'rejected'       │                   │
│  │ Counter → 25%-175% of reference          │                   │
│  │   Max 5 negotiation_rounds               │                   │
│  │   Only affects this farmer's cost_share  │                   │
│  │ 48h timeout → auto-reject                │                   │
│  └───────────────────┬─────────────────────┘                   │
│                      │                                          │
│  4. LP COUNTER-BID (optional)                                   │
│  ┌───────────────────▼─────────────────────┐                   │
│  │ LP can counter-bid to ALL farmers        │                   │
│  │ Recalculates all cost_shares             │                   │
│  │ Resets ALL farmers to pending            │                   │
│  │ Price bounds: 25%-175% of reference      │                   │
│  │ Notifies all farmers with new amounts    │                   │
│  │ Route: POST /pooling/{id}/logistics-counter│                 │
│  └───────────────────┬─────────────────────┘                   │
│                      │                                          │
│  5. ALL ACCEPTED → CONFIRMED                                    │
│  ┌───────────────────▼─────────────────────┐                   │
│  │ Status: confirmed                        │                   │
│  │ Driver assigned (auto or manual)         │                   │
│  │ Notification sent to driver              │                   │
│  └───────────────────┬─────────────────────┘                   │
│                      │                                          │
│  6. START DELIVERY (LP)                                         │
│  ┌───────────────────▼─────────────────────┐                   │
│  │ Status: in_progress                      │                   │
│  │ started_at = now()                       │                   │
│  │ Weather check via WeatherService         │                   │
│  └───────────────────┬─────────────────────┘                   │
│                      │                                          │
│  7. ACTIVE DELIVERY (DRIVER)                                    │
│  ┌───────────────────▼─────────────────────┐                   │
│  │ Per-stop: assigned → arrived → loaded    │                   │
│  │          → delivered                      │                   │
│  │ Geofence check (≤500m) at each farm      │                   │
│  │ Load photo + crop confirmation required   │                   │
│  │ Delivery receipt required                 │                   │
│  │ GPS streaming every 15s                  │                   │
│  │                                            │                   │
│  │ DelayDetectionService (every 15min):      │                   │
│  │ • Stall: speed <1 kmh for 15+ min        │                   │
│  │ • Stop delay: >20 min at pickup/dock      │                   │
│  │ • GPS dark: no signal for 10+ min         │                   │
│  │ • ETA delay: 2h+ with 0 stops done        │                   │
│  │ • Auto-escalation for critical delays     │                   │
│  │ • Resolution detection                    │                   │
│  └───────────────────┬─────────────────────┘                   │
│                      │                                          │
│  8. COMPLETE DELIVERY (DRIVER)                                  │
│  ┌───────────────────▼─────────────────────┐                   │
│  │ Status: awaiting_confirmation            │                   │
│  │ completed_at = now()                     │                   │
│  │ actual_distance_km from GPS records       │                   │
│  │ All pivot delivered_at timestamps set     │                   │
│  └───────────────────┬─────────────────────┘                   │
│                      │                                          │
│  9. BUYER CONFIRMS                                              │
│  ┌───────────────────▼─────────────────────┐                   │
│  │ Buyer verifies receipt                   │                   │
│  │ POST /buyer/deliveries/{id}/confirm      │                   │
│  │ Status: completed                        │                   │
│  │ pivot.buyer_confirmed_at = now()          │                   │
│  │ 48h timeout → auto-complete              │                   │
│  └───────────────────┬─────────────────────┘                   │
│                      │                                          │
│ 10. INVOICE GENERATED                                           │
│  ┌───────────────────▼─────────────────────┐                   │
│  │ InvoiceService::getOrCreateInvoice()     │                   │
│  │ • Sum cost_share pivots                  │                   │
│  │ • Generate PDF via DomPDF                │                   │
│  │ • Store in public/invoices/              │                   │
│  │ • Email to LP + all farmers              │                   │
│  │ • AuditLog::create()                     │                   │
│  └─────────────────────────────────────────┘                   │
│                                                                 │
└─────────────────────────────────────────────────────────────────┘
```

### PoolingJob Status State Machine

```
  ┌─────────┐  farmers    ┌───────────┐  driver   ┌──────────────┐
  │         │  accept      │           │  starts   │              │
  │ PENDING ├─────────────►│CONFIRMED  ├──────────►│ IN_PROGRESS  │
  │         │              │           │           │              │
  └────┬────┘              └───────────┘           └──────┬───────┘
       │                                                  │
       │ reject (48h auto)                   driver completes
       │                                    all stops + odometer
       ▼                                                  ▼
  ┌───────────┐                                ┌──────────────────┐
  │CANCELLED  │                                │AWAITING_CONFIRM  │
  └───────────┘                                │ (48h auto)       │
                                               └────────┬─────────┘
                                                        │
                                                        │ buyer confirms
                                                        ▼
                                               ┌──────────────────┐
                                               │   COMPLETED      │
                                               └──────────────────┘

  Pivot statuses: pending → accepted/rejected → assigned → arrived → loaded → delivered
  Payment statuses: unpaid → submitted → paid
```

---

## Delivery Execution Flow

See [Driver Flow](#driver-flow) section for complete details.

Key metrics:
- GPS accuracy filter: >500m rejected
- GPS deduplication: same coords within 30s skipped
- Speed computed from consecutive GPS pings
- ETAService: median speed filter, 0.85 terrain multiplier, confidence scoring
- DelayDetection: 5 alert types with auto-escalation and resolution detection

---

## Cost Ledger & Payment Flow

```
┌─────────────────────────────────────────────────────────────────┐
│              COST LEDGER LIFECYCLE                               │
├─────────────────────────────────────────────────────────────────┤
│                                                                 │
│  1. VIEW COST LEDGER                                            │
│  ┌─────────────────────────────────────────┐                   │
│  │ GET /pooling/{poolingJob}/cost-ledger    │                   │
│  │ Middleware: role:farmer,logistics_partner │                   │
│  │                                           │                   │
│  │ Shows per-farmer breakdown:              │                   │
│  │ • Farmer name + crop + quantity           │                   │
│  │ • Cost share (proportional by weight)    │                   │
│  │ • Payment status (unpaid/submitted/paid)  │                   │
│  │ • Receipt upload status                   │                   │
│  │ • Loaded quantity confirmation            │                   │
│  │ Cost mismatch detection (warns if wrong)  │                   │
│  └───────────────────┬─────────────────────┘                   │
│                      │                                          │
│  2. FARMER UPLOADS RECEIPT                                      │
│  ┌───────────────────▼─────────────────────┐                   │
│  │ POST /pooling/{id}/cost-ledger/{harvest} │                   │
│  │            /upload-receipt                │                   │
│  │ • File: JPG/PNG/PDF (10MB max)           │                   │
│  │ • payment_status → 'submitted'            │                   │
│  │ • receipt_path stored                     │                   │
│  │ • AuditLog::create()                      │                   │
│  └───────────────────┬─────────────────────┘                   │
│                      │                                          │
│  3. FARMER CONFIRMS QUANTITY                                    │
│  ┌───────────────────▼─────────────────────┐                   │
│  │ POST /pooling/{id}/cost-ledger/{harvest} │                   │
│  │            /confirm-quantity              │                   │
│  │ • farmer_qty_confirmed = actual loaded kg │                   │
│  │ • Idempotent (once-only)                  │                   │
│  │ • AuditLog::create()                      │                   │
│  └───────────────────┬─────────────────────┘                   │
│                      │                                          │
│  4. LOGISTICS VERIFIES + MARKS PAID                             │
│  ┌───────────────────▼─────────────────────┐                   │
│  │ POST /pooling/{id}/cost-ledger/{harvest} │                   │
│  │            /mark-paid                     │                   │
│  │ • Verifies receipt exists                 │                   │
│  │ • payment_status → 'paid'                 │                   │
│  │ • AuditLog::create()                      │                   │
│  └─────────────────────────────────────────┘                   │
│                                                                 │
└─────────────────────────────────────────────────────────────────┘
```

---

## Invoice Generation Flow

```
┌─────────────────────────────────────────────────────────────────┐
│                 INVOICE GENERATION PIPELINE                      │
├─────────────────────────────────────────────────────────────────┤
│                                                                 │
│  ┌─────────────────┐                                           │
│  │ Trigger:         │                                           │
│  │ • Job completed  │  invoices:generate (hourly)               │
│  │ • Manual request │  PoolingJobController@downloadPdf         │
│  └────────┬────────┘                                           │
│           │                                                      │
│           ▼                                                      │
│  ┌──────────────────────────────────────────┐                   │
│  │  InvoiceService::getOrCreateInvoice()     │                   │
│  │  1. Check if invoice exists for job       │                   │
│  │  2. If not, create Invoice record         │                   │
│  │  3. invoice_number = HH-INV-{YYYYMMDD}   │                   │
│  │     -{zero-padded-job-id}                 │                   │
│  └────────┬─────────────────────────────────┘                   │
│           │                                                      │
│           ▼                                                      │
│  ┌──────────────────────────────────────────┐                   │
│  │  Calculate totals from pivot:             │                   │
│  │  • Sum of cost_share across all harvests  │                   │
│  │  • total_kg, farm_count                   │                   │
│  └────────┬─────────────────────────────────┘                   │
│           │                                                      │
│           ▼                                                      │
│  ┌──────────────────────────────────────────┐                   │
│  │  renderInvoiceHtml()                      │                   │
│  │  1. Styled HTML with job details          │                   │
│  │  2. Per-farmer cost breakdown table       │                   │
│  │  3. Route, harvests, costs                │                   │
│  │  4. DomPDF → PDF file                     │                   │
│  │  5. Store in public/invoices/             │                   │
│  └────────┬─────────────────────────────────┘                   │
│           │                                                      │
│           ▼                                                      │
│  ┌──────────────────────────────────────────┐                   │
│  │  Email via InvoiceMail                    │                   │
│  │  • Attach PDF                             │                   │
│  │  • Send to: LP, all farmers              │                   │
│  │  • Notification::create() per user        │                   │
│  │  • AuditLog::create()                     │                   │
│  └──────────────────────────────────────────┘                   │
│                                                                 │
│  Voiding:                                                       │
│  │  voidInvoice() → status='voided'                             │
│  │  Records: voided_at, void_reason                             │
│  │  AuditLog::create()                                          │
│                                                                 │
└─────────────────────────────────────────────────────────────────┘
```

---

## Real-Time Tracking Flow

```
┌─────────────────────────────────────────────────────────────────┐
│                 WEBSOCKET TRACKING ARCHITECTURE                  │
├─────────────────────────────────────────────────────────────────┤
│                                                                 │
│  ┌──────────┐     ┌──────────────┐     ┌──────────────────┐   │
│  │  DRIVER  │     │   SERVER     │     │    BUYER/FARMER  │   │
│  │  (Mobile)│     │  (Laravel)   │     │  (Browser)       │   │
│  └────┬─────┘     └──────┬───────┘     └────────┬─────────┘   │
│       │                  │                      │              │
│       │  GPS POST        │                      │              │
│       │  /driver/tracking│                      │              │
│       │  /store          │                      │              │
│       │─────────────────►│                      │              │
│       │                  │                      │              │
│       │                  │  Save to DB:         │              │
│       │                  │  • TrackingRecord    │              │
│       │                  │  • Speed + bearing   │              │
│       │                  │    computed           │              │
│       │                  │                      │              │
│       │                  │  ┌────────────────┐  │              │
│       │                  │  │ WebSocket      │  │              │
│       │                  │  │ Server :8080   │  │              │
│       │                  │  │ (custom PHP)   │  │              │
│       │                  │  │                │  │              │
│       │                  │  │ Polls          │  │              │
│       │                  │  │ tracking_records│ │              │
│       │                  │  │ every 2s       │  │              │
│       │                  │  │                │  │              │
│       │                  │  │ Broadcasts to  │  │              │
│       │                  │  │ all connected  │  │              │
│       │                  │  │ clients        │  │              │
│       │                  │  └───────┬────────┘  │              │
│       │                  │          │            │              │
│       │                  │          │  WebSocket │              │
│       │                  │          │  frame     │              │
│       │                  │          │───────────►│              │
│       │                  │          │            │              │
│       │                  │          │  Update    │              │
│       │                  │          │  Leaflet   │              │
│       │                  │          │  map       │              │
│                                                                 │
│  WebSocket Server Details:                                     │
│  • Custom raw PHP socket (NOT Laravel Broadcasting)            │
│  • Token-based auth (query string: /?token=xxx)               │
│  • Ping/pong heartbeat every 30s                              │
│  • Client timeout: 120s inactivity                            │
│  • Max 50 records per poll cycle                              │
│  • RFC 6455 WebSocket frame encode/decode                     │
│  • Start: php artisan websocket:serve                         │
│                                                                 │
│  ETA Endpoint:                                                 │
│  • GET /tracking/{poolingJob}/eta (cached 10s)                │
│  • ETAService: median speed filter, 0.85 terrain mult         │
│  • Confidence: high/medium/low/stale                          │
│  • Considers remaining waypoints + destination                │
│                                                                 │
│  Latest GPS Endpoint:                                          │
│  • GET /tracking/{poolingJob}/latest                          │
│  • Role-authorized (admin/LP/driver/buyer/farmer)             │
│  • Polls every 10s by client                                  │
│                                                                 │
└─────────────────────────────────────────────────────────────────┘
```

### Delay Detection System

```
┌─────────────────────────────────────────────────────────────────┐
│  DelayDetectionService (runs every 15min via cron)              │
├─────────────────────────────────────────────────────────────────┤
│                                                                 │
│  DETECTION TYPES:                                               │
│  ┌───────────────────────────────────────────────────────┐     │
│  │ 1. Stall          │ speed < 1 kmh for 15+ min        │     │
│  │ 2. Stop Delay     │ arrived/loaded for 20+ min        │     │
│  │ 3. GPS Signal Lost│ no ping for 10+ min               │     │
│  │ 4. ETA Delay      │ active 2h+ with 0 stops done      │     │
│  │ 5. Auto-Escalation│ critical → LP notification        │     │
│  └───────────────────────────────────────────────────────┘     │
│                                                                 │
│  SEVERITY LEVELS:                                               │
│  • Warning → informational notification                        │
│  • Critical (>30min stall, >30min dark, >45min stop)           │
│    → Escalated to logistics partner                            │
│  • Deduplication: 30min window per alert type                  │
│                                                                 │
│  RESOLUTION:                                                    │
│  • Detects when stall/GPS loss ends                            │
│  • Sends "delay resolved" notification                         │
│                                                                 │
└─────────────────────────────────────────────────────────────────┘
```

---

## Scheduled Tasks Flow

```
┌─────────────────────────────────────────────────────────────────┐
│                 CRON / SCHEDULED COMMANDS                        │
├─────────────────────────────────────────────────────────────────┤
│                                                                 │
│  EVERY 15 MINUTES                                               │
│  └── delays:check                                              │
│      └── DelayDetectionService::checkAllActiveJobs()           │
│          Stall / Stop delay / GPS loss / ETA delay             │
│          + auto-escalation + resolution detection              │
│                                                                 │
│  EVERY 30 MINUTES                                               │
│  └── weather:check                                             │
│      └── WeatherService for active/pending/confirmed jobs      │
│          Checks: depot + each farm waypoint + destination      │
│          Stores WeatherLog per job                             │
│          Sends severe weather notifications                    │
│                                                                 │
│  HOURLY                                                         │
│  ├── deliveries:auto-complete                                  │
│  │   └── awaiting_confirmation jobs > 48h → completed          │
│  │       Marks buyer_confirmed_at on all pivots                │
│  │                                                              │
│  ├── deliveries:auto-complete-stale                            │
│  │   └── in_progress jobs > 48h → completed (force)            │
│  │       Frees truck, marks all harvests completed             │
│  │                                                              │
│  ├── invoices:generate                                         │
│  │   └── completed jobs without invoice → generate PDF         │
│  │                                                              │
│  └── proposals:auto-reject-expired                             │
│      └── pending proposals > 48h → cancelled                   │
│          Frees trucks, reverts harvests to sold                │
│                                                                 │
│  DAILY                                                          │
│  ├── negotiations:auto-close-stale                             │
│  │   └── OPEN/AGREED negotiations > 48h inactive → cancelled   │
│  │      Restores harvest status, notifies both parties         │
│  │                                                              │
│  └── data:cleanup                                              │
│      ├── Delete tracking_records > 30 days                     │
│      ├── Delete read notifications > 90 days                   │
│      └── Delete weather_logs > 7 days                          │
│                                                                 │
│  MANUAL (non-scheduled):                                        │
│  ├── crops:scrape         → Scrape DA prices for baseline kg  │
│  ├── websocket:serve      → Start WS server on port 8080      │
│  └── app:backfill-buyer-profiles → One-off migration           │
│                                                                 │
└─────────────────────────────────────────────────────────────────┘
```

---

## Entity Relationship Diagram

```
┌─────────────────────────────────────────────────────────────────────────────────┐
│                           ENTITY RELATIONSHIPS                                  │
├─────────────────────────────────────────────────────────────────────────────────┤
│                                                                                 │
│  ┌─────────┐ 1    n ┌──────────────┐ n    n ┌──────────────┐                  │
│  │  User   ├────────┤  Harvest     ├────────┤  PoolingJob  │                  │
│  │         │        │              │        │  (via pivot)  │                  │
│  │  id     │        │  id          │        │              │                  │
│  │  role   │        │  user_id(FK) │        │  id          │                  │
│  │  email  │        │  driver_idFK │        │  truck_id FK │                  │
│  │  status │        │  crop_cat FK │        │  driver_idFK │                  │
│  └────┬────┘        │  crop_id FK  │        │  buyer_id FK │                  │
│       │             │  crop_var FK │        │  status      │                  │
│       │             │  dest_id FK  │        │  price_ref   │                  │
│       │             │  quantity_kg │        │  negotiated  │                  │
│       │             │  remaining_kg│        │  _price      │                  │
│       │             │  status      │        └──────┬───────┘                  │
│       │             │  visibility  │               │                           │
│       │             │  lat/lng     │               │ 1                         │
│       │             │  crop_photos │               ▼                           │
│       │             └──────┬───────┘        ┌──────────────┐                  │
│       │                    │                │   Invoice    │                  │
│       │                    │ n              │              │                  │
│       │                    ▼                │  id          │                  │
│       │             ┌──────────────┐        │  invoice_no  │                  │
│       │             │ Negotiation  │        │  total_amount│                  │
│       │             │              │        │  status      │                  │
│       │             │  id          │        │  pdf_path    │                  │
│       │             │  buyer_id FK │        └──────────────┘                  │
│       │             │  farmer_idFK │                                           │
│       │             │  harvest_idFK│        ┌──────────────┐                  │
│       │             │  status      │        │ WeatherLog   │                  │
│       │             │  dest_lat/lng│        │              │                  │
│       │             └──────┬───────┘        │  pooling_job │                  │
│       │                    │ 1              │  _id FK      │                  │
│       │                    ▼                │  lat/lng     │                  │
│       │             ┌──────────────┐        │  condition   │                  │
│       │             │Negotiation   │        │  advisory    │                  │
│       │             │  Message     │        │  is_severe   │                  │
│       │             │              │        └──────────────┘                  │
│       │             │  id          │                                           │
│       │             │  sender_id FK│        ┌──────────────┐                  │
│       │             │  message_text│        │ TrackingRecord│                  │
│       │             └──────────────┘        │              │                  │
│       │                                     │  pooling_job │                  │
│       │ 1                                   │  _id FK      │                  │
│       ├────────┐                            │  driver_idFK │                  │
│       │        │                            │  lat/lng     │                  │
│       ▼        │                            │  speed_kmh   │                  │
│  ┌─────────┐   │                            │  posted_at   │                  │
│  │ Farmer  │   │                            └──────────────┘                  │
│  │ Profile │   │                                                               │
│  │         │   │ 1    n  ┌──────────────┐                                   │
│  │ user_id │   ├─────────┤   Truck      │                                   │
│  │ coop_id │   │         │              │                                   │
│  │ lat/lng │   │         │  id          │                                   │
│  │ affil.  │   │         │  plate_number│                                   │
│  └─────────┘   │         │  capacity_kg │                                   │
│                │         │  status      │                                   │
│  ┌─────────┐   │         │  driver_idFK │                                   │
│  │Logistics│   │         └──────────────┘                                   │
│  │ Profile │   │                                                              │
│  │         │   │ 1    n  ┌──────────────┐                                   │
│  │ user_id │   ├─────────┤ DriverProfile│                                   │
│  │ company │   │         │              │                                   │
│  │ type    │   │         │  user_id FK  │                                   │
│  │ coop/   │   │         │  partner_idFK│                                   │
│  │ company │   │         │  license_no  │                                   │
│  └─────────┘   │         │  identity    │                                   │
│                │         │  _verified   │                                   │
│  ┌─────────┐   │         └──────────────┘                                   │
│  │ Buyer   │   │                                                              │
│  │ Profile │   │ 1    n  ┌──────────────┐                                   │
│  │         │   ├─────────┤  FuelLog     │                                   │
│  │ user_id │   │         │              │                                   │
│  └─────────┘   │         │  driver_id FK│                                   │
│                │         │  truck_id FK │                                   │
│                │         │  fuel_liters │                                   │
│                │         │  cost        │                                   │
│                │         │  odometer    │                                   │
│                │         └──────────────┘                                   │
│                                                                                 │
│  Other tables:                                                                 │
│  ├── CropCategory (1:n → Crop)                                                │
│  ├── Crop (1:n → CropVariety, 1:n → Harvest)                                 │
│  ├── CropVariety (n:1 → Crop)                                                 │
│  ├── Destination (1:n → Harvest)                                              │
│  ├── Notification (n:1 → User)                                                │
│  ├── AuditLog (n:1 → User via admin_id)                                       │
│  ├── FarmerDocument (n:1 → User)                                              │
│  ├── LogisticsDocument (n:1 → User)                                           │
│  └── DriverHeartbeat (n:1 → User, n:1 → LogisticsProfile)                   │
│                                                                                 │
│  PoolingJob Harvest Pivot (pooling_job_harvests):                              │
│  ├── pickup_order, quantity_kg, distance_from_route                            │
│  ├── cost_share, status (assigned/arrived/loaded/delivered)                   │
│  ├── payment_status (unpaid/submitted/paid)                                   │
│  ├── receipt_path, delivery_receipt_path, load_photo_path                     │
│  ├── loaded_quantity_kg, actual_quantity_kg, farmer_qty_confirmed             │
│  ├── crop_confirmed, arrived_at, loaded_at, delivered_at                      │
│  └── buyer_confirmed_at, loaded_volume_cubic_meters                           │
│                                                                                 │
└─────────────────────────────────────────────────────────────────────────────────┘
```

---

## Security & Vulnerability Summary

```
┌─────────────────────────────────────────────────────────────────┐
│              BUSINESS LOGIC VULNERABILITY ASSESSMENT             │
├─────────────────────────────────────────────────────────────────┤
│                                                                 │
│  CRITICAL (2)                                                   │
│  ├── VULN-01: Registration no rate limiting                    │
│  │   routes/web.php — mass account creation possible           │
│  │   Fix: Add throttle:10,1 middleware                         │
│  │                                                              │
│  └── VULN-02: Immediate full access post-registration          │
│      RegisterController — no approval gate                     │
│      Fix: Set status='pending' + verification middleware       │
│                                                                 │
│  HIGH (3)                                                       │
│  ├── VULN-03: Harvest creation no rate limiting                │
│  │   — flood crop board possible                               │
│  │                                                              │
│  ├── VULN-04: No hard price floor in B2B negotiations          │
│  │   NegotiationController — ₱0.01/kg possible                 │
│  │                                                              │
│  └── VULN-05: Counter-proposal doesn't reset others            │
│      PoolingJobController — integrity gap                      │
│                                                                 │
│  MEDIUM (5)                                                     │
│  ├── VULN-06: Invoice PDF HTML injection                       │
│  │   InvoiceService — unescaped user data in HTML              │
│  │                                                              │
│  ├── VULN-07: Client-submitted total_kg ±1% tolerance         │
│  │   PoolingJobController — weight inflation                   │
│  │                                                              │
│  ├── VULN-08: confirmQuantity no driver reconciliation         │
│  │   CostLedgerController — quantity under-report              │
│  │                                                              │
│  ├── VULN-09: acceptProposal TOCTOU race condition             │
│  │   PoolingJobController — double confirm                     │
│  │                                                              │
│  └── VULN-10: plan endpoint no harvest scope check             │
│      PoolingJobController — harvest interception               │
│                                                                 │
│  LOW (3)                                                        │
│  ├── VULN-11: Cost share rounding mismatch                     │
│  ├── VULN-12: FuelLog odometer not monotonic                   │
│  └── VULN-13: Counter-proposal ledger inconsistency            │
│                                                                 │
├─────────────────────────────────────────────────────────────────┤
│              MITIGATIONS IN PLACE                                │
├─────────────────────────────────────────────────────────────────┤
│                                                                 │
│  ✓ DB::transaction + lockForUpdate on negotiations             │
│  ✓ Harvest ownership checks (Auth::user()->harvests())        │
│  ✓ Login/OTP throttling (5/min)                               │
│  ✓ Negotiation endpoint throttling (10-15/min)                │
│  ✓ GPS endpoint throttling (12/min)                           │
│  ✓ Timing-safe OTP comparison (hash_equals)                   │
│  ✓ Invoice deduplication (getOrCreateInvoice)                 │
│  ✓ GPS accuracy filter (>500m rejected)                       │
│  ✓ GPS deduplication (30s window)                             │
│  ✓ Driver shift rest enforcement (8hr)                        │
│  ✓ Proposal expiry (48h auto-reject)                          │
│  ✓ Negotiation auto-close (48h inactivity)                    │
│  ✓ Price bounds on counter-offers (25-175%)                   │
│  ✓ Max negotiation rounds (5 for pooling, 10 for B2B)        │
│  ✓ Cooperative/independent scoping                            │
│  ✓ Single-buyer validation on pooling plans                   │
│  ✓ No-conflicting-pending-job validation                      │
│  ✓ Duplicate odometer prevention                              │
│  ✓ Loaded quantity ≤ allocation validation                    │
│                                                                 │
├─────────────────────────────────────────────────────────────────┤
│              LARAVEL PATTERNS COMPLIANCE: 5/10                  │
├─────────────────────────────────────────────────────────────────┤
│                                                                 │
│  POSITIVE:                                                      │
│  ✓ Service layer (6 services)                                  │
│  ✓ Transaction + locking patterns                              │
│  ✓ Policy classes (2 policies)                                 │
│  ✓ Consistent audit logging                                    │
│  ✓ Idempotent operations (invoice, qty confirm)               │
│                                                                 │
│  GAPS:                                                          │
│  ✗ Zero FormRequest classes (inline validation)               │
│  ✗ No SoftDeletes (data permanently destroyed)               │
│  ✗ Business logic in controllers (not fully in services)      │
│  ✗ No Model Observers (50+ Notification::create inline)      │
│  ✗ Custom WebSocket (not using Laravel Broadcasting)          │
│                                                                 │
└─────────────────────────────────────────────────────────────────┘
```

---

## API Endpoints Summary

| Method | Route | Controller | Purpose | Auth |
|--------|-------|------------|---------|------|
| GET | `/` | DashboardController@index | Landing page | Guest |
| GET | `/register/{role}` | RegisterController@show | Role-specific form | Guest |
| POST | `/register` | RegisterController@register | Create account | Guest |
| POST | `/login` | LoginController@login | Authentication | Guest |
| POST | `/logout` | LogoutController@logout | End session | Auth |
| GET | `/forgot-password` | PasswordController@show | Reset form | Guest |
| POST | `/forgot-password` | PasswordController@send | Send reset link | Guest |
| GET | `/reset-password/{token}` | PasswordController@showReset | New password form | Guest |
| POST | `/reset-password` | PasswordController@reset | Save new password | Guest |
| POST | `/email/verify-otp` | VerifyOtpController@verify | Verify email | Guest |
| POST | `/email/resend-otp` | VerifyOtpController@resend | Resend OTP | Guest |
| GET | `/dashboard` | DashboardController@switcher | Role redirect | Auth |
| GET | `/profile` | ProfileController@index | View profile | Auth |
| PUT | `/profile` | ProfileController@update | Update profile | Auth |
| PUT | `/profile/password` | ProfileController@updatePassword | Change password | Auth |
| **ADMIN** | | | | |
| GET | `/admin/users` | AdminController@users | User list | Admin |
| POST | `/admin/users` | AdminController@storeUser | Create user | Admin |
| PUT | `/admin/users/{user}` | AdminController@updateUser | Update user | Admin |
| POST | `/admin/users/{user}/status` | AdminController@toggleStatus | Toggle active | Admin |
| GET | `/admin/farmers` | AdminController@farmers | Farmer list | Admin |
| POST | `/admin/farmers/{user}/verify` | AdminController@verifyFarmer | Verify | Admin |
| POST | `/admin/farmers/{user}/reject` | AdminController@rejectFarmer | Reject | Admin |
| GET | `/admin/logistics` | AdminController@logistics | LP list | Admin |
| POST | `/admin/logistics/{user}/verify` | AdminController@verifyLogistics | Verify | Admin |
| POST | `/admin/logistics/{user}/reject` | AdminController@rejectLogistics | Reject | Admin |
| GET | `/admin/buyers` | AdminController@buyers | Buyer list | Admin |
| POST | `/admin/buyers/{user}/verify` | AdminController@verifyBuyer | Verify | Admin |
| POST | `/admin/buyers/{user}/reject` | AdminController@rejectBuyer | Reject | Admin |
| GET | `/admin/drivers` | AdminController@drivers | Driver list | Admin |
| POST | `/admin/drivers/{user}/verify-identity` | AdminController@verifyIdentity | Verify ID | Admin |
| POST | `/admin/drivers/{user}/reject-identity` | AdminController@rejectIdentity | Reject ID | Admin |
| GET | `/admin/harvests` | AdminController@harvests | Harvest list | Admin |
| GET | `/admin/analytics` | AdminController@analytics | Analytics | Admin |
| GET | `/admin/audit-logs` | AdminController@auditLogs | Audit trail | Admin |
| GET | `/admin/export/users` | AdminController@exportUsers | Export users | Admin |
| GET | `/admin/export/harvests` | AdminController@exportHarvests | Export harvests | Admin |
| GET | `/admin/crops` | CropManagerController@index | Crop mgmt | Admin |
| POST | `/admin/crops/categories` | CropManagerController@storeCategory | Add category | Admin |
| POST | `/admin/crops` | CropManagerController@storeCrop | Add crop | Admin |
| PUT | `/admin/crops/{crop}` | CropManagerController@updateCrop | Update crop | Admin |
| POST | `/admin/crops/{crop}/baseline-price` | CropManagerController@setBaselinePrice | Set price | Admin |
| POST | `/admin/crops/{crop}/varieties` | CropManagerController@storeVariety | Add variety | Admin |
| PUT | `/admin/crops/varieties/{variety}` | CropManagerController@updateVariety | Update variety | Admin |
| GET | `/admin/farmer-documents` | AdminFarmerDocumentController@index | Review docs | Admin |
| PATCH | `/admin/farmer-documents/{doc}/approve` | AdminFarmerDocumentController@approve | Approve | Admin |
| PATCH | `/admin/farmer-documents/{doc}/reject` | AdminFarmerDocumentController@reject | Reject | Admin |
| GET | `/admin/logistics-documents` | AdminLogisticsDocumentController@index | Review docs | Admin |
| PATCH | `/admin/logistics-documents/{doc}/approve` | AdminLogisticsDocumentController@approve | Approve | Admin |
| PATCH | `/admin/logistics-documents/{doc}/reject` | AdminLogisticsDocumentController@reject | Reject | Admin |
| **FARMER** | | | | |
| GET | `/harvests` | HarvestController@index | My harvests | Farmer |
| GET | `/harvests/create` | HarvestController@create | New harvest form | Farmer |
| POST | `/harvests` | HarvestController@store | Create harvest | Farmer |
| GET | `/harvests/{harvest}/edit` | HarvestController@edit | Edit form | Farmer |
| PUT | `/harvests/{harvest}` | HarvestController@update | Update harvest | Farmer |
| DELETE | `/harvests/{harvest}` | HarvestController@destroy | Delete harvest | Farmer |
| GET | `/farmer/proposals` | PoolingJobController@farmerProposals | View proposals | Farmer |
| POST | `/pooling/{poolingJob}/accept` | PoolingJobController@acceptProposal | Accept | Farmer |
| POST | `/pooling/{poolingJob}/reject` | PoolingJobController@rejectProposal | Reject | Farmer |
| POST | `/pooling/{poolingJob}/counter` | PoolingJobController@counterProposal | Counter-offer | Farmer |
| GET | `/farmer/predictor` | PredictorController@farmerPredict | Price predict | Farmer |
| GET | `/farmer/negotiations` | NegotiationController@farmerIndex | My negotiations | Farmer |
| GET | `/my-documents` | DocumentController@index | My documents | Farmer |
| POST | `/my-documents` | DocumentController@store | Upload doc | Farmer |
| DELETE | `/my-documents/{doc}` | DocumentController@destroy | Delete doc | Farmer |
| GET | `/tracking` | TrackingController@index | Live tracking | Farmer |
| **LOGISTICS** | | | | |
| GET | `/pooling/proposals` | PoolingJobController@proposalInbox | Proposal inbox | Logistics |
| POST | `/pooling/plan` | PoolingJobController@plan | Plan route | Logistics |
| POST | `/pooling/confirm` | PoolingJobController@confirm | Confirm route | Logistics |
| POST | `/pooling/{poolingJob}/logistics-accept` | PoolingJobController@logisticsAcceptCounter | Accept counter | Logistics |
| POST | `/pooling/{poolingJob}/logistics-counter` | PoolingJobController@logisticsCounter | Counter-bid | Logistics |
| GET | `/pooling/cost-ledger` | CostLedgerController@index | Cost ledger | Logistics |
| GET | `/pooling/{poolingJob}` | CostLedgerController@show | Job cost detail | Logistics |
| GET | `/logistics/analytics` | CostLedgerController@fleetAnalytics | Fleet analytics | Logistics |
| GET | `/logistics/predictor` | PredictorController@logisticsPredict | Fleet predict | Logistics |
| GET | `/route-optimization` | RouteOptimizationController@index | Optimizer UI | Logistics |
| POST | `/route-optimization/auto-assign-driver` | RouteOptimizationController@autoAssign | Auto-assign | Logistics |
| GET | `/drivers` | DriverController@index | Driver list | Logistics |
| GET | `/drivers/create` | DriverController@create | New driver form | Logistics |
| POST | `/drivers` | DriverController@store | Create driver | Logistics |
| GET | `/vehicles` | TruckController@index | Vehicle list | Logistics |
| GET | `/vehicles/create` | TruckController@create | New vehicle form | Logistics |
| POST | `/vehicles` | TruckController@store | Create vehicle | Logistics |
| GET | `/business-documents` | DocumentController@index | My documents | Logistics |
| POST | `/business-documents` | DocumentController@store | Upload doc | Logistics |
| DELETE | `/business-documents/{doc}` | DocumentController@destroy | Delete doc | Logistics |
| GET | `/tracking/{poolingJob}/latest` | TrackingController@latestGPS | Latest position | Logistics |
| GET | `/tracking/{poolingJob}/eta` | TrackingController@eta | ETA data | Logistics |
| **BUYER** | | | | |
| GET | `/buyer` | BuyerController@dashboard | Dashboard | Buyer |
| GET | `/buyer/crop-board` | BuyerController@cropBoard | Browse crops | Buyer |
| GET | `/buyer/crop-board/{harvest}` | BuyerController@showCropDetail | Crop detail | Buyer |
| GET | `/buyer/negotiations` | NegotiationController@buyerIndex | My negotiations | Buyer |
| GET | `/buyer/tracking` | TrackingController@index | Track delivery | Buyer |
| POST | `/buyer/deliveries/{poolingJob}/confirm` | BuyerController@confirmReceipt | Confirm receipt | Buyer |
| **NEGOTIATIONS (Shared)** | | | | |
| POST | `/negotiations/start` | NegotiationController@start | Start negotiation | Farmer/Buyer |
| GET | `/negotiations/list` | NegotiationController@list | JSON list | Farmer/Buyer |
| GET | `/negotiations/{negotiation}` | NegotiationController@room | Chat room | Farmer/Buyer |
| POST | `/negotiations/{negotiation}/message` | NegotiationController@sendMessage | Send message | Farmer/Buyer |
| POST | `/negotiations/{negotiation}/propose` | NegotiationController@proposeTerms | Propose terms | Farmer/Buyer |
| POST | `/negotiations/{negotiation}/agree` | NegotiationController@agreeTerms | Agree terms | Farmer/Buyer |
| POST | `/negotiations/{negotiation}/finalize` | NegotiationController@finalizeDeal | Finalize deal | Buyer |
| POST | `/negotiations/{negotiation}/cancel` | NegotiationController@cancelDeal | Cancel | Farmer/Buyer |
| GET | `/negotiations/{negotiation}/messages` | NegotiationController@messages | Poll messages | Farmer/Buyer |
| **COST LEDGER (Shared)** | | | | |
| GET | `/pooling/{poolingJob}/cost-ledger` | CostLedgerController@show | Cost breakdown | Farmer/Logistics |
| POST | `/pooling/{poolingJob}/cost-ledger/{harvestId}/upload-receipt` | CostLedgerController@uploadReceipt | Upload receipt | Farmer |
| POST | `/pooling/{poolingJob}/cost-ledger/{harvestId}/confirm-quantity` | CostLedgerController@confirmQuantity | Confirm qty | Farmer |
| POST | `/pooling/{poolingJob}/cost-ledger/{harvestId}/mark-paid` | CostLedgerController@markPaid | Mark paid | Logistics |
| **DRIVER** | | | | |
| GET | `/driver/` | DriverController@index | Dashboard | Driver |
| GET | `/driver/jobs/{poolingJob}` | DriverController@show | Job detail | Driver |
| POST | `/driver/jobs/{poolingJob}/accept` | DriverController@acceptJob | Accept job | Driver |
| PATCH | `/driver/jobs/{poolingJob}/status` | DriverController@updateStatus | Status checkpoint | Driver |
| PATCH | `/driver/jobs/{poolingJob}/harvests/{harvest}/status` | DriverController@updateStopStatus | Per-stop status | Driver |
| POST | `/driver/jobs/{poolingJob}/fuel-log` | DriverController@storeFuelLog | Log fuel | Driver |
| POST | `/driver/identity-upload` | DriverController@uploadIdentity | Upload ID/selfie | Driver |
| POST | `/driver/tracking/store` | TrackingController@store | GPS telemetry | Driver |
| **TELEMETRY** | | | | |
| POST | `/tracking/stream` | TrackingController@storeStream | GPS fallback | Driver |
| GET | `/tracking/{poolingJob}/latest` | TrackingController@latestGPS | Latest position | Auth |
| GET | `/tracking/{poolingJob}/eta` | TrackingController@eta | ETA data | Auth |
| GET | `/notifications` | NotificationController@index | Notifications | Auth |
| POST | `/api/notifications/{id}/read` | NotificationController@markRead | Mark read | Auth |
| POST | `/api/notifications/read-all` | NotificationController@markAllRead | Mark all read | Auth |

---

*Document updated: 2026-07-16*
*Based on codebase analysis of HarvestHaul v0.0.1*
*Updated from: original 2026-07-14 version*
