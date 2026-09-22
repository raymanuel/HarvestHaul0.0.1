# B2B Crop Distribution and Logistics Management System

### Running System Document — Working Version

**Note:** This is a working document. Requirements, workflows, roles, objectives, and system features may change as the system is further analyzed and developed.

# System Overview

The proposed system is an **integrated web-based B2B crop distribution and logistics management system** designed to connect farmers, agricultural cooperatives, and business buyers through a cooperative-centered distribution process.

Farmers are affiliated with cooperatives and **sell their harvested crops exclusively to their cooperative**. Business buyers do not transact directly with individual farmers. Instead, buyers purchase crops from registered cooperatives through the platform.

The system supports the flow of harvested crops from:

**Farmer → Cooperative → Buyer**

while integrating the associated **harvest pickup requests, pickup scheduling, consolidated hauling, route planning and optimization, receiving, grading, procurement, crop availability, B2B ordering, delivery, communication, location monitoring, market-price monitoring, and reporting processes.**

The logistics component specifically supports **resource pooling** by allowing multiple compatible farmer harvests to be considered for one hauling trip when they can fit within a truck's capacity and can be collected within their respective pickup schedules and time windows.

The system will be deployed using shared hosting constraints. Therefore, real-time WebSocket communication and background workers will not be required. Location updates for active hauling and deliveries will use **HTTP polling**, with **Leaflet and OpenStreetMap** for map visualization.

# Technology Stack

### Backend

- Laravel 12
- PHP 8.2+
- MySQL
- Laravel Tinker
- Doctrine DBAL
- dompdf for PDF reports
- DOM Crawler for collecting/scraping DA market price data

### Frontend

- Blade Templates
- Tailwind CSS v4
- Alpine.js
- Vite
- Leaflet
- OpenStreetMap
- SweetAlert2

### Development / Testing

- Composer
- npm
- Playwright
- Pest / PHPUnit
- Laravel Pint
- php artisan route:list

### Deployment Constraint

The system is designed for shared hosting and therefore avoids:

- WebSockets
- Persistent socket connections
- Queue workers as a required system component
- Other server processes that are unsuitable for typical shared hosting

For location monitoring, the system uses **periodic HTTP polling**.

The route-planning and consolidation functions are designed to execute as normal Laravel application operations and do not depend on continuously running optimization workers.

# General Objective

To develop an integrated web-based B2B Crop Distribution and Logistics Management System that facilitates the acquisition of farmers' harvests by cooperatives and the subsequent distribution of available crops to business buyers through integrated procurement, communication, pickup scheduling, consolidated hauling, route planning and optimization, delivery, location monitoring, market-price monitoring, and reporting functions.

# Specific Objectives

1. **To develop a cooperative and user management module** that enables platform administrators to verify cooperative registrations and manage user accounts according to their respective roles and cooperative affiliations.
2. **To develop a farmer harvest procurement and communication module** that enables cooperatives to manage farmer records, receive harvest and haul requests, schedule and coordinate pickups, document the acquisition of crops from affiliated farmers, and facilitate in-app communication between farmers and authorized cooperative personnel regarding harvest and pickup activities.
3. **To develop a crop availability and B2B ordering module** that enables cooperatives to manage crops acquired from farmers and allows verified business buyers to view available crops, place orders, and monitor their transactions with cooperatives.
4. **To develop a pickup scheduling, consolidated hauling, and route optimization module** that enables cooperatives to schedule farmer harvest pickups according to requested dates and time windows, consider truck availability and capacity, identify compatible pickup requests for resource pooling, and generate feasible road-based pickup routes involving multiple farmer pickup points.
5. **To develop a delivery, location monitoring, market-price monitoring, communication, and reporting module** that facilitates delivery scheduling, coordination with delivery personnel, periodic location monitoring using HTTP-based polling, agricultural market-price monitoring, in-app operational communication, and the generation of reports related to procurement, pickup scheduling, consolidated hauling, crop availability, orders, deliveries, and related transactions.

# Core User Roles

The system currently has **six core user roles**.

## A. Super Admin

The Super Admin operates at the **platform level**.

### Responsibilities

- Review and verify cooperative registrations
- Approve, reject, suspend, or reactivate cooperative accounts
- Manage Cooperative Admin access
- Manage system-wide reference data and classifications
- Monitor registered cooperatives
- View platform-level reports and summaries

### Important limitation

The Super Admin does **not** manage individual farmers or delivery personnel belonging to a cooperative.

The Super Admin manages the **platform and cooperative accounts**, while each cooperative manages its own people and operations.

# B. Cooperative Admin

The Cooperative Admin operates at the **cooperative level**.

### Responsibilities

- Manage the cooperative's account and information
- Manage affiliated farmers
- Manage cooperative personnel
- Review and manage farmer haul requests
- Review future pickup requests by date
- Schedule crop pickups
- Review truck and personnel availability
- Plan consolidated pickup trips
- Review system-generated pickup combinations and routes
- Modify proposed pickup combinations or routes
- Assign trucks
- Assign delivery/hauling personnel
- Create and approve pickup trips
- Record or confirm crop receiving information
- Record actual crop weight
- Record actual number of sacks/packages
- Record quality classification
- Record the cooperative's buying price
- Confirm procurement transactions
- Manage crop availability for buyers
- Process buyer orders
- Schedule outbound buyer deliveries
- Manage outbound buyer deliveries
- Monitor active hauling/delivery operations
- Communicate with farmers and delivery personnel through in-app messaging
- Review logistics and transaction information
- Generate cooperative-level reports

The Cooperative Admin is responsible for the cooperative's overall system operations and retains final operational control over proposed pickup schedules and routes.

# C. Cooperative Field / Receiving Personnel

This role represents the cooperative **physically during farm pickup and crop receiving**.

The role exists because the actual weight, quality, and buying price may need to be determined at the farm during pickup.

### Responsibilities

- Accompany or participate in scheduled farm pickups
- Physically inspect the harvested crops
- Observe or record the actual weighing
- Record the actual number of sacks/packages
- Record the actual weight
- Evaluate and record the quality classification
- Record the applicable/agreed buying price
- Record receiving remarks
- Submit the receiving information to the cooperative system
- Communicate with the Coop Admin when receiving or procurement issues arise

The field/receiving personnel represents the cooperative during the physical procurement process.

The role does not necessarily mean a completely separate employee in every cooperative. In a small cooperative, one person may perform multiple functions. The system should nevertheless keep the responsibilities conceptually separate.

# D. Delivery Personnel

Delivery Personnel are responsible primarily for the **physical transportation** of crops.

There are two logistics stages in the system:

### Inbound Logistics

**Farmer → Cooperative**

This is the crop pickup/haul.

### Outbound Logistics

**Cooperative → Buyer**

This is the buyer delivery.

### Responsibilities

- View assigned hauling/delivery tasks
- View assigned pickup sequence and route
- View pickup or delivery information
- View farmer pickup locations
- View destination information
- Update transport status
- Confirm arrival at pickup location
- Confirm pickup
- Confirm delivery
- Provide periodic location updates through HTTP requests
- Use the PWA while traveling
- View assigned destination and route information
- Communicate with the Coop Admin through in-app messaging
- Report operational issues such as delays, capacity problems, or route problems

Delivery Personnel do not automatically have authority to determine crop quality or procurement pricing unless the cooperative assigns those additional responsibilities to them.

# E. Farmer

Farmers are affiliated with a cooperative and **sell their harvested crops exclusively to that cooperative through the system's procurement workflow**.

Farmers do not directly sell crops to platform buyers.

### Responsibilities

- Maintain their farmer profile
- View their cooperative affiliation
- Submit harvest haul requests
- Provide estimated crop information
- Provide estimated number of sacks/packages
- Provide estimated weight
- Specify requested/preferred pickup date
- Specify preferred pickup time or time window
- Confirm or maintain their pickup location
- View haul request status
- View scheduled pickup information
- Communicate with authorized cooperative personnel through in-app messaging
- View recorded receiving information
- View quality classification assigned to their harvest
- View actual received quantity/weight
- View buying price and procurement transaction information

### Important principle

The farmer does **not** determine the final:

- Quality grade
- Actual weight
- Cooperative buying price
- Final procurement amount

These are determined/recorded during the cooperative's receiving/procurement process.

# F. Buyer

Buyers are businesses that purchase agricultural crops from cooperatives.

Buyers **do not directly contact or transact with individual farmers**.

### Responsibilities

- Register a buyer/business account
- Maintain business profile
- Browse available crops from cooperatives
- View crop quantity and quality classification
- View cooperative selling prices
- Place B2B orders
- View order status
- View delivery information
- Track active deliveries through periodic location updates
- View transaction/order history
- Confirm receipt of orders

### Buyer Verification

Buyer registration and buyer account verification are separate from cooperative membership.

A buyer does not become a member of a cooperative.

The buyer interacts with the cooperative through orders and transactions.

# 6\. User Registration Structure

Only **cooperatives and buyers** have independent public registration processes.

### Cooperative Registration

```
Prospective Cooperative
        ↓
Register Cooperative
        ↓
Submit organizational information
and supporting documents
        ↓
Super Admin Review
        ↓
Approved / Rejected / Additional Information
        ↓
Cooperative Activated
        ↓
Cooperative Admin Access
```

### Buyer Registration

```
Business Buyer
      ↓
Register
      ↓
Buyer Account
      ↓
Verification / Activation
      ↓
Browse Cooperatives
      ↓
Place Orders
```

### Internally Managed Accounts

Farmers and cooperative personnel do not independently register as members of a cooperative.

Their accounts are created/managed through the cooperative.

```
Cooperative Admin
       │
       ├── Farmer Accounts
       │
       ├── Field/Receiving Personnel
       │
       └── Delivery Personnel
```

# 7\. Cooperative Verification

A cooperative registration should provide enough information for the Super Admin to review whether the organization is a legitimate cooperative before activating its platform account.

### Cooperative Information

- Cooperative name
- Cooperative type
- Address
- Province
- City/Municipality
- Barangay
- Contact number
- Official email
- Year established
- Business activities
- Agricultural/crop-related activities

### Registration Information

- CDA registration number
- Certificate of Registration
- Articles of Cooperation
- By-Laws
- Supporting registration/compliance documents, where applicable

### Authorized Representative

The cooperative should identify its authorized representative/initial Cooperative Admin.

Information may include:

- Full name
- Position/designation
- Contact information
- Identification information
- Authorization document / Board Resolution or equivalent proof of authorization

### Verification Status

The cooperative registration should have a lifecycle such as:

```
Pending
   ↓
Under Review
   ↓
┌─────────────────┐
│                 │
▼                 ▼
Approved       Rejected
│
▼
Active
```

The system should also support:

**Request Additional Information**

rather than forcing the Super Admin to immediately reject an incomplete application.

# 8\. Core Business Workflow

The current primary workflow is:

```
FARMER
   │
   │ Harvests crops
   ▼
HARVEST READY
   │
   │ Requests haul
   ▼
HAUL REQUEST
   │
   │ Cooperative reviews
   ▼
PICKUP SCHEDULING
   │
   ├── Requested date
   ├── Preferred time window
   ├── Estimated crop quantity
   ├── Estimated weight
   └── Pickup location
   │
   ▼
CONSOLIDATED PICKUP PLANNING
   │
   ├── Truck availability
   ├── Truck capacity
   ├── Farmer pickup locations
   ├── Geographic compatibility
   ├── Pickup time windows
   └── Estimated load
   │
   ▼
ROUTE PLANNING / OPTIMIZATION
   │
   ├── Road distance
   ├── Travel time
   ├── Route sequence
   └── Time-window validation
   │
   ▼
COOP ADMIN REVIEW
   │
   ├── Accept
   ├── Modify
   └── Re-plan
   │
   ▼
PICKUP TRIP
   │
   │ Driver / field personnel assigned
   ▼
CONSOLIDATED FARM PICKUP
   │
   │ Multiple farmer stops
   ▼
RECEIVING & GRADING
   │
   ├── Actual sacks/packages
   ├── Actual weight
   ├── Quality grade
   └── Buying price
   │
   ▼
PROCUREMENT TRANSACTION
   │
   ▼
CROP AVAILABILITY
   │
   ▼
BUYER
   │
   │ Places B2B order
   ▼
COOPERATIVE PROCESSES ORDER
   │
   ▼
BUYER DELIVERY
   │
   ▼
BUYER RECEIVES CROPS
```

# 9\. Farmer Haul Request

The farmer's main action is not to "manage inventory."

The farmer communicates:

**"I have harvested crops ready for pickup by my cooperative."**

### Example

A farmer has:

**80 sacks of corn**

The farmer submits:

- Crop: Corn
- Packaging: Sack
- Number of sacks: 80
- Estimated weight: 4,000 kg
- Harvest date
- Requested pickup date
- Preferred pickup time/window
- Pickup location
- Notes

The farmer does **not** need to enter the final purchase price.

The requested pickup date may be **several days in the future**, such as three or seven days ahead, because the cooperative may have limited trucks serving many affiliated farmers.

# 10\. Packaging vs. Weight

The system distinguishes between **how crops are packaged** and **how crops are measured**.

Example:

80 sacks of corn

does not necessarily mean the final weight is exactly:

4,000 kg

The request may contain an estimated weight.

During actual receiving, the cooperative may record:

| **Measurement**  | **Example** |
| ---------------- | ----------- |
| Estimated sacks  | 80          |
| Estimated weight | 4,000 kg    |
| Actual sacks     | 80          |
| Actual weight    | 3,920 kg    |

### Principle

**Package quantity** describes the physical packaging.

**Weight** describes the measured quantity.

Both can be recorded.

For logistics planning, the **estimated weight** is used for initial truck-capacity calculations, while the **actual weight** is recorded during physical receiving.

# 11\. Crop Quality Classification

Quality classifications are system-wide reference values.

Example:

```
Grade A
Grade B
Grade C
```

The **Super Admin manages the available classification options**.

The **Cooperative applies the classification** to an actual harvest during receiving.

The Super Admin does not decide the quality of an individual farmer's harvest.

### Example

```
Super Admin
     │
     │ maintains classifications
     ▼
Grade A / Grade B / Grade C
     │
     ▼
Cooperative Receiving
     │
     ▼
Juan's Corn
3,920 kg
Grade A
```

Quality belongs to the **harvest/procurement record**, not permanently to the farmer.

A farmer can have:

- One harvest graded A
- Another harvest graded B
- Another crop graded A

# 12\. Crop Pricing

The system distinguishes between **market reference price**, **cooperative buying price**, and **cooperative selling price**.

## A. Market Reference Price

The system can retrieve DA market-price information.

This serves as a reference for cooperative operations.

It is not automatically the farmer's purchase price.

## B. Cooperative Buying Price

This is the amount the cooperative pays the farmer.

Example:

```
Actual weight: 3,920 kg
Quality: Grade A
Buying price: ₱18/kg
```

The system calculates:

**3,920 × ₱18 = ₱70,560**

## C. Cooperative Selling Price

The cooperative may later make the crop available to buyers at a different price.

Example:

```
Cooperative buying price:
₱18/kg
Cooperative selling price:
₱23/kg
```

The farmer's purchase price and buyer's selling price are therefore separate transaction values.

# 13\. When Is the Buying Price Determined?

The haul request does **not require the farmer to post a crop price**.

The price is determined/recorded during the cooperative's receiving/procurement process.

The actual arrangement may involve:

- A cooperative-set buying price
- A price based on quality
- A negotiated price
- Another cooperative-defined pricing arrangement

The system should record the **actual buying price used in the procurement transaction**.

If the price is negotiated during pickup, the system can record the agreed price and calculate the final procurement amount.

# 14\. Pickup / Receiving Example

### Farmer Request

```
Farmer: Juan Dela Cruz
Crop: Corn
Packaging: Sack
Estimated quantity: 80 sacks
Estimated weight: 4,000 kg
Requested pickup:
September 25
Preferred pickup window:
7:00 AM – 10:00 AM
```

### Scheduled Consolidated Haul

```
Haul: HAUL-00021
Driver: Pedro Reyes
Vehicle: Truck 003
Pickup Date: September 25, 2026
Pickup Stops:
1. Juan Dela Cruz
2. Maria Santos
3. Pedro Garcia
```

### Actual Receiving

The cooperative's authorized field/receiving personnel observes the physical receiving process.

```
Sacks received: 80
Actual weight: 3,920 kg
Quality: Grade A
Buying price: ₱18/kg
```

### Procurement

```
3,920 kg × ₱18/kg
= ₱70,560
```

The procurement transaction is then confirmed by the cooperative.

# 15\. Logistics Structure

The system has two distinct logistics workflows.

## A. Farmer-to-Cooperative Hauling

Purpose:

Collect harvested crops from farmers.

Flow:

```
Haul Request
      ↓
Pickup Scheduling
      ↓
Pickup Consolidation
      ↓
Truck Capacity Check
      ↓
Route Planning / Optimization
      ↓
Coop Admin Review
      ↓
Truck / Personnel Assignment
      ↓
Consolidated Farm Pickup
      ↓
Cooperative Receiving
```

The system supports multiple farmers being assigned to one hauling trip when their harvests and pickup schedules are compatible.

## B. Cooperative-to-Buyer Delivery

Purpose:

Deliver purchased crops to the buyer.

Flow:

```
Buyer Order
      ↓
Order Processing
      ↓
Crop Allocation / Fulfillment
      ↓
Delivery Scheduling
      ↓
Driver Assignment
      ↓
Route / Destination Information
      ↓
Crop Delivery
      ↓
Buyer Receipt
```

These should not be treated as one generic delivery process because they represent different business activities.

# 16\. Pickup Scheduling, Consolidation and Route Optimization

This module is specifically designed around the cooperative's **limited transportation resources**.

A cooperative may have:

- Hundreds of affiliated farmers
- Multiple harvest requests
- Only a small number of trucks
- Limited delivery personnel
- Farmers requesting pickups several days ahead
- Different pickup dates and time windows
- Different estimated crop weights
- Farmer locations distributed across different areas

Therefore, the system must determine not only:

**"What is the shortest route?"**

but also:

**"Which farmers can feasibly be picked up together, on which date, by which truck, and in what sequence?"**

## A. Pickup Scheduling

Each haul request contains scheduling information such as:

- Requested pickup date
- Preferred pickup date, where applicable
- Preferred pickup time
- Pickup time window
- Harvest readiness
- Pickup location

Example:

```
Farmer A
September 25
7:00–9:00 AM
Farmer B
September 25
8:00–11:00 AM
Farmer C
September 25
10:00 AM–1:00 PM
```

The system evaluates whether these requests can be included in the same trip.

## B. Truck Availability

The cooperative maintains truck information such as:

- Truck ID
- Plate number
- Vehicle type
- Maximum carrying capacity
- Current status
- Availability
- Existing scheduled trips

Possible truck statuses:

```
Available
Scheduled
In Transit
On Pickup
Maintenance
Unavailable
```

The planner must ensure that the same truck is not assigned to overlapping trips.

## C. Consolidated Pickup

The system attempts to combine compatible pickup requests.

Example:

```
Truck capacity = 4,000 kg
Farmer A = 1,500 kg
Farmer B =   800 kg
Farmer C = 1,200 kg
```

Combined:

```
3,500 kg
```

Truck utilization:

```
3,500 / 4,000 = 87.5%
```

This can be considered a feasible consolidated load, subject to geographic and scheduling constraints.

## D. Consolidation Is Not Based on Capacity Alone

A farmer combination must also be operationally feasible.

The system considers:

### Capacity

Can the estimated harvest fit?

### Date

Are the requested pickup dates compatible?

### Time

Can all farmers be visited within their pickup windows?

### Geography

Are the farms reasonably close or along a practical route?

### Truck availability

Is a suitable truck available on that date?

### Personnel availability

Is an assigned delivery/hauling person available?

Therefore:

A combination that fits the truck does not automatically become a valid consolidated trip.

# 17\. Route Planning and Optimization

The route-planning feature is more than simply finding a shortest path.

The system's route-planning problem considers:

- Pickup date
- Pickup time windows
- Farmer locations
- Cooperative/depot location
- Truck capacity
- Estimated harvest weight
- Truck availability
- Personnel availability
- Road distance
- Estimated travel time
- Pickup sequence

The long-term optimization problem can be modeled as a **Capacitated Vehicle Routing Problem with Time Windows (CVRPTW)**.

However, the initial implementation will use simpler and more practical algorithms that fit the project's Laravel/shared-hosting environment.

## Planned Optimization Approach

```
Pending Pickup Requests
        ↓
Filter by Pickup Date
        ↓
Filter by Compatible Time Windows
        ↓
Geographic Filtering
        ↓
Truck Availability
        ↓
Capacity-Based Selection
        ↓
Knapsack-Based Consolidation
        ↓
OSRM Road Distance / Travel Time
        ↓
Initial Route
        ↓
Nearest Neighbor
        ↓
2-opt Route Improvement
        ↓
Time-Window Validation
        ↓
Proposed Route
        ↓
Coop Admin Review
```

## A. Knapsack-Based Capacity Selection

A knapsack-based approach can be used to determine which pickup requests can fit within a truck's estimated capacity.

Example:

```
Truck capacity = 4,000 kg
A = 1,500 kg
B =   800 kg
C = 1,200 kg
D = 1,000 kg
```

The system can identify:

```
A + B + C = 3,500 kg
```

while:

```
A + B + C + D = 4,500 kg
```

is infeasible.

The knapsack stage is therefore used primarily for **load consolidation**, not as the complete routing algorithm.

## B. Geographic Filtering

Before evaluating route combinations, the system can eliminate obviously impractical combinations.

Example:

```
Farmer A ── 3 km ── Farmer B
                 │
                 5 km
                 │
              Farmer C
Farmer D ───────────── 80 km ─────────── Farmer A
```

A, B, and C are more natural candidates for consolidation.

D may be considered for another trip.

## C. OSRM

The system can use **OSRM** to obtain road-based routing information.

It can provide:

- Road distance
- Estimated travel time
- Road route geometry

This is preferable to relying only on straight-line geographic distance.

Example:

```
Coop → Farmer A = 8 km
A → Farmer B = 12 km
B → Farmer C = 7 km
C → Coop = 10 km
Total = 37 km
```

## D. Nearest Neighbor

An initial route can be generated using a heuristic such as:

Visit the nearest feasible next pickup point.

Example:

```
Coop
 ↓
Farmer A
 ↓
Farmer B
 ↓
Farmer C
 ↓
Coop
```

This provides an initial route for improvement.

## E. 2-opt

The initial route can then be improved using **2-opt**.

The system tests route segment swaps to determine whether the total travel distance can be reduced.

Conceptually:

```
Initial:
Coop → A → B → C → D → Coop
        ↓ 2-opt
Improved:
Coop → A → C → B → D → Coop
```

The process continues until no useful route improvement is found or the configured optimization limit is reached.

## F. Time-Window Validation

After generating or improving a route, the system checks whether the route remains feasible against the farmers' pickup windows.

Example:

```
Farmer A:
7:00–9:00 AM
Estimated arrival:
7:30 AM
✓ Feasible
```

But:

```
Farmer C:
7:00–8:00 AM
Estimated arrival:
10:00 AM
✗ Conflict
```

The system should flag the conflict rather than silently creating an impossible schedule.

# 18\. Consolidated Pickup Planner

The main logistics planning interface is:

**Consolidated Pickup Planner**

It combines:

- Pending pickup requests
- Pickup calendar
- Available trucks
- Available personnel
- Estimated load
- Pickup time windows
- Farmer locations
- Map
- Pickup sequence
- Route distance
- Estimated travel time
- Capacity utilization
- Route feasibility

## Example Webpage Layout

```
┌───────────────────────────────────────────────────────────────┐
│ CONSOLIDATED PICKUP PLANNER                                  │
│ September 25, 2026                                           │
├──────────────────────┬────────────────────────────────────────┤
│ PICKUP REQUESTS      │                                        │
│                      │                 MAP                    │
│ ☑ Farmer A           │                                        │
│   Corn               │          ② Farmer B                    │
│   ~1,500 kg          │             /                          │
│   7–10 AM            │      ① Farmer A                        │
│                      │            \                           │
│ ☑ Farmer B           │             ③ Farmer C                 │
│   Corn               │                  \                     │
│   ~800 kg            │                  COOP                   │
│   8–11 AM            │                                        │
│                      │                                        │
│ ☑ Farmer C           │                                        │
│   Vegetables         │                                        │
│   ~1,200 kg          │                                        │
│   10–1 PM            │                                        │
│                      │                                        │
├──────────────────────┴────────────────────────────────────────┤
│ TRUCK 03                                                     │
│ Capacity: 4,000 kg    Estimated Load: 3,500 kg    87.5%      │
│ Distance: 37 km       Estimated Time: 1h 18m                 │
│ Time Windows: ✓ Feasible                                     │
│                                                               │
│ [Re-optimize] [Modify Route] [Create Pickup Trip]             │
└───────────────────────────────────────────────────────────────┘
```

# 19\. Route Map

The map uses:

**Leaflet + OpenStreetMap**

The map displays:

- Cooperative location
- Farmer pickup locations
- Numbered pickup sequence
- Planned route
- Active truck location during execution

Markers can display information such as:

```
Farmer B
Crop:
Corn
Estimated:
800 kg
Pickup:
8:00–11:00 AM
Status:
Scheduled
```

The map therefore functions as both a **planning interface** and a **monitoring interface**.

# 20\. Coop Admin Route Review

The system provides a proposed route rather than automatically dispatching the truck.

Example:

```
PROPOSED ROUTE
Truck 03
1. Farmer A
2. Farmer B
3. Farmer C
Estimated Load:
3,500 / 4,000 kg
Utilization:
87.5%
Distance:
37 km
Estimated Duration:
1h 18m
Time Windows:
✓ Feasible
```

The Coop Admin can:

- Accept the proposed route
- Modify the route
- Add a pickup
- Remove a pickup
- Change pickup sequence
- Change truck
- Change assigned personnel
- Re-optimize
- Reschedule a pickup

# 21\. Manual Route Modification

If the Coop Admin changes:

```
A → B → C
```

to:

```
A → C → B
```

the system recalculates:

- Road distance
- Travel time
- Estimated arrival times
- Time-window feasibility
- Capacity information

Example:

```
Distance:
37 km → 42 km
Travel time:
1h 18m → 1h 31m
Time windows:
✓ Still feasible
```

The Coop Admin retains final operational control.

# 22\. Pickup Trip Creation

After the Coop Admin approves the plan:

```
Pickup Trip #PU-00421
Date:
September 25
Truck:
Truck 03
Delivery Personnel:
Juan D.
Stops:
1. Farmer A
2. Farmer B
3. Farmer C
Estimated Load:
3,500 kg
Truck Capacity:
4,000 kg
Estimated Utilization:
87.5%
```

The selected haul requests change from:

```
Pending
```

to:

```
Scheduled for Pickup
```

# 23\. Actual Pickup and Receiving

At each farm, the physical pickup process occurs.

The planned quantity and weight are only estimates.

The actual process is:

```
Arrive at Farm
      ↓
Inspect Harvest
      ↓
Load Harvest
      ↓
Physically Weigh Crop
      ↓
Record Actual Sacks/Packages
      ↓
Record Actual Weight
      ↓
Determine/Record Quality Grade
      ↓
Determine/Record Buying Price
      ↓
Calculate Total Purchase
      ↓
Confirm Pickup
```

The actual values become part of the procurement record.

# 24\. Actual Weight and Consolidated Capacity

Actual weights can differ from estimates.

Example:

### Planned

```
A = 1,500 kg
B =   800 kg
C = 1,200 kg
Total = 3,500 kg
```

### Actual

```
A = 1,700 kg
B =   900 kg
C = 1,600 kg
Total = 4,200 kg
```

If the truck capacity is:

```
4,000 kg
```

the system flags:

**Truck capacity exceeded.**

The Delivery Personnel can communicate with the Coop Admin through in-app messaging so the cooperative can decide how to handle the situation.

Possible operational actions may include:

- Reassigning part of the load
- Using another truck
- Adjusting the remaining pickup plan
- Returning excess cargo
- Recording the actual trip outcome

The system should record the actual result rather than silently changing the original estimate.

# 25\. In-App Messaging

Messaging is integrated into the operational workflow.

### Farmer ↔ Cooperative

Used for:

- Pickup coordination
- Harvest readiness
- Pickup schedule
- Pickup delays
- Questions regarding receiving
- Procurement-related communication
- Other harvest/pickup concerns

### Cooperative ↔ Delivery Personnel

Used for:

- Route changes
- Pickup problems
- Weight/capacity issues
- Delayed pickup
- Truck problems
- Changes in pickup sequence
- Operational instructions

The initial system does **not** require direct Farmer ↔ Buyer messaging.

# 26\. Location Tracking

Because the system will run on shared hosting, it will not depend on WebSockets or persistent server processes.

Instead:

```
Delivery Personnel PWA
          │
          │ periodic location update
          ▼
     HTTP Request
          │
          ▼
       Laravel
          │
          ▼
        MySQL
          │
          │ HTTP polling
          ▼
Coop Admin / Buyer Dashboard
          │
          ▼
Leaflet + OpenStreetMap
```

The system uses **HTTP polling** to retrieve updated location information.

The same approach can support:

- Farmer-to-cooperative hauling
- Cooperative-to-buyer deliveries

depending on the current active assignment.

The system should treat this as **periodic location monitoring**, not continuous real-time positioning.

# 27\. PWA

The system is designed as a **Progressive Web App** so that all system roles can access the platform through a browser while delivery personnel can use the system on a mobile device during transit.

The PWA approach applies to:

- Super Admin
- Cooperative Admin
- Field/Receiving Personnel
- Delivery Personnel
- Farmer
- Buyer

The interface remains role-specific.

Example:

```
Login
  ↓
Role Detection
  │
  ├── Super Admin
  │      ↓
  │   Platform Dashboard
  │
  ├── Coop Admin
  │      ↓
  │   Cooperative Dashboard
  │
  ├── Field/Receiving
  │      ↓
  │   Receiving Dashboard
  │
  ├── Delivery Personnel
  │      ↓
  │   Mobile Trip Dashboard
  │
  ├── Farmer
  │      ↓
  │   Farmer Dashboard
  │
  └── Buyer
         ↓
     Buyer Dashboard
```

The Delivery Personnel interface should be designed primarily for **mobile/PWA use**, since the user will generally be operating the system while traveling rather than from a laptop.

# 28\. System-Wide Reference Data

The Super Admin manages standardized values used throughout the platform.

Potential reference data includes:

### Crop Information

- Crop categories
- Crop types

### Quality Classifications

- Grade A
- Grade B
- Grade C

### Packaging

- Sack
- Crate
- Other applicable packaging types

### Logistics

- Haul statuses
- Pickup statuses
- Trip statuses
- Delivery statuses
- Truck statuses

### Orders

- Order statuses
- Other system-wide transaction statuses

The Super Admin maintains the available choices, while cooperative users apply them to actual transactions.

The quality classification itself is still determined by the cooperative during physical receiving.

# 29\. Core Modules

Based on the current workflow, the system is currently divided into these major modules:

1. **Cooperative Registration & Verification**
2. **User and Role Management**
3. **Farmer Management**
4. **Harvest Haul Requests**
5. **Pickup Scheduling**
6. **Pickup / Hauling Management**
7. **Consolidated Pickup Planning**
8. **Route Planning & Optimization**
9. **Fleet / Truck Management**
10. **Receiving & Quality Grading**
11. **Cooperative Procurement**
12. **Crop Availability**
13. **B2B Buyer Orders**
14. **Buyer Delivery Management**
15. **In-App Messaging**
16. **Location Monitoring**
17. **Market Price Monitoring**
18. **Reports**
19. **System Reference Data**

These modules remain subject to refinement as each user role and workflow is examined.

# 30\. Core Data Concepts

The current conceptual relationships are:

```
COOPERATIVE
     │
     ├── Cooperative Admin
     │
     ├── Field/Receiving Personnel
     │
     ├── Delivery Personnel
     │
     ├── Farmers
     │
     └── Trucks
            │
            ▼
      HAUL REQUEST
            │
            ▼
   PICKUP SCHEDULE
            │
            ▼
  CONSOLIDATED PICKUP PLAN
            │
       ┌────┴────┐
       │         │
       ▼         ▼
    Farmers    Truck
       │         │
       └────┬────┘
            ▼
       ROUTE PLAN
            │
            ▼
        PICKUP TRIP
            │
            ▼
         RECEIVING
            │
       ┌────┼─────────┐
       ▼    ▼         ▼
    Weight Grade     Price
       │    │         │
       └────┼─────────┘
            ▼
       PROCUREMENT
            │
            ▼
     CROP AVAILABILITY
            │
            ▼
          BUYER
            │
            ▼
          ORDER
            │
            ▼
        DELIVERY
```

A key principle is that the **farmer is the source of the harvest, the cooperative is the procurement and distribution intermediary, and the buyer is the B2B customer**.

The logistics planning layer additionally connects multiple farmer haul requests to a shared truck when consolidation is feasible.

# 31\. Current System Boundary

The system is primarily concerned with:

**Farmer → Cooperative → Buyer**

and the supporting logistics and transactions surrounding that flow.

The system is not intended to:

- Replace CDA cooperative registration
- Function as a government regulatory system
- Allow buyers to bypass cooperatives and purchase directly from farmers
- Require farmers to manage a conventional warehouse-style inventory
- Require WebSockets for live tracking
- Require a server-side worker process for core functionality
- Automatically dispatch trucks without Coop Admin review
- Treat route optimization as simply finding the mathematically shortest road without considering truck capacity, pickup dates, time windows, and availability

# 32\. Current Key Design Principles

### Principle 1 — Cooperative-centered distribution

Farmers sell their harvests through their cooperative.

### Principle 2 — Buyers transact with cooperatives

Buyers do not directly purchase from or contact individual farmers for platform transactions.

### Principle 3 — Farmers request hauling

The farmer reports harvested crops that are ready for cooperative pickup.

### Principle 4 — Pickup requests may be submitted in advance

Farmers may request pickup several days ahead because cooperative trucks are limited resources shared among many affiliated farmers.

### Principle 5 — Pickup scheduling precedes route planning

The system must first determine which requests are compatible by date, time window, truck availability, and other scheduling constraints before constructing a route.

### Principle 6 — Resource pooling / consolidated hauling

Multiple compatible farmer harvests may be assigned to one truck when their combined estimated load fits the vehicle and their pickup schedules and locations allow a feasible trip.

### Principle 7 — Route optimization considers more than distance

Route planning considers:

- Pickup locations
- Road distance
- Travel time
- Truck capacity
- Pickup dates
- Pickup time windows
- Truck availability
- Personnel availability
- Consolidation opportunities

### Principle 8 — Final route remains subject to human review

The system provides a proposed route. The Coop Admin can accept, modify, or reject the proposal before creating the pickup trip.

### Principle 9 — Final quantity is determined during receiving

The farmer may provide an estimated quantity/weight, but the cooperative records the actual received quantity and weight.

### Principle 10 — Quality is determined during receiving

The cooperative assigns the quality classification to the actual harvest.

### Principle 11 — Pricing is separate from haul requests

The farmer does not have to post the final buying price when requesting a haul.

### Principle 12 — Packaging and weight are separate measurements

For example:

**80 sacks of corn → actual measured weight of 3,920 kg.**

### Principle 13 — Buying and selling prices are separate

The price paid by the cooperative to the farmer may differ from the price charged to the buyer.

### Principle 14 — Physical responsibilities are separated

The person operating the truck does not automatically have authority to determine quality or procurement price.

### Principle 15 — HTTP polling for logistics tracking

Location monitoring uses periodic HTTP requests rather than WebSockets because of the shared-hosting deployment constraint.

### Principle 16 — PWA-based field operations

Delivery and field personnel can access their assigned operational functions through a mobile-friendly PWA rather than requiring laptops during transit.

### Principle 17 — Super Admin controls the platform, not cooperative operations

The Super Admin verifies and manages cooperatives and platform-level information.

### Principle 18 — Cooperative manages its own people

Farmers, field/receiving personnel, and delivery personnel are managed within the cooperative.

### Principle 19 — Algorithm supports, not replaces, operational decisions

The route optimization engine provides recommendations based on available system data, while the Coop Admin retains final authority over scheduling, consolidation, truck assignment, and route approval.

# 33\. Current Overall Flow

The system's current conceptual flow is:

```
                         SUPER ADMIN
                              │
                       Verifies Cooperatives
                              │
                              ▼
                         COOPERATIVE
                              │
                       COOPERATIVE ADMIN
                              │
       ┌──────────────────────┼────────────────────────┐
       │                      │                        │
    FARMERS             FIELD/RECEIVING           DELIVERY
                           PERSONNEL              PERSONNEL
       │                      │                        │
       │                      │                        │
       │                Physical Receiving             │
       │                      │                        │
       │                      │                        │
       └────────────── HARVEST / HAUL ─────────────────┘
                              │
                              ▼
                     HAUL REQUESTS
                              │
                              ▼
                    PICKUP SCHEDULING
                              │
                ┌─────────────┼─────────────┐
                │             │             │
             Date         Time Window     Truck
                │             │         Availability
                └─────────────┼─────────────┘
                              ▼
                 CONSOLIDATED PICKUP
                       PLANNING
                              │
                ┌─────────────┼─────────────┐
                │             │             │
            Capacity      Geography      Locations
                │             │             │
                └─────────────┼─────────────┘
                              ▼
                    ROUTE OPTIMIZATION
                              │
                ┌─────────────┼─────────────┐
                │             │             │
             OSRM        Nearest Neighbor   2-opt
                │             │             │
                └─────────────┼─────────────┘
                              ▼
                   TIME-WINDOW VALIDATION
                              │
                              ▼
                     PROPOSED PICKUP
                          ROUTE
                              │
                              ▼
                       COOP ADMIN
                          REVIEW
                              │
                    ┌─────────┴─────────┐
                    │                   │
                  Accept             Modify
                    │                   │
                    └─────────┬─────────┘
                              ▼
                       PICKUP TRIP
                              │
                              ▼
                     CONSOLIDATED FARM
                          PICKUP
                              │
                              ▼
                     RECEIVING / GRADING
                              │
                              ▼
                        PROCUREMENT
                              │
                              ▼
                      CROP AVAILABILITY
                              │
                              ▼
                            BUYER
                              │
                              ▼
                            ORDER
                              │
                              ▼
                      COOP PROCESSING
                              │
                              ▼
                       BUYER DELIVERY
                              │
                              ▼
                            BUYER
```

This is the **current working baseline**. Future discussions can modify individual sections without assuming that the entire system is already finalized.

In depth dive into each user roles

# 1\. Super Admin

The Super Admin is the **platform-level administrator**. Think of them as the person responsible for deciding **who is allowed to operate on the platform**, rather than someone who manages the day-to-day crop business of a cooperative.

They should **not** be managing farmer harvests, buyer orders, or deliveries. Those belong to the Cooperative Admin.

## A. Verify Cooperative Registrations

### What does this mean?

A cooperative wants to use your system.

They go to:

**Register Cooperative**

They provide information such as:

Ddesign the registration process in **three sections**.

## 1\. Cooperative Information

The cooperative provides basic organizational information.

### Required fields

**Cooperative Name**

Example: Davao Farmers Agricultural Cooperative

**Cooperative Type**

Primary / Secondary / Other

**Cooperative Address**

**Province / City / Municipality / Barangay**

**Contact Number**

**Official Email**

**Year Established**

**Business Activities**

For your system, this is particularly important:

Does the cooperative engage in agricultural/crop procurement and distribution?

This helps establish that the cooperative actually fits the purpose of your platform.

# 2\. Legal / Registration Information

This is the important part.

I'd ask for:

### CDA Registration Number

For example:

CDA Registration No.: \_**\_**\____

# 3\. Authorized Representative

This is extremely important.

You don't want somebody randomly creating:

**"Davao Super Farmers Cooperative"**

and then becoming its administrator.

The registration should identify the person who is authorized to create the cooperative's platform account.

### Information

**Authorized Representative**

- Full name
- Position/designation
- Contact number
- Email
- Government-issued ID
- ID number, if appropriate for your project's requirements
- Proof of authorization

### Authorization document

For example:

**Board Resolution / Authorization Letter**

Basically:

"This person is authorized by the cooperative to register and administer its account on the platform."

This gives your Super Admin something to verify beyond just an email address.

# 4\. What Does the Super Admin Actually Verify?

This is where I would make the UI interesting.

The Super Admin gets:

### Cooperative Application

**Davao Farmers Agricultural Cooperative**

**Status: Pending Verification**

### Organization Information

| **Field**         | **Submitted Information**                     |
| ----------------- | --------------------------------------------- |
| Cooperative Name  | Davao Farmers Agricultural Cooperative        |
| Type              | Primary                                       |
| Address           | Davao City                                    |
| Contact           | 09XXXXXXXXX                                   |
| Email             | [admin@example.com](mailto:admin@example.com) |
| Business Activity | Agricultural Crop Distribution                |

### Registration Information

| **Field**            | **Information** |
| -------------------- | --------------- |
| CDA Registration No. | XXXXX           |
| Registration Date    | XX/XX/XXXX      |
| Certificate          | 📄 View         |
| Articles             | 📄 View         |
| By-Laws              | 📄 View         |

### Authorized Representative

**Juan Dela Cruz**  
Cooperative Manager  
📄 Government ID  
📄 Authorization / Board Resolution

Then the Super Admin has:

**\[Approve Cooperative\]**

**\[Reject Application\]**

**\[Request Additional Information\]**

# 5\. Don't Make Verification Just a Boolean

I would **not** design the database as:

```
is_verified = true/false
```

That's too simplistic.

Instead:

```
Pending
   ↓
Under Review
   ↓
 ┌───────────────┐
 │               │
 ▼               ▼
Approved       Rejected
 │
 ▼
Active
```

And potentially:

```
Active
  ↓
Suspended
  ↓
Reactivated
```

This gives us an actual lifecycle.

# What Happens After Approval?

This is the important connection to our other user roles.

Once approved:

```
COOPERATIVE APPLICATION
        │
        ▼
     APPROVED
        │
        ▼
COOPERATIVE ACCOUNT ACTIVATED
        │
        ▼
COOPERATIVE ADMIN
        │
        ├── Manage Farmers
        ├── Manage Delivery Personnel
        ├── Manage Harvests
        ├── Manage Crop Availability
        ├── Process Buyer Orders
        └── Manage Deliveries
```

The **Cooperative Admin does not exist operationally until the cooperative has been approved**, unless we deliberately allow a limited pre-approval account.

# 7\. One Important Question: How Strict Should We Be?

There are actually two possible designs.

### Basic verification

Require:

- CDA registration number
- Certificate of Registration
- Cooperative information
- Authorized representative
- Authorization document

Then the Super Admin manually reviews the application.

**Pros:** Much simpler and appropriate for a student system  
**Cons:** Less comprehensive verification

After submitting, the cooperative is **not immediately activated**.

Its status becomes:

**Pending Verification**

The Super Admin sees something like:

### Cooperative Applications

| **Cooperative**         | **Representative** | **Date Submitted** | **Status** | **Action** |
| ----------------------- | ------------------ | ------------------ | ---------- | ---------- |
| ABC Farmers Cooperative | Juan Dela Cruz     | Sept. 18           | Pending    | Review     |
| XYZ Agricultural Coop   | Maria Santos       | Sept. 17           | Pending    | Review     |

The Super Admin clicks **Review**.

They can inspect the submitted information and documents.

Then they can:

**Approve**

or

**Reject**

Possibly:

**Request Changes**

if you want a more sophisticated workflow.

### System-wise

This means we need something along the lines of:

```
cooperatives
- id
- name
- address
- contact_information
- registration_details
- representative
- status
- created_at
- updated_at
```

with statuses such as:

```
pending
approved
rejected
suspended
```

And the system should prevent an unapproved cooperative from using its operational features.

So this isn't just a button saying "verified."

It creates an actual **access-control boundary**.

# B. Manage Cooperative Accounts

This one needs to be narrower than it initially sounded.

I **would not** make the Super Admin responsible for managing every farmer, buyer, order, and delivery inside every cooperative.

Instead, "manage cooperative accounts" means managing the **cooperatives registered on the platform**.

For example:

### Cooperative Management

```
Cooperatives
ABC Farmers Cooperative
Status: Approved
Admin: Juan Dela Cruz
Registered: Sept. 10, 2026
[View] [Suspend]
```

The Super Admin can:

- View cooperative information
- Approve a cooperative
- Reject a cooperative
- Suspend a cooperative
- Reactivate a cooperative
- Update certain platform-level cooperative information
- View the cooperative's status
- View its assigned Cooperative Admin

### Why suspension matters

Suppose a cooperative is approved today but later should no longer have access.

The Super Admin can change:

```
Approved
    ↓
Suspended
```

The cooperative's users can no longer access operational functions.

This is different from **deleting the cooperative**.

I'd actually avoid allowing the Super Admin to casually delete cooperatives because doing so could destroy historical transactions.

Instead, use statuses.

# C. Manage Platform-Level User Accounts and Roles

This is another one I'd refine.

The Super Admin should primarily manage **platform users**, especially Cooperative Admin accounts.

For example:

```
Users
Juan Dela Cruz
Role: Cooperative Admin
Cooperative: ABC Farmers Cooperative
Status: Active
Maria Santos
Role: Cooperative Admin
Cooperative: XYZ Agricultural Coop
Status: Active
```

The Super Admin could:

- View users
- Activate/deactivate accounts
- Reset accounts/access where appropriate
- Assign or change roles where authorized
- Associate a Cooperative Admin with a cooperative
- Disable an account

However, I would **not** necessarily give the Super Admin a giant interface containing every farmer and delivery person from every cooperative.

That's unnecessary scope.

The system hierarchy should be:

```
SUPER ADMIN
     │
     ├── Cooperatives
     │      ├── Cooperative A
     │      ├── Cooperative B
     │      └── Cooperative C
     │
     └── Platform Users
            ├── Cooperative Admin A
            ├── Cooperative Admin B
            └── ...
```

Then the Cooperative Admin manages the people **inside their cooperative**.

**Super Admin manages the platform and cooperatives. Cooperative Admin manages the people and operations within their cooperative.**

That gives us a clear hierarchy.

# 1\. Who Manages Whom?

| **Account**            | **Created/Registered By**              | **Verified/Approved By** | **Managed By**            |
| ---------------------- | -------------------------------------- | ------------------------ | ------------------------- |
| **Super Admin**        | System/developer                       | —                        | —                         |
| **Cooperative**        | Cooperative representative             | **Super Admin**          | **Super Admin**           |
| **Cooperative Admin**  | Cooperative registration / Super Admin | **Super Admin**          | **Super Admin**           |
| **Farmer**             | Cooperative Admin                      | **Cooperative Admin**    | **Cooperative Admin**     |
| **Delivery Personnel** | Cooperative Admin                      | **Cooperative Admin**    | **Cooperative Admin**     |
| **Buyer**              | Buyer                                  | Depends on design        | Cooperative / Super Admin |

That means **no**, the Super Admin doesn't need to manage every farmer.

# 2\. Cooperative Admin

This is where farmer management belongs.

Suppose:

ABC Farmers Cooperative

has:

- 150 farmers
- 5 delivery personnel
- 2 cooperative administrators/staff

The Super Admin shouldn't have to manually manage those 157 people.

Instead:

```
SUPER ADMIN
     │
     │ approves
     ▼
ABC COOPERATIVE
     │
     ▼
COOPERATIVE ADMIN
     │
     ├── Farmers
     │
     ├── Delivery Personnel
     │
     └── Cooperative Staff
```

The Cooperative Admin is responsible for its own members/personnel.

# 3\. How Would Farmer Registration Work?

I would **not** let a farmer simply register themselves and immediately become affiliated with a cooperative.

Instead, there are two reasonable approaches.

### Recommended: Cooperative Creates Farmer Account

The Cooperative Admin goes to:

**Farmers → Add Farmer**

They enter:

- Farmer name
- Contact information
- Address
- Farm information
- Crops commonly produced
- Membership information
- Other relevant farmer details

Then the farmer receives an account invitation or credentials.

The relationship is immediately:

```
Farmer
   │
   └── Cooperative ID: ABC Cooperative
```

This is very clean.

# 4\. What About Delivery Personnel?

Same principle.

The Cooperative Admin creates:

**Delivery Personnel Account**

For example:

**Pedro Santos**

Role:

Delivery Personnel

Cooperative:

ABC Farmers Cooperative

The Super Admin doesn't need to approve Pedro.

The Super Admin already approved **ABC Cooperative**.

The Cooperative Admin is responsible for deciding who works for that cooperative.

# 5\. What About Buyers?

This is the one role where we have a design decision.

A buyer is **not affiliated with a cooperative**.

For example:

```
ABC Cooperative
       │
       ├──── Buyer A
       ├──── Buyer B
       └──── Buyer C
XYZ Cooperative
       │
       ├──── Buyer B
       ├──── Buyer D
       └──── Buyer E
```

A buyer might purchase from multiple cooperatives.

Therefore, we **shouldn't put cooperative_id directly on the buyer's account** as though they belong to one cooperative.

Instead:

```
users
  │
  └── buyer
orders
  ├── buyer_id
  └── cooperative_id
```

So an order tells us:

Buyer A bought from ABC Cooperative.

Later, the same buyer could order from XYZ Cooperative.

# 6\. Who Verifies Buyers?

This is where I'd make another distinction.

If the platform is supposed to be a **verified B2B marketplace**, we probably want some form of buyer verification.

### Super Admin verifies buyers

```
Buyer registers
      ↓
Pending
      ↓
Super Admin reviews
      ↓
Approved
```

The Super Admin could request:

- Business name
- Business registration information
- Contact person
- Business address
- Supporting business documents

This gives the platform control over who is allowed to become a buyer.

# 7\. So What Does "Manage Platform-Level User Accounts and Roles" Become?

Honestly, after going through this, **I'd change that bullet**.

It is too broad and potentially misleading.

Instead, for the Super Admin:

**Manage cooperative accounts and Cooperative Admin access**

This means:

- View Cooperative Admin accounts
- Activate/deactivate Cooperative Admin accounts
- Assign a Cooperative Admin to an approved cooperative
- Reset/deactivate access when necessary
- Manage the cooperative's platform status

The Super Admin **does not manage farmers or delivery personnel**.

# 8\. Revised Super Admin Role

I'd now document it as:

### Super Admin

**Platform-level user responsible for cooperative verification, platform access, and system-wide administration.**

**Can:**

1. Review and verify cooperative registrations
2. Approve, reject, suspend, or reactivate cooperatives
3. Manage Cooperative Admin accounts and access
4. Monitor registered cooperatives
5. Manage system-wide reference data
6. View platform-level summary reports

And:

### Cooperative Admin

**Cooperative-level user responsible for managing the cooperative's members, procurement, distribution, and logistics operations.**

**Can:**

1. Manage farmers affiliated with the cooperative
2. Verify/approve farmer accounts or membership
3. Manage delivery personnel
4. Record farmer harvests
5. Record cooperative purchases
6. Manage crop availability
7. Process buyer orders
8. Manage deliveries
9. Monitor delivery locations
10. Generate cooperative reports

## The hierarchy is now very clean

```
                    PLATFORM
                       │
                 ┌─────▼─────┐
                 │ SUPER ADMIN│
                 └─────┬─────┘
                       │
             verifies/manages
                       │
        ┌──────────────┼──────────────┐
        ▼              ▼              ▼
     COOP A          COOP B          COOP C
        │              │              │
    COOP ADMIN     COOP ADMIN     COOP ADMIN
        │              │              │
   ┌────┼────┐    ┌────┼────┐    ┌────┼────┐
   ▼    ▼    ▼    ▼    ▼    ▼    ▼    ▼    ▼
 Farmer Driver   Farmer Driver   Farmer Driver
        │              │              │
        └──────────────┼──────────────┘
                       │
                     BUYERS
```

The key concept is **ownership of responsibility**:

- **Super Admin → Cooperatives**
- **Cooperative Admin → Farmers + Delivery Personnel + Cooperative operations**
- **Buyer → their own business account and orders**
- **Farmer → their own harvests**
- **Delivery Personnel → their assigned deliveries**

That division is what I'd carry forward into our running document, because it will directly determine our **Laravel roles, policies, cooperative_id relationships, routes, and database structure** later.

If the actual business process is:

**Any verified B2B buyer can purchase available crops from any cooperative**

then simply do:

```
Buyer registers
      ↓
Buyer verified
      ↓
Browse cooperatives/crops
      ↓
Place order
```

The cooperative can still **accept or reject individual orders**.

That's much simpler:

```
Buyer
  ↓
Order
  ↓
Cooperative
  ↓
Accept / Reject
```

And I think this is worth distinguishing in our running design:

**Buyer account verification** and **order acceptance** are separate concepts.

The buyer doesn't necessarily need to be pre-approved by each cooperative just to place an order. The cooperative can make its decision **at the order level**.

# D. Monitor Registered Cooperatives

This sounds similar to the previous one, so I'd actually combine or clarify it.

Instead of having:

Manage cooperative accounts

and

Monitor registered cooperatives

I'd define them as two different things:

### Manage

Changes the cooperative's platform status/information.

### Monitor

Provides an overview.

For example:

### Super Admin Dashboard

```
Cooperatives
────────────────────
Total:             12
Pending:            3
Approved:           8
Suspended:          1
Recent Applications
────────────────────
ABC Cooperative      Pending
DEF Cooperative      Pending
GHI Cooperative      Approved
```

The Super Admin can see:

- Number of registered cooperatives
- Pending applications
- Approved cooperatives
- Suspended cooperatives
- Recently registered cooperatives

This is more of a **dashboard/overview function**.

# E. Manage System-Wide Settings and Reference Data

This one is potentially dangerous because **"system settings" can become a huge scope**.

I'd make it very specific.

The Super Admin could manage reference data such as:

### Crop Categories

```
Vegetables
Fruits
Root Crops
Grains
Other
```

### Crop Types

```
Banana
Mango
Tomato
Cabbage
...
```

### Quality/Classifications

```
Grade A
Grade B
Grade C
```

Potentially other system-wide values that should be consistent across cooperatives.

The key idea is:

**Reference data used by multiple cooperatives should be controlled at the platform level.**

Meanwhile, cooperative-specific information remains under the Cooperative Admin.

The Super Admin should only manage the **available classification options**, while the cooperative applies those classifications to actual crops.

Think of it like this:

**Super Admin defines the vocabulary. Cooperative uses the vocabulary.**

If the farmer harvests first and then **requests a haul/pickup from the cooperative**, the farmer should not be entering the final quality grade. The **cooperative evaluates and grades the actual harvest during pickup/receiving**.

That gives us a more realistic workflow.

# Revised Farmer → Cooperative Flow

```
FARMER
  │
  │ 1. Harvests crops
  ▼
HARVEST
  │
  │ 2. Requests haul
  ▼
HAUL REQUEST
  │
  │ 3. Coop schedules pickup
  ▼
PICKUP / HAUL
  │
  │ 4. Coop evaluates harvest
  ▼
QUALITY GRADING
  │
  │ 5. Coop records actual quantity
  ▼
COOPERATIVE RECEIVES HARVEST
  │
  │ 6. Coop purchases harvest
  ▼
CROP BECOMES AVAILABLE
  │
  ▼
BUYER
```

# 1\. Farmer Harvests

The farmer doesn't need to create a complicated inventory record.

They basically tell the cooperative:

**"I have harvested crops and I need the cooperative to pick them up."**

For example:

### Request Haul

**Farmer:** Juan Dela Cruz

**Crop:** Banana

**Estimated Quantity:** 500 kg

**Harvest Date:** September 19, 2026

**Preferred Pickup Date:** September 20, 2026

**Pickup Location:** Farmer's Farm

**Notes:** Ready for pickup

Then:

**\[Submit Haul Request\]**

# 2\. The Request Becomes Pending

The cooperative sees:

### Haul Requests

| **Farmer**     | **Crop** | **Estimated Qty.** | **Requested Pickup** | **Status** |
| -------------- | -------- | ------------------ | -------------------- | ---------- |
| Juan Dela Cruz | Banana   | 500 kg             | Sept. 20             | Pending    |
| Pedro Santos   | Mango    | 300 kg             | Sept. 20             | Pending    |

The Cooperative Admin can then:

**Accept / Schedule Pickup**

or potentially:

**Reject**

or **Request Changes**.

# 3\. Cooperative Schedules the Pickup

The cooperative determines how to collect the harvest.

For example:

**Haul #HAUL-00021**

```
Farmer:
Juan Dela Cruz
Pickup:
Farm Location
Crop:
Banana
Estimated Quantity:
500 kg
Pickup Date:
September 20, 2026
Assigned Driver:
Pedro Reyes
Vehicle:
Truck-003
```

Now the Delivery Personnel sees:

**Assigned Pickup**

This is important because **pickup is not yet a delivery to a buyer**.

You actually have two logistics stages:

### Stage 1 — Farmer → Cooperative

**Harvest pickup / hauling**

### Stage 2 — Cooperative → Buyer

**Customer delivery**

That's something we should explicitly represent in the system.

# 4\. On Pickup Day: The Actual Grading Happens

This is where your earlier question about Grade A/B/C fits.

The driver/cooperative representative arrives at the farm.

The farmer says:

"This is the 500 kg of banana I requested to haul."

The cooperative evaluates it.

Maybe the estimated quantity was:

**500 kg**

But after weighing:

**462 kg**

And after inspection:

**Grade B**

The cooperative records the **actual received harvest**.

### Receiving / Grading

```
Farmer
Juan Dela Cruz
Crop
Banana
Estimated Quantity
500 kg
Actual Quantity
462 kg
Quality Classification
Grade B
Purchase Price
₱42.00 / kg
Date Received
September 20, 2026
Remarks
Acceptable quality; minor defects
```

Then:

**\[Confirm Receipt\]**

# 5\. This Creates the Actual Purchase

Now the system records:

Cooperative purchased **462 kg of Grade B Banana** from Juan.

For example:

```
462 kg × ₱42/kg
= ₱19,404
```

The important thing is that **this is the actual cooperative procurement transaction**.

The original farmer request was only an **estimate/request**.

# 1\. Farmer Harvests

The farmer doesn't need to create a complicated inventory record.

They basically tell the cooperative:

**"I have harvested crops and I need the cooperative to pick them up."**

For example:

### Request Haul

**Farmer:** Juan Dela Cruz

**Crop:** Banana

**Estimated Quantity:** 500 kg

**Harvest Date:** September 19, 2026

**Preferred Pickup Date:** September 20, 2026

**Pickup Location:** Farmer's Farm

**Notes:** Ready for pickup

Then:

**\[Submit Haul Request\]**

# 2\. The Request Becomes Pending

The cooperative sees:

### Haul Requests

| **Farmer**     | **Crop** | **Estimated Qty.** | **Requested Pickup** | **Status** |
| -------------- | -------- | ------------------ | -------------------- | ---------- |
| Juan Dela Cruz | Banana   | 500 kg             | Sept. 20             | Pending    |
| Pedro Santos   | Mango    | 300 kg             | Sept. 20             | Pending    |

The Cooperative Admin can then:

**Accept / Schedule Pickup**

or potentially:

**Reject**

or **Request Changes**.

# 3\. Cooperative Schedules the Pickup

The cooperative determines how to collect the harvest.

For example:

**Haul #HAUL-00021**

```
Farmer:
Juan Dela Cruz
Pickup:
Farm Location
Crop:
Banana
Estimated Quantity:
500 kg
Pickup Date:
September 20, 2026
Assigned Driver:
Pedro Reyes
Vehicle:
Truck-003
```

Now the Delivery Personnel sees:

**Assigned Pickup**

This is important because **pickup is not yet a delivery to a buyer**.

You actually have two logistics stages:

### Stage 1 — Farmer → Cooperative

**Harvest pickup / hauling**

### Stage 2 — Cooperative → Buyer

**Customer delivery**

That's something we should explicitly represent in the system.

# 4\. On Pickup Day: The Actual Grading Happens

This is where your earlier question about Grade A/B/C fits.

The driver/cooperative representative arrives at the farm.

The farmer says:

"This is the 500 kg of banana I requested to haul."

The cooperative evaluates it.

Maybe the estimated quantity was:

**500 kg**

But after weighing:

**462 kg**

And after inspection:

**Grade B**

The cooperative records the **actual received harvest**.

### Receiving / Grading

```
Farmer
Juan Dela Cruz
Crop
Banana
Estimated Quantity
500 kg
Actual Quantity
462 kg
Quality Classification
Grade B
Purchase Price
₱42.00 / kg
Date Received
September 20, 2026
Remarks
Acceptable quality; minor defects
```

Then:

**\[Confirm Receipt\]**

# 5\. This Creates the Actual Purchase

Now the system records:

Cooperative purchased **462 kg of Grade B Banana** from Juan.

For example:

```
462 kg × ₱42/kg
= ₱19,404
```

The important thing is that **this is the actual cooperative procurement transaction**.

The original farmer request was only an **estimate/request**.

yea but in requesting the haul. does the farmer already post the price of the crop? or they negotiate in person?

It depends on how the cooperative actually operates, but **based on the flow you've described, I would not have the farmer post the price in the haul request**.

The haul request should primarily mean:

**"I have this harvest ready. Please arrange pickup."**

The **price can be determined by the cooperative during or after receiving/grading**, especially if the final price depends on actual weight and quality.

**I would model it like this**

**1\. Farmer requests haul**

80 sacks of corn  
Estimated 4,000 kg  
Pickup requested: Sept. 20

No price yet.

**2\. Cooperative schedules pickup**

Driver + vehicle + pickup date

**3\. Cooperative receives and evaluates**

80 sacks  
Actual weight: 3,920 kg  
Grade: A

**4\. Cooperative determines/records buying price**

For example:

Grade A Corn → ₱18/kg

**5\. System calculates purchase**

3,920 kg × ₱18 = ₱70,560

**What if the farmer and cooperative negotiate?**

That's also possible.

In that case, the system shouldn't pretend the price was automatically determined.

You could have:

**Buying Price**

₱18.00/kg

**Price Determination**

Cooperative Set / Negotiated

And perhaps a notes field:

"Price agreed during pickup."

But I would **not build a full negotiation/chat/bidding system** unless that's an actual requirement. That's a lot of additional scope.

**There is another possibility**

The cooperative could have a **published buying price** beforehand.

For example:

**Corn — Grade A — ₱18/kg**

But the farmer still doesn't necessarily know their final grade until the cooperative inspects the harvest.

So the farmer's haul request could show:

**Estimated crop:** Corn  
**Estimated quantity:** 80 sacks  
**Estimated weight:** 4,000 kg

Then the cooperative determines:

**Actual:** 3,920 kg  
**Grade:** A  
**Buying price:** ₱18/kg

**So I'd keep the rule simple for now**

**The haul request does not contain a required crop price. The buying price is determined by the cooperative during the harvest receiving/procurement process based on the actual quantity, quality classification, and applicable pricing arrangement.**

This gives us flexibility if the real cooperative later tells us:

- "We have a fixed daily buying price."
- "Price depends on grade."
- "We negotiate with farmers."
- "Price comes from our management."
- "We use the DA market price as a reference."

We can accommodate those without redesigning the farmer's haul-request workflow.

**If nobody from the cooperative is physically present at pickup, then the system has no reliable way to know the actual weight, grade, or negotiated buying price.**

So your business process needs a person who represents the cooperative **at the farm during pickup**.

And I think this reveals that we should **not overload the term "Cooperative Admin."**

**A realistic setup would be:**

**Cooperative Admin**

Manages the cooperative's system and operations from the office.

**Cooperative Field/Receiving Personnel**

Goes with the truck to the farm, observes the weighing, evaluates the harvest, negotiates/records the buying price, and records the receiving details.

**Delivery Personnel**

Drives/operates the truck and handles the physical hauling.

So the pickup becomes:

```
                 FARMER
                    │
              80 sacks corn
                    │
                    ▼
        ┌──────────────────────┐
        │ COOP FIELD PERSONNEL │
        │       + DRIVER       │
        └──────────┬───────────┘
                   │
             At the farm
                   │
       ┌───────────┼───────────┐
       ▼           ▼           ▼
    Weighing     Grading    Price
    3,920 kg     Grade A    ₱18/kg
       │           │           │
       └───────────┼───────────┘
                   ▼
          Receiving Record
                   │
                   ▼
            COOPERATIVE
                   │
             Admin reviews
                   │
                   ▼
          Procurement confirmed
```

**The field person would enter:**

- Actual number of sacks
- Actual weight from the scale
- Quality grade
- Agreed buying price/kg
- Total calculated purchase amount
- Pickup/receiving confirmation
- Remarks, if necessary

The system can automatically calculate:

**3,920 kg × ₱18/kg = ₱70,560**

Then the **Cooperative Admin reviews and confirms** the transaction.

**So I would add a role**

I think we were initially trying too hard to keep the system to only five roles.

A more realistic set is:

1. **Super Admin**
2. **Cooperative Admin**
3. **Cooperative Field/Receiving Personnel**
4. **Farmer**
5. **Delivery Personnel**
6. **Buyer**

# F. View Platform-Level Reports

This should also be carefully scoped.

The Super Admin doesn't necessarily need to see every operational detail.

Instead, they could have **platform-level summaries**, such as:

```
Cooperatives: 12
Active Buyers: 145
Registered Farmers: 682
Completed Orders: 1,240
Completed Deliveries: 1,102
```

Potentially:

- Number of cooperatives
- Number of farmers
- Number of buyers
- Number of orders
- Number of completed deliveries
- Overall transaction volume
- Cooperative registration statistics

Whether the Super Admin can see **financial/transaction details** across cooperatives is a business/privacy decision we can make later.

I would initially keep it to **aggregate platform-level reporting**.

# So What Does the Super Admin Actually See?

Their dashboard could be very simple:

```
┌─────────────────────────────────────────────────┐
│ SUPER ADMIN DASHBOARD                            │
├─────────────────────────────────────────────────┤
│                                                 │
│  Cooperatives    Pending      Buyers    Farmers │
│      12             3           145       682   │
│                                                 │
├─────────────────────────────────────────────────┤
│ Pending Cooperative Applications                │
│                                                 │
│ ABC Farmers Cooperative              [Review]   │
│ XYZ Agricultural Cooperative          [Review]   │
│                                                 │
├─────────────────────────────────────────────────┤
│ Recent Cooperatives                             │
│                                                 │
│ DEF Cooperative      Approved        [View]      │
│ GHI Cooperative      Approved        [View]      │
│                                                 │
└─────────────────────────────────────────────────┘
```

Notice what's **not** there:

❌ Farmer harvest management  
❌ Buyer orders  
❌ Delivery assignment  
❌ Driver GPS map  
❌ Crop purchasing

Those are operational activities of the **Cooperative Admin**.

# The Most Important Boundary

I'd define the Super Admin's job as:

**"Who is allowed to use the platform?"**

while the Cooperative Admin's job is:

**"What happens inside my cooperative?"**

And the other users operate within that cooperative:

```
                 PLATFORM
                    │
               SUPER ADMIN
                    │
        verifies / manages access
                    │
        ┌───────────┴───────────┐
        ▼                       ▼
   COOPERATIVE A           COOPERATIVE B
        │                       │
   COOP ADMIN               COOP ADMIN
        │                       │
   ┌────┼────┐             ┌────┼────┐
   ▼    ▼    ▼             ▼    ▼    ▼
Farmers Drivers            Farmers Drivers
        │                       │
        └─────────┐   ┌─────────┘
                  ▼   ▼
                 BUYERS
```

This separation will make the eventual **Laravel authorization policies and database relationships much cleaner**.

Based on the workflow we've developed, I would currently treat these as the **final core user roles**:

| **Role**                                      | **Main responsibility**                                                           |
| --------------------------------------------- | --------------------------------------------------------------------------------- |
| **1\. Super Admin**                           | Manages the platform and verifies/controls cooperative accounts                   |
| **2\. Cooperative Admin**                     | Manages the cooperative, its farmers, operations, transactions, and staff         |
| **3\. Cooperative Field/Receiving Personnel** | Goes to the farm during pickup and records actual weight, grade, and buying price |
| **4\. Delivery Personnel**                    | Handles physical hauling and deliveries and updates transport status/location     |
| **5\. Farmer**                                | Registers harvests/requests hauling and views their procurement transactions      |
| **6\. Buyer**                                 | Finds available crops, places B2B orders, and tracks deliveries                   |

### The hierarchy would be

```
                    SUPER ADMIN
                         │
              verifies/manages
                         │
                  COOPERATIVE
                         │
                COOPERATIVE ADMIN
                         │
          ┌──────────────┼──────────────┐
          │              │              │
       FARMERS       FIELD/RECEIVING   DELIVERY
                       PERSONNEL       PERSONNEL
          │              │              │
          └──────────────┴──────────────┘
                         │
                    COOPERATIVE
                         │
                         ▼
                       BUYERS
```

And the most important distinction is:

**Cooperative Admin** = manages the cooperative's **system and business operations**.

**Field/Receiving Personnel** = physically represents the cooperative during **farm pickup and receiving**.

**Delivery Personnel** = physically handles **transport**.

They can be the same real-world person in a small cooperative, but the system roles/permissions should remain conceptually separate. That way, if one person eventually does both jobs, we can assign both permissions without redesigning the system.

and what if we make the system PWA? because for sure delivery personnel wont be bringing laptops while in transit

Yes. **Making it a PWA makes a lot of sense for this system**, especially for the Delivery Personnel and Field/Receiving Personnel.

You don't need to turn the whole project into a mobile app. You can keep the same **Laravel + Blade + Tailwind + Alpine** application and make the web application installable as a **Progressive Web App**.

### The architecture would become

```
                    WEB-BASED SYSTEM
                           │
              ┌────────────┴────────────┐
              │                         │
          DESKTOP/LAPTOP              PHONE
              │                         │
       Coop Admin / Super Admin     Field / Delivery
              │                         │
              └────────────┬────────────┘
                           │
                    Laravel Backend
                           │
                         MySQL
```

So:

- **Super Admin** → laptop/desktop
- **Cooperative Admin** → laptop/desktop, possibly phone
- **Field/Receiving Personnel** → phone/PWA
- **Delivery Personnel** → phone/PWA
- **Farmer** → phone/PWA or browser
- **Buyer** → desktop/browser or phone

No separate Android/iOS application is required.

## What the Delivery Personnel would actually see

Instead of bringing a laptop, they use their phone.

They open the installed PWA:

**My Assigned Hauls**

```
┌──────────────────────────┐
│  COOP HAULING             │
│                          │
│  Today's Assignment       │
│                          │
│  HAUL #00021              │
│  Juan Dela Cruz           │
│                          │
│  🌽 Corn                 │
│  80 sacks                │
│                          │
│  Pickup: 8:30 AM         │
│                          │
│  [ View Pickup ]          │
│                          │
└──────────────────────────┘
```

They tap **View Pickup**.

```
┌──────────────────────────┐
│  Pickup Details            │
│                          │
│  Juan Dela Cruz           │
│  80 sacks Corn             │
│  Est. 4,000 kg            │
│                          │
│  📍 Farm Location         │
│                          │
│  [ Navigate ]              │
│                          │
│  [ Start Pickup ]          │
└──────────────────────────┘
```

Then:

**Start Pickup**

The phone periodically sends its location to Laravel through HTTP.

# The Field/Receiving Personnel is where PWA becomes even more useful

If this person physically goes with the truck, they can use their phone at the farm.

For example:

```
┌──────────────────────────┐
│  HARVEST RECEIVING        │
│                          │
│  Farmer                   │
│  Juan Dela Cruz           │
│                          │
│  Crop                     │
│  Corn                     │
│                          │
│  Packages                 │
│  [ 80 ] sacks             │
│                          │
│  Actual Weight             │
│  [ 3920 ] kg              │
│                          │
│  Quality                  │
│  [ Grade A ▼ ]             │
│                          │
│  Buying Price             │
│  [ ₱18.00 ] / kg          │
│                          │
│  Total                     │
│  ₱70,560.00               │
│                          │
│  [ Confirm Receiving ]    │
└──────────────────────────┘
```

That's much more practical than expecting someone to bring a laptop to the farm.

**Plan for the Route Optimization**

Treat the **Pickup Scheduling & Route Optimization** module as one of the major subsystems of the platform—not merely a map page.

The goal is to solve this real cooperative problem:

**Hundreds of affiliated farmers may request harvest pickup on different dates and time periods, while the cooperative has a limited number of trucks. The system should help the cooperative consolidate compatible farmer pickups into shared hauling trips, respect truck capacity and pickup availability, determine an efficient pickup sequence, and allow the cooperative to review and approve the resulting plan.**

Below is the full design I would use.

# 1\. What the module is actually solving

There are **four connected problems**:

### Problem A — Scheduling

When should this farmer's harvest be picked up?

Because farmers can request:

- 3 days ahead
- 7 days ahead
- a specific date
- a preferred time period

### Problem B — Resource allocation

Which available truck should handle the pickup?

Because the cooperative may have:

- 3 trucks
- 5 trucks
- 10 trucks

but potentially **hundreds of affiliated farmers**.

### Problem C — Load consolidation

Which farmers can share the same truck?

For example:

```
Truck capacity = 4,000 kg
Farmer A = 1,500 kg
Farmer B =   800 kg
Farmer C = 1,200 kg
Total = 3,500 kg
```

These can potentially be consolidated.

### Problem D — Route optimization

Once the farmers have been selected:

In what order should the truck visit them?

For example:

```
Cooperative
     ↓
Farmer A
     ↓
Farmer C
     ↓
Farmer B
     ↓
Cooperative
```

The system tries to make that route efficient.

# 2\. The overall system flow

The complete process becomes:

```
                    FARMER
                       │
                       │ Harvest Pickup Request
                       ▼
              ┌─────────────────┐
              │ Laravel / MySQL │
              └────────┬────────┘
                       │
                       ▼
              PENDING PICKUPS
                       │
                       ▼
              PICKUP SCHEDULING
                       │
        ┌──────────────┼───────────────┐
        │              │               │
        ▼              ▼               ▼
   Pickup Date     Time Window     Truck Availability
        │              │               │
        └──────────────┼───────────────┘
                       ▼
              CANDIDATE PICKUPS
                       │
                       ▼
             LOAD CONSOLIDATION
                 (Knapsack)
                       │
                       ▼
              ROUTE GENERATION
                       │
                       ▼
                    OSRM
             Road distance/time
                       │
                       ▼
             ROUTE IMPROVEMENT
                  (2-opt)
                       │
                       ▼
               FEASIBILITY CHECK
                       │
                       ▼
                COOP ADMIN
                  REVIEWS
                       │
             ┌─────────┴─────────┐
             ▼                   ▼
          MODIFY               APPROVE
             │                   │
             └─────────┬─────────┘
                       ▼
                 PICKUP TRIP
                       │
                       ▼
              DELIVERY PERSONNEL
                       │
                       ▼
             ACTUAL PICKUP/WEIGHING
                       │
                       ▼
                HTTP LOCATION
                   POLLING
                       │
                       ▼
                TRIP MONITORING
```

# 3\. Step 1 — Farmer submits a pickup request

The farmer doesn't schedule a truck directly.

They submit a **Harvest Pickup Request** to their cooperative.

For example:

### Harvest Pickup Request

**Crop**

Corn

**Estimated quantity**

80 sacks

**Estimated weight**

1,500 kg

**Harvest ready date**

September 25

**Preferred pickup window**

7:00 AM – 10:00 AM

**Pickup location**

Registered farm location

**Notes**

Harvest will be ready by 7:00 AM.

The request enters:

**Pending Pickup Scheduling**

# 4\. Estimated weight is important

At this point, the weight is **not final**.

The farmer might say:

80 sacks ≈ 1,500 kg

But actual weighing happens during pickup.

So your database should distinguish:

```
estimated_weight
```

from:

```
actual_weight
```

The route planner works using:

**estimated weight**

and the actual weight is recorded later.

This is important because otherwise the system would incorrectly assume the farmer already knows the exact final weight.

# 5\. Step 2 — Pickup requests enter the scheduling pool

The Coop Admin sees something like:

```
PICKUP REQUESTS
September 25
────────────────────────────────────────
Farmer A
Corn
80 sacks
~1,500 kg
7:00–10:00 AM
Farmer B
Corn
40 sacks
~800 kg
8:00–11:00 AM
Farmer C
Vegetables
60 crates
~1,200 kg
10:00 AM–1:00 PM
Farmer D
Corn
50 sacks
~1,000 kg
7:00–9:00 AM
```

The system can also show:

```
17 pickup requests
3 available trucks
```

# 6\. Step 3 — The planning calendar

Because farmers can request pickups days in advance, the Coop Admin needs a calendar.

Something like:

```
              PICKUP PLANNING CALENDAR
 Sept 25       Sept 26       Sept 27       Sept 28
 ───────       ───────       ───────       ───────
 17 Requests   24 Requests   31 Requests   19 Requests
 3 Trucks      4 Trucks      5 Trucks      3 Trucks
 Available     Available     Available     Available
```

The Admin selects:

**September 25**

and enters the consolidated pickup planner.

# 7\. Step 4 — Determine available trucks

The system checks the fleet.

Example:

```
TRUCK AVAILABILITY — SEPTEMBER 25
Truck 01
Capacity: 4,000 kg
Status: Available
Truck 02
Capacity: 5,000 kg
Status: Available
Truck 03
Capacity: 4,000 kg
Status: Available
Truck 04
Capacity: 4,000 kg
Status: Scheduled
```

Now the planner knows:

```
Available vehicles:
Truck 01
Truck 02
Truck 03
```

# 8\. Step 5 — Identify candidate farmer groups

You don't want to run every farmer in the entire cooperative against every other farmer.

If there are 500 pending requests, that's unnecessary.

The system can first filter candidates using:

### Date

Farmer needs pickup on/around September 25.

### Time

Their pickup window must be potentially compatible.

### Geographic proximity

Farmers shouldn't be hundreds of kilometers apart.

### Vehicle requirements

If a crop requires a specific type of vehicle, only compatible trucks should be considered.

Then the system generates candidate groups.

Example:

```
Candidate Group A
Farmer A
1,500 kg
Farmer B
800 kg
Farmer C
1,200 kg
Total = 3,500 kg
```

# 9\. Step 6 — Knapsack handles load selection

This is where your Knapsack approach comes in.

For:

```
Truck capacity = 4,000 kg
```

the algorithm determines which pickup requests can be placed into that truck.

For example:

```
Farmer A = 1,500 kg
Farmer B =   800 kg
Farmer C = 1,200 kg
Farmer D = 1,000 kg
```

The combination:

```
A + B + C
```

equals:

```
3,500 kg
```

so it is feasible.

But:

```
A + B + C + D
```

equals:

```
4,500 kg
```

so it is not.

# 10\. But Knapsack alone isn't enough

This is an important design decision.

The algorithm shouldn't select farmers **only because their harvest fits the truck**.

Suppose:

```
Farmer A = 1,500 kg
```

is 40 km away,

while:

```
Farmer D = 1,000 kg
```

is 3 km away.

Pure Knapsack doesn't care.

So before/around the load selection stage, the system should consider:

- geographic proximity
- pickup date
- pickup time window
- estimated weight
- truck capacity
- request priority/urgency

This creates **feasible candidate groups**.

# 11\. Step 7 — Time-window feasibility

This is where the design becomes much more realistic.

Suppose:

```
Farmer A
7:00–9:00 AM
Farmer B
8:00–11:00 AM
Farmer C
10:00 AM–1:00 PM
```

The system needs to determine whether a truck can physically visit all three within those windows.

For example:

```
07:00
Depart Cooperative
07:30
Farmer A
08:20
Farmer B
10:10
Farmer C
11:00
Return Cooperative
```

This is feasible.

But if Farmer C's window were:

```
07:00–08:00
```

then the proposed route wouldn't work.

The system should flag:

**Time-window conflict**

rather than simply creating an impossible route.

# 12\. Hard vs. soft scheduling constraints

I would explicitly distinguish these.

### Hard constraint

Cannot be violated.

Example:

Farmer only accepts pickup September 25.

or:

Pickup must occur between 7:00–9:00 AM.

### Soft constraint

Preferred but negotiable.

Example:

Preferred date: September 25  
Acceptable date: September 26

This gives the system flexibility when the fleet is overloaded.

# 13\. Step 8 — Generate the road route

Once we have a feasible group:

```
A
B
C
```

the system needs actual road information.

This is where **OSRM** comes in.

Leaflet/OSM provides your map visualization, while OSRM can provide road-based:

- distance
- travel time
- route geometry

The system might calculate:

```
Coop → A = 8 km
A → B = 12 km
B → C = 7 km
C → Coop = 10 km
Total = 37 km
```

# 14\. Step 9 — Determine the pickup sequence

Now you have the routing problem.

The system might initially produce:

```
Cooperative
↓
A
↓
B
↓
C
↓
Cooperative
```

But another sequence might be:

```
Cooperative
↓
A
↓
C
↓
B
↓
Cooperative
```

with a shorter total distance.

This is where a route heuristic can be used.

I'd use:

### Initial route

**Nearest Neighbor**

Then:

### Route improvement

**2-opt**

2-opt tests whether swapping route segments produces a shorter route.

# 15\. So your algorithmic pipeline becomes

```
PICKUP REQUESTS
       │
       ▼
FILTER BY DATE
       │
       ▼
FILTER BY TIME WINDOW
       │
       ▼
FILTER BY GEOGRAPHIC AREA
       │
       ▼
CHECK TRUCK COMPATIBILITY
       │
       ▼
KNAPSACK
"Which requests fit?"
       │
       ▼
GENERATE ROUTE
       │
       ▼
OSRM
"How long/far are these road segments?"
       │
       ▼
NEAREST NEIGHBOR
       │
       ▼
2-OPT
"Can the sequence be improved?"
       │
       ▼
TIME-WINDOW VALIDATION
       │
       ▼
FINAL PROPOSED ROUTE
```

# 16\. What the actual web page looks like

I'd make the **Consolidated Pickup Planner** a three-part interface.

```
┌─────────────────────────────────────────────────────────────────────┐
│ CONSOLIDATED PICKUP PLANNER                                        │
│ September 25, 2026                         [Change Date ▼]          │
├─────────────────────┬───────────────────────────────────────────────┤
│                     │                                               │
│ PICKUP REQUESTS     │                                               │
│                     │                                               │
│ ☑ Farmer A          │                                               │
│   Corn              │                                               │
│   ~1,500 kg         │                                               │
│   7–10 AM           │                                               │
│                     │                  MAP                          │
│ ☑ Farmer B          │                                               │
│   Corn              │                                               │
│   ~800 kg           │                                               │
│   8–11 AM           │                                               │
│                     │                                               │
│ ☑ Farmer C          │                                               │
│   Vegetables        │                                               │
│   ~1,200 kg         │                                               │
│   10–1 PM           │                                               │
│                     │                                               │
│ ☐ Farmer D          │                                               │
│   Corn              │                                               │
│   ~1,000 kg         │                                               │
│                     │                                               │
├─────────────────────┴───────────────────────────────────────────────┤
│ TRUCK: Truck 03 ▼                                                   │
│ Capacity: 4,000 kg                                                  │
│                                                                     │
│ Load: 3,500 kg          Utilization: 87.5%                         │
│ Distance: 37 km         Est. Time: 1h 18m                          │
│                                                                     │
│ [ Optimize Route ]                       [ Create Pickup Trip ]     │
└─────────────────────────────────────────────────────────────────────┘
```

# 17\. The map

The map should show numbered stops.

Something like:

```
                         ②
                    Farmer B
                   8–11 AM
                      ●
                     /
                    /
           ①       /
       Farmer A ●──
       7–10 AM \
                 \
                  ● ③
               Farmer C
                10–1 PM
                    │
                    │
                    ●
                  COOP
```

The route line follows actual roads.

Clicking a marker opens:

```
Farmer B
Crop:
Corn
Estimated:
800 kg
Pickup window:
8:00–11:00 AM
Request:
#HP-00281
Status:
Scheduled
```

# 18\. The route panel

On the right side or below the map:

```
PROPOSED PICKUP ROUTE
Truck 03
Capacity: 4,000 kg
────────────────────────
START
Cooperative
07:00 AM
↓
STOP 1
Farmer A
07:28 AM
Corn
~1,500 kg
↓
STOP 2
Farmer B
08:16 AM
Corn
~800 kg
↓
STOP 3
Farmer C
10:04 AM
Vegetables
~1,200 kg
↓
RETURN
Cooperative
11:05 AM
```

# 19\. The system should show why the route is feasible

This is important for usability and also very useful for your system evaluation.

Show:

```
ROUTE VALIDATION
✓ Truck capacity
  3,500 / 4,000 kg
✓ Pickup date
  September 25
✓ Farmer A time window
  7–10 AM
✓ Farmer B time window
  8–11 AM
✓ Farmer C time window
  10 AM–1 PM
✓ Estimated route duration
✓ All pickup points reachable
```

If something fails:

```
⚠ TIME WINDOW CONFLICT
Farmer C:
10:00 AM–1:00 PM
Estimated arrival:
9:20 AM
Suggested action:
Wait 40 minutes
OR
Reorder pickup sequence
```

That's much better than simply showing "Optimization failed."

# 20\. The Admin can manually modify the route

This is important.

The system should **recommend**, not force.

For example:

```
PROPOSED:
1. Farmer A
2. Farmer B
3. Farmer C
```

The Admin can drag:

```
1. Farmer A
2. Farmer C
3. Farmer B
```

The system recalculates:

```
Distance:
37 km → 42 km
Travel time:
1h 18m → 1h 31m
Time-window:
✓ Feasible
```

Then the Admin can decide whether the operational reason for the change is worth the additional travel.

# 21\. What happens if the Admin adds another farmer?

Suppose they click:

**Add Pickup**

and choose Farmer D.

The system immediately recalculates:

```
Current load:
3,500 kg
Farmer D:
1,000 kg
New total:
4,500 kg
Truck capacity:
4,000 kg
```

The system displays:

🔴 **Truck capacity exceeded by 500 kg**

It can suggest:

```
Possible actions:
[ Remove Farmer D ]
[ Choose Larger Truck ]
[ Move Farmer D to Another Trip ]
```

# 22\. What happens if the truck is unavailable?

Suppose Truck 03 was suddenly assigned to another trip.

The planner updates:

```
Truck 03
🔴 Unavailable
Scheduled trip:
10:00 AM – 2:00 PM
```

The system can suggest:

```
Available alternatives:
Truck 01
Capacity: 4,000 kg
Truck 02
Capacity: 5,000 kg
```

The Admin can select another truck and the planner recalculates.

# 23\. What happens when the actual pickup begins?

This is where the planning system transitions into operations.

The delivery personnel's PWA shows:

```
PICKUP TRIP #00421
Truck 03
3 Stops
3,500 kg estimated
       MAP
     ①
      \
       ②
        \
         ③
          \
          COOP
NEXT STOP:
Farmer A
[ Start Trip ]
```

# 24\. Actual pickup changes the load

At Farmer A:

Estimated:

```
1,500 kg
```

Actual:

```
1,620 kg
```

The delivery personnel enters:

```
Actual Weight: 1,620 kg
Price/kg: ₱XX
Total Purchase: ₱XX
```

The system updates:

```
Truck:
1,620 / 4,000 kg
```

Then Farmer B:

```
Actual: 850 kg
```

Now:

```
2,470 / 4,000 kg
```

And so forth.

# 25\. What if actual weights cause an overload?

This is one of the most important real-world scenarios.

Planned:

```
A = 1,500
B =   800
C = 1,200
Total = 3,500 kg
```

Actual:

```
A = 1,700
B =   900
C = 1,600
Total = 4,200 kg
```

Truck:

```
4,000 kg
```

The PWA should show:

🔴 **Capacity exceeded**

And the Coop Admin receives the alert.

The delivery personnel can message:

"Farmer C actual weight is 1,600 kg. Current load exceeds Truck 03 capacity."

The Coop Admin can then decide whether to:

- transfer part of the load
- send another vehicle
- modify the trip
- coordinate another pickup
- resolve the situation operationally

This is why **in-app messaging, capacity planning, and route planning are actually interconnected features**.

# 26\. Live monitoring after dispatch

Once the trip starts, your HTTP polling architecture takes over.

The delivery personnel's PWA periodically sends its latest location.

The Coop Admin sees:

```
TRIP #00421
Status:
En route to Farmer C
Truck:
Truck 03
Current load:
2,470 kg
Next stop:
Farmer C
ETA:
10:04 AM
```

The Leaflet map updates the truck marker periodically.

No WebSocket is necessary.

# 27\. The final data model should support this

At a high level, you'll eventually need concepts like:

```
farmers
cooperatives
trucks
delivery_personnel
harvest_pickup_requests
pickup_request_items
pickup_trips
pickup_trip_stops
route_plans
route_plan_stops
truck_schedules
pickup_records
crop_purchase_records
location_logs
messages
```

The important distinction is:

### Pickup Request

"What does the farmer want picked up?"

### Pickup Trip

"What did the cooperative schedule?"

### Pickup Trip Stop

"Which farmers does this truck visit, and in what order?"

### Pickup Record

"What actually happened at the farm?"

That separation will make your system much easier to maintain.

# 28\. The algorithms in the final design

I'd structure the actual implementation like this:

| **Requirement**                    | **Method**                                  |
| ---------------------------------- | ------------------------------------------- |
| Select compatible harvest requests | **0/1 Knapsack / capacity-aware selection** |
| Limit candidate geographic area    | **Distance/geographic filtering**           |
| Road distance                      | **OSRM**                                    |
| Road travel time                   | **OSRM**                                    |
| Initial route                      | **Nearest Neighbor**                        |
| Route improvement                  | **2-opt**                                   |
| Pickup time constraints            | **Time-window validation**                  |
| Truck scheduling                   | **Fleet availability/schedule checking**    |
| Map                                | **Leaflet + OpenStreetMap**                 |
| Live truck location                | **HTTP polling**                            |
| Actual weight changes              | **Real-time capacity recalculation**        |
| Human override                     | **Coop Admin approval/editing**             |

# 29\. If you want the "true" optimization version later

The architecture we've designed is a **progressive implementation**.

You can start with:

```
Knapsack
+
OSRM
+
Nearest Neighbor
+
2-opt
+
Time-window validation
```

Then, if the project eventually requires a more mathematically integrated optimizer, you can move toward:

**Capacitated Vehicle Routing Problem with Time Windows (CVRPTW)**

That model can simultaneously consider:

```
Truck capacity
+
Pickup locations
+
Pickup time windows
+
Multiple vehicles
+
Route distance/time
+
Depot
```

You wouldn't need to throw away the existing system. The planner UI and database structure can remain largely the same while the optimization engine becomes more sophisticated.

# 30\. So what the finished feature looks like to the Coop Admin

The Coop Admin essentially gets this:

```
╔══════════════════════════════════════════════════════════════════╗
║             CONSOLIDATED PICKUP PLANNER                         ║
║             September 25, 2026                                  ║
╠═══════════════════════════╦══════════════════════════════════════╣
║                           ║                                      ║
║  PICKUP REQUESTS          ║               MAP                    ║
║                           ║                                      ║
║  ☑ Farmer A               ║          ②                           ║
║    Corn                   ║       ● Farmer B                     ║
║    ~1,500 kg              ║       /                              ║
║    7–10 AM                ║      /                               ║
║                           ║  ① ●                                 ║
║  ☑ Farmer B               ║    Farmer A                          ║
║    Corn                   ║        \                             ║
║    ~800 kg                ║         \                            ║
║    8–11 AM                ║          ● ③                         ║
║                           ║        Farmer C                       ║
║  ☑ Farmer C               ║             \                        ║
║    Vegetables             ║              ● COOP                   ║
║    ~1,200 kg              ║                                      ║
║    10–1 PM                ║                                      ║
║                           ║                                      ║
║  ☐ Farmer D               ║                                      ║
║    ~1,000 kg              ║                                      ║
║                           ║                                      ║
╠═══════════════════════════╩══════════════════════════════════════╣
║ TRUCK 03                                                        ║
║ Capacity: 4,000 kg       Load: 3,500 kg       87.5%             ║
║                                                                  ║
║ Distance: 37 km          Time: 1h 18m                            ║
║                                                                  ║
║ ✓ Capacity   ✓ Time Windows   ✓ Truck Available                  ║
╠══════════════════════════════════════════════════════════════════╣
║                                                                  ║
║ PICKUP SEQUENCE                                                  ║
║                                                                  ║
║  07:00  Cooperative                                            ║
║     ↓                                                            ║
║  07:28  ① Farmer A — 1,500 kg                                  ║
║     ↓                                                            ║
║  08:16  ② Farmer B — 800 kg                                    ║
║     ↓                                                            ║
║  10:04  ③ Farmer C — 1,200 kg                                  ║
║     ↓                                                            ║
║  11:05  Cooperative                                             ║
║                                                                  ║
║              [Re-optimize] [Modify Route] [Create Trip]          ║
╚══════════════════════════════════════════════════════════════════╝
```

And that is the key idea:

**The map isn't the feature.**

The map is the **visual interface for a scheduling, consolidation, capacity, and routing engine** underneath it.

The system is effectively helping the cooperative answer:

**"Given all the farmers who need pickup, the dates/times they are available, and the trucks we actually have, which harvests should we pool together and what route should each truck take?"**

One correction from our earlier discussion is important: **OSRM itself is open-source, but router.project-osrm.org is a hosted public instance.** The OSRM HTTP API provides Route, Table, Nearest, Match, and Trip services; importantly for your system, the **Table service can return road-distance and travel-time matrices**, which is exactly what the consolidation planner needs.

# OSRM ROUTING + CONSOLIDATED PICKUP IMPLEMENTATION PLAN

## 1\. Project Context

Build the routing component for an existing:

- Laravel 12
- PHP 8.2+
- MySQL
- Blade
- Tailwind CSS v4
- Alpine.js
- Vite
- Leaflet
- OpenStreetMap
- SweetAlert2
- PWA
- Hostinger shared hosting

### Deployment constraints

The application is deployed on **Hostinger shared hosting**.

Therefore:

- Do not require a VPS.
- Do not install OSRM on the Hostinger server.
- Do not require Docker.
- Do not require WebSockets.
- Do not require a persistent Node server.
- Do not require a Laravel queue worker for routing.
- Do not require a continuously running background process.

The Laravel application communicates with an external hosted OSRM HTTP endpoint.

# 2\. Routing Architecture

Use this architecture:

```
                         HOSTINGER
                    Shared Hosting
                          │
                          │ HTTPS
                          ▼
              ┌───────────────────────┐
              │ Laravel Routing Layer │
              └───────────┬───────────┘
                          │
                          ▼
                RoutingServiceContract
                          │
              ┌───────────┴───────────┐
              │                       │
              ▼                       ▼
       OsrmRoutingService      HaversineFallback
              │
              ▼
    router.project-osrm.org
              │
              ▼
        Project OSRM
```

The application must **not hard-code OSRM requests directly inside controllers**.

# 3\. Environment Configuration

Add environment variables:

```
ROUTING_PROVIDER=osrm
OSRM_BASE_URL=https://router.project-osrm.org
OSRM_PROFILE=driving
OSRM_TIMEOUT=10
OSRM_RETRY_TIMES=2
OSRM_RETRY_SLEEP=500
```

Do not hard-code:

```
router.project-osrm.org
```

throughout the application.

# 4\. Laravel Configuration

Create:

```
config/routing.php
```

Conceptually:

```
return [
    'provider' => env('ROUTING_PROVIDER', 'osrm'),
    'osrm' => [
        'base_url' => env(
            'OSRM_BASE_URL',
            'https://router.project-osrm.org'
        ),
        'profile' => env('OSRM_PROFILE', 'driving'),
        'timeout' => (int) env('OSRM_TIMEOUT', 10),
        'retry_times' => (int) env('OSRM_RETRY_TIMES', 2),
        'retry_sleep' => (int) env('OSRM_RETRY_SLEEP', 500),
    ],
];
```

# 5\. Routing Contract

Create an abstraction.

For example:

```
app/Contracts/RoutingService.php
```

The contract should support at minimum:

```
route(...)
table(...)
nearest(...)
```

The first two are essential.

### Route

Used after a route sequence has already been selected.

Example:

```
Coop
→ Farmer A
→ Farmer B
→ Farmer C
→ Coop
```

OSRM Route Service calculates the road route for that ordered sequence. OSRM's Route service accepts coordinates in order and can return route geometry, duration, distance, and optional steps.

### Table

Used by the optimization engine.

Example:

```
Coop
Farmer A
Farmer B
Farmer C
Farmer D
```

The Table Service returns the travel-time and/or distance matrix between locations. These are **road-network values**, not straight-line distances.

# 6\. OSRM Service

Create:

```
app/Services/Routing/OsrmRoutingService.php
```

Responsibilities:

- Build OSRM URLs
- Send HTTP requests
- Validate responses
- Parse OSRM responses
- Convert meters → kilometers
- Convert seconds → minutes
- Return consistent application-level objects
- Handle timeouts
- Handle HTTP errors
- Handle OSRM error codes
- Log failures
- Never expose raw OSRM responses directly to controllers

Use Laravel's HTTP client.

Conceptually:

```
Http::timeout(...)
    ->retry(...)
    ->get(...);
```

# 7\. OSRM Route Endpoint

Use:

```
/route/v1/driving/{coordinates}
```

Example:

```
https://router.project-osrm.org/route/v1/driving/
125.123,7.123;
125.456,7.456;
125.789,7.789
```

Request:

```
overview=full
geometries=geojson
steps=false
```

The important result is:

```
distance
duration
geometry
waypoints
```

OSRM supports GeoJSON route geometry, which is convenient for rendering the resulting route with Leaflet.

# 8\. OSRM Table Endpoint

This is the **most important OSRM service for your route optimizer**.

Use:

```
/table/v1/driving/{coordinates}
```

Request:

```
annotations=distance,duration
```

Example conceptual input:

```
Coop
Farmer A
Farmer B
Farmer C
Farmer D
```

The response provides:

```
distance matrix
duration matrix
```

where distance is in meters and duration is in seconds.

# 9\. Do NOT Use OSRM for Every Pair Individually

Do not do this:

```
Coop → Farmer A
Coop → Farmer B
Coop → Farmer C
Farmer A → Farmer B
Farmer A → Farmer C
...
```

as separate HTTP requests.

Instead:

```
Coop
A
B
C
D
E
```

→ one Table request

→ matrix.

This significantly reduces external routing calls.

# 10\. Routing Cache

Add database caching for routing results.

Do not rely entirely on Laravel's normal cache because the application may need the routing information later for:

- route planning
- re-optimization
- reports
- trip history
- debugging
- route comparison

Create something like:

```
route_distance_cache
```

or:

```
routing_matrices
```

A practical design is to cache individual origin/destination pairs.

Example:

```
origin_location_id
destination_location_id
distance_meters
duration_seconds
provider
calculated_at
```

Use a normalized origin/destination pair.

For symmetric routing, do **not automatically assume A→B equals B→A**. Road networks can be asymmetric because of one-way roads and routing rules.

Therefore store:

```
A → B
```

separately from:

```
B → A
```

unless the routing provider explicitly establishes symmetry.

# 11\. Location Data

Pickup locations must have coordinates.

For each farmer:

```
latitude
longitude
```

The cooperative also needs:

```
latitude
longitude
```

for its pickup/receiving facility.

Truck data should not need geographic coordinates unless you later want to track the truck's current position.

# 12\. Farmer Pickup Request

A haul request should contain approximately:

```
id
farmer_id
cooperative_id
crop_type
package_type
estimated_package_quantity
estimated_weight_kg
requested_pickup_date
pickup_window_start
pickup_window_end
pickup_latitude
pickup_longitude
status
notes
```

Example:

```
Farmer: Juan
Crop: Corn
Sacks: 80
Estimated weight: 4,000 kg
Pickup date:
September 25
Time window:
07:00–10:00
```

# 13\. Truck Data

Truck:

```
id
cooperative_id
vehicle_name
plate_number
capacity_kg
status
```

Statuses:

```
available
scheduled
in_transit
maintenance
unavailable
```

# 14\. Pickup Trip

Create a separate concept from a haul request.

A haul request means:

"Farmer needs a pickup."

A pickup trip means:

"The cooperative has grouped these requests into one actual truck trip."

Example:

```
Pickup Trip #PU-00021
Truck:
Truck 03
Date:
September 25
Stops:
1. Farmer A
2. Farmer B
3. Farmer C
Estimated load:
3,500 kg
Truck capacity:
4,000 kg
```

# 15\. Pickup Trip Stops

Create a child record for every stop.

For example:

```
pickup_trip_stops
```

Fields:

```
pickup_trip_id
haul_request_id
sequence_number
planned_arrival_at
actual_arrival_at
status
estimated_weight_kg
actual_weight_kg
```

This is important because the route is not just a list of farmers.

It is a **scheduled sequence of operational stops**.

# 16\. Pickup Planning Workflow

The planner should operate in this order:

```
Pending Haul Requests
        ↓
Filter by pickup date
        ↓
Filter by compatible time windows
        ↓
Filter by truck availability
        ↓
Filter by geographic proximity
        ↓
Check estimated load capacity
        ↓
Build candidate groups
        ↓
Get OSRM distance/time matrix
        ↓
Optimize pickup sequence
        ↓
Validate time windows
        ↓
Validate truck capacity
        ↓
Generate proposed trip
        ↓
Coop Admin reviews
        ↓
Approve / Modify / Reject
        ↓
Create Pickup Trip
```

# 17\. Do Not Let OSRM Decide Which Farmers Belong Together

This is critical.

OSRM tells you:

"Driving from A to B takes approximately X minutes and X kilometers."

OSRM does **not** decide:

"These three farmers should be consolidated."

That is your Laravel application's responsibility.

# 18\. Candidate Filtering

Suppose there are:

```
100 pending requests
```

Do not send all 100 to the optimizer.

First filter by:

### Date

Only requests for the selected pickup date.

### Time

Remove obviously incompatible pickup windows.

### Truck

Only trucks available on that date.

### Capacity

Remove combinations that can never fit.

### Geography

Use Haversine as a cheap preliminary distance filter.

Example:

```
Farmer A
Farmer B
Farmer C
```

are geographically close.

But:

```
Farmer D
```

is 100+ km away.

Do not waste routing requests evaluating D with every candidate.

# 19\. Haversine

Implement:

```
app/Services/Routing/HaversineService.php
```

Haversine is **not the final driving-distance calculation**.

It is only used for:

- preliminary geographic filtering
- fallback estimates when OSRM is unavailable
- simple proximity calculations

The system must label Haversine results as approximate.

# 20\. Capacity Consolidation

For every candidate group:

```
sum estimated weight <= truck capacity
```

Example:

```
Truck:
4,000 kg
A:
1,500 kg
B:
800 kg
C:
1,200 kg
Total:
3,500 kg
Utilization:
87.5%
```

This is feasible by capacity.

# 21\. Knapsack

Use a knapsack-style selection algorithm for the **load-consolidation stage**.

Objective:

Select compatible pickup requests that maximize useful truck utilization without exceeding capacity.

Do not describe this as the complete route optimization algorithm.

It solves:

**Which loads should go together?**

It does not solve:

**In what order should the truck visit them?**

# 22\. Route Sequencing

After selecting a candidate group:

```
Coop
A
B
C
```

use the OSRM Table matrix.

Then generate an initial route using:

**Nearest Neighbor**

Example:

```
Coop
 ↓
A
 ↓
B
 ↓
C
 ↓
Coop
```

# 23\. 2-opt Improvement

After Nearest Neighbor produces a route, apply **2-opt**.

Purpose:

Try exchanging route segments to reduce total travel cost.

Example:

```
Coop → A → B → C → D → Coop
```

Try alternative sequences and retain improvements where appropriate.

The optimizer should calculate the route cost using the **OSRM road-distance/time matrix**, not Haversine distance.

# 24\. Time Windows

This is a major constraint.

Example:

```
Farmer A
07:00–09:00
Farmer B
08:00–11:00
Farmer C
10:00–13:00
```

Suppose:

```
Coop → A = 20 min
A → B = 30 min
B → C = 40 min
```

The system calculates estimated arrival times.

It must account for:

```
travel time
+
pickup/service time
+
waiting time
```

# 25\. Pickup Service Time

Do not assume that a pickup takes zero minutes.

Create a configurable estimated service duration.

For example:

```
pickup_service_minutes = 30
```

This represents the expected time for:

- loading
- weighing
- checking crops
- documentation
- other pickup activities

The actual time can later be recorded.

This is important because:

```
travel time alone ≠ trip schedule
```

# 26\. Waiting Time

If the truck arrives early:

```
Arrival:
07:30
Window:
08:00–10:00
```

the system should calculate:

```
waiting = 30 minutes
```

The truck's effective service time becomes:

```
08:00
```

# 27\. Time-Window Feasibility

A candidate route is feasible only if every stop can be reached within its allowed window.

Example:

```
Farmer A
07:00–09:00
Arrival:
07:45
✓
Farmer B
08:00–11:00
Arrival:
09:00
✓
Farmer C
10:00–13:00
Arrival:
10:20
✓
```

Route:

**FEASIBLE**

# 28\. Infeasible Route

Example:

```
Farmer C
07:00–08:00
Estimated arrival:
09:30
```

Result:

```
INFEASIBLE
```

Do not silently accept it.

The planner should show:

Farmer C cannot be reached within the requested pickup window.

# 29\. Route Cost

Initially, use a simple objective:

```
Primary:
Minimize total road travel distance
```

subject to:

```
truck capacity
pickup date
pickup time windows
truck availability
personnel availability
```

Later, the objective can be expanded to:

```
travel distance
+ travel time
+ waiting time
+ route penalties
```

But don't overcomplicate version 1.

# 30\. Proposed Route Result

The optimizer should return a structured result:

```
{
    "truck_id": 3,
    "stops": [
        {
            "haul_request_id": 21,
            "sequence": 1,
            "arrival": "07:42"
        },
        {
            "haul_request_id": 34,
            "sequence": 2,
            "arrival": "08:35"
        },
        {
            "haul_request_id": 41,
            "sequence": 3,
            "arrival": "10:12"
        }
    ],
    "estimated_weight_kg": 3500,
    "capacity_kg": 4000,
    "utilization_percent": 87.5,
    "distance_km": 37.2,
    "duration_minutes": 82,
    "feasible": true
}
```

# 31\. Database Storage of Proposed Route

Do not immediately create an official trip when the optimizer runs.

There should be a distinction between:

### Proposal

Generated by the optimizer.

and:

### Approved Trip

Created after Coop Admin approval.

This prevents an accidental optimization click from changing actual schedules.

# 32\. Planner UI

Create a page such as:

```
/cooperative/pickup-planner
```

### Top bar

```
Consolidated Pickup Planner
Pickup Date: [ September 25, 2026 ]
[ Refresh Requests ]
[ Optimize ]
```

# 33\. Left Panel

Show pending requests:

```
PENDING PICKUPS
☐ Farmer A
   Corn
   ~1,500 kg
   7:00–10:00 AM
☐ Farmer B
   Corn
   ~800 kg
   8:00–11:00 AM
☐ Farmer C
   Vegetables
   ~1,200 kg
   10:00 AM–1:00 PM
```

Allow selection/filtering.

# 34\. Truck Panel

Show:

```
AVAILABLE TRUCKS
Truck 01
Capacity: 4,000 kg
Status: Available
Truck 02
Capacity: 6,000 kg
Status: Available
Truck 03
Capacity: 3,000 kg
Status: Scheduled
```

When the optimizer generates a proposal, show the selected truck.

# 35\. Map

Use:

**Leaflet + OpenStreetMap**

Display:

- Cooperative
- Farmer A
- Farmer B
- Farmer C
- Numbered stop markers
- Route polyline
- Optional truck current position

Example:

```
       ① Farmer A
          │
          │
     ② Farmer B
          │
          │
     ③ Farmer C
          │
          ▼
       COOPERATIVE
```

The actual route polyline comes from OSRM.

# 36\. Route Summary Card

Show:

```
ROUTE SUMMARY
Truck:
Truck 03
Capacity:
4,000 kg
Estimated Load:
3,500 kg
Utilization:
87.5%
Stops:
3
Distance:
37.2 km
Estimated Travel:
1h 22m
Estimated Pickup Time:
1h 30m
Total Estimated Trip:
2h 52m
Time Windows:
✓ Feasible
```

# 37\. Route Warnings

Use clear warnings.

Examples:

```
⚠ Truck capacity exceeded
⚠ Farmer B pickup window cannot be satisfied
⚠ Routing service unavailable
⚠ Route uses approximate distances
```

Use SweetAlert2 for confirmation actions.

# 38\. Coop Admin Actions

After optimization:

```
[ Accept Route ]
[ Modify Route ]
[ Re-optimize ]
[ Cancel ]
```

If accepting:

SweetAlert:

Create pickup trip from this proposed route?

Then:

```
Confirm
Cancel
```

# 39\. Manual Modification

The Coop Admin should be able to:

- Change truck
- Add farmer
- Remove farmer
- Change sequence
- Change pickup schedule
- Re-optimize

After a change, recalculate:

```
distance
duration
arrival times
capacity
time-window feasibility
```

# 40\. Route Recalculation

If the admin changes:

```
A → B → C
```

to:

```
A → C → B
```

the system must recalculate the actual road route through OSRM.

Do not simply redraw a straight line between markers.

# 41\. Final Route Geometry

Once the sequence is approved:

```
Coop
→ A
→ B
→ C
→ Coop
```

send the ordered coordinates to OSRM Route Service.

Request:

```
geometries=geojson
overview=full
steps=false
```

Store the returned route geometry if useful.

Render it with Leaflet.

# 42\. Live Pickup Tracking

Once the pickup trip starts:

```
Delivery Personnel PWA
        │
        │ GPS
        ▼
HTTP POST
        │
        ▼
Laravel
        │
        ▼
MySQL
        │
        │ periodic polling
        ▼
Coop Admin
        │
        ▼
Leaflet
```

No WebSockets.

No OSRM is needed for every GPS update.

OSRM is for **road routing**.

The live location itself comes from the delivery personnel's device.

# 43\. GPS Location Records

Store something like:

```
vehicle_location_logs
```

with:

```
pickup_trip_id
delivery_personnel_id
latitude
longitude
recorded_at
accuracy
```

You can later optimize storage by keeping only meaningful updates.

# 44\. HTTP Polling

The Coop Admin dashboard can poll:

```
GET /api/pickup-trips/{trip}/location
```

every configurable interval.

Example:

```
10–30 seconds
```

Do not promise "real-time."

Call it:

**Periodic location monitoring**

# 45\. OSRM Is NOT Needed for Every GPS Update

This is important.

Do not do:

```
GPS update
 ↓
OSRM
 ↓
GPS update
 ↓
OSRM
```

That wastes routing requests.

Instead:

```
GPS update
 ↓
MySQL
 ↓
Map marker moves
```

OSRM is only invoked when you need:

- route planning
- route recalculation
- route display
- road distance/time
- travel matrix

# 46\. OSRM Failure Handling

If OSRM fails:

```
HTTP timeout
```

or:

```
NoRoute
```

or:

```
Invalid response
```

the application must not crash.

Return a controlled application exception/result:

```
RoutingServiceUnavailable
```

Then show:

Road routing service is temporarily unavailable. Approximate geographic distance is being used where supported.

# 47\. Fallback Rules

Haversine should **not pretend to be road distance**.

Label it:

```
Approximate straight-line distance
```

Never:

```
Road distance: 12 km
```

if it came from Haversine.

Use:

```
Approximate distance: 12 km
```

# 48\. Do Not Automatically Approve a Fallback Route

If OSRM fails during optimization:

```
Haversine fallback
```

the result should be marked:

```
routing_confidence = approximate
```

The Coop Admin should see:

Routing service unavailable. Route distances are approximate. Manual review required.

This protects against creating an apparently precise route using inaccurate distances.

# 49\. Request Throttling

Because the public hosted OSRM instance is a shared service, design the application to **minimize and throttle requests**.

Do not assume unlimited routing requests.

Use:

```
candidate filtering
+
Table batching
+
database caching
+
request throttling
```

The public instance should therefore be treated as a constrained external dependency.

# 50\. Route Calculation Cache

Before requesting:

```
A → B
```

check whether a recent result exists.

If yes:

```
use cached result
```

If no:

```
request OSRM
store result
```

This is particularly useful because farmer coordinates generally don't change frequently.

# 51\. Location Changes

If a farmer changes their pickup coordinates significantly:

```
invalidate affected route-cache entries
```

because the previous routing data may no longer represent the current location.

# 52\. Cache Key

Use something conceptually like:

```
osrm:driving:{origin_location_id}:{destination_location_id}
```

But include enough information to prevent stale results if coordinates change.

For example, include a coordinate hash.

# 53\. Route Matrix Cache

For a planner run:

```
date
candidate locations
```

store the generated matrix where practical.

If the same candidate set is optimized again, reuse the matrix.

# 54\. Security

Never allow the browser to directly call:

```
router.project-osrm.org
```

for core routing operations.

Use:

```
Browser
 ↓
Laravel
 ↓
OSRM
```

This gives you:

- centralized error handling
- caching
- throttling
- logging
- control over provider changes

# 55\. API Credentials

The public OSRM endpoint currently does not require an API key.

Nevertheless, keep the endpoint configurable through .env.

Do not design the application around the assumption that every future routing provider will be keyless.

# 56\. Testing

Create unit tests for:

### Haversine

```
known coordinates
→ expected approximate distance
```

### Capacity

```
3500 <= 4000
→ feasible
4500 > 4000
→ infeasible
```

### Time windows

```
arrival inside window
→ feasible
arrival after window
→ infeasible
```

### Knapsack

Verify that selected loads never exceed truck capacity.

### Nearest Neighbor

Verify deterministic route construction.

### 2-opt

Verify that an improved route is actually shorter according to the supplied matrix.

### Routing Service

Mock OSRM responses.

Do not make PHPUnit/Pest tests depend on the public OSRM server.

# 57\. OSRM Integration Tests

Have a separate integration test that can be run manually/optionally.

Example:

```
php artisan routing:test
```

It should test:

```
Hostinger
 ↓
OSRM endpoint
 ↓
valid response
```

But the normal automated test suite must use mocks.

This keeps the existing test suite reliable even if the external OSRM service is unavailable.

# 58\. Artisan Diagnostic Command

Add:

```
php artisan routing:health
```

Output:

```
Routing Provider: OSRM
Endpoint:
https://router.project-osrm.org
Connectivity:
✓
Route Service:
✓
Table Service:
✓
```

If unavailable:

```
Routing Provider: OSRM
Connectivity:
✗
Error:
Connection timeout
```

This is especially useful after deployment to Hostinger.

# 59\. Admin Routing Settings

Do not allow normal cooperative users to modify:

```
OSRM_BASE_URL
```

That belongs to deployment configuration.

The Coop Admin can configure business rules such as:

- default pickup service duration
- geographic consolidation threshold
- planning parameters

but not infrastructure endpoints.

# 60\. Suggested Database Tables

At minimum, the routing feature will interact with:

```
cooperatives
farmers
users
trucks
haul_requests
pickup_trips
pickup_trip_stops
vehicle_location_logs
```

Add:

```
route_calculations
```

for route results/cache if appropriate.

Possible fields:

```
id
provider
profile
origin_location_id
destination_location_id
distance_meters
duration_seconds
route_geometry
calculated_at
expires_at
```

If storing complete route geometry is too large for pairwise cache records, keep geometry only at the **approved trip** level.

# 61\. Approved Trip Route Record

For an approved pickup trip, store:

```
pickup_trip_id
route_geometry
total_distance_meters
estimated_duration_seconds
optimization_method
routing_provider
routing_status
```

Example:

```
optimization_method:
knapsack + nearest_neighbor + 2opt
routing_provider:
osrm
routing_status:
confirmed
```

This makes the system auditable.

# 62\. Route Audit Trail

When the Coop Admin changes a proposed route, record:

```
who changed it
when
old sequence
new sequence
old truck
new truck
reason
```

This is useful because the optimizer's proposal and the actual operational decision may differ.

# 63\. Important Business Rule

The optimized route is a **recommendation**.

The Coop Admin makes the final operational decision.

Therefore:

```
Algorithm
    ↓
Recommendation
    ↓
Human review
    ↓
Approved trip
```

not:

```
Algorithm
    ↓
Automatically dispatch truck
```

# 64\. Progressive Implementation Order

Tell OpenCode **not to build the entire thing at once**.

Build it progressively.

### Phase 1 — Routing foundation

Implement:

- Routing contract
- OSRM service
- Haversine service
- .env configuration
- routing config
- basic tests

Then test:

```
A → B
```

and:

```
A, B, C → matrix
```

### Phase 2 — Location data

Implement:

- farmer coordinates
- cooperative coordinates
- coordinate validation
- map display

Show all farmers on Leaflet.

No optimization yet.

### Phase 3 — Haul requests

Implement:

- farmer haul request
- requested date
- time window
- estimated sacks
- estimated weight
- pickup location

### Phase 4 — Truck management

Implement:

- truck records
- capacity
- availability
- status

### Phase 5 — Pickup planner

Build:

```
Pending Requests
+
Available Trucks
+
Date
+
Time Windows
```

No optimization yet.

### Phase 6 — Consolidation

Implement:

- capacity filtering
- candidate grouping
- knapsack

Test:

```
A + B + C fits
A + B + C + D doesn't
```

### Phase 7 — OSRM Matrix

Integrate:

```
Table Service
```

for candidate groups.

### Phase 8 — Route Optimization

Implement:

```
Nearest Neighbor
        ↓
2-opt
        ↓
Time-window validation
```

### Phase 9 — Route Map

Display:

- numbered stops
- OSRM route geometry
- distance
- duration
- capacity
- time-window status

### Phase 10 — Admin Approval

Implement:

```
Optimize
 ↓
Review
 ↓
Modify
 ↓
Approve
 ↓
Create Pickup Trip
```

### Phase 11 — PWA Pickup Execution

Delivery Personnel sees:

```
Today's Pickup Trip
Stop 1
Farmer A
07:30 AM
[Start]
Stop 2
Farmer B
08:30 AM
[Arrived]
[Picked Up]
```

### Phase 12 — HTTP Location Monitoring

Add:

- GPS collection
- HTTP location updates
- database storage
- polling
- Leaflet marker updates

### Phase 13 — Actual Receiving

At pickup:

```
Estimated:
4,000 kg
Actual:
3,920 kg
Sacks:
80
Grade:
A
Price:
₱18/kg
```

This is where your existing procurement workflow connects to logistics.

# 65\. Final End-to-End Scenario

OpenCode should ultimately be able to demonstrate this scenario:

### Day 1

Farmer A submits:

```
80 sacks
Corn
~4,000 kg
September 25
7–10 AM
```

Farmer B:

```
40 sacks
Corn
~2,000 kg
September 25
8–11 AM
```

Farmer C:

```
60 sacks
Vegetables
~1,500 kg
September 25
10 AM–1 PM
```

### Day 2

Coop Admin opens:

**Consolidated Pickup Planner**

Selects:

```
September 25
```

System finds:

```
A
B
C
```

are potentially compatible.

Truck:

```
4,000 kg
```

System evaluates:

```
A + B + C
= 7,500 kg
```

Not feasible.

It considers:

```
A + B
= 6,000 kg
```

Still not feasible.

Maybe:

```
B + C
= 3,500 kg
```

Feasible.

The system then uses OSRM to evaluate:

```
Coop
B
C
Coop
```

and produces:

```
Distance: 31 km
Duration: 1h 08m
Capacity: 87.5%
Time windows: Feasible
```

### Coop Admin sees

```
┌───────────────────────────────────────────────┐
│ CONSOLIDATED PICKUP PLAN                      │
├───────────────────────────────────────────────┤
│ Truck 03                                      │
│ Capacity: 4,000 kg                            │
│ Estimated load: 3,500 kg                      │
│ Utilization: 87.5%                            │
│                                               │
│ ① Farmer B — 8:00–11:00                      │
│ ② Farmer C — 10:00–1:00                      │
│                                               │
│ Distance: 31 km                               │
│ Travel time: 1h 08m                           │
│                                               │
│ ✓ Capacity feasible                           │
│ ✓ Time windows feasible                       │
│ ✓ Route generated                             │
│                                               │
│ [Modify] [Re-optimize] [Approve Trip]         │
└───────────────────────────────────────────────┘
```

The map shows:

```
              ① B
             /
            /
       COOP
            \
             \
              ② C
```

with the actual OSRM road geometry.

# 66\. Final Architecture to Give OpenCode

Tell OpenCode that the final architecture is:

```
                         HOSTINGER
                    SHARED HOSTING ONLY
                              │
          ┌───────────────────┴────────────────────┐
          │                                        │
       Laravel 12                               MySQL
          │
          ├── Authentication / Roles
          ├── Farmer Management
          ├── Haul Requests
          ├── Pickup Scheduling
          ├── Truck Management
          ├── Consolidation Engine
          ├── Knapsack
          ├── Nearest Neighbor
          ├── 2-opt
          ├── Time-window Validation
          ├── Procurement
          ├── B2B Orders
          ├── Delivery
          ├── Messaging
          ├── HTTP GPS Polling
          └── RoutingService
                    │
                    │ HTTPS
                    ▼
          ┌────────────────────────┐
          │ Hosted Project OSRM    │
          │ router.project-osrm.org│
          └───────────┬────────────┘
                      │
             ┌────────┴─────────┐
             │                  │
          Route               Table
             │                  │
        Road geometry      Distance/time
        Final route        matrix
             │                  │
             └────────┬─────────┘
                      ▼
              Laravel Optimizer
                      │
                      ▼
                Proposed Route
                      │
                      ▼
                  Coop Admin
                      │
               Approve / Modify
                      │
                      ▼
                 Pickup Trip
                      │
                      ▼
               Delivery PWA
                      │
                HTTP GPS updates
                      │
                      ▼
                 Leaflet Map
```

## 67\. Non-Negotiable Implementation Rules

Give OpenCode these rules explicitly:

1. **Do not install OSRM on Hostinger.**
2. **Do not introduce a VPS.**
3. **Do not introduce WebSockets.**
4. **Do not introduce a persistent worker requirement.**
5. **Do not call OSRM directly from Blade/JavaScript for core routing.**
6. **Do not hard-code the OSRM URL.**
7. **Use a Laravel routing service contract.**
8. **Use OSRM Table Service for distance/time matrices.**
9. **Use OSRM Route Service for final ordered route geometry.**
10. **Use Haversine only as preliminary/fallback geographic distance.**
11. **Never represent Haversine distance as road distance.**
12. **Cache routing results.**
13. **Batch routing requests whenever possible.**
14. **Throttle external routing requests.**
15. **Never make automated tests depend on the public OSRM server.**
16. **Mock OSRM in Pest/PHPUnit.**
17. **Keep the public OSRM endpoint configurable.**
18. **Handle OSRM downtime gracefully.**
19. **Do not automatically approve optimized routes.**
20. **Coop Admin always has final authority over the proposed pickup plan.**

The key architectural decision is therefore:

**Laravel owns the logistics intelligence; OSRM supplies road-network intelligence.**

That separation is what lets the system stay compatible with **Hostinger shared hosting** while still having a genuine road-based routing component. OSRM's official API supports both the route calculation and the multi-location Table Service needed for this design.