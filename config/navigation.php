<?php

/*
|--------------------------------------------------------------------------
| Role-based sidebar navigation
|--------------------------------------------------------------------------
| The sidebar renders the entry for the signed-in user's role. Each item is:
|   label     => visible text
|   letter    => single letter shown when the sidebar is collapsed
|   route     => route name pattern used to detect the active item
|   route_url => route name used to build the link
|   tooltip   => text shown when the sidebar is collapsed
|
| Only routes that actually exist for the role are listed. Add an item when
| its module ships so the sidebar never links to a missing page.
*/

return [

    'super_admin' => [
        'section_label' => 'Administration',
        'items' => [
            [
                'label' => 'Cooperatives',
                'letter' => 'C',
                'route' => 'admin.cooperatives.*',
                'route_url' => 'admin.cooperatives.index',
                'tooltip' => 'Cooperatives',
            ],
            [
                'label' => 'Buyers',
                'letter' => 'B',
                'route' => 'admin.buyers.*',
                'route_url' => 'admin.buyers.index',
                'tooltip' => 'Buyers',
            ],
            [
                'label' => 'Users',
                'letter' => 'U',
                'route' => 'admin.users.*',
                'route_url' => 'admin.users.index',
                'tooltip' => 'Users',
            ],
            [
                'label' => 'Crops',
                'letter' => 'C',
                'route' => 'admin.crops.*',
                'route_url' => 'admin.crops.index',
                'tooltip' => 'Crops',
            ],
            [
                'label' => 'Grades & Packaging',
                'letter' => 'G',
                'route' => 'admin.reference.*',
                'route_url' => 'admin.reference.index',
                'tooltip' => 'Grades & Packaging',
            ],
            [
                'label' => 'Audit Logs',
                'letter' => 'A',
                'route' => 'admin.audit-logs',
                'route_url' => 'admin.audit-logs',
                'tooltip' => 'Audit Logs',
            ],
        ],
    ],

    'coop_admin' => [
        'section_label' => 'Cooperative',
        'items' => [
            [
                'label' => 'Farmers',
                'letter' => 'F',
                'route' => 'coop.farmers.*',
                'route_url' => 'coop.farmers.index',
                'tooltip' => 'Farmer Members',
            ],
            [
                'label' => 'Trucks',
                'letter' => 'T',
                'route' => 'coop.trucks.*',
                'route_url' => 'coop.trucks.index',
                'tooltip' => 'Truck Fleet',
            ],
            [
                'label' => 'Pickup Requests',
                'letter' => 'P',
                'route' => 'coop.haul-requests.*',
                'route_url' => 'coop.haul-requests.index',
                'tooltip' => 'Pickup Requests',
            ],
            [
                'label' => 'Pickup Trips',
                'letter' => 'K',
                'route' => 'coop.pickups.*',
                'route_url' => 'coop.pickups.index',
                'tooltip' => 'Pickup Trips & Scheduling',
            ],
            [
                'label' => 'Procurement',
                'letter' => 'R',
                'route' => 'coop.procurement.*',
                'route_url' => 'coop.procurement.index',
                'tooltip' => 'Procurement / Receiving Confirmation',
            ],
            [
                'label' => 'Messages',
                'letter' => 'M',
                'route' => 'messages.*',
                'route_url' => 'messages.index',
                'tooltip' => 'Messages',
            ],
        ],
    ],

    'field_receiving' => [
        'section_label' => 'Receiving',
        'items' => [
            [
                'label' => 'Receiving',
                'letter' => 'R',
                'route' => 'field.receiving.*',
                'route_url' => 'field.receiving.index',
                'tooltip' => 'Receiving Queue',
            ],
            [
                'label' => 'Messages',
                'letter' => 'M',
                'route' => 'messages.*',
                'route_url' => 'messages.index',
                'tooltip' => 'Messages',
            ],
        ],
    ],

    'delivery_personnel' => [
        'section_label' => 'Delivery',
        'items' => [
            [
                'label' => 'My Trips',
                'letter' => 'T',
                'route' => 'delivery.trips.*',
                'route_url' => 'delivery.trips.index',
                'tooltip' => 'My Pickup & Delivery Trips',
            ],
            [
                'label' => 'Messages',
                'letter' => 'M',
                'route' => 'messages.*',
                'route_url' => 'messages.index',
                'tooltip' => 'Messages',
            ],
        ],
    ],

    'farmer' => [
        'section_label' => 'My Farm',
        'items' => [
            [
                'label' => 'Pickup Requests',
                'letter' => 'P',
                'route' => 'farmer.haul-requests.*',
                'route_url' => 'farmer.haul-requests.index',
                'tooltip' => 'Pickup Requests',
            ],
            [
                'label' => 'Messages',
                'letter' => 'M',
                'route' => 'messages.*',
                'route_url' => 'messages.index',
                'tooltip' => 'Messages',
            ],
        ],
    ],

    // Buyer has no routes registered yet (DashboardController redirects to
    // 'buyer.dashboard', which doesn't exist — every buyer login 500s).
    // Left empty on purpose until that module is built; not an oversight.
    'buyer' => [
        'section_label' => 'Buyer',
        'items' => [],
    ],

];
