<x-layout>
<div class="w-full max-w-7xl mx-auto">

    <!-- Nice Admin Page Header -->
    <header class="mb-8">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <p class="text-[10px] font-bold uppercase tracking-widest text-slate-400 dark:text-slate-550 mb-1">Admin / Security control</p>
                <h1 class="text-2xl font-extrabold text-slate-800 dark:text-white heading-font tracking-tight">User Management</h1>
                <p class="text-sm text-slate-400 dark:text-slate-500 mt-1 font-semibold">View, create, edit, and archive all registered platform accounts</p>
            </div>
            <div class="flex items-center gap-3">
                <span class="text-[10px] font-bold uppercase tracking-widest text-emerald-700 dark:text-emerald-400 bg-emerald-50 dark:bg-emerald-950/20 px-3 py-1.5 rounded-lg border border-emerald-500/10 dark:border-emerald-500/20">{{ $users->count() }} Total Accounts</span>
                <button onclick="openCreateUserModal()" 
                    class="bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold px-4 py-2.5 rounded-xl shadow-md shadow-emerald-600/10 hover:shadow-lg transition-all flex items-center gap-1.5 cursor-pointer">
                    <span>➕</span> Add User
                </button>
            </div>
        </div>
    </header>

    @if (session('success'))
        <div class="mb-6 bg-emerald-50 dark:bg-emerald-950/30 border border-emerald-200 dark:border-emerald-800 text-emerald-700 dark:text-emerald-400 rounded-xl px-5 py-4 text-sm font-semibold flex items-center gap-2">
            <span>✅</span> {{ session('success') }}
        </div>
    @endif

    @if (session('error'))
        <div class="mb-6 bg-red-50 dark:bg-red-950/30 border border-red-200 dark:border-red-800 text-red-700 dark:text-red-400 rounded-xl px-5 py-4 text-sm font-semibold flex items-center gap-2">
            <span>⚠️</span> {{ session('error') }}
        </div>
    @endif

    @if ($errors->any())
        <div class="mb-6 bg-red-50 dark:bg-red-950/30 border border-red-250 dark:border-red-900/30 text-red-700 dark:text-red-400 rounded-xl px-5 py-4 text-sm font-semibold">
            <span class="font-bold">Errors occurred:</span>
            <ul class="list-disc list-inside mt-1 font-medium text-xs space-y-0.5">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <!-- Nice Admin Card Table -->
    <div class="bg-white dark:bg-slate-800 border border-slate-200/70 dark:border-slate-700/80 rounded-2xl shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-sm text-left" style="min-width: 900px;">
                <thead>
                    <tr class="border-b border-slate-100 dark:border-slate-700/60 bg-slate-50/50 dark:bg-slate-900/40">
                        <th class="px-6 py-4 text-[10px] font-extrabold text-slate-500 dark:text-slate-500 uppercase tracking-widest">Name</th>
                        <th class="px-6 py-4 text-[10px] font-extrabold text-slate-500 dark:text-slate-500 uppercase tracking-widest">Email</th>
                        <th class="px-6 py-4 text-[10px] font-extrabold text-slate-500 dark:text-slate-500 uppercase tracking-widest">Role</th>
                        <th class="px-6 py-4 text-[10px] font-extrabold text-slate-500 dark:text-slate-500 uppercase tracking-widest">Details</th>
                        <th class="px-6 py-4 text-[10px] font-extrabold text-slate-500 dark:text-slate-500 uppercase tracking-widest">Status</th>
                        <th class="px-6 py-4 text-[10px] font-extrabold text-slate-500 dark:text-slate-500 uppercase tracking-widest text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-50 dark:divide-slate-700/40">
                    @foreach($users as $user)
                    <tr class="hover:bg-slate-50/50 dark:hover:bg-slate-900/40 transition">
                        <td class="px-6 py-4">
                            <div class="flex items-center gap-3">
                                <div class="w-8 h-8 rounded-lg bg-gradient-to-tr from-slate-100 to-slate-50 dark:from-slate-700 dark:to-slate-800 border border-slate-200 dark:border-slate-750 flex items-center justify-center text-[10px] font-extrabold text-slate-600 dark:text-slate-300 uppercase shrink-0">{{ substr($user->name, 0, 2) }}</div>
                                <div class="truncate max-w-[180px]">
                                    <span class="font-bold text-slate-800 dark:text-slate-200 text-sm block leading-tight">{{ $user->name }}</span>
                                    <span class="text-[9px] text-slate-400 dark:text-slate-550 font-bold uppercase tracking-wider">Joined {{ $user->created_at->format('M d, Y') }}</span>
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-4 text-slate-500 dark:text-slate-400 text-xs font-semibold">{{ $user->email }}</td>
                        <td class="px-6 py-4">
                            <span class="bg-slate-100 dark:bg-slate-900/50 text-slate-700 dark:text-slate-300 text-[10px] font-bold px-2.5 py-1 rounded-lg uppercase tracking-wide">
                                {{ str_replace('_', ' ', $user->role) }}
                            </span>
                        </td>
                        <td class="px-6 py-4 text-slate-500 dark:text-slate-400 text-xs font-medium">
                            @if($user->role === 'farmer')
                                <div class="space-y-0.5">
                                    <p>📞 {{ $user->farmerProfile?->phone ?? 'No Phone' }}</p>
                                    <p class="text-[10px] text-slate-400">📍 {{ $user->farmerProfile?->farm_location ?? 'No Location' }}</p>
                                    <p class="text-[9px] bg-slate-50 dark:bg-slate-900 px-1.5 py-0.5 rounded border border-slate-100 dark:border-slate-800 inline-block capitalize font-bold">
                                        {{ $user->farmerProfile?->affiliation_type ?? 'Independent' }} 
                                        @if($user->farmerProfile?->cooperative)
                                            ({{ $user->farmerProfile->cooperative->company_name }})
                                        @endif
                                    </p>
                                </div>
                            @elseif($user->role === 'logistics_partner')
                                <div class="space-y-0.5">
                                    <p class="font-bold text-slate-700 dark:text-slate-300">{{ $user->logisticsProfile?->company_name ?? 'No Company' }}</p>
                                    <p>📞 {{ $user->logisticsProfile?->phone ?? 'No Phone' }}</p>
                                    <p class="text-[9px] uppercase tracking-wide text-slate-400 font-extrabold">{{ $user->logisticsProfile?->logistics_type ?? 'Company' }}</p>
                                </div>
                            @elseif($user->role === 'driver')
                                <div class="space-y-0.5">
                                    <p>💳 License: {{ $user->driverProfile?->license_number ?? 'No License' }}</p>
                                    <p>📞 {{ $user->driverProfile?->phone ?? 'No Phone' }}</p>
                                    @if($user->driverProfile?->partner)
                                        <p class="text-[10px] text-emerald-600 dark:text-emerald-400 font-bold">🏢 {{ $user->driverProfile->partner->company_name }}</p>
                                    @endif
                                </div>
                            @else
                                <span class="text-slate-400 text-[10px] italic">—</span>
                            @endif
                        </td>
                        <td class="px-6 py-4">
                            @if($user->status === 'active')
                                <span class="bg-emerald-50 dark:bg-emerald-950/20 text-emerald-700 dark:text-emerald-400 text-[10px] font-bold px-2.5 py-1 rounded-lg uppercase tracking-wide">Active</span>
                            @else
                                <span class="bg-red-50 dark:bg-red-950/20 text-red-650 dark:text-red-400 text-[10px] font-bold px-2.5 py-1 rounded-lg uppercase tracking-wide">Archived</span>
                            @endif
                        </td>
                        <td class="px-6 py-4 text-right">
                            <div class="flex items-center justify-end gap-3.5">
                                @if($user->role !== 'admin')
                                    <button onclick="openEditUserModal({{ json_encode([
                                        'id' => $user->id,
                                        'name' => $user->name,
                                        'email' => $user->email,
                                        'role' => $user->role,
                                        'status' => $user->status,
                                        'phone' => $user->farmerProfile?->phone ?? $user->logisticsProfile?->phone ?? $user->driverProfile?->phone ?? '',
                                        'farm_location' => $user->farmerProfile?->farm_location ?? '',
                                        'affiliation_type' => $user->farmerProfile?->affiliation_type ?? '',
                                        'cooperative_id' => $user->farmerProfile?->cooperative_id ?? '',
                                        'company_name' => $user->logisticsProfile?->company_name ?? '',
                                        'business_permit_no' => $user->logisticsProfile?->business_permit_no ?? '',
                                        'logistics_type' => $user->logisticsProfile?->logistics_type ?? '',
                                        'cda_registration_no' => $user->logisticsProfile?->cda_registration_no ?? '',
                                        'license_number' => $user->driverProfile?->license_number ?? '',
                                        'partner_id' => $user->driverProfile?->partner_id ?? '',
                                        'vehicle_type' => $user->driverProfile?->vehicle_type ?? '',
                                    ]) }})"
                                    class="inline-flex items-center justify-center w-8 h-8 rounded-lg bg-emerald-50 text-emerald-600 hover:bg-emerald-100 dark:bg-emerald-950/20 dark:hover:bg-emerald-950/40 dark:text-emerald-400 transition"
                                    title="Edit User">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                                        </svg>
                                    </button>

                                    @if($user->status === 'active')
                                        @if($user->role === 'farmer')
                                            <button type="button" onclick="checkAndArchive({{ $user->id }}, '{{ addslashes($user->name) }}')" class="inline-flex items-center justify-center w-8 h-8 rounded-lg bg-amber-50 text-amber-600 hover:bg-amber-100 dark:bg-amber-950/20 dark:hover:bg-amber-950/40 dark:text-amber-400 transition" title="Archive User">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                                                </svg>
                                            </button>
                                        @else
                                            <form method="POST" action="{{ route('admin.users.status', $user->id) }}" class="inline" id="archive-form-{{ $user->id }}">
                                                @csrf
                                                <button type="button" onclick="swalConfirm(this.closest('form'), {title: 'Archive User?', text: 'Archive {{ addslashes($user->name) }}?', confirmText: 'Yes, archive', icon: 'warning', confirmColor: '#f59e0b'})" class="inline-flex items-center justify-center w-8 h-8 rounded-lg bg-amber-50 text-amber-600 hover:bg-amber-100 dark:bg-amber-950/20 dark:hover:bg-amber-950/40 dark:text-amber-400 transition" title="Archive User">
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                                                    </svg>
                                                </button>
                                            </form>
                                        @endif
                                    @else
                                        <form method="POST" action="{{ route('admin.users.status', $user->id) }}" class="inline" id="reactivate-form-{{ $user->id }}">
                                            @csrf
                                            <button type="button" onclick="swalConfirm(this.closest('form'), {title: 'Reactivate User?', text: 'Reactivate {{ addslashes($user->name) }}?', confirmText: 'Yes, reactivate', icon: 'question', confirmColor: '#10b981'})" class="inline-flex items-center justify-center w-8 h-8 rounded-lg bg-emerald-50 text-emerald-600 hover:bg-emerald-100 dark:bg-emerald-950/20 dark:hover:bg-emerald-950/40 dark:text-emerald-400 transition" title="Reactivate User">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0l3.181 3.183a8.25 8.25 0 0013.803-3.7M4.031 9.865a8.25 8.25 0 0113.803-3.7l3.181 3.182m0-4.991v4.99" />
                                                </svg>
                                            </button>
                                        </form>
                                    @endif
                                @else
                                    <span class="text-slate-300 dark:text-slate-700 text-xs">—</span>
                                @endif
                            </div>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>

{{-- CRUD User Form Modal (Unified Create/Edit) --}}
<div id="userModal" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-slate-950/40 backdrop-blur-sm overflow-y-auto">
    <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-2xl w-full max-w-lg mx-4 my-8 p-7 border border-slate-100 dark:border-slate-700 relative">
        <div class="flex items-center justify-between mb-5">
            <h3 id="modalTitle" class="text-lg font-extrabold text-slate-800 dark:text-white heading-font">Create New User</h3>
            <button onclick="closeUserModal()" class="text-slate-400 hover:text-slate-800 dark:hover:text-white text-lg leading-none transition cursor-pointer">&times;</button>
        </div>

        <form id="userForm" method="POST" class="space-y-4">
            @csrf
            <input type="hidden" id="formMethod" name="_method" value="POST">

            {{-- Row 1: Name & Email --}}
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-[10px] font-extrabold text-slate-500 dark:text-slate-400 uppercase tracking-widest mb-1.5">Full Name <span class="text-red-400">*</span></label>
                    <input type="text" id="user-name" name="name" required placeholder="John Doe"
                        class="w-full bg-slate-50/50 dark:bg-slate-900/50 border border-slate-200 dark:border-slate-700 text-slate-800 dark:text-slate-200 text-sm rounded-xl px-4 py-2.5 focus:outline-none focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20 transition" />
                </div>
                <div>
                    <label class="block text-[10px] font-extrabold text-slate-500 dark:text-slate-400 uppercase tracking-widest mb-1.5">Email Address <span class="text-red-400">*</span></label>
                    <input type="email" id="user-email" name="email" required placeholder="john@example.com"
                        class="w-full bg-slate-50/50 dark:bg-slate-900/50 border border-slate-200 dark:border-slate-700 text-slate-800 dark:text-slate-200 text-sm rounded-xl px-4 py-2.5 focus:outline-none focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20 transition" />
                </div>
            </div>

            {{-- Row 2: Password & Role --}}
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-[10px] font-extrabold text-slate-500 dark:text-slate-400 uppercase tracking-widest mb-1.5">Password <span id="passRequiredStar" class="text-red-400">*</span></label>
                    <input type="password" id="user-password" name="password" placeholder="••••••••"
                        class="w-full bg-slate-50/50 dark:bg-slate-900/50 border border-slate-200 dark:border-slate-700 text-slate-800 dark:text-slate-200 text-sm rounded-xl px-4 py-2.5 focus:outline-none focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20 transition" />
                    <p id="passwordHint" class="text-[10px] text-slate-400 mt-1 hidden font-semibold">Leave blank to keep current password.</p>
                </div>
                <div>
                    <label class="block text-[10px] font-extrabold text-slate-500 dark:text-slate-400 uppercase tracking-widest mb-1.5">Account Role <span class="text-red-400">*</span></label>
                    <select id="user-role" name="role" required onchange="handleRoleChange(this.value)"
                        class="w-full bg-slate-50/50 dark:bg-slate-900/50 border border-slate-200 dark:border-slate-700 text-slate-800 dark:text-slate-200 text-sm rounded-xl px-4 py-2.5 focus:outline-none focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20 transition">
                        <option value="admin">Administrator</option>
                        <option value="farmer">Farmer Cooperative Member</option>
                        <option value="logistics_partner">Logistics Freight Partner</option>
                        <option value="driver">Freight Vehicle Driver</option>
                    </select>
                </div>
            </div>

            {{-- Row 3: Status & Phone (Phone is shared by Farmer, Logistics, Driver) --}}
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-[10px] font-extrabold text-slate-500 dark:text-slate-400 uppercase tracking-widest mb-1.5">Account Status <span class="text-red-400">*</span></label>
                    <select id="user-status" name="status" required
                        class="w-full bg-slate-50/50 dark:bg-slate-900/50 border border-slate-200 dark:border-slate-700 text-slate-800 dark:text-slate-200 text-sm rounded-xl px-4 py-2.5 focus:outline-none focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20 transition">
                        <option value="active">Active</option>
                        <option value="inactive">Archived</option>
                    </select>
                </div>
                <div id="phone-container" class="hidden">
                    <label class="block text-[10px] font-extrabold text-slate-500 dark:text-slate-400 uppercase tracking-widest mb-1.5">Phone Number <span class="text-red-400">*</span></label>
                    <input type="text" id="user-phone" name="phone" placeholder="e.g. +639123456789"
                        class="w-full bg-slate-50/50 dark:bg-slate-900/50 border border-slate-200 dark:border-slate-700 text-slate-800 dark:text-slate-200 text-sm rounded-xl px-4 py-2.5 focus:outline-none focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20 transition" />
                </div>
            </div>

            <hr class="border-slate-100 dark:border-slate-700/60 my-2" />

            {{-- DYNAMIC SECTION 1: Farmer Cooperative Details --}}
            <div id="farmer-fields" class="hidden space-y-4">
                <div>
                    <label class="block text-[10px] font-extrabold text-slate-500 dark:text-slate-400 uppercase tracking-widest mb-1.5">Farm Location (Address) <span class="text-red-400">*</span></label>
                    <input type="text" id="user-farm-location" name="farm_location" placeholder="e.g. Barangay Tupi, South Cotabato"
                        class="w-full bg-slate-50/50 dark:bg-slate-900/50 border border-slate-200 dark:border-slate-700 text-slate-800 dark:text-slate-200 text-sm rounded-xl px-4 py-2.5 focus:outline-none focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20 transition" />
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-[10px] font-extrabold text-slate-500 dark:text-slate-400 uppercase tracking-widest mb-1.5">Affiliation Type <span class="text-red-400">*</span></label>
                        <select id="user-affiliation-type" name="affiliation_type" onchange="handleAffiliationChange(this.value)"
                            class="w-full bg-slate-50/50 dark:bg-slate-900/50 border border-slate-200 dark:border-slate-700 text-slate-800 dark:text-slate-200 text-sm rounded-xl px-4 py-2.5 focus:outline-none focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20 transition">
                            <option value="independent">Independent Farmer</option>
                            <option value="cooperative">Under Cooperative</option>
                        </select>
                    </div>
                    <div id="cooperative-select-container" class="hidden">
                        <label class="block text-[10px] font-extrabold text-slate-500 dark:text-slate-400 uppercase tracking-widest mb-1.5">Select Cooperative <span class="text-red-400">*</span></label>
                        <select id="user-cooperative-id" name="cooperative_id"
                            class="w-full bg-slate-50/50 dark:bg-slate-900/50 border border-slate-200 dark:border-slate-700 text-slate-800 dark:text-slate-200 text-sm rounded-xl px-4 py-2.5 focus:outline-none focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20 transition">
                            <option value="">— Select Cooperative —</option>
                            @foreach($cooperatives as $coop)
                                @if($coop->isCooperative())
                                    <option value="{{ $coop->id }}">{{ $coop->company_name }}</option>
                                @endif
                            @endforeach
                        </select>
                    </div>
                </div>
            </div>

            {{-- DYNAMIC SECTION 2: Logistics Partner Details --}}
            <div id="logistics-fields" class="hidden space-y-4">
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-[10px] font-extrabold text-slate-500 dark:text-slate-400 uppercase tracking-widest mb-1.5">Company / Cooperative Name <span class="text-red-400">*</span></label>
                        <input type="text" id="user-company-name" name="company_name" placeholder="e.g. Gensan Logistics"
                            class="w-full bg-slate-50/50 dark:bg-slate-900/50 border border-slate-200 dark:border-slate-700 text-slate-800 dark:text-slate-200 text-sm rounded-xl px-4 py-2.5 focus:outline-none focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20 transition" />
                    </div>
                    <div>
                        <label class="block text-[10px] font-extrabold text-slate-500 dark:text-slate-400 uppercase tracking-widest mb-1.5">Business Permit No. <span class="text-red-400">*</span></label>
                        <input type="text" id="user-business-permit-no" name="business_permit_no" placeholder="e.g. BP-2026-10294"
                            class="w-full bg-slate-50/50 dark:bg-slate-900/50 border border-slate-200 dark:border-slate-700 text-slate-800 dark:text-slate-200 text-sm rounded-xl px-4 py-2.5 focus:outline-none focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20 transition" />
                    </div>
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-[10px] font-extrabold text-slate-500 dark:text-slate-400 uppercase tracking-widest mb-1.5">Logistics Type <span class="text-red-400">*</span></label>
                        <select id="user-logistics-type" name="logistics_type" onchange="handleLogisticsTypeChange(this.value)"
                            class="w-full bg-slate-50/50 dark:bg-slate-900/50 border border-slate-200 dark:border-slate-700 text-slate-800 dark:text-slate-200 text-sm rounded-xl px-4 py-2.5 focus:outline-none focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20 transition">
                            <option value="company">Logistics Corporation</option>
                            <option value="cooperative">Transport Cooperative</option>
                        </select>
                    </div>
                    <div id="cda-registration-container" class="hidden">
                        <label class="block text-[10px] font-extrabold text-slate-500 dark:text-slate-400 uppercase tracking-widest mb-1.5">CDA Registration No. <span class="text-red-400">*</span></label>
                        <input type="text" id="user-cda-registration-no" name="cda_registration_no" placeholder="CDA-REG-94829"
                            class="w-full bg-slate-50/50 dark:bg-slate-900/50 border border-slate-200 dark:border-slate-700 text-slate-800 dark:text-slate-200 text-sm rounded-xl px-4 py-2.5 focus:outline-none focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20 transition" />
                    </div>
                </div>
            </div>

            {{-- DYNAMIC SECTION 3: Driver Details --}}
            <div id="driver-fields" class="hidden space-y-4">
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-[10px] font-extrabold text-slate-500 dark:text-slate-400 uppercase tracking-widest mb-1.5">Professional License No. <span class="text-red-400">*</span></label>
                        <input type="text" id="user-license-number" name="license_number" placeholder="e.g. N01-12-345678"
                            class="w-full bg-slate-50/50 dark:bg-slate-900/50 border border-slate-200 dark:border-slate-700 text-slate-800 dark:text-slate-200 text-sm rounded-xl px-4 py-2.5 focus:outline-none focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20 transition" />
                    </div>
                    <div>
                        <label class="block text-[10px] font-extrabold text-slate-500 dark:text-slate-400 uppercase tracking-widest mb-1.5">Logistics Employer <span class="text-red-400">*</span></label>
                        <select id="user-partner-id" name="partner_id"
                            class="w-full bg-slate-50/50 dark:bg-slate-900/50 border border-slate-200 dark:border-slate-700 text-slate-800 dark:text-slate-200 text-sm rounded-xl px-4 py-2.5 focus:outline-none focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20 transition">
                            <option value="">— Select Employer —</option>
                            @foreach($cooperatives as $partner)
                                <option value="{{ $partner->id }}">{{ $partner->company_name }} ({{ $partner->logistics_type }})</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div>
                    <label class="block text-[10px] font-extrabold text-slate-500 dark:text-slate-400 uppercase tracking-widest mb-1.5">Assigned Vehicle Category</label>
                    <input type="text" id="user-vehicle-type" name="vehicle_type" placeholder="e.g. 10-Wheeler Wing Van (Optional)"
                        class="w-full bg-slate-50/50 dark:bg-slate-900/50 border border-slate-200 dark:border-slate-700 text-slate-800 dark:text-slate-200 text-sm rounded-xl px-4 py-2.5 focus:outline-none focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20 transition" />
                </div>
            </div>

            {{-- Submit and Cancel --}}
            <div class="flex gap-3 pt-3">
                <button type="submit" class="flex-1 bg-slate-800 hover:bg-slate-700 text-white text-xs font-bold py-2.5 rounded-xl transition shadow-sm cursor-pointer">
                    Save User
                </button>
                <button type="button" onclick="closeUserModal()" class="flex-1 bg-slate-100 dark:bg-slate-700 text-slate-700 dark:text-slate-300 text-xs font-bold py-2.5 rounded-xl hover:bg-slate-200 dark:hover:bg-slate-600 transition cursor-pointer">
                    Cancel
                </button>
            </div>
        </form>
    </div>
</div>

{{-- Warning Modal --}}
<script>
    // --- Active Harvest Checks for Farmer Archiving (SweetAlert) ---
    function checkAndArchive(userId, userName) {
        swalConfirm(() => {
            fetch(`/admin/users/${userId}/status`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Accept': 'application/json',
                },
                body: JSON.stringify({})
            })
            .then(res => res.json())
            .then(data => {
                if (data.requires_confirmation) {
                    const count = data.active_harvest_count;
                    Swal.fire({
                        icon: 'warning',
                        title: 'Active Listings Detected',
                        html: `<p class="mb-2">${userName} currently has <strong>${count}</strong> active harvest listing${count > 1 ? 's' : ''}.</p><p class="text-red-500 text-sm font-bold">Proceeding will cancel all active listings and remove this farmer's pins from the logistics map.</p>`,
                        showCancelButton: true,
                        confirmButtonText: 'Archive Anyway',
                        cancelButtonText: 'Cancel',
                        confirmButtonColor: '#ef4444',
                        cancelButtonColor: '#64748b',
                        background: document.documentElement.classList.contains('dark') ? '#1e293b' : '#fff',
                        color: document.documentElement.classList.contains('dark') ? '#e2e8f0' : '#1e293b',
                        customClass: { popup: 'rounded-xl shadow-2xl' },
                        reverseButtons: true
                    }).then((result) => {
                        if (result.isConfirmed) {
                            // Submit force archive via AJAX
                            fetch(`/admin/users/${userId}/status`, {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/json',
                                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                                    'Accept': 'application/json',
                                },
                                body: JSON.stringify({ force: true })
                            })
                            .then(res => res.json())
                            .then(data => {
                                if (data.success) {
                                    Swal.fire({
                                        icon: 'success',
                                        title: 'Archived',
                                        text: `${userName} has been archived.`,
                                        timer: 2000,
                                        showConfirmButton: false,
                                        toast: true,
                                        position: 'top-end',
                                        background: document.documentElement.classList.contains('dark') ? '#1e293b' : '#fff',
                                        color: document.documentElement.classList.contains('dark') ? '#e2e8f0' : '#1e293b',
                                        iconColor: '#10b981'
                                    });
                                    setTimeout(() => window.location.reload(), 1500);
                                }
                            });
                        }
                    });
                } else if (data.success) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Archived',
                        text: `${userName} has been archived.`,
                        timer: 2000,
                        showConfirmButton: false,
                        toast: true,
                        position: 'top-end',
                        background: document.documentElement.classList.contains('dark') ? '#1e293b' : '#fff',
                        color: document.documentElement.classList.contains('dark') ? '#e2e8f0' : '#1e293b',
                        iconColor: '#10b981'
                    });
                    setTimeout(() => window.location.reload(), 1500);
                }
            });
        }, {
            title: 'Archive User?',
            text: `Archive ${userName}?`,
            confirmText: 'Yes, archive',
            icon: 'warning',
            confirmColor: '#f59e0b'
        });
    }

    // --- User CRUD Modals & Dynamic Form Fields ---
    const userModal = document.getElementById('userModal');
    const userForm = document.getElementById('userForm');
    const formMethod = document.getElementById('formMethod');
    const modalTitle = document.getElementById('modalTitle');
    const passRequiredStar = document.getElementById('passRequiredStar');
    const passwordHint = document.getElementById('passwordHint');

    function openCreateUserModal() {
        // Reset form
        userForm.reset();
        modalTitle.textContent = "Create New User";
        formMethod.value = "POST";
        userForm.action = "{{ route('admin.users.store') }}";
        passRequiredStar.classList.remove('hidden');
        passwordHint.classList.add('hidden');
        document.getElementById('user-password').required = true;

        // Toggle dynamic fields
        handleRoleChange('admin');

        // Show modal
        userModal.classList.remove('hidden');
    }

    function openEditUserModal(user) {
        userForm.reset();
        modalTitle.textContent = `Edit User — ${user.name}`;
        formMethod.value = "PUT";
        userForm.action = `/admin/users/${user.id}`;
        passRequiredStar.classList.add('hidden');
        passwordHint.classList.remove('hidden');
        document.getElementById('user-password').required = false;

        // Load core data
        document.getElementById('user-name').value = user.name;
        document.getElementById('user-email').value = user.email;
        document.getElementById('user-role').value = user.role;
        document.getElementById('user-status').value = user.status;

        // Setup dynamic views and populate
        handleRoleChange(user.role);

        if (user.role === 'farmer') {
            document.getElementById('user-phone').value = user.phone;
            document.getElementById('user-farm-location').value = user.farm_location;
            document.getElementById('user-affiliation-type').value = user.affiliation_type;
            handleAffiliationChange(user.affiliation_type);
            document.getElementById('user-cooperative-id').value = user.cooperative_id;
        } else if (user.role === 'logistics_partner') {
            document.getElementById('user-phone').value = user.phone;
            document.getElementById('user-company-name').value = user.company_name;
            document.getElementById('user-business-permit-no').value = user.business_permit_no;
            document.getElementById('user-logistics-type').value = user.logistics_type;
            handleLogisticsTypeChange(user.logistics_type);
            document.getElementById('user-cda-registration-no').value = user.cda_registration_no;
        } else if (user.role === 'driver') {
            document.getElementById('user-phone').value = user.phone;
            document.getElementById('user-license-number').value = user.license_number;
            document.getElementById('user-partner-id').value = user.partner_id;
            document.getElementById('user-vehicle-type').value = user.vehicle_type;
        }

        userModal.classList.remove('hidden');
    }

    function closeUserModal() {
        userModal.classList.add('hidden');
    }

    userModal.addEventListener('click', function(e) {
        if (e.target === this) closeUserModal();
    });

    // Close on Escape key
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') {
            closeUserModal();
            closeModal();
        }
    });

    // Toggle logic for roles
    function handleRoleChange(role) {
        const phoneContainer = document.getElementById('phone-container');
        const farmerFields = document.getElementById('farmer-fields');
        const logisticsFields = document.getElementById('logistics-fields');
        const driverFields = document.getElementById('driver-fields');

        // Hide all
        phoneContainer.classList.add('hidden');
        farmerFields.classList.add('hidden');
        logisticsFields.classList.add('hidden');
        driverFields.classList.add('hidden');

        // Remove required fields for hidden blocks
        toggleInputsRequired(farmerFields, false);
        toggleInputsRequired(logisticsFields, false);
        toggleInputsRequired(driverFields, false);
        document.getElementById('user-phone').required = false;

        if (role === 'farmer') {
            phoneContainer.classList.remove('hidden');
            farmerFields.classList.remove('hidden');
            document.getElementById('user-phone').required = true;
            toggleInputsRequired(farmerFields, true);
            // Affiliation check
            handleAffiliationChange(document.getElementById('user-affiliation-type').value);
        } else if (role === 'logistics_partner') {
            phoneContainer.classList.remove('hidden');
            logisticsFields.classList.remove('hidden');
            document.getElementById('user-phone').required = true;
            toggleInputsRequired(logisticsFields, true);
            // Logistics type check
            handleLogisticsTypeChange(document.getElementById('user-logistics-type').value);
        } else if (role === 'driver') {
            phoneContainer.classList.remove('hidden');
            driverFields.classList.remove('hidden');
            document.getElementById('user-phone').required = true;
            toggleInputsRequired(driverFields, true);
        }
    }

    function handleAffiliationChange(type) {
        const coopContainer = document.getElementById('cooperative-select-container');
        const coopSelect = document.getElementById('user-cooperative-id');
        if (type === 'cooperative') {
            coopContainer.classList.remove('hidden');
            coopSelect.required = true;
        } else {
            coopContainer.classList.add('hidden');
            coopSelect.required = false;
        }
    }

    function handleLogisticsTypeChange(type) {
        const cdaContainer = document.getElementById('cda-registration-container');
        const cdaInput = document.getElementById('user-cda-registration-no');
        if (type === 'cooperative') {
            cdaContainer.classList.remove('hidden');
            cdaInput.required = true;
        } else {
            cdaContainer.classList.add('hidden');
            cdaInput.required = false;
        }
    }

    function toggleInputsRequired(parentEl, isRequired) {
        const inputs = parentEl.querySelectorAll('input, select, textarea');
        inputs.forEach(input => {
            // Only toggle if not explicitly excluded or conditionally handled
            if (input.id !== 'user-cooperative-id' && input.id !== 'user-cda-registration-no' && input.id !== 'user-vehicle-type') {
                input.required = isRequired;
            }
        });
    }
</script>
</x-layout>
