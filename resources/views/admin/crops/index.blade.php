<x-layout>
    <div class="min-h-screen bg-gray-950 text-gray-100 px-6 py-8">

        {{-- ============================================================
             PAGE HEADER
        ============================================================ --}}
        <div class="mb-8 border-b border-gray-800 pb-6">
            <p class="text-xs font-mono text-gray-500 uppercase tracking-widest mb-1">Admin / Crop Management</p>
            <h1 class="text-2xl font-bold text-white tracking-tight">Crop Registry</h1>
            <p class="text-sm text-gray-400 mt-1">Manage crop categories, crops, and varieties. All changes are reflected immediately in farmer harvest forms.</p>
        </div>

        {{-- ============================================================
             FLASH MESSAGES
        ============================================================ --}}
        @if (session('success'))
            <div class="mb-6 flex items-start gap-3 bg-emerald-950 border border-emerald-700 text-emerald-300 text-sm px-4 py-3 rounded">
                <span class="mt-0.5 text-emerald-400">✓</span>
                <span>{{ session('success') }}</span>
            </div>
        @endif

        @if (session('error'))
            <div class="mb-6 flex items-start gap-3 bg-red-950 border border-red-700 text-red-300 text-sm px-4 py-3 rounded">
                <span class="mt-0.5 text-red-400">✕</span>
                <span>{{ session('error') }}</span>
            </div>
        @endif

        {{-- ============================================================
             TOP PANEL — THREE ENTRY FORMS
        ============================================================ --}}
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-4 mb-10">

            {{-- ADD CATEGORY --}}
            <div class="bg-gray-900 border border-gray-800 rounded-lg p-5">
                <h2 class="text-xs font-mono uppercase tracking-widest text-gray-400 mb-4">New Category</h2>
                <form method="POST" action="{{ route('admin.crops.categories.store') }}" class="space-y-3">
                    @csrf
                    <div>
                        <label class="block text-xs text-gray-400 mb-1">Name <span class="text-red-400">*</span></label>
                        <input
                            type="text"
                            name="name"
                            value="{{ old('name') }}"
                            placeholder="e.g. Fruits"
                            class="w-full bg-gray-800 border border-gray-700 text-gray-100 text-sm rounded px-3 py-2 placeholder-gray-600 focus:outline-none focus:border-gray-500 focus:ring-1 focus:ring-gray-600"
                        />
                        @error('name') <p class="text-red-400 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-xs text-gray-400 mb-1">Description</label>
                        <textarea
                            name="description"
                            rows="2"
                            placeholder="Optional notes"
                            class="w-full bg-gray-800 border border-gray-700 text-gray-100 text-sm rounded px-3 py-2 placeholder-gray-600 focus:outline-none focus:border-gray-500 focus:ring-1 focus:ring-gray-600 resize-none"
                        >{{ old('description') }}</textarea>
                    </div>
                    <button type="submit" class="w-full bg-white text-gray-950 text-sm font-semibold py-2 rounded hover:bg-gray-200 transition-colors">
                        Add Category
                    </button>
                </form>
            </div>

            {{-- ADD CROP --}}
            <div class="bg-gray-900 border border-gray-800 rounded-lg p-5">
                <h2 class="text-xs font-mono uppercase tracking-widest text-gray-400 mb-4">New Crop</h2>
                <form method="POST" action="{{ route('admin.crops.store') }}" class="space-y-3">
                    @csrf
                    <div>
                        <label class="block text-xs text-gray-400 mb-1">Category <span class="text-red-400">*</span></label>
                        <select
                            name="crop_category_id"
                            class="w-full bg-gray-800 border border-gray-700 text-gray-100 text-sm rounded px-3 py-2 focus:outline-none focus:border-gray-500 focus:ring-1 focus:ring-gray-600"
                        >
                            <option value="">— Select category —</option>
                            @foreach ($categories as $cat)
                                <option value="{{ $cat->id }}" {{ old('crop_category_id') == $cat->id ? 'selected' : '' }}>
                                    {{ $cat->name }}
                                </option>
                            @endforeach
                        </select>
                        @error('crop_category_id') <p class="text-red-400 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-xs text-gray-400 mb-1">Crop Name <span class="text-red-400">*</span></label>
                        <input
                            type="text"
                            name="name"
                            value="{{ old('name') }}"
                            placeholder="e.g. Pineapple"
                            class="w-full bg-gray-800 border border-gray-700 text-gray-100 text-sm rounded px-3 py-2 placeholder-gray-600 focus:outline-none focus:border-gray-500 focus:ring-1 focus:ring-gray-600"
                        />
                        @error('name') <p class="text-red-400 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-xs text-gray-400 mb-1">Description</label>
                        <textarea
                            name="description"
                            rows="2"
                            placeholder="Optional notes"
                            class="w-full bg-gray-800 border border-gray-700 text-gray-100 text-sm rounded px-3 py-2 placeholder-gray-600 focus:outline-none focus:border-gray-500 focus:ring-1 focus:ring-gray-600 resize-none"
                        >{{ old('description') }}</textarea>
                    </div>
                    <button type="submit" class="w-full bg-white text-gray-950 text-sm font-semibold py-2 rounded hover:bg-gray-200 transition-colors">
                        Add Crop
                    </button>
                </form>
            </div>

            {{-- ADD VARIETY --}}
            <div class="bg-gray-900 border border-gray-800 rounded-lg p-5">
                <h2 class="text-xs font-mono uppercase tracking-widest text-gray-400 mb-4">New Variety</h2>
                <form method="POST" action="{{ route('admin.crops.varieties.store') }}" class="space-y-3">
                    @csrf
                    <div>
                        <label class="block text-xs text-gray-400 mb-1">Crop <span class="text-red-400">*</span></label>
                        <select
                            name="crop_id"
                            class="w-full bg-gray-800 border border-gray-700 text-gray-100 text-sm rounded px-3 py-2 focus:outline-none focus:border-gray-500 focus:ring-1 focus:ring-gray-600"
                        >
                            <option value="">— Select crop —</option>
                            @foreach ($categories as $cat)
                                @if ($cat->crops->count())
                                    <optgroup label="{{ $cat->name }}">
                                        @foreach ($cat->crops as $crop)
                                            <option value="{{ $crop->id }}" {{ old('crop_id') == $crop->id ? 'selected' : '' }}>
                                                {{ $crop->name }}
                                            </option>
                                        @endforeach
                                    </optgroup>
                                @endif
                            @endforeach
                        </select>
                        @error('crop_id') <p class="text-red-400 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-xs text-gray-400 mb-1">Variety Name <span class="text-red-400">*</span></label>
                        <input
                            type="text"
                            name="name"
                            value="{{ old('name') }}"
                            placeholder="e.g. Queen"
                            class="w-full bg-gray-800 border border-gray-700 text-gray-100 text-sm rounded px-3 py-2 placeholder-gray-600 focus:outline-none focus:border-gray-500 focus:ring-1 focus:ring-gray-600"
                        />
                        @error('name') <p class="text-red-400 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-xs text-gray-400 mb-1">Price / kg <span class="text-red-400">*</span></label>
                        <div class="relative">
                            <span class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-sm">₱</span>
                            <input
                                type="number"
                                name="price_per_kg"
                                value="{{ old('price_per_kg', '0.00') }}"
                                step="0.01"
                                min="0"
                                class="w-full bg-gray-800 border border-gray-700 text-gray-100 text-sm rounded pl-7 pr-3 py-2 placeholder-gray-600 focus:outline-none focus:border-gray-500 focus:ring-1 focus:ring-gray-600"
                            />
                        </div>
                        @error('price_per_kg') <p class="text-red-400 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-xs text-gray-400 mb-1">Description</label>
                        <textarea
                            name="description"
                            rows="2"
                            placeholder="Optional notes"
                            class="w-full bg-gray-800 border border-gray-700 text-gray-100 text-sm rounded px-3 py-2 placeholder-gray-600 focus:outline-none focus:border-gray-500 focus:ring-1 focus:ring-gray-600 resize-none"
                        >{{ old('description') }}</textarea>
                    </div>
                    <button type="submit" class="w-full bg-white text-gray-950 text-sm font-semibold py-2 rounded hover:bg-gray-200 transition-colors">
                        Add Variety
                    </button>
                </form>
            </div>

        </div>

        {{-- ============================================================
             REGISTRY TABLE — CATEGORIES → CROPS → VARIETIES
        ============================================================ --}}
        <div class="space-y-4">

            @forelse ($categories as $category)
                <div class="bg-gray-900 border border-gray-800 rounded-lg overflow-hidden">

                    {{-- CATEGORY ROW --}}
                    <div class="flex items-center justify-between px-5 py-4 border-b border-gray-800 bg-gray-900">
                        <div class="flex items-center gap-3">
                            <span class="text-xs font-mono text-gray-500 w-6 text-right">{{ $loop->iteration }}</span>
                            <div>
                                <p class="text-sm font-semibold text-white">{{ $category->name }}</p>
                                @if ($category->description)
                                    <p class="text-xs text-gray-500 mt-0.5">{{ $category->description }}</p>
                                @endif
                            </div>
                        </div>
                        <div class="flex items-center gap-3">
                            <span @class([
                                'text-xs font-mono px-2 py-0.5 rounded',
                                'bg-emerald-950 text-emerald-400 border border-emerald-800' => $category->status === 'active',
                                'bg-gray-800 text-gray-500 border border-gray-700' => $category->status === 'inactive',
                            ])>{{ $category->status }}</span>
                            <span class="text-xs text-gray-600">{{ $category->crops->count() }} crop{{ $category->crops->count() !== 1 ? 's' : '' }}</span>
                            <button
                                onclick="openEditCategory({{ $category->id }}, '{{ addslashes($category->name) }}', '{{ addslashes($category->description ?? '') }}', '{{ $category->status }}')"
                                class="text-xs text-gray-400 hover:text-white transition-colors px-2 py-1 rounded hover:bg-gray-800"
                            >Edit</button>
                            <button
                                onclick="confirmDeleteCategory({{ $category->id }}, '{{ addslashes($category->name) }}')"
                                class="text-xs text-red-500 hover:text-red-400 transition-colors px-2 py-1 rounded hover:bg-gray-800"
                            >Delete</button>
                        </div>
                    </div>

                    {{-- CROPS UNDER CATEGORY --}}
                    @forelse ($category->crops as $crop)
                        <div class="border-b border-gray-800 last:border-b-0">

                            {{-- CROP ROW --}}
                            <div class="flex items-center justify-between px-5 py-3 pl-12 bg-gray-950">
                                <div class="flex items-center gap-3">
                                    <span class="w-1 h-1 rounded-full bg-gray-600 flex-shrink-0"></span>
                                    <div>
                                        <p class="text-sm text-gray-200">{{ $crop->name }}</p>
                                        @if ($crop->description)
                                            <p class="text-xs text-gray-600 mt-0.5">{{ $crop->description }}</p>
                                        @endif
                                    </div>
                                </div>
                                <div class="flex items-center gap-3">
                                    <span @class([
                                        'text-xs font-mono px-2 py-0.5 rounded',
                                        'bg-emerald-950 text-emerald-400 border border-emerald-800' => $crop->status === 'active',
                                        'bg-gray-800 text-gray-500 border border-gray-700' => $crop->status === 'inactive',
                                    ])>{{ $crop->status }}</span>
                                    <span class="text-xs text-gray-600">{{ $crop->varieties->count() }} var.</span>
                                    <button
                                        onclick="openEditCrop({{ $crop->id }}, {{ $crop->crop_category_id }}, '{{ addslashes($crop->name) }}', '{{ addslashes($crop->description ?? '') }}', '{{ $crop->status }}')"
                                        class="text-xs text-gray-400 hover:text-white transition-colors px-2 py-1 rounded hover:bg-gray-800"
                                    >Edit</button>
                                    <button
                                        onclick="confirmDeleteCrop({{ $crop->id }}, '{{ addslashes($crop->name) }}')"
                                        class="text-xs text-red-500 hover:text-red-400 transition-colors px-2 py-1 rounded hover:bg-gray-800"
                                    >Delete</button>
                                </div>
                            </div>

                            {{-- VARIETIES UNDER CROP --}}
                            @forelse ($crop->varieties as $variety)
                                <div class="flex items-center justify-between px-5 py-2.5 pl-20 bg-gray-950 border-t border-gray-900">
                                    <div class="flex items-center gap-3">
                                        <span class="w-0.5 h-0.5 rounded-full bg-gray-700 flex-shrink-0"></span>
                                        <div>
                                            <p class="text-xs text-gray-400">{{ $variety->name }}</p>
                                            @if ($variety->description)
                                                <p class="text-xs text-gray-600 mt-0.5">{{ $variety->description }}</p>
                                            @endif
                                        </div>
                                    </div>
                                    <div class="flex items-center gap-3">
                                        <span class="text-xs font-mono text-gray-300">₱{{ number_format($variety->price_per_kg, 2) }}/kg</span>
                                        <span @class([
                                            'text-xs font-mono px-2 py-0.5 rounded',
                                            'bg-emerald-950 text-emerald-400 border border-emerald-800' => $variety->status === 'active',
                                            'bg-gray-800 text-gray-500 border border-gray-700' => $variety->status === 'inactive',
                                        ])>{{ $variety->status }}</span>
                                        <button
                                            onclick="openEditVariety({{ $variety->id }}, {{ $variety->crop_id }}, '{{ addslashes($variety->name) }}', '{{ addslashes($variety->description ?? '') }}', '{{ $variety->price_per_kg }}', '{{ $variety->status }}')"
                                            class="text-xs text-gray-400 hover:text-white transition-colors px-2 py-1 rounded hover:bg-gray-800"
                                        >Edit</button>
                                        <button
                                            onclick="confirmDeleteVariety({{ $variety->id }}, '{{ addslashes($variety->name) }}')"
                                            class="text-xs text-red-500 hover:text-red-400 transition-colors px-2 py-1 rounded hover:bg-gray-800"
                                        >Delete</button>
                                    </div>
                                </div>
                            @empty
                                <div class="px-5 py-2 pl-20 bg-gray-950 border-t border-gray-900">
                                    <p class="text-xs text-gray-700 italic">No varieties added yet.</p>
                                </div>
                            @endforelse

                        </div>
                    @empty
                        <div class="px-5 py-3 pl-12 bg-gray-950">
                            <p class="text-xs text-gray-700 italic">No crops under this category yet.</p>
                        </div>
                    @endforelse

                </div>
            @empty
                <div class="bg-gray-900 border border-gray-800 rounded-lg px-6 py-12 text-center">
                    <p class="text-gray-600 text-sm">No crop categories found. Add one above to get started.</p>
                </div>
            @endforelse

        </div>
    </div>

    {{-- ============================================================
         MODAL: EDIT CATEGORY
    ============================================================ --}}
    <div id="modal-edit-category" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-black/70 backdrop-blur-sm">
        <div class="bg-gray-900 border border-gray-700 rounded-lg w-full max-w-md mx-4 shadow-2xl">
            <div class="flex items-center justify-between px-6 py-4 border-b border-gray-800">
                <h3 class="text-sm font-semibold text-white">Edit Category</h3>
                <button onclick="closeModal('modal-edit-category')" class="text-gray-500 hover:text-white text-lg leading-none">&times;</button>
            </div>
            <form id="form-edit-category" method="POST" class="px-6 py-5 space-y-4">
                @csrf
                @method('PUT')
                <div>
                    <label class="block text-xs text-gray-400 mb-1">Name <span class="text-red-400">*</span></label>
                    <input
                        type="text"
                        id="edit-category-name"
                        name="name"
                        class="w-full bg-gray-800 border border-gray-700 text-gray-100 text-sm rounded px-3 py-2 focus:outline-none focus:border-gray-500 focus:ring-1 focus:ring-gray-600"
                    />
                </div>
                <div>
                    <label class="block text-xs text-gray-400 mb-1">Description</label>
                    <textarea
                        id="edit-category-description"
                        name="description"
                        rows="2"
                        class="w-full bg-gray-800 border border-gray-700 text-gray-100 text-sm rounded px-3 py-2 focus:outline-none focus:border-gray-500 focus:ring-1 focus:ring-gray-600 resize-none"
                    ></textarea>
                </div>
                <div>
                    <label class="block text-xs text-gray-400 mb-1">Status <span class="text-red-400">*</span></label>
                    <select
                        id="edit-category-status"
                        name="status"
                        class="w-full bg-gray-800 border border-gray-700 text-gray-100 text-sm rounded px-3 py-2 focus:outline-none focus:border-gray-500 focus:ring-1 focus:ring-gray-600"
                    >
                        <option value="active">Active</option>
                        <option value="inactive">Inactive</option>
                    </select>
                </div>
                <div class="flex gap-3 pt-2">
                    <button type="submit" class="flex-1 bg-white text-gray-950 text-sm font-semibold py-2 rounded hover:bg-gray-200 transition-colors">Save Changes</button>
                    <button type="button" onclick="closeModal('modal-edit-category')" class="flex-1 bg-gray-800 text-gray-300 text-sm py-2 rounded hover:bg-gray-700 transition-colors">Cancel</button>
                </div>
            </form>
        </div>
    </div>

    {{-- ============================================================
         MODAL: EDIT CROP
    ============================================================ --}}
    <div id="modal-edit-crop" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-black/70 backdrop-blur-sm">
        <div class="bg-gray-900 border border-gray-700 rounded-lg w-full max-w-md mx-4 shadow-2xl">
            <div class="flex items-center justify-between px-6 py-4 border-b border-gray-800">
                <h3 class="text-sm font-semibold text-white">Edit Crop</h3>
                <button onclick="closeModal('modal-edit-crop')" class="text-gray-500 hover:text-white text-lg leading-none">&times;</button>
            </div>
            <form id="form-edit-crop" method="POST" class="px-6 py-5 space-y-4">
                @csrf
                @method('PUT')
                <div>
                    <label class="block text-xs text-gray-400 mb-1">Category <span class="text-red-400">*</span></label>
                    <select
                        id="edit-crop-category"
                        name="crop_category_id"
                        class="w-full bg-gray-800 border border-gray-700 text-gray-100 text-sm rounded px-3 py-2 focus:outline-none focus:border-gray-500 focus:ring-1 focus:ring-gray-600"
                    >
                        @foreach ($categories as $cat)
                            <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-xs text-gray-400 mb-1">Crop Name <span class="text-red-400">*</span></label>
                    <input
                        type="text"
                        id="edit-crop-name"
                        name="name"
                        class="w-full bg-gray-800 border border-gray-700 text-gray-100 text-sm rounded px-3 py-2 focus:outline-none focus:border-gray-500 focus:ring-1 focus:ring-gray-600"
                    />
                </div>
                <div>
                    <label class="block text-xs text-gray-400 mb-1">Description</label>
                    <textarea
                        id="edit-crop-description"
                        name="description"
                        rows="2"
                        class="w-full bg-gray-800 border border-gray-700 text-gray-100 text-sm rounded px-3 py-2 focus:outline-none focus:border-gray-500 focus:ring-1 focus:ring-gray-600 resize-none"
                    ></textarea>
                </div>
                <div>
                    <label class="block text-xs text-gray-400 mb-1">Status <span class="text-red-400">*</span></label>
                    <select
                        id="edit-crop-status"
                        name="status"
                        class="w-full bg-gray-800 border border-gray-700 text-gray-100 text-sm rounded px-3 py-2 focus:outline-none focus:border-gray-500 focus:ring-1 focus:ring-gray-600"
                    >
                        <option value="active">Active</option>
                        <option value="inactive">Inactive</option>
                    </select>
                </div>
                <div class="flex gap-3 pt-2">
                    <button type="submit" class="flex-1 bg-white text-gray-950 text-sm font-semibold py-2 rounded hover:bg-gray-200 transition-colors">Save Changes</button>
                    <button type="button" onclick="closeModal('modal-edit-crop')" class="flex-1 bg-gray-800 text-gray-300 text-sm py-2 rounded hover:bg-gray-700 transition-colors">Cancel</button>
                </div>
            </form>
        </div>
    </div>

    {{-- ============================================================
         MODAL: EDIT VARIETY
    ============================================================ --}}
    <div id="modal-edit-variety" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-black/70 backdrop-blur-sm">
        <div class="bg-gray-900 border border-gray-700 rounded-lg w-full max-w-md mx-4 shadow-2xl">
            <div class="flex items-center justify-between px-6 py-4 border-b border-gray-800">
                <h3 class="text-sm font-semibold text-white">Edit Variety</h3>
                <button onclick="closeModal('modal-edit-variety')" class="text-gray-500 hover:text-white text-lg leading-none">&times;</button>
            </div>
            <form id="form-edit-variety" method="POST" class="px-6 py-5 space-y-4">
                @csrf
                @method('PUT')
                <div>
                    <label class="block text-xs text-gray-400 mb-1">Variety Name <span class="text-red-400">*</span></label>
                    <input
                        type="text"
                        id="edit-variety-name"
                        name="name"
                        class="w-full bg-gray-800 border border-gray-700 text-gray-100 text-sm rounded px-3 py-2 focus:outline-none focus:border-gray-500 focus:ring-1 focus:ring-gray-600"
                    />
                </div>
                <div>
                    <label class="block text-xs text-gray-400 mb-1">Price / kg <span class="text-red-400">*</span></label>
                    <div class="relative">
                        <span class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-sm">₱</span>
                        <input
                            type="number"
                            id="edit-variety-price"
                            name="price_per_kg"
                            step="0.01"
                            min="0"
                            class="w-full bg-gray-800 border border-gray-700 text-gray-100 text-sm rounded pl-7 pr-3 py-2 focus:outline-none focus:border-gray-500 focus:ring-1 focus:ring-gray-600"
                        />
                    </div>
                </div>
                <div>
                    <label class="block text-xs text-gray-400 mb-1">Description</label>
                    <textarea
                        id="edit-variety-description"
                        name="description"
                        rows="2"
                        class="w-full bg-gray-800 border border-gray-700 text-gray-100 text-sm rounded px-3 py-2 focus:outline-none focus:border-gray-500 focus:ring-1 focus:ring-gray-600 resize-none"
                    ></textarea>
                </div>
                <div>
                    <label class="block text-xs text-gray-400 mb-1">Status <span class="text-red-400">*</span></label>
                    <select
                        id="edit-variety-status"
                        name="status"
                        class="w-full bg-gray-800 border border-gray-700 text-gray-100 text-sm rounded px-3 py-2 focus:outline-none focus:border-gray-500 focus:ring-1 focus:ring-gray-600"
                    >
                        <option value="active">Active</option>
                        <option value="inactive">Inactive</option>
                    </select>
                </div>
                <div class="flex gap-3 pt-2">
                    <button type="submit" class="flex-1 bg-white text-gray-950 text-sm font-semibold py-2 rounded hover:bg-gray-200 transition-colors">Save Changes</button>
                    <button type="button" onclick="closeModal('modal-edit-variety')" class="flex-1 bg-gray-800 text-gray-300 text-sm py-2 rounded hover:bg-gray-700 transition-colors">Cancel</button>
                </div>
            </form>
        </div>
    </div>

    {{-- ============================================================
         MODAL: DELETE CONFIRMATION (shared)
    ============================================================ --}}
    <div id="modal-delete-confirm" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-black/70 backdrop-blur-sm">
        <div class="bg-gray-900 border border-red-900 rounded-lg w-full max-w-sm mx-4 shadow-2xl">
            <div class="px-6 py-5">
                <p class="text-xs font-mono uppercase tracking-widest text-red-400 mb-2">Confirm Delete</p>
                <p id="delete-confirm-message" class="text-sm text-gray-300 mb-6">Are you sure?</p>
                <div class="flex gap-3">
                    <form id="form-delete" method="POST" class="flex-1">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="w-full bg-red-700 hover:bg-red-600 text-white text-sm font-semibold py-2 rounded transition-colors">
                            Delete
                        </button>
                    </form>
                    <button
                        type="button"
                        onclick="closeModal('modal-delete-confirm')"
                        class="flex-1 bg-gray-800 text-gray-300 text-sm py-2 rounded hover:bg-gray-700 transition-colors"
                    >Cancel</button>
                </div>
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
