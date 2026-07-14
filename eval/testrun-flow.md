# HarvestHaul — Full Transaction Test Run Flow

## Prerequisites

- `composer install` & `npm install` done
- `.env` configured with DB credentials
- `php artisan migrate --seed` run (or at minimum migrations)
- `php artisan serve` + `npm run dev` running
- Open browser to `http://localhost:8000`

---

## Phase 1 — Registration & Onboarding

### 1.1 Register a Farmer
- Visit `/register` → select **Farmer**
- Fill form: name, email, phone, password, farm location, lat/lng
- Submit → auto-logged in → redirected to email verification notice
- Check email/console for OTP → enter OTP → email verified
- Land on `/dashboard` → sees farmer dashboard (empty state)

### 1.2 Register a Logistics Partner
- Visit `/register` → select **Logistics Partner**
- Fill form: name, email, phone, password, company_name, business_permit_no, logistics_type (company)
- Submit → verify OTP → dashboard → sees logistics dashboard (empty state)

### 1.3 Register a Buyer
- Visit `/register` → select **Buyer**
- Fill form: name, email, phone, password, affiliation_type
- Submit → verify OTP → dashboard → sees buyer dashboard

### 1.4 Register a Driver (via Logistics Partner)
- Login as logistics partner → navigate to `/drivers/create`
- Fill: name, email, phone, password, license_number, vehicle_type
- Submit → driver user account created (auto-verified email)

---

## Phase 2 — Admin Verification

### 2.1 Login as Admin
- Login with admin credentials (seed manually if none exists)
- If no admin exists: `php artisan tinker` → `User::create([...])` with role=admin

### 2.2 View Pending Verifications
- `/admin` dashboard shows pending farmers & logistics partners

### 2.3 Approve Farmer
- `/admin/farmers` → find farmer → click **Verify**
- Confirm → farmer profile `is_verified = true`

### 2.4 Approve Logistics Partner
- `/admin/logistics` → find partner → click **Verify**
- Confirm → logistics profile `is_verified = true`

---

## Phase 3 — Crop Matrix Setup (Admin)

### 3.1 Create Crop Category
- Navigate to `/admin/crops`
- Add category: "Root Crops"

### 3.2 Create Crop
- Under "Root Crops" → add "Potato"

### 3.3 Create Crop Variety
- Under "Potato" → add "Granola" at ₱45.00/kg

### 3.4 Set Baseline Price
- Navigate to `/admin/analytics`
- Find "Potato" → set baseline_price_per_kg to ₱48.00

---

## Phase 4 — Farmer: Post Harvest Listings

### 4.1 Create a Harvest
- Login as farmer → `/harvests/create`
- Select: Potato → Granola, quantity: 500 kg, harvest date: yesterday
- Pick destination from list (or pin manually)
- Submit → harvest listed as **active**

### 4.2 View Your Listings
- `/harvests` → see the active harvest in table

### 4.3 Upload Compliance Document (optional for auto-verify)
- `/my-documents` → upload government ID + RSBSA cert
- Admin approves via `/admin/farmer-documents`
- If both approved → farmer auto-verified (already done in 2.3 but tests the flow)

### 4.4 Check Farmer Predictor
- `/farmer/predictor` → see yield predictions (may show empty state if first harvest)

---

## Phase 5 — Logistics: Route Optimization & Pooling

### 5.1 Create a Truck
- Login as logistics partner → `/vehicles/create`
- Fill: truck name, plate number, vehicle type, capacity kg (e.g. 2000), select driver
- Submit → truck registered as **available**

### 5.2 Open Route Optimization Map
- `/route-optimization` → Leaflet map loads
- See farmer markers with harvest data, available trucks listed
- Suggested truck pre-selected by algorithm

### 5.3 Plan a Pooling Route
- Select truck, pick farmer's harvest
- Set start (warehouse) & end (market) coordinates
- Set radius (e.g. 50 km)
- Click **Plan Route** → sees route polyline, cost estimate, pickup order

### 5.4 Confirm the Pooling Job
- Click **Confirm Route** → modal with final details
- Confirm → job created as **pending** → notifications sent to farmer

### 5.5 Check Proposals Inbox
- `/pooling/proposals` → see the pending proposal

### 5.6 Check Cost Ledger
- Click into the proposal → see cost breakdown per farmer
- Shows cost_share proportional to weight

---

## Phase 6 — Farmer: Accept/Reject/Counter Proposal

### 6.1 View Proposals
- Login as farmer → `/farmer/proposals`
- See pending pooling proposal with truck info, cost, route details

### 6.2 Accept Proposal
- Click **Accept** → farmer's pivot status = accepted
- If all farmers accept → job auto-transitions to **confirmed**
- Driver notified of new route

### 6.3 (Alternative) Reject Proposal
- Click **Reject** → harvest detached from job → status back to **sold**
- If no harvests remain → job cancelled, truck freed

### 6.4 (Alternative) Counter Proposal
- Enter counter price → submit → logistics notified
- Logistics can accept or counter again

---

## Phase 7 — Buyer: Negotiation & Purchase Workflow

### 7.1 View Crop Board
- Login as buyer → `/buyer/crop-board`
- See available harvests from verified farmers (scoped by affiliation)

### 7.2 Start a Negotiation
- Click "Negotiate" on a harvest listing
- Redirected to negotiation chat room

### 7.3 Send Messages
- Type message → sent via AJAX → appears in chat
- Farmer receives notification

### 7.4 Propose Terms
- Buyer enters: price per kg + volume
- Farmer sees the offer in chat

### 7.5 Agree to Terms
- Farmer clicks **Agree** → status = **AGREED**

### 7.6 Finalize the Deal
- Buyer enters drop-off address + coordinates
- Click **Finalize** → harvest status = **sold**
- Negotiation status = **COMPLETED**
- Harvest now visible on logistics route optimization map

---

## Phase 8 — Driver: Execute the Route

### 8.1 View Assigned Jobs
- Login as driver → dashboard shows confirmed/in_progress jobs
- See route with sequential pickup stops, truck info, cargo details

### 8.2 View Job Detail
- Click into job → ordered stops with farmer names, crops, quantities
- Navigation-style layout for field use

### 8.3 Log Fuel Purchase
- On a job → fill: liters, cost, odometer reading
- Submit → fuel logged, audit trail recorded

### 8.4 Update Stop Status (Pickup Loop)
- **Stop 1:** Click **Arrived** → farmer notified
- **Stop 1:** Click **Loaded** → input loaded_kg + volume → harvest status = in_progress → farmer notified
- Repeat for remaining stops

### 8.5 Stream GPS Telemetry
- POST `/driver/tracking/store` with pooling_job_id, lat, lng
- Returns speed, bearing computed from haversine formula
- (Simulate with curl/Postman or JS console)

### 8.6 Mark All Stops Delivered
- Each stop: **Delivered** → upload delivery receipt image
- Harvest status = **completed**, buyer notified

### 8.7 Finalize the Route
- Click **Complete Route** (only available if all stops = delivered)
- Job status = **awaiting_confirmation**
- Buyer notified to confirm receipt

---

## Phase 9 — Buyer: Confirm Receipt

### 9.1 View Active Deliveries
- Login as buyer → `/buyer/tracking`
- See delivery awaiting confirmation with driver + truck info

### 9.2 Confirm Receipt
- Click **Confirm Receipt** → job status = **completed**
- `buyer_confirmed_at` timestamp saved on pivot
- Logistics partner notified

---

## Phase 10 — Logistics: Cost Ledger & Payment

### 10.1 View Cost Ledger
- Login as logistics partner → `/pooling/cost-ledger` (or click from proposals)
- See proportional cost breakdown per farmer

### 10.2 Farmer Uploads Payment Receipt
- Login as farmer → navigate to cost ledger for the job
- Click **Upload Payment Receipt** → upload image
- Status = **submitted**, logistics notified

### 10.3 Logistics Marks as Paid
- Login as logistics → cost ledger → see receipt
- Click **Mark as Paid** → payment_status = **paid**
- Farmer notified of payment verification

---

## Phase 11 — Analytics & Reporting

### 11.1 Logistics Fleet Analytics
- `/logistics/analytics` → per-truck: fuel logs, KPL, revenue, net income
- Overall fleet summary metrics

### 11.2 Logistics Fleet Predictor
- `/logistics/predictor` → trucks needed vs available estimate
- Based on active harvest kg / historical avg kg per job

### 11.3 Admin Analytics Dashboard
- `/admin/analytics` → crop pricing trends, weekly price chart
- Fleet efficiency (avg trip days), fuel cost summary
- Baseline price management per crop

### 11.4 Admin Audit Logs
- `/admin/audit-logs` → searchable log of every system action

---

## Phase 12 — Edge Cases to Test

| Scenario | How to Trigger |
|----------|---------------|
| **Inactive user blocked** | Admin sets user status = inactive → user force-logged out on next request |
| **Unverified farmer blocked** | Unverified farmer tries to post/create harvest → redirected with error |
| **Unverified logistics blocked** | Unverified logistics opens route optimization → redirected with error |
| **Archive farmer with active harvests** | Admin toggles status on farmer with active harvests → JS confirmation prompt → harvests auto-cancelled on force |
| **Delete driver with active jobs** | Try removing a driver profile that's actively assigned → would need additional guard |
| **Duplicate negotiation** | Buyer starts negotiation on same harvest twice → redirected to existing room |
| **Pooling with no available trucks** | Verify empty state shows in route optimization |
| **Pooling with no visible farmers** | Verify empty map state |
| **Counter-offer loop** | Farmer counters → logistics counters → farmer counters → etc. |
| **Telemetry with WS offline** | GPS stream works without WebSocket (silent fail) |
| **Stop status wrong sequence** | Try "Delivered" before "Loaded" → validation error |
| **Expired OTP** | Wait 10 min after OTP sent → try verifying → "OTP has expired" |

---

## Test Accounts Quick Reference

| Role | Actions |
|------|---------|
| **Admin** | verify users, manage crops, audit logs, analytics |
| **Farmer** | post harvests, accept pooling, negotiate with buyers, upload docs |
| **Logistics** | register trucks/drivers, plan routes, manage proposals, cost ledger |
| **Driver** | view jobs, update stop status, log fuel, stream GPS |
| **Buyer** | browse crops, negotiate, track deliveries, confirm receipt |

---

## API Endpoints Used by Frontend

| Endpoint | Method | Purpose |
|----------|--------|---------|
| `/api/notifications` | GET | Fetch unread notifications |
| `/api/notifications/{id}/read` | POST | Mark single notification read |
| `/api/notifications/read-all` | POST | Mark all notifications read |
| `/pooling/plan` | GET | Plan route (AJAX) |
| `/pooling/confirm` | POST | Save pooling job |
| `/tracking/{job}/latest` | GET | Latest GPS position |
| `/tracking/{job}/eta` | GET | Estimated arrival time |
| `/driver/tracking/store` | POST | Driver GPS ingress |
| `/negotiations/{id}/messages` | GET | Poll chat messages |
| `/negotiations/{id}/message` | POST | Send chat message |
| `/negotiations/{id}/propose` | POST | Propose price terms |
| `/verification-status` | GET | Check email verified status |
