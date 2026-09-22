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
                'label' => 'Reference Data',
                'letter' => 'R',
                'route' => 'admin.crops.*',
                'tooltip' => 'Reference Data',
                'children' => [
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
                ],
            ],
            [
                'label' => 'Audit Logs',
                'letter' => 'A',
                'route' => 'admin.audit-logs',
                'route_url' => 'admin.audit-logs',
                'tooltip' => 'Audit Logs',
            ],
            [
                'label' => 'Market Prices',
                'letter' => 'M',
                'route' => 'admin.market-prices.*',
                'route_url' => 'admin.market-prices.index',
                'tooltip' => 'Market Prices',
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
                'label' => 'Harvest & Pickups',
                'letter' => 'H',
                'route' => 'coop.haul-requests.*',
                'tooltip' => 'Harvest & Pickups',
                'children' => [
                    [
                        'label' => 'Haul Requests',
                        'letter' => 'H',
                        'route' => 'coop.haul-requests.*',
                        'route_url' => 'coop.haul-requests.index',
                        'tooltip' => 'Pickup Requests',
                    ],
                    [
                        'label' => 'Pickup Schedule',
                        'letter' => 'S',
                        'route' => 'coop.pickups.calendar',
                        'route_url' => 'coop.pickups.calendar',
                        'tooltip' => 'Pickup Scheduling Calendar',
                    ],
                    [
                        'label' => 'Pickup Trips',
                        'letter' => 'K',
                        'route' => 'coop.pickups.*',
                        'route_url' => 'coop.pickups.index',
                        'tooltip' => 'Pickup Trips & Planning',
                    ],
                ],
            ],
            [
                'label' => 'Logistics & Trips',
                'letter' => 'L',
                'route' => 'coop.tracking.*',
                'tooltip' => 'Logistics & Trips',
                'children' => [
                    [
                        'label' => 'Live Map',
                        'letter' => 'L',
                        'route' => 'coop.tracking.*',
                        'route_url' => 'coop.tracking.index',
                        'tooltip' => 'Location Monitoring',
                    ],
                    [
                        'label' => 'Deliveries',
                        'letter' => 'D',
                        'route' => 'coop.outbound.*',
                        'route_url' => 'coop.outbound.index',
                        'tooltip' => 'Outbound Delivery Planning',
                    ],
                ],
            ],
            [
                'label' => 'Receiving & Procurement',
                'letter' => 'R',
                'route' => 'coop.procurement.*',
                'tooltip' => 'Receiving & Procurement',
                'children' => [
                    [
                        'label' => 'Procurement',
                        'letter' => 'R',
                        'route' => 'coop.procurement.*',
                        'route_url' => 'coop.procurement.index',
                        'tooltip' => 'Procurement / Receiving Confirmation',
                    ],
                    [
                        'label' => 'Facility Receiving',
                        'letter' => 'V',
                        'route' => 'coop.facility-receiving.*',
                        'route_url' => 'coop.facility-receiving.index',
                        'tooltip' => 'Facility Receiving Verification',
                    ],
                ],
            ],
            [
                'label' => 'Crop Availability',
                'letter' => 'A',
                'route' => 'coop.availability.*',
                'route_url' => 'coop.availability.index',
                'tooltip' => 'Crop Availability / B2B Inventory',
            ],
            [
                'label' => 'Buyer Orders',
                'letter' => 'B',
                'route' => 'coop.buyer-orders.*',
                'route_url' => 'coop.buyer-orders.index',
                'tooltip' => 'Buyer Order Review',
            ],
            [
                'label' => 'Trucks',
                'letter' => 'T',
                'route' => 'coop.trucks.*',
                'route_url' => 'coop.trucks.index',
                'tooltip' => 'Truck Fleet',
            ],
            [
                'label' => 'Reports',
                'letter' => 'D',
                'route' => 'coop.reports.*',
                'route_url' => 'coop.reports.index',
                'tooltip' => 'Reports',
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

    'buyer' => [
        'section_label' => 'Buyer',
        'items' => [
            [
                'label' => 'Listings',
                'letter' => 'L',
                'route' => 'buyer.listings.*',
                'route_url' => 'buyer.listings.index',
                'tooltip' => 'Browse Crop Listings',
            ],
            [
                'label' => 'My Orders',
                'letter' => 'O',
                'route' => 'buyer.orders.*',
                'route_url' => 'buyer.orders.index',
                'tooltip' => 'My Orders',
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

];
