<x-layout>
    <div class="w-full max-w-7xl mx-auto">

        {{-- ============================================================
             PAGE HEADER
        ============================================================ --}}
        <header class="mb-8">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <p class="text-[10px] font-bold uppercase tracking-widest text-slate-400 dark:text-slate-550 mb-1">Admin / Platform settings</p>
                    <h1 class="text-2xl font-extrabold text-slate-800 dark:text-white heading-font tracking-tight">Crop Registry</h1>
                    <p class="text-sm text-slate-400 dark:text-slate-500 mt-1 font-semibold">Manage crop categories, crop names, and variety pricing. Updates reflect immediately on farmer harvest listings.</p>
                </div>
                <button onclick="openCreateEntityModal()"
                    class="bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold px-4 py-2.5 rounded-xl shadow-md shadow-emerald-600/10 hover:shadow-lg transition-all flex items-center gap-1.5 cursor-pointer self-start">
                    <span>➕</span> Add Registry Entity
                </button>
            </div>
        </header>

        {{-- ============================================================
             FLASH MESSAGES
        ============================================================ --}}
        @if (session('success'))
            <div class="mb-6 bg-emerald-50 dark:bg-emerald-950/30 border border-emerald-200 dark:border-emerald-800 text-emerald-700 dark:text-emerald-400 rounded-xl px-5 py-4 text-sm font-semibold flex items-center gap-2">
                <span>✓</span> {{ session('success') }}
            </div>
        @endif

        @if (session('error'))
            <div class="mb-6 bg-red-50 dark:bg-red-950/30 border border-red-200 dark:border-red-800 text-red-700 dark:text-red-400 rounded-xl px-5 py-4 text-sm font-semibold flex items-center gap-2">
                <span>✕</span> {{ session('error') }}
            </div>
        @endif

        {{-- ============================================================
             ANALYTICS / STATS OVERVIEW
        ============================================================ --}}
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-5 mb-8">
            {{-- Categories Card --}}
            <div class="bg-white dark:bg-slate-800 border border-slate-200/60 dark:border-slate-700/80 rounded-2xl p-5 flex items-center justify-between shadow-sm hover:shadow-md transition">
                <div>
                    <span class="text-[9px] font-extrabold text-slate-400 dark:text-slate-500 uppercase tracking-widest block mb-1">Crop Categories</span>
                    <span class="text-2xl font-black text-slate-800 dark:text-white heading-font">{{ $categories->count() }}</span>
                </div>
                <div class="w-10 h-10 rounded-xl bg-slate-50 dark:bg-slate-900 border border-slate-100 dark:border-slate-850 flex items-center justify-center text-lg">📂</div>
            </div>
            {{-- Crops Card --}}
            <div class="bg-white dark:bg-slate-800 border border-slate-200/60 dark:border-slate-700/80 rounded-2xl p-5 flex items-center justify-between shadow-sm hover:shadow-md transition">
                <div>
                    <span class="text-[9px] font-extrabold text-slate-400 dark:text-slate-500 uppercase tracking-widest block mb-1">Distinct Crops</span>
                    <span class="text-2xl font-black text-slate-800 dark:text-white heading-font">{{ $categories->sum(fn($c) => $c->crops->count()) }}</span>
                </div>
                <div class="w-10 h-10 rounded-xl bg-slate-50 dark:bg-slate-900 border border-slate-100 dark:border-slate-850 flex items-center justify-center text-lg">🌱</div>
            </div>
            {{-- Varieties Card --}}
            <div class="bg-white dark:bg-slate-800 border border-slate-200/60 dark:border-slate-700/80 rounded-2xl p-5 flex items-center justify-between shadow-sm hover:shadow-md transition">
                <div>
                    <span class="text-[9px] font-extrabold text-slate-400 dark:text-slate-500 uppercase tracking-widest block mb-1">Total Varieties</span>
                    <span class="text-2xl font-black text-slate-800 dark:text-white heading-font">{{ $categories->sum(fn($c) => $c->crops->sum(fn($cr) => $cr->varieties->count())) }}</span>
                </div>
                <div class="w-10 h-10 rounded-xl bg-slate-50 dark:bg-slate-900 border border-slate-100 dark:border-slate-850 flex items-center justify-center text-lg">🏷️</div>
            </div>
        </div>

        {{-- ============================================================
             SEARCH & FILTER BAR
        ============================================================ --}}
        <div class="bg-white dark:bg-slate-800 border border-slate-200/60 dark:border-slate-700/80 rounded-2xl p-4 mb-6 flex flex-col md:flex-row gap-4 items-center justify-between shadow-sm">
            <div class="relative w-full md:w-96">
                <span class="absolute left-4 top-1/2 -translate-y-1/2 text-slate-400 dark:text-slate-550 text-xs">🔍</span>
                <input type="text" id="cropSearchInput" onkeyup="filterRegistry()" placeholder="Search categories, crops or varieties..."
                    class="w-full bg-slate-50/50 dark:bg-slate-900/50 border border-slate-200 dark:border-slate-700 text-slate-800 dark:text-slate-200 text-xs rounded-xl pl-9 pr-4 py-2.5 focus:outline-none focus:border-emerald-500 transition" />
            </div>
            <div class="flex items-center gap-3 w-full md:w-auto self-end md:self-auto justify-end">
                <select id="cropCategoryFilter" onchange="filterRegistry()"
                    class="bg-slate-50/50 dark:bg-slate-900/50 border border-slate-200 dark:border-slate-700 text-slate-700 dark:text-slate-300 text-xs rounded-xl px-4 py-2.5 focus:outline-none focus:border-emerald-500 transition cursor-pointer">
                    <option value="">All Categories</option>
                    @foreach($categories as $cat)
                        <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                    @endforeach
                </select>
                <select id="cropStatusFilter" onchange="filterRegistry()"
                    class="bg-slate-50/50 dark:bg-slate-900/50 border border-slate-200 dark:border-slate-700 text-slate-700 dark:text-slate-300 text-xs rounded-xl px-4 py-2.5 focus:outline-none focus:border-emerald-500 transition cursor-pointer">
                    <option value="">All Statuses</option>
                    <option value="active">Active Only</option>
                    <option value="inactive">Inactive Only</option>
                </select>
            </div>
        </div>

        {{-- ============================================================
             REGISTRY CATALOG HIERARCHY
        ============================================================ --}}
        <div id="registryCatalog" class="space-y-4">

            @forelse ($categories as $category)
                <div class="category-card bg-white dark:bg-slate-800 border border-slate-200/70 dark:border-slate-700/80 rounded-2xl shadow-sm overflow-hidden transition-all duration-200"
                     data-category-id="{{ $category->id }}" data-category-name="{{ strtolower($category->name) }}" data-category-status="{{ $category->status }}">

                    {{-- Collapsible Category Row --}}
                    <div class="flex items-center justify-between px-6 py-4 border-b border-slate-100 dark:border-slate-700/60 bg-slate-50/50 dark:bg-slate-900/40 select-none">
                        <div class="flex items-center gap-3 cursor-pointer" onclick="toggleCategoryCollapse({{ $category->id }})">
                            <div class="w-8 h-8 rounded-lg bg-gradient-to-tr from-emerald-100 to-emerald-50 dark:from-emerald-950/30 dark:to-emerald-900/20 border border-emerald-200/50 dark:border-emerald-800/30 flex items-center justify-center text-[10px] font-extrabold text-emerald-700 dark:text-emerald-400 uppercase shrink-0">
                                {{ substr($category->name, 0, 2) }}
                            </div>
                            <div>
                                <div class="flex items-center gap-2">
                                    <p class="text-sm font-extrabold text-slate-800 dark:text-slate-200">{{ $category->name }}</p>
                                    <span class="text-slate-300 dark:text-slate-750">
                                        <svg id="chevron-cat-{{ $category->id }}" xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5 transform transition-transform duration-200 rotate-90" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
                                        </svg>
                                    </span>
                                </div>
                                @if ($category->description)
                                    <p class="text-[10px] text-slate-400 dark:text-slate-500 font-semibold mt-0.5">{{ $category->description }}</p>
                                @endif
                            </div>
                        </div>
                        <div class="flex items-center gap-3">
                            <span class="text-[9px] font-bold px-2 py-0.5 rounded-lg uppercase tracking-wide {{ $category->status === 'active' ? 'bg-emerald-50 dark:bg-emerald-950/20 text-emerald-600 dark:text-emerald-400' : 'bg-slate-100 dark:bg-slate-900/50 text-slate-450 dark:text-slate-500' }}">{{ $category->status }}</span>
                            <span class="text-[10px] text-slate-400 dark:text-slate-550 font-bold uppercase tracking-wider">{{ $category->crops->count() }} Crops</span>
                            <button
                                onclick="openEditCategory({{ $category->id }}, '{{ addslashes($category->name) }}', '{{ addslashes($category->description ?? '') }}', '{{ $category->status }}')"
                                class="inline-flex items-center justify-center w-8 h-8 rounded-lg bg-emerald-50 text-emerald-600 hover:bg-emerald-100 dark:bg-emerald-950/20 dark:hover:bg-emerald-950/40 dark:text-emerald-400 transition cursor-pointer"
                                title="Edit Category">
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                                </svg>
                            </button>
                            <button
                                onclick="confirmDeleteCategory({{ $category->id }}, '{{ addslashes($category->name) }}')"
                                class="inline-flex items-center justify-center w-8 h-8 rounded-lg bg-rose-50 text-rose-600 hover:bg-rose-100 dark:bg-rose-950/20 dark:hover:bg-rose-950/40 dark:text-rose-400 transition cursor-pointer"
                                title="Delete Category">
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                </svg>
                            </button>
                        </div>
                    </div>

                    {{-- Collapsible Crops Panel --}}
                    <div id="cat-body-{{ $category->id }}" class="category-body divide-y divide-slate-50 dark:divide-slate-700/30" data-paginate="crop" data-per-page="5">
                        @forelse ($category->crops as $crop)
                            <div class="crop-row paginate-item border-slate-50 dark:border-slate-700/30"
                                 data-crop-id="{{ $crop->id }}" data-crop-name="{{ strtolower($crop->name) }}" data-crop-status="{{ $crop->status }}">

                                {{-- Crop Row --}}
                                <div class="flex items-center justify-between px-6 py-3 pl-12 bg-white dark:bg-slate-800 hover:bg-slate-50/30 dark:hover:bg-slate-900/20 transition">
                                    <div class="flex items-center gap-2.5">
                                        <span class="w-1.5 h-1.5 rounded-full bg-slate-350 dark:bg-slate-650 flex-shrink-0"></span>
                                        <div>
                                            <p class="text-sm font-bold text-slate-700 dark:text-slate-200">{{ $crop->name }}</p>
                                            @if ($crop->description)
                                                <p class="text-[10px] text-slate-400 dark:text-slate-500 font-semibold mt-0.5">{{ $crop->description }}</p>
                                            @endif
                                        </div>
                                    </div>
                                    <div class="flex items-center gap-3">
                                        <span class="text-[9px] font-bold px-2 py-0.5 rounded-lg uppercase tracking-wide {{ $crop->status === 'active' ? 'bg-emerald-50 dark:bg-emerald-950/20 text-emerald-600 dark:text-emerald-400' : 'bg-slate-100 dark:bg-slate-900/50 text-slate-450 dark:text-slate-500' }}">{{ $crop->status }}</span>
                                        <span class="text-[10px] text-slate-400 dark:text-slate-550 font-bold uppercase tracking-wider">{{ $crop->varieties->count() }} Var.</span>
                                        <button
                                            onclick="openEditCrop({{ $crop->id }}, {{ $crop->crop_category_id }}, '{{ addslashes($crop->name) }}', '{{ addslashes($crop->description ?? '') }}', '{{ $crop->status }}')"
                                            class="inline-flex items-center justify-center w-8 h-8 rounded-lg bg-emerald-50 text-emerald-600 hover:bg-emerald-100 dark:bg-emerald-950/20 dark:hover:bg-emerald-950/40 dark:text-emerald-400 transition cursor-pointer"
                                            title="Edit Crop">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                                            </svg>
                                        </button>
                                        <button
                                            onclick="confirmDeleteCrop({{ $crop->id }}, '{{ addslashes($crop->name) }}')"
                                            class="inline-flex items-center justify-center w-8 h-8 rounded-lg bg-rose-50 text-rose-600 hover:bg-rose-100 dark:bg-rose-950/20 dark:hover:bg-rose-950/40 dark:text-rose-400 transition cursor-pointer"
                                            title="Delete Crop">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                            </svg>
                                        </button>
                                    </div>
                                </div>

                                {{-- Varieties Container under Crop --}}
                                <div class="crop-varieties-container divide-y divide-slate-50 dark:divide-slate-700/20" data-paginate="variety" data-per-page="8">
                                    @forelse ($crop->varieties as $variety)
                                        <div class="variety-row paginate-item flex items-center justify-between px-6 py-2.5 pl-20 bg-slate-50/20 dark:bg-slate-900/10 border-t border-slate-50 dark:border-slate-700/30 hover:bg-slate-50 dark:hover:bg-slate-900/20 transition"
                                             data-variety-id="{{ $variety->id }}" data-variety-name="{{ strtolower($variety->name) }}" data-variety-status="{{ $variety->status }}">
                                            <div class="flex items-center gap-2.5">
                                                <span class="w-1 h-1 rounded-full bg-slate-300 dark:bg-slate-750 flex-shrink-0"></span>
                                                <div>
                                                    <p class="text-xs text-slate-650 dark:text-slate-350 font-bold">{{ $variety->name }}</p>
                                                    @if ($variety->description)
                                                        <p class="text-[9px] text-slate-400 dark:text-slate-500 mt-0.5 font-medium">{{ $variety->description }}</p>
                                                    @endif
                                                </div>
                                            </div>
                                            <div class="flex items-center gap-4">
                                                <span class="text-xs font-mono font-black text-slate-700 dark:text-slate-350 bg-slate-100/60 dark:bg-slate-900/50 px-2 py-0.5 rounded border border-slate-200/20">₱{{ number_format($variety->price_per_kg, 2) }}/kg</span>
                                                <span class="text-[9px] font-bold px-2 py-0.5 rounded-lg uppercase tracking-wide {{ $variety->status === 'active' ? 'bg-emerald-50 dark:bg-emerald-950/20 text-emerald-600 dark:text-emerald-400' : 'bg-slate-100 dark:bg-slate-900/50 text-slate-450 dark:text-slate-500' }}">{{ $variety->status }}</span>
                                                <button
                                                    onclick="openEditVariety({{ $variety->id }}, {{ $variety->crop_id }}, '{{ addslashes($variety->name) }}', '{{ addslashes($variety->description ?? '') }}', '{{ $variety->price_per_kg }}', '{{ $variety->status }}')"
                                                    class="inline-flex items-center justify-center w-8 h-8 rounded-lg bg-emerald-50 text-emerald-600 hover:bg-emerald-100 dark:bg-emerald-950/20 dark:hover:bg-emerald-950/40 dark:text-emerald-400 transition cursor-pointer"
                                                    title="Edit Variety">
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                                                    </svg>
                                                </button>
                                                <button
                                                    onclick="confirmDeleteVariety({{ $variety->id }}, '{{ addslashes($variety->name) }}')"
                                                    class="inline-flex items-center justify-center w-8 h-8 rounded-lg bg-rose-50 text-rose-600 hover:bg-rose-100 dark:bg-rose-950/20 dark:hover:bg-rose-950/40 dark:text-rose-400 transition cursor-pointer"
                                                    title="Delete Variety">
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                    </svg>
                                                </button>
                                            </div>
                                        </div>
                                    @empty
                                        <div class="px-6 py-2 pl-20 bg-slate-50/20 dark:bg-slate-900/10 border-t border-slate-50 dark:border-slate-700/30">
                                            <p class="text-[10px] text-slate-405 dark:text-slate-550 italic font-semibold">No varieties registered for this crop.</p>
                                        </div>
                                    @endforelse
                                </div>
                                {{-- Variety Pagination Controls --}}
                                @if($crop->varieties->count() > 8)
                                <div class="pagination-controls flex items-center justify-between px-6 py-2 pl-20 bg-slate-50/30 dark:bg-slate-900/20 border-t border-slate-100 dark:border-slate-700/30">
                                    <span class="pagination-info text-[10px] text-slate-400 dark:text-slate-500 font-semibold"></span>
                                    <div class="flex items-center gap-1">
                                        <button type="button" class="pagination-prev inline-flex items-center justify-center w-7 h-7 rounded-lg bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-500 dark:text-slate-400 hover:bg-slate-50 dark:hover:bg-slate-700 text-[10px] font-bold transition disabled:opacity-30 disabled:cursor-not-allowed cursor-pointer" title="Previous">‹</button>
                                        <button type="button" class="pagination-next inline-flex items-center justify-center w-7 h-7 rounded-lg bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-500 dark:text-slate-400 hover:bg-slate-50 dark:hover:bg-slate-700 text-[10px] font-bold transition disabled:opacity-30 disabled:cursor-not-allowed cursor-pointer" title="Next">›</button>
                                    </div>
                                </div>
                                @endif

                            </div>
                        @empty
                            <div class="px-6 py-4 pl-12 bg-white dark:bg-slate-800">
                                <p class="text-[10px] text-slate-405 dark:text-slate-550 italic font-semibold">No crops registered under this category.</p>
                            </div>
                        @endforelse
                    </div>
                    {{-- Crop Pagination Controls --}}
                    @if($category->crops->count() > 5)
                    <div class="pagination-controls flex items-center justify-between px-6 py-2.5 bg-slate-50/40 dark:bg-slate-900/30 border-t border-slate-100 dark:border-slate-700/40">
                        <span class="pagination-info text-[10px] text-slate-400 dark:text-slate-500 font-semibold"></span>
                        <div class="flex items-center gap-1">
                            <button type="button" class="pagination-prev inline-flex items-center justify-center w-7 h-7 rounded-lg bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-500 dark:text-slate-400 hover:bg-slate-50 dark:hover:bg-slate-700 text-[10px] font-bold transition disabled:opacity-30 disabled:cursor-not-allowed cursor-pointer" title="Previous">‹</button>
                            <button type="button" class="pagination-next inline-flex items-center justify-center w-7 h-7 rounded-lg bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-500 dark:text-slate-400 hover:bg-slate-50 dark:hover:bg-slate-700 text-[10px] font-bold transition disabled:opacity-30 disabled:cursor-not-allowed cursor-pointer" title="Next">›</button>
                        </div>
                    </div>
                    @endif

                </div>
            @empty
                <div class="bg-white dark:bg-slate-800 border border-slate-200/60 dark:border-slate-700/80 rounded-2xl p-12 text-center shadow-sm">
                    <div class="text-3xl mb-3">📦</div>
                    <p class="text-slate-400 dark:text-slate-500 text-sm font-semibold">No crop categories found. Create one to populate the registry catalog.</p>
                </div>
            @endforelse

        </div>
    </div>

    {{-- ============================================================
         MODAL: CREATE REGISTRY ENTITY (Unified Tabbed Creator)
    ============================================================ --}}
    <div id="modal-create-entity" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-slate-950/40 backdrop-blur-sm">
        <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-2xl w-full max-w-md mx-4 border border-slate-100 dark:border-slate-700 p-7">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-base font-extrabold text-slate-800 dark:text-white heading-font">Add Registry Entity</h3>
                <button onclick="closeModal('modal-create-entity')" class="text-slate-400 hover:text-slate-800 dark:hover:text-white text-lg leading-none transition cursor-pointer">&times;</button>
            </div>
            
            {{-- Tabs --}}
            <div class="flex border-b border-slate-100 dark:border-slate-700 mb-5 text-xs font-bold text-slate-400">
                <button onclick="switchTab('tab-category')" id="btn-tab-category" class="flex-1 pb-2.5 border-b-2 border-emerald-600 text-emerald-600 select-none cursor-pointer">Category</button>
                <button onclick="switchTab('tab-crop')" id="btn-tab-crop" class="flex-1 pb-2.5 border-b-2 border-transparent hover:text-slate-700 dark:hover:text-slate-200 select-none cursor-pointer">Crop</button>
                <button onclick="switchTab('tab-variety')" id="btn-tab-variety" class="flex-1 pb-2.5 border-b-2 border-transparent hover:text-slate-700 dark:hover:text-slate-200 select-none cursor-pointer">Variety</button>
            </div>

            {{-- FORM: CATEGORY --}}
            <form id="tab-category" method="POST" action="{{ route('admin.crops.categories.store') }}" class="space-y-4">
                @csrf
                <div>
                    <label class="block text-[10px] font-extrabold text-slate-500 dark:text-slate-400 uppercase tracking-widest mb-1.5">Category Name <span class="text-red-400">*</span></label>
                    <input type="text" name="name" required placeholder="e.g. Root Crops"
                        class="w-full bg-slate-50/50 dark:bg-slate-900/50 border border-slate-200 dark:border-slate-700 text-slate-800 dark:text-slate-200 text-sm rounded-xl px-4 py-2.5 focus:outline-none focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20 transition" />
                </div>
                <div>
                    <label class="block text-[10px] font-extrabold text-slate-500 dark:text-slate-400 uppercase tracking-widest mb-1.5">Description</label>
                    <textarea name="description" rows="2" placeholder="Optional notes"
                        class="w-full bg-slate-50/50 dark:bg-slate-900/50 border border-slate-200 dark:border-slate-700 text-slate-800 dark:text-slate-200 text-sm rounded-xl px-4 py-2.5 focus:outline-none focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20 transition resize-none"></textarea>
                </div>
                <button type="submit" class="w-full bg-slate-800 hover:bg-slate-700 text-white text-xs font-bold py-2.5 rounded-xl transition shadow-sm cursor-pointer">Save Category</button>
            </form>

            {{-- FORM: CROP --}}
            <form id="tab-crop" method="POST" action="{{ route('admin.crops.store') }}" class="hidden space-y-4">
                @csrf
                <div>
                    <label class="block text-[10px] font-extrabold text-slate-500 dark:text-slate-400 uppercase tracking-widest mb-1.5">Parent Category <span class="text-red-400">*</span></label>
                    <select name="crop_category_id" required
                        class="w-full bg-slate-50/50 dark:bg-slate-900/50 border border-slate-200 dark:border-slate-700 text-slate-850 dark:text-slate-250 text-sm rounded-xl px-4 py-2.5 focus:outline-none focus:border-emerald-500 transition">
                        <option value="">— Select Category —</option>
                        @foreach ($categories as $cat)
                            <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-[10px] font-extrabold text-slate-500 dark:text-slate-400 uppercase tracking-widest mb-1.5">Crop Name <span class="text-red-400">*</span></label>
                    <input type="text" name="name" required placeholder="e.g. Potato"
                        class="w-full bg-slate-50/50 dark:bg-slate-900/50 border border-slate-200 dark:border-slate-700 text-slate-800 dark:text-slate-200 text-sm rounded-xl px-4 py-2.5 focus:outline-none focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20 transition" />
                </div>
                <div>
                    <label class="block text-[10px] font-extrabold text-slate-500 dark:text-slate-400 uppercase tracking-widest mb-1.5">Description</label>
                    <textarea name="description" rows="2" placeholder="Optional handling guidelines"
                        class="w-full bg-slate-50/50 dark:bg-slate-900/50 border border-slate-200 dark:border-slate-700 text-slate-800 dark:text-slate-200 text-sm rounded-xl px-4 py-2.5 focus:outline-none focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20 transition resize-none"></textarea>
                </div>
                <button type="submit" class="w-full bg-slate-800 hover:bg-slate-700 text-white text-xs font-bold py-2.5 rounded-xl transition shadow-sm cursor-pointer">Save Crop</button>
            </form>

            {{-- FORM: VARIETY --}}
            <form id="tab-variety" method="POST" action="{{ route('admin.crops.varieties.store') }}" class="hidden space-y-4">
                @csrf
                <div>
                    <label class="block text-[10px] font-extrabold text-slate-500 dark:text-slate-400 uppercase tracking-widest mb-1.5">Parent Crop <span class="text-red-400">*</span></label>
                    <select name="crop_id" required
                        class="w-full bg-slate-50/50 dark:bg-slate-900/50 border border-slate-200 dark:border-slate-700 text-slate-850 dark:text-slate-250 text-sm rounded-xl px-4 py-2.5 focus:outline-none focus:border-emerald-500 transition">
                        <option value="">— Select Crop —</option>
                        @foreach ($categories as $cat)
                            @if ($cat->crops->count())
                                <optgroup label="{{ $cat->name }}">
                                    @foreach ($cat->crops as $cr)
                                        <option value="{{ $cr->id }}">{{ $cr->name }}</option>
                                    @endforeach
                                </optgroup>
                            @endif
                        @endforeach
                    </select>
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-[10px] font-extrabold text-slate-500 dark:text-slate-400 uppercase tracking-widest mb-1.5">Variety Name <span class="text-red-400">*</span></label>
                        <input type="text" name="name" required placeholder="e.g. Yukon Gold"
                            class="w-full bg-slate-50/50 dark:bg-slate-900/50 border border-slate-200 dark:border-slate-700 text-slate-800 dark:text-slate-200 text-sm rounded-xl px-4 py-2.5 focus:outline-none focus:border-emerald-500 transition" />
                    </div>
                    <div>
                        <label class="block text-[10px] font-extrabold text-slate-500 dark:text-slate-400 uppercase tracking-widest mb-1.5">Price / kg <span class="text-red-400">*</span></label>
                        <div class="relative">
                            <span class="absolute left-4 top-1/2 -translate-y-1/2 text-slate-450 dark:text-slate-500 text-sm font-bold">₱</span>
                            <input type="number" name="price_per_kg" required step="0.01" min="0" placeholder="0.00"
                                class="w-full bg-slate-50/50 dark:bg-slate-900/50 border border-slate-200 dark:border-slate-700 text-slate-850 dark:text-slate-250 text-sm rounded-xl pl-8 pr-4 py-2.5 focus:outline-none focus:border-emerald-500 transition" />
                        </div>
                    </div>
                </div>
                <div>
                    <label class="block text-[10px] font-extrabold text-slate-500 dark:text-slate-400 uppercase tracking-widest mb-1.5">Description</label>
                    <textarea name="description" rows="2" placeholder="Optional notes"
                        class="w-full bg-slate-50/50 dark:bg-slate-900/50 border border-slate-200 dark:border-slate-700 text-slate-800 dark:text-slate-200 text-sm rounded-xl px-4 py-2.5 focus:outline-none focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20 transition resize-none"></textarea>
                </div>
                <button type="submit" class="w-full bg-slate-800 hover:bg-slate-700 text-white text-xs font-bold py-2.5 rounded-xl transition shadow-sm cursor-pointer">Save Variety</button>
            </form>
        </div>
    </div>

    {{-- ============================================================
         MODAL: EDIT CATEGORY
    ============================================================ --}}
    <div id="modal-edit-category" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-slate-950/40 backdrop-blur-sm">
        <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-2xl w-full max-w-md mx-4 border border-slate-100 dark:border-slate-700 p-7">
            <div class="flex items-center justify-between mb-5">
                <h3 class="text-base font-extrabold text-slate-800 dark:text-white heading-font">Edit Category</h3>
                <button onclick="closeModal('modal-edit-category')" class="text-slate-400 hover:text-slate-800 dark:hover:text-white text-lg leading-none transition cursor-pointer">&times;</button>
            </div>
            <form id="form-edit-category" method="POST" class="space-y-4">
                @csrf @method('PUT')
                <div>
                    <label class="block text-[10px] font-extrabold text-slate-500 dark:text-slate-400 uppercase tracking-widest mb-1.5">Name <span class="text-red-400">*</span></label>
                    <input type="text" id="edit-category-name" name="name" required
                        class="w-full bg-slate-50/50 dark:bg-slate-900/50 border border-slate-200 dark:border-slate-700 text-slate-800 dark:text-slate-200 text-sm rounded-xl px-4 py-2.5 focus:outline-none focus:border-emerald-500 transition" />
                </div>
                <div>
                    <label class="block text-[10px] font-extrabold text-slate-500 dark:text-slate-400 uppercase tracking-widest mb-1.5">Description</label>
                    <textarea id="edit-category-description" name="description" rows="2"
                        class="w-full bg-slate-50/50 dark:bg-slate-900/50 border border-slate-200 dark:border-slate-700 text-slate-800 dark:text-slate-200 text-sm rounded-xl px-4 py-2.5 focus:outline-none focus:border-emerald-500 transition resize-none"></textarea>
                </div>
                <div>
                    <label class="block text-[10px] font-extrabold text-slate-500 dark:text-slate-400 uppercase tracking-widest mb-1.5">Status <span class="text-red-400">*</span></label>
                    <select id="edit-category-status" name="status" required
                        class="w-full bg-slate-50/50 dark:bg-slate-900/50 border border-slate-200 dark:border-slate-700 text-slate-800 dark:text-slate-200 text-sm rounded-xl px-4 py-2.5 focus:outline-none focus:border-emerald-500 transition">
                        <option value="active">Active</option>
                        <option value="inactive">Inactive</option>
                    </select>
                </div>
                <div class="flex gap-3 pt-2">
                    <button type="submit" class="flex-1 bg-slate-800 hover:bg-slate-700 text-white text-xs font-bold py-2.5 rounded-xl transition shadow-sm cursor-pointer">Save Changes</button>
                    <button type="button" onclick="closeModal('modal-edit-category')" class="flex-1 bg-slate-100 dark:bg-slate-700 text-slate-700 dark:text-slate-300 text-xs font-bold py-2.5 rounded-xl hover:bg-slate-200 dark:hover:bg-slate-600 transition cursor-pointer">Cancel</button>
                </div>
            </form>
        </div>
    </div>

    {{-- ============================================================
         MODAL: EDIT CROP
    ============================================================ --}}
    <div id="modal-edit-crop" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-slate-950/40 backdrop-blur-sm">
        <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-2xl w-full max-w-md mx-4 border border-slate-100 dark:border-slate-700 p-7">
            <div class="flex items-center justify-between mb-5">
                <h3 class="text-base font-extrabold text-slate-800 dark:text-white heading-font">Edit Crop</h3>
                <button onclick="closeModal('modal-edit-crop')" class="text-slate-400 hover:text-slate-800 dark:hover:text-white text-lg leading-none transition cursor-pointer">&times;</button>
            </div>
            <form id="form-edit-crop" method="POST" class="space-y-4">
                @csrf @method('PUT')
                <div>
                    <label class="block text-[10px] font-extrabold text-slate-500 dark:text-slate-400 uppercase tracking-widest mb-1.5">Category <span class="text-red-400">*</span></label>
                    <select id="edit-crop-category" name="crop_category_id" required
                        class="w-full bg-slate-50/50 dark:bg-slate-900/50 border border-slate-200 dark:border-slate-700 text-slate-850 dark:text-slate-250 text-sm rounded-xl px-4 py-2.5 focus:outline-none focus:border-emerald-500 transition">
                        @foreach ($categories as $cat)
                            <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-[10px] font-extrabold text-slate-500 dark:text-slate-400 uppercase tracking-widest mb-1.5">Crop Name <span class="text-red-400">*</span></label>
                    <input type="text" id="edit-crop-name" name="name" required
                        class="w-full bg-slate-50/50 dark:bg-slate-900/50 border border-slate-200 dark:border-slate-700 text-slate-800 dark:text-slate-200 text-sm rounded-xl px-4 py-2.5 focus:outline-none focus:border-emerald-500 transition" />
                </div>
                <div>
                    <label class="block text-[10px] font-extrabold text-slate-500 dark:text-slate-400 uppercase tracking-widest mb-1.5">Description</label>
                    <textarea id="edit-crop-description" name="description" rows="2"
                        class="w-full bg-slate-50/50 dark:bg-slate-900/50 border border-slate-200 dark:border-slate-700 text-slate-800 dark:text-slate-200 text-sm rounded-xl px-4 py-2.5 focus:outline-none focus:border-emerald-500 transition resize-none"></textarea>
                </div>
                <div>
                    <label class="block text-[10px] font-extrabold text-slate-500 dark:text-slate-400 uppercase tracking-widest mb-1.5">Status <span class="text-red-400">*</span></label>
                    <select id="edit-crop-status" name="status" required
                        class="w-full bg-slate-50/50 dark:bg-slate-900/50 border border-slate-200 dark:border-slate-700 text-slate-800 dark:text-slate-200 text-sm rounded-xl px-4 py-2.5 focus:outline-none focus:border-emerald-500 transition">
                        <option value="active">Active</option>
                        <option value="inactive">Inactive</option>
                    </select>
                </div>
                <div class="flex gap-3 pt-2">
                    <button type="submit" class="flex-1 bg-slate-800 hover:bg-slate-700 text-white text-xs font-bold py-2.5 rounded-xl transition shadow-sm cursor-pointer">Save Changes</button>
                    <button type="button" onclick="closeModal('modal-edit-crop')" class="flex-1 bg-slate-100 dark:bg-slate-700 text-slate-700 dark:text-slate-300 text-xs font-bold py-2.5 rounded-xl hover:bg-slate-200 dark:hover:bg-slate-600 transition cursor-pointer">Cancel</button>
                </div>
            </form>
        </div>
    </div>

    {{-- ============================================================
         MODAL: EDIT VARIETY
    ============================================================ --}}
    <div id="modal-edit-variety" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-slate-950/40 backdrop-blur-sm">
        <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-2xl w-full max-w-md mx-4 border border-slate-100 dark:border-slate-700 p-7">
            <div class="flex items-center justify-between mb-5">
                <h3 class="text-base font-extrabold text-slate-800 dark:text-white heading-font">Edit Variety</h3>
                <button onclick="closeModal('modal-edit-variety')" class="text-slate-400 hover:text-slate-800 dark:hover:text-white text-lg leading-none transition cursor-pointer">&times;</button>
            </div>
            <form id="form-edit-variety" method="POST" class="space-y-4">
                @csrf @method('PUT')
                <div>
                    <label class="block text-[10px] font-extrabold text-slate-500 dark:text-slate-400 uppercase tracking-widest mb-1.5">Variety Name <span class="text-red-400">*</span></label>
                    <input type="text" id="edit-variety-name" name="name" required
                        class="w-full bg-slate-50/50 dark:bg-slate-900/50 border border-slate-200 dark:border-slate-700 text-slate-800 dark:text-slate-200 text-sm rounded-xl px-4 py-2.5 focus:outline-none focus:border-emerald-500 transition" />
                </div>
                <div>
                    <label class="block text-[10px] font-extrabold text-slate-500 dark:text-slate-400 uppercase tracking-widest mb-1.5">Price / kg <span class="text-red-400">*</span></label>
                    <div class="relative">
                        <span class="absolute left-4 top-1/2 -translate-y-1/2 text-slate-450 dark:text-slate-500 text-sm font-bold">₱</span>
                        <input type="number" id="edit-variety-price" name="price_per_kg" required step="0.01" min="0"
                            class="w-full bg-slate-50/50 dark:bg-slate-900/50 border border-slate-200 dark:border-slate-700 text-slate-850 dark:text-slate-250 text-sm rounded-xl pl-8 pr-4 py-2.5 focus:outline-none focus:border-emerald-500 transition" />
                    </div>
                </div>
                <div>
                    <label class="block text-[10px] font-extrabold text-slate-500 dark:text-slate-400 uppercase tracking-widest mb-1.5">Description</label>
                    <textarea id="edit-variety-description" name="description" rows="2"
                        class="w-full bg-slate-50/50 dark:bg-slate-900/50 border border-slate-200 dark:border-slate-700 text-slate-800 dark:text-slate-200 text-sm rounded-xl px-4 py-2.5 focus:outline-none focus:border-emerald-500 transition resize-none"></textarea>
                </div>
                <div>
                    <label class="block text-[10px] font-extrabold text-slate-500 dark:text-slate-400 uppercase tracking-widest mb-1.5">Status <span class="text-red-400">*</span></label>
                    <select id="edit-variety-status" name="status" required
                        class="w-full bg-slate-50/50 dark:bg-slate-900/50 border border-slate-200 dark:border-slate-700 text-slate-800 dark:text-slate-200 text-sm rounded-xl px-4 py-2.5 focus:outline-none focus:border-emerald-500 transition">
                        <option value="active">Active</option>
                        <option value="inactive">Inactive</option>
                    </select>
                </div>
                <div class="flex gap-3 pt-2">
                    <button type="submit" class="flex-1 bg-slate-800 hover:bg-slate-700 text-white text-xs font-bold py-2.5 rounded-xl transition shadow-sm cursor-pointer">Save Changes</button>
                    <button type="button" onclick="closeModal('modal-edit-variety')" class="flex-1 bg-slate-100 dark:bg-slate-700 text-slate-700 dark:text-slate-300 text-xs font-bold py-2.5 rounded-xl hover:bg-slate-200 dark:hover:bg-slate-600 transition cursor-pointer">Cancel</button>
                </div>
            </form>
        </div>
    </div>

    {{-- ============================================================
         MODAL: DELETE CONFIRMATION (Shared)
    ============================================================ --}}
    <div id="modal-delete-confirm" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-slate-950/40 backdrop-blur-sm">
        <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-2xl w-full max-w-sm mx-4 border border-red-100 dark:border-red-900/30 p-7">
            <p class="text-[10px] font-extrabold uppercase tracking-widest text-red-500 mb-2">Confirm Delete</p>
            <p id="delete-confirm-message" class="text-sm text-slate-600 dark:text-slate-300 mb-6 leading-relaxed">Are you sure?</p>
            <div class="flex gap-3">
                <form id="form-delete" method="POST" class="flex-1">
                    @csrf @method('DELETE')
                    <button type="submit" class="w-full bg-red-600 hover:bg-red-700 text-white text-xs font-bold py-2.5 rounded-xl transition shadow-sm cursor-pointer">Delete</button>
                </form>
                <button type="button" onclick="closeModal('modal-delete-confirm')" class="flex-1 bg-slate-100 dark:bg-slate-700 text-slate-700 dark:text-slate-300 text-xs font-bold py-2.5 rounded-xl hover:bg-slate-200 dark:hover:bg-slate-600 transition cursor-pointer">Cancel</button>
            </div>
        </div>
    </div>

    {{-- ============================================================
         JAVASCRIPT
    ============================================================ --}}
    <script>
        // --- MODAL UTILS ---
        function closeModal(id) {
            document.getElementById(id).classList.add('hidden');
        }

        function openModal(id) {
            document.getElementById(id).classList.remove('hidden');
        }

        // Close modal on backdrop click
        document.querySelectorAll('[id^="modal-"]').forEach(modal => {
            modal.addEventListener('click', function (e) {
                if (e.target === this) closeModal(this.id);
            });
        });

        // Close on Escape key
        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape') {
                document.querySelectorAll('[id^="modal-"]').forEach(modal => {
                    modal.classList.add('hidden');
                });
            }
        });

        // --- COLLAPSE CATEGORIES ---
        function toggleCategoryCollapse(catId) {
            const body = document.getElementById(`cat-body-${catId}`);
            const chevron = document.getElementById(`chevron-cat-${catId}`);
            if (body.classList.contains('hidden')) {
                body.classList.remove('hidden');
                chevron.classList.add('rotate-90');
            } else {
                body.classList.add('hidden');
                chevron.classList.remove('rotate-90');
            }
        }

        // --- OPEN UNIFIED CREATOR MODAL ---
        function openCreateEntityModal() {
            openModal('modal-create-entity');
            switchTab('tab-category');
        }

        // Switch between tabs in Unified Creator
        function switchTab(tabId) {
            const tabs = ['tab-category', 'tab-crop', 'tab-variety'];
            tabs.forEach(t => {
                const el = document.getElementById(t);
                const btn = document.getElementById(`btn-${t}`);
                if (t === tabId) {
                    el.classList.remove('hidden');
                    btn.classList.add('border-emerald-600', 'text-emerald-600');
                    btn.classList.remove('border-transparent');
                } else {
                    el.classList.add('hidden');
                    btn.classList.remove('border-emerald-600', 'text-emerald-600');
                    btn.classList.add('border-transparent');
                }
            });
        }

        // --- EDIT CATEGORY ---
        function openEditCategory(id, name, description, status) {
            const form = document.getElementById('form-edit-category');
            form.action = `/admin/crops/categories/${id}`;
            document.getElementById('edit-category-name').value = name;
            document.getElementById('edit-category-description').value = description;
            document.getElementById('edit-category-status').value = status;
            openModal('modal-edit-category');
        }

        // --- EDIT CROP ---
        function openEditCrop(id, categoryId, name, description, status) {
            const form = document.getElementById('form-edit-crop');
            form.action = `/admin/crops/${id}`;
            document.getElementById('edit-crop-category').value = categoryId;
            document.getElementById('edit-crop-name').value = name;
            document.getElementById('edit-crop-description').value = description;
            document.getElementById('edit-crop-status').value = status;
            openModal('modal-edit-crop');
        }

        // --- EDIT VARIETY ---
        function openEditVariety(id, cropId, name, description, price, status) {
            const form = document.getElementById('form-edit-variety');
            form.action = `/admin/crops/varieties/${id}`;
            document.getElementById('edit-variety-name').value = name;
            document.getElementById('edit-variety-description').value = description;
            document.getElementById('edit-variety-price').value = price;
            document.getElementById('edit-variety-status').value = status;
            openModal('modal-edit-variety');
        }

        // --- DELETE CATEGORY ---
        function confirmDeleteCategory(id, name) {
            document.getElementById('delete-confirm-message').textContent =
                `Delete category "${name}"? This will fail if crops are still assigned to it.`;
            document.getElementById('form-delete').action = `/admin/crops/categories/${id}`;
            openModal('modal-delete-confirm');
        }

        // --- DELETE CROP ---
        function confirmDeleteCrop(id, name) {
            document.getElementById('delete-confirm-message').textContent =
                `Delete crop "${name}"? This will fail if varieties are still assigned to it.`;
            document.getElementById('form-delete').action = `/admin/crops/${id}`;
            openModal('modal-delete-confirm');
        }

        // --- DELETE VARIETY ---
        function confirmDeleteVariety(id, name) {
            document.getElementById('delete-confirm-message').textContent =
                `Delete variety "${name}"? This will fail if active harvest listings reference it.`;
            document.getElementById('form-delete').action = `/admin/crops/varieties/${id}`;
            openModal('modal-delete-confirm');
        }

        // --- REAL-TIME SEARCH & FILTER FUNCTION ---
        function filterRegistry() {
            const query = document.getElementById('cropSearchInput').value.toLowerCase().trim();
            const categoryFilter = document.getElementById('cropCategoryFilter').value;
            const statusFilter = document.getElementById('cropStatusFilter').value;

            const categoryCards = document.querySelectorAll('.category-card');

            categoryCards.forEach(card => {
                const cardCatId = card.getAttribute('data-category-id');
                const cardCatName = card.getAttribute('data-category-name');
                const cardCatStatus = card.getAttribute('data-category-status');

                // Filter Category itself
                const matchCategorySelect = !categoryFilter || cardCatId === categoryFilter;
                const matchStatusSelect = !statusFilter || cardCatStatus === statusFilter;

                const cropRows = card.querySelectorAll('.crop-row');
                let visibleCropsInCard = 0;

                cropRows.forEach(cropRow => {
                    const cropName = cropRow.getAttribute('data-crop-name');
                    const cropStatus = cropRow.getAttribute('data-crop-status');

                    const varietyRows = cropRow.querySelectorAll('.variety-row');
                    let visibleVarietiesInCrop = 0;

                    varietyRows.forEach(varietyRow => {
                        const varietyName = varietyRow.getAttribute('data-variety-name');
                        const varietyStatus = varietyRow.getAttribute('data-variety-status');

                        // Check search query matches category, crop or variety name
                        const matchesQuery = !query || 
                            cardCatName.includes(query) || 
                            cropName.includes(query) || 
                            varietyName.includes(query);

                        // Check status filter
                        const matchesStatus = !statusFilter || varietyStatus === statusFilter;

                        if (matchesQuery && matchesStatus) {
                            varietyRow.classList.remove('hidden');
                            visibleVarietiesInCrop++;
                        } else {
                            varietyRow.classList.add('hidden');
                        }
                    });

                    // Crop is visible if crop itself matches query/status OR it has visible varieties under it
                    const cropMatchesQuery = !query || cardCatName.includes(query) || cropName.includes(query);
                    const cropMatchesStatus = !statusFilter || cropStatus === statusFilter;

                    const showCrop = (cropMatchesQuery && cropMatchesStatus) || visibleVarietiesInCrop > 0;

                    if (showCrop) {
                        cropRow.classList.remove('hidden');
                        visibleCropsInCard++;
                    } else {
                        cropRow.classList.add('hidden');
                    }
                });

                // Category card is visible if it matches category filter AND matches status filter AND (matches query/status itself OR has visible crops inside)
                const catMatchesQuery = !query || cardCatName.includes(query);
                const catMatchesStatus = !statusFilter || cardCatStatus === statusFilter;

                const showCategory = matchCategorySelect && matchStatusSelect && (catMatchesQuery || visibleCropsInCard > 0);

                if (showCategory) {
                    card.classList.remove('hidden');
                } else {
                    card.classList.add('hidden');
                }
            });
        }
        // --- CLIENT-SIDE PAGINATION ---
        function initPagination() {
            document.querySelectorAll('[data-paginate]').forEach(container => {
                const perPage = parseInt(container.getAttribute('data-per-page')) || 5;
                const items = container.querySelectorAll(':scope > .paginate-item');
                if (items.length <= perPage) return;

                // Find the pagination-controls sibling
                const controls = container.nextElementSibling;
                if (!controls || !controls.classList.contains('pagination-controls')) return;

                let currentPage = 1;
                const totalPages = Math.ceil(items.length / perPage);

                const infoEl = controls.querySelector('.pagination-info');
                const prevBtn = controls.querySelector('.pagination-prev');
                const nextBtn = controls.querySelector('.pagination-next');

                function renderPage() {
                    const start = (currentPage - 1) * perPage;
                    const end = start + perPage;
                    items.forEach((item, idx) => {
                        item.style.display = (idx >= start && idx < end) ? '' : 'none';
                    });
                    infoEl.textContent = `Page ${currentPage} of ${totalPages} (${items.length} total)`;
                    prevBtn.disabled = currentPage <= 1;
                    nextBtn.disabled = currentPage >= totalPages;
                }

                prevBtn.addEventListener('click', () => { if (currentPage > 1) { currentPage--; renderPage(); } });
                nextBtn.addEventListener('click', () => { if (currentPage < totalPages) { currentPage++; renderPage(); } });

                renderPage();
            });
        }

        // Init pagination on page load
        document.addEventListener('DOMContentLoaded', initPagination);
    </script>
</x-layout>
