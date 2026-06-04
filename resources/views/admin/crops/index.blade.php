<x-layout>
    <div class="w-full max-w-7xl mx-auto">

        {{-- ============================================================
             PAGE HEADER
        ============================================================ --}}
        <header class="mb-8">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <p class="text-[10px] font-bold uppercase tracking-widest text-slate-400 dark:text-slate-500 mb-1">Admin / Crop Management</p>
                    <h1 class="text-2xl font-extrabold text-slate-800 dark:text-white heading-font tracking-tight">Crop Registry</h1>
                    <p class="text-sm text-slate-400 dark:text-slate-500 mt-1 font-semibold">Manage crop categories, crops, and varieties. Changes reflect immediately in farmer harvest forms.</p>
                </div>
                <span class="text-[10px] font-bold uppercase tracking-widest text-emerald-700 dark:text-emerald-400 bg-emerald-50 dark:bg-emerald-950/20 px-3 py-1.5 rounded-lg border border-emerald-500/10 dark:border-emerald-500/20 self-start">{{ $categories->count() }} Categories</span>
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
             TOP PANEL — THREE ENTRY FORMS
        ============================================================ --}}
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-5 mb-10">

            {{-- ADD CATEGORY --}}
            <div class="bg-white dark:bg-slate-800 border border-slate-200/70 dark:border-slate-700/80 rounded-2xl shadow-sm p-6">
                <h2 class="text-[10px] font-extrabold uppercase tracking-widest text-slate-500 dark:text-slate-400 mb-4">New Category</h2>
                <form method="POST" action="{{ route('admin.crops.categories.store') }}" class="space-y-3">
                    @csrf
                    <div>
                        <label class="block text-[10px] font-extrabold text-slate-500 dark:text-slate-400 uppercase tracking-widest mb-1">Name <span class="text-red-400">*</span></label>
                        <input type="text" name="name" value="{{ old('name') }}" placeholder="e.g. Fruits"
                            class="w-full bg-slate-50/50 dark:bg-slate-900/50 border border-slate-200 dark:border-slate-750 text-slate-800 dark:text-slate-200 text-sm rounded-xl px-4 py-2.5 placeholder-slate-400 dark:placeholder-slate-505 focus:outline-none focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20 transition" />
                        @error('name') <p class="text-red-500 text-xs mt-1 font-semibold">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-[10px] font-extrabold text-slate-500 dark:text-slate-400 uppercase tracking-widest mb-1">Description</label>
                        <textarea name="description" rows="2" placeholder="Optional notes"
                            class="w-full bg-slate-50/50 dark:bg-slate-900/50 border border-slate-200 dark:border-slate-750 text-slate-800 dark:text-slate-200 text-sm rounded-xl px-4 py-2.5 placeholder-slate-400 dark:placeholder-slate-550 focus:outline-none focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20 transition resize-none"
                        >{{ old('description') }}</textarea>
                    </div>
                    <button type="submit" class="w-full bg-slate-800 text-white text-xs font-bold py-2.5 rounded-xl hover:bg-slate-700 transition shadow-sm">
                        Add Category
                    </button>
                </form>
            </div>

            {{-- ADD CROP --}}
            <div class="bg-white dark:bg-slate-800 border border-slate-200/70 dark:border-slate-700/80 rounded-2xl shadow-sm p-6">
                <h2 class="text-[10px] font-extrabold uppercase tracking-widest text-slate-500 dark:text-slate-400 mb-4">New Crop</h2>
                <form method="POST" action="{{ route('admin.crops.store') }}" class="space-y-3">
                    @csrf
                    <div>
                        <label class="block text-[10px] font-extrabold text-slate-500 dark:text-slate-400 uppercase tracking-widest mb-1">Category <span class="text-red-400">*</span></label>
                        <select name="crop_category_id"
                            class="w-full bg-slate-50/50 dark:bg-slate-900/50 border border-slate-200 dark:border-slate-750 text-slate-800 dark:text-slate-200 text-sm rounded-xl px-4 py-2.5 focus:outline-none focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20 transition">
                            <option value="">— Select category —</option>
                            @foreach ($categories as $cat)
                                <option value="{{ $cat->id }}" {{ old('crop_category_id') == $cat->id ? 'selected' : '' }}>{{ $cat->name }}</option>
                            @endforeach
                        </select>
                        @error('crop_category_id') <p class="text-red-500 text-xs mt-1 font-semibold">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-[10px] font-extrabold text-slate-500 dark:text-slate-400 uppercase tracking-widest mb-1">Crop Name <span class="text-red-400">*</span></label>
                        <input type="text" name="name" value="{{ old('name') }}" placeholder="e.g. Pineapple"
                            class="w-full bg-slate-50/50 dark:bg-slate-900/50 border border-slate-200 dark:border-slate-750 text-slate-800 dark:text-slate-200 text-sm rounded-xl px-4 py-2.5 placeholder-slate-400 dark:placeholder-slate-550 focus:outline-none focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20 transition" />
                        @error('name') <p class="text-red-500 text-xs mt-1 font-semibold">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-[10px] font-extrabold text-slate-500 dark:text-slate-400 uppercase tracking-widest mb-1">Description</label>
                        <textarea name="description" rows="2" placeholder="Optional notes"
                            class="w-full bg-slate-50/50 dark:bg-slate-900/50 border border-slate-200 dark:border-slate-750 text-slate-800 dark:text-slate-200 text-sm rounded-xl px-4 py-2.5 placeholder-slate-400 dark:placeholder-slate-550 focus:outline-none focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20 transition resize-none"
                        >{{ old('description') }}</textarea>
                    </div>
                    <button type="submit" class="w-full bg-slate-800 text-white text-xs font-bold py-2.5 rounded-xl hover:bg-slate-700 transition shadow-sm">
                        Add Crop
                    </button>
                </form>
            </div>

            {{-- ADD VARIETY --}}
            <div class="bg-white dark:bg-slate-800 border border-slate-200/70 dark:border-slate-700/80 rounded-2xl shadow-sm p-6">
                <h2 class="text-[10px] font-extrabold uppercase tracking-widest text-slate-500 dark:text-slate-400 mb-4">New Variety</h2>
                <form method="POST" action="{{ route('admin.crops.varieties.store') }}" class="space-y-3">
                    @csrf
                    <div>
                        <label class="block text-[10px] font-extrabold text-slate-500 dark:text-slate-400 uppercase tracking-widest mb-1">Crop <span class="text-red-400">*</span></label>
                        <select name="crop_id"
                            class="w-full bg-slate-50/50 dark:bg-slate-900/50 border border-slate-200 dark:border-slate-750 text-slate-800 dark:text-slate-200 text-sm rounded-xl px-4 py-2.5 focus:outline-none focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20 transition">
                            <option value="">— Select crop —</option>
                            @foreach ($categories as $cat)
                                @if ($cat->crops->count())
                                    <optgroup label="{{ $cat->name }}">
                                        @foreach ($cat->crops as $crop)
                                            <option value="{{ $crop->id }}" {{ old('crop_id') == $crop->id ? 'selected' : '' }}>{{ $crop->name }}</option>
                                        @endforeach
                                    </optgroup>
                                @endif
                            @endforeach
                        </select>
                        @error('crop_id') <p class="text-red-500 text-xs mt-1 font-semibold">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-[10px] font-extrabold text-slate-500 dark:text-slate-400 uppercase tracking-widest mb-1">Variety Name <span class="text-red-400">*</span></label>
                        <input type="text" name="name" value="{{ old('name') }}" placeholder="e.g. Queen"
                            class="w-full bg-slate-50/50 dark:bg-slate-900/50 border border-slate-200 dark:border-slate-750 text-slate-800 dark:text-slate-200 text-sm rounded-xl px-4 py-2.5 placeholder-slate-400 dark:placeholder-slate-550 focus:outline-none focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20 transition" />
                        @error('name') <p class="text-red-500 text-xs mt-1 font-semibold">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-[10px] font-extrabold text-slate-500 dark:text-slate-400 uppercase tracking-widest mb-1">Price / kg <span class="text-red-400">*</span></label>
                        <div class="relative">
                            <span class="absolute left-4 top-1/2 -translate-y-1/2 text-slate-400 dark:text-slate-550 text-sm font-bold">₱</span>
                            <input type="number" name="price_per_kg" value="{{ old('price_per_kg', '0.00') }}" step="0.01" min="0"
                                class="w-full bg-slate-50/50 dark:bg-slate-900/50 border border-slate-200 dark:border-slate-750 text-slate-800 dark:text-slate-200 text-sm rounded-xl pl-8 pr-4 py-2.5 placeholder-slate-400 dark:placeholder-slate-550 focus:outline-none focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20 transition" />
                        </div>
                        @error('price_per_kg') <p class="text-red-500 text-xs mt-1 font-semibold">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-[10px] font-extrabold text-slate-500 dark:text-slate-400 uppercase tracking-widest mb-1">Description</label>
                        <textarea name="description" rows="2" placeholder="Optional notes"
                            class="w-full bg-slate-50/50 dark:bg-slate-900/50 border border-slate-200 dark:border-slate-750 text-slate-800 dark:text-slate-200 text-sm rounded-xl px-4 py-2.5 placeholder-slate-400 dark:placeholder-slate-550 focus:outline-none focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20 transition resize-none"
                        >{{ old('description') }}</textarea>
                    </div>
                    <button type="submit" class="w-full bg-emerald-600 text-white text-xs font-bold py-2.5 rounded-xl hover:bg-emerald-700 transition shadow-sm">
                        Add Variety
                    </button>
                </form>
            </div>

        </div>

        {{-- ============================================================
             REGISTRY TABLE — CATEGORIES → CROPS → VARIETIES
        ============================================================ --}}
        <div class="space-y-5">

            @forelse ($categories as $category)
                <div class="bg-white dark:bg-slate-800 border border-slate-200/70 dark:border-slate-700/80 rounded-2xl shadow-sm overflow-hidden">

                    {{-- CATEGORY ROW --}}
                    <div class="flex items-center justify-between px-6 py-4 border-b border-slate-100 dark:border-slate-700/60 bg-slate-50/50 dark:bg-slate-900/40">
                        <div class="flex items-center gap-3">
                            <div class="w-8 h-8 rounded-lg bg-gradient-to-tr from-emerald-100 to-emerald-50 dark:from-emerald-950/20 dark:to-emerald-900/20 border border-emerald-200/50 dark:border-emerald-800/30 flex items-center justify-center text-[10px] font-extrabold text-emerald-700 dark:text-emerald-400 uppercase">{{ substr($category->name, 0, 2) }}</div>
                            <div>
                                <p class="text-sm font-extrabold text-slate-800 dark:text-slate-200">{{ $category->name }}</p>
                                @if ($category->description)
                                    <p class="text-[10px] text-slate-400 dark:text-slate-500 font-semibold mt-0.5">{{ $category->description }}</p>
                                @endif
                            </div>
                        </div>
                        <div class="flex items-center gap-3">
                            <span class="text-[10px] font-bold px-2 py-0.5 rounded-lg uppercase tracking-wide {{ $category->status === 'active' ? 'bg-emerald-50 dark:bg-emerald-950/20 text-emerald-600 dark:text-emerald-400' : 'bg-slate-100 dark:bg-slate-900/50 text-slate-400 dark:text-slate-500' }}">{{ $category->status }}</span>
                            <span class="text-[10px] text-slate-400 dark:text-slate-500 font-semibold">{{ $category->crops->count() }} crop{{ $category->crops->count() !== 1 ? 's' : '' }}</span>
                            <button
                                onclick="openEditCategory({{ $category->id }}, '{{ addslashes($category->name) }}', '{{ addslashes($category->description ?? '') }}', '{{ $category->status }}')"
                                class="text-[10px] text-slate-500 hover:text-slate-800 dark:text-slate-400 dark:hover:text-white border border-slate-200 dark:border-slate-700 rounded-lg px-3 py-1 font-bold uppercase tracking-wide transition hover:bg-slate-100 dark:hover:bg-slate-700">Edit</button>
                            <button
                                onclick="confirmDeleteCategory({{ $category->id }}, '{{ addslashes($category->name) }}')"
                                class="text-[10px] text-red-500 hover:text-red-700 border border-red-100 dark:border-red-900/30 rounded-lg px-3 py-1 font-bold uppercase tracking-wide transition hover:bg-red-50 dark:hover:bg-red-950/20">Delete</button>
                        </div>
                    </div>

                    {{-- CROPS UNDER CATEGORY --}}
                    @forelse ($category->crops as $crop)
                        <div class="border-b border-slate-50 dark:border-slate-700/40 last:border-b-0">

                            {{-- CROP ROW --}}
                            <div class="flex items-center justify-between px-6 py-3 pl-14 bg-white dark:bg-slate-800 hover:bg-slate-50/30 dark:hover:bg-slate-900/20 transition">
                                <div class="flex items-center gap-3">
                                    <span class="w-1.5 h-1.5 rounded-full bg-slate-300 dark:bg-slate-600 flex-shrink-0"></span>
                                    <div>
                                        <p class="text-sm font-bold text-slate-700 dark:text-slate-200">{{ $crop->name }}</p>
                                        @if ($crop->description)
                                            <p class="text-[10px] text-slate-400 dark:text-slate-500 font-medium mt-0.5">{{ $crop->description }}</p>
                                        @endif
                                    </div>
                                </div>
                                <div class="flex items-center gap-3">
                                    <span class="text-[10px] font-bold px-2 py-0.5 rounded-lg uppercase tracking-wide {{ $crop->status === 'active' ? 'bg-emerald-50 dark:bg-emerald-950/20 text-emerald-600 dark:text-emerald-400' : 'bg-slate-100 dark:bg-slate-900/50 text-slate-400 dark:text-slate-500' }}">{{ $crop->status }}</span>
                                    <span class="text-[10px] text-slate-400 dark:text-slate-500 font-semibold">{{ $crop->varieties->count() }} var.</span>
                                    <button
                                        onclick="openEditCrop({{ $crop->id }}, {{ $crop->crop_category_id }}, '{{ addslashes($crop->name) }}', '{{ addslashes($crop->description ?? '') }}', '{{ $crop->status }}')"
                                        class="text-[10px] text-slate-500 hover:text-slate-800 dark:text-slate-400 dark:hover:text-white border border-slate-200 dark:border-slate-700 rounded-lg px-3 py-1 font-bold uppercase tracking-wide transition hover:bg-slate-100 dark:hover:bg-slate-700">Edit</button>
                                    <button
                                        onclick="confirmDeleteCrop({{ $crop->id }}, '{{ addslashes($crop->name) }}')"
                                        class="text-[10px] text-red-500 hover:text-red-700 border border-red-100 dark:border-red-900/30 rounded-lg px-3 py-1 font-bold uppercase tracking-wide transition hover:bg-red-50 dark:hover:bg-red-950/20">Delete</button>
                                </div>
                            </div>

                            {{-- VARIETIES UNDER CROP --}}
                            @forelse ($crop->varieties as $variety)
                                <div class="flex items-center justify-between px-6 py-2.5 pl-20 bg-slate-50/30 dark:bg-slate-900/10 border-t border-slate-50 dark:border-slate-700/40 hover:bg-slate-50 dark:hover:bg-slate-900/20 transition">
                                    <div class="flex items-center gap-3">
                                        <span class="w-1 h-1 rounded-full bg-slate-200 dark:bg-slate-700 flex-shrink-0"></span>
                                        <div>
                                            <p class="text-xs text-slate-600 dark:text-slate-300 font-semibold">{{ $variety->name }}</p>
                                            @if ($variety->description)
                                                <p class="text-[10px] text-slate-400 dark:text-slate-500 mt-0.5">{{ $variety->description }}</p>
                                            @endif
                                        </div>
                                    </div>
                                    <div class="flex items-center gap-3">
                                        <span class="text-xs font-mono font-bold text-slate-700 dark:text-slate-300">₱{{ number_format($variety->price_per_kg, 2) }}/kg</span>
                                        <span class="text-[10px] font-bold px-2 py-0.5 rounded-lg uppercase tracking-wide {{ $variety->status === 'active' ? 'bg-emerald-50 dark:bg-emerald-950/20 text-emerald-600 dark:text-emerald-400' : 'bg-slate-100 dark:bg-slate-900/50 text-slate-400 dark:text-slate-500' }}">{{ $variety->status }}</span>
                                        <button
                                            onclick="openEditVariety({{ $variety->id }}, {{ $variety->crop_id }}, '{{ addslashes($variety->name) }}', '{{ addslashes($variety->description ?? '') }}', '{{ $variety->price_per_kg }}', '{{ $variety->status }}')"
                                            class="text-[10px] text-slate-500 hover:text-slate-800 dark:text-slate-400 dark:hover:text-white border border-slate-200 dark:border-slate-700 rounded-lg px-3 py-1 font-bold uppercase tracking-wide transition hover:bg-slate-100 dark:hover:bg-slate-700">Edit</button>
                                        <button
                                            onclick="confirmDeleteVariety({{ $variety->id }}, '{{ addslashes($variety->name) }}')"
                                            class="text-[10px] text-red-500 hover:text-red-700 border border-red-100 dark:border-red-900/30 rounded-lg px-3 py-1 font-bold uppercase tracking-wide transition hover:bg-red-50 dark:hover:bg-red-950/20">Delete</button>
                                    </div>
                                </div>
                            @empty
                                <div class="px-6 py-2.5 pl-20 bg-slate-50/30 dark:bg-slate-900/10 border-t border-slate-50 dark:border-slate-700/40">
                                    <p class="text-[10px] text-slate-400 dark:text-slate-500 italic font-semibold">No varieties added yet.</p>
                                </div>
                            @endforelse

                        </div>
                    @empty
                        <div class="px-6 py-4 pl-14">
                            <p class="text-[10px] text-slate-400 dark:text-slate-500 italic font-semibold">No crops under this category yet.</p>
                        </div>
                    @endforelse

                </div>
            @empty
                <div class="bg-white dark:bg-slate-800 border border-slate-200/70 dark:border-slate-700/80 rounded-2xl shadow-sm p-12 text-center">
                    <svg class="w-10 h-10 text-slate-200 dark:text-slate-700 mx-auto mb-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" /></svg>
                    <p class="text-slate-400 dark:text-slate-550 text-sm font-semibold">No crop categories found. Add one above to get started.</p>
                </div>
            @endforelse

        </div>
    </div>

    {{-- ============================================================
         MODAL: EDIT CATEGORY
    ============================================================ --}}
    <div id="modal-edit-category" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-slate-950/40 backdrop-blur-sm">
        <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-2xl w-full max-w-md mx-4 border border-slate-100 dark:border-slate-700 p-7">
            <div class="flex items-center justify-between mb-5">
                <h3 class="text-base font-extrabold text-slate-800 dark:text-white heading-font">Edit Category</h3>
                <button onclick="closeModal('modal-edit-category')" class="text-slate-400 hover:text-slate-800 dark:hover:text-white text-lg leading-none transition">&times;</button>
            </div>
            <form id="form-edit-category" method="POST" class="space-y-4">
                @csrf @method('PUT')
                <div>
                    <label class="block text-[10px] font-extrabold text-slate-500 dark:text-slate-400 uppercase tracking-widest mb-1.5">Name <span class="text-red-400">*</span></label>
                    <input type="text" id="edit-category-name" name="name"
                        class="w-full bg-slate-50/50 dark:bg-slate-900/50 border border-slate-200 dark:border-slate-700 text-slate-800 dark:text-slate-200 text-sm rounded-xl px-4 py-2.5 focus:outline-none focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20 transition" />
                </div>
                <div>
                    <label class="block text-[10px] font-extrabold text-slate-500 dark:text-slate-400 uppercase tracking-widest mb-1.5">Description</label>
                    <textarea id="edit-category-description" name="description" rows="2"
                        class="w-full bg-slate-50/50 dark:bg-slate-900/50 border border-slate-200 dark:border-slate-700 text-slate-800 dark:text-slate-200 text-sm rounded-xl px-4 py-2.5 focus:outline-none focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20 transition resize-none"></textarea>
                </div>
                <div>
                    <label class="block text-[10px] font-extrabold text-slate-500 dark:text-slate-400 uppercase tracking-widest mb-1.5">Status <span class="text-red-400">*</span></label>
                    <select id="edit-category-status" name="status"
                        class="w-full bg-slate-50/50 dark:bg-slate-900/50 border border-slate-200 dark:border-slate-700 text-slate-800 dark:text-slate-200 text-sm rounded-xl px-4 py-2.5 focus:outline-none focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20 transition">
                        <option value="active">Active</option>
                        <option value="inactive">Inactive</option>
                    </select>
                </div>
                <div class="flex gap-3 pt-2">
                    <button type="submit" class="flex-1 bg-slate-800 text-white text-xs font-bold py-2.5 rounded-xl hover:bg-slate-700 transition shadow-sm">Save Changes</button>
                    <button type="button" onclick="closeModal('modal-edit-category')" class="flex-1 bg-slate-100 dark:bg-slate-700 text-slate-700 dark:text-slate-300 text-xs font-bold py-2.5 rounded-xl hover:bg-slate-200 dark:hover:bg-slate-600 transition">Cancel</button>
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
                <button onclick="closeModal('modal-edit-crop')" class="text-slate-400 hover:text-slate-800 dark:hover:text-white text-lg leading-none transition">&times;</button>
            </div>
            <form id="form-edit-crop" method="POST" class="space-y-4">
                @csrf @method('PUT')
                <div>
                    <label class="block text-[10px] font-extrabold text-slate-500 dark:text-slate-400 uppercase tracking-widest mb-1.5">Category <span class="text-red-400">*</span></label>
                    <select id="edit-crop-category" name="crop_category_id"
                        class="w-full bg-slate-50/50 dark:bg-slate-900/50 border border-slate-200 dark:border-slate-700 text-slate-800 dark:text-slate-200 text-sm rounded-xl px-4 py-2.5 focus:outline-none focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20 transition">
                        @foreach ($categories as $cat)
                            <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-[10px] font-extrabold text-slate-500 dark:text-slate-400 uppercase tracking-widest mb-1.5">Crop Name <span class="text-red-400">*</span></label>
                    <input type="text" id="edit-crop-name" name="name"
                        class="w-full bg-slate-50/50 dark:bg-slate-900/50 border border-slate-200 dark:border-slate-700 text-slate-800 dark:text-slate-200 text-sm rounded-xl px-4 py-2.5 focus:outline-none focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20 transition" />
                </div>
                <div>
                    <label class="block text-[10px] font-extrabold text-slate-500 dark:text-slate-400 uppercase tracking-widest mb-1.5">Description</label>
                    <textarea id="edit-crop-description" name="description" rows="2"
                        class="w-full bg-slate-50/50 dark:bg-slate-900/50 border border-slate-200 dark:border-slate-700 text-slate-800 dark:text-slate-200 text-sm rounded-xl px-4 py-2.5 focus:outline-none focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20 transition resize-none"></textarea>
                </div>
                <div>
                    <label class="block text-[10px] font-extrabold text-slate-500 dark:text-slate-400 uppercase tracking-widest mb-1.5">Status <span class="text-red-400">*</span></label>
                    <select id="edit-crop-status" name="status"
                        class="w-full bg-slate-50/50 dark:bg-slate-900/50 border border-slate-200 dark:border-slate-700 text-slate-800 dark:text-slate-200 text-sm rounded-xl px-4 py-2.5 focus:outline-none focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20 transition">
                        <option value="active">Active</option>
                        <option value="inactive">Inactive</option>
                    </select>
                </div>
                <div class="flex gap-3 pt-2">
                    <button type="submit" class="flex-1 bg-slate-800 text-white text-xs font-bold py-2.5 rounded-xl hover:bg-slate-700 transition shadow-sm">Save Changes</button>
                    <button type="button" onclick="closeModal('modal-edit-crop')" class="flex-1 bg-slate-100 dark:bg-slate-700 text-slate-700 dark:text-slate-300 text-xs font-bold py-2.5 rounded-xl hover:bg-slate-200 dark:hover:bg-slate-600 transition">Cancel</button>
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
                <button onclick="closeModal('modal-edit-variety')" class="text-slate-400 hover:text-slate-800 dark:hover:text-white text-lg leading-none transition">&times;</button>
            </div>
            <form id="form-edit-variety" method="POST" class="space-y-4">
                @csrf @method('PUT')
                <div>
                    <label class="block text-[10px] font-extrabold text-slate-500 dark:text-slate-400 uppercase tracking-widest mb-1.5">Variety Name <span class="text-red-400">*</span></label>
                    <input type="text" id="edit-variety-name" name="name"
                        class="w-full bg-slate-50/50 dark:bg-slate-900/50 border border-slate-200 dark:border-slate-700 text-slate-800 dark:text-slate-200 text-sm rounded-xl px-4 py-2.5 focus:outline-none focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20 transition" />
                </div>
                <div>
                    <label class="block text-[10px] font-extrabold text-slate-500 dark:text-slate-400 uppercase tracking-widest mb-1.5">Price / kg <span class="text-red-400">*</span></label>
                    <div class="relative">
                        <span class="absolute left-4 top-1/2 -translate-y-1/2 text-slate-400 dark:text-slate-500 text-sm font-bold">₱</span>
                        <input type="number" id="edit-variety-price" name="price_per_kg" step="0.01" min="0"
                            class="w-full bg-slate-50/50 dark:bg-slate-900/50 border border-slate-200 dark:border-slate-700 text-slate-800 dark:text-slate-200 text-sm rounded-xl pl-8 pr-4 py-2.5 focus:outline-none focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20 transition" />
                    </div>
                </div>
                <div>
                    <label class="block text-[10px] font-extrabold text-slate-500 dark:text-slate-400 uppercase tracking-widest mb-1.5">Description</label>
                    <textarea id="edit-variety-description" name="description" rows="2"
                        class="w-full bg-slate-50/50 dark:bg-slate-900/50 border border-slate-200 dark:border-slate-700 text-slate-800 dark:text-slate-200 text-sm rounded-xl px-4 py-2.5 focus:outline-none focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20 transition resize-none"></textarea>
                </div>
                <div>
                    <label class="block text-[10px] font-extrabold text-slate-500 dark:text-slate-400 uppercase tracking-widest mb-1.5">Status <span class="text-red-400">*</span></label>
                    <select id="edit-variety-status" name="status"
                        class="w-full bg-slate-50/50 dark:bg-slate-900/50 border border-slate-200 dark:border-slate-700 text-slate-800 dark:text-slate-200 text-sm rounded-xl px-4 py-2.5 focus:outline-none focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20 transition">
                        <option value="active">Active</option>
                        <option value="inactive">Inactive</option>
                    </select>
                </div>
                <div class="flex gap-3 pt-2">
                    <button type="submit" class="flex-1 bg-slate-800 text-white text-xs font-bold py-2.5 rounded-xl hover:bg-slate-700 transition shadow-sm">Save Changes</button>
                    <button type="button" onclick="closeModal('modal-edit-variety')" class="flex-1 bg-slate-100 dark:bg-slate-700 text-slate-700 dark:text-slate-300 text-xs font-bold py-2.5 rounded-xl hover:bg-slate-200 dark:hover:bg-slate-600 transition">Cancel</button>
                </div>
            </form>
        </div>
    </div>

    {{-- ============================================================
         MODAL: DELETE CONFIRMATION (shared)
    ============================================================ --}}
    <div id="modal-delete-confirm" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-slate-950/40 backdrop-blur-sm">
        <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-2xl w-full max-w-sm mx-4 border border-red-100 dark:border-red-900/30 p-7">
            <p class="text-[10px] font-extrabold uppercase tracking-widest text-red-500 mb-2">Confirm Delete</p>
            <p id="delete-confirm-message" class="text-sm text-slate-600 dark:text-slate-300 mb-6 leading-relaxed">Are you sure?</p>
            <div class="flex gap-3">
                <form id="form-delete" method="POST" class="flex-1">
                    @csrf @method('DELETE')
                    <button type="submit" class="w-full bg-red-600 hover:bg-red-700 text-white text-xs font-bold py-2.5 rounded-xl transition shadow-sm">Delete</button>
                </form>
                <button type="button" onclick="closeModal('modal-delete-confirm')" class="flex-1 bg-slate-100 dark:bg-slate-700 text-slate-700 dark:text-slate-300 text-xs font-bold py-2.5 rounded-xl hover:bg-slate-200 dark:hover:bg-slate-600 transition">Cancel</button>
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
    </script>
</x-layout>
