<x-layout>
<div class="w-full max-w-7xl mx-auto">

    <!-- Nice Admin Page Header -->
    <header class="mb-8">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h1 class="text-2xl font-extrabold text-slate-800 heading-font tracking-tight">Crop Manager</h1>
                <p class="text-sm text-slate-400 mt-1 font-semibold">Manage crop categories, crops, and variety pricing</p>
            </div>
            <span class="text-[10px] font-bold uppercase tracking-widest text-[#3A7D44] bg-[#3A7D44]/10 px-3 py-1.5 rounded-lg border border-[#3A7D44]/10 self-start">{{ $categories->count() }} Categories</span>
        </div>
    </header>

    {{-- Flash Messages --}}
    @if (session('success'))
        <div class="mb-6 bg-[#3A7D44]/10 border border-[#3A7D44]/20 text-[#3A7D44] rounded-xl px-5 py-4 text-sm font-semibold flex items-center gap-2">
            <span>✅</span> {{ session('success') }}
        </div>
    @endif
    @if (session('error'))
        <div class="mb-6 bg-red-50 border border-red-200 text-red-700 rounded-xl px-5 py-4 text-sm font-semibold flex items-center gap-2">
            <span>❌</span> {{ session('error') }}
        </div>
    @endif

    {{-- ================================================ --}}
    {{-- ADD FORMS ROW --}}
    {{-- ================================================ --}}
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-5 mb-8">

        {{-- Add Category --}}
        <div class="bg-white border border-slate-200/70 rounded-2xl shadow-sm p-6">
            <h2 class="text-xs font-extrabold text-slate-500 uppercase tracking-widest mb-4">Add Category</h2>
            <form method="POST" action="{{ route('admin.crops.categories.store') }}" class="flex flex-col gap-3">
                @csrf
                <input type="text" name="name" placeholder="Category name (e.g. Fruits)"
                    class="w-full border border-slate-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-[#3A7D44]/20 focus:border-[#3A7D44] bg-slate-50/50 text-slate-800 placeholder-slate-400 transition"
                    required />
                <input type="text" name="description" placeholder="Description (optional)"
                    class="w-full border border-slate-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-[#3A7D44]/20 focus:border-[#3A7D44] bg-slate-50/50 text-slate-800 placeholder-slate-400 transition" />
                <button type="submit"
                    class="w-full bg-slate-800 text-white text-xs font-bold px-5 py-2.5 rounded-xl hover:bg-slate-700 transition shadow-sm">
                    Add Category
                </button>
            </form>
            @error('name') <p class="text-red-500 text-xs mt-2 font-semibold">{{ $message }}</p> @enderror
        </div>

        {{-- Add Crop --}}
        <div class="bg-white border border-slate-200/70 rounded-2xl shadow-sm p-6">
            <h2 class="text-xs font-extrabold text-slate-500 uppercase tracking-widest mb-4">Add Crop</h2>
            <form method="POST" action="{{ route('admin.crops.store') }}" class="flex flex-col gap-3">
                @csrf
                <select name="crop_category_id"
                    class="w-full border border-slate-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-[#3A7D44]/20 focus:border-[#3A7D44] bg-slate-50/50 text-slate-800 transition"
                    required>
                    <option value="" disabled selected>Select category</option>
                    @foreach ($categories as $cat)
                        <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                    @endforeach
                </select>
                <input type="text" name="name" placeholder="Crop name (e.g. Pineapple)"
                    class="w-full border border-slate-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-[#3A7D44]/20 focus:border-[#3A7D44] bg-slate-50/50 text-slate-800 placeholder-slate-400 transition"
                    required />
                <button type="submit"
                    class="w-full bg-slate-800 text-white text-xs font-bold px-5 py-2.5 rounded-xl hover:bg-slate-700 transition shadow-sm">
                    Add Crop
                </button>
            </form>
        </div>

        {{-- Add Variety --}}
        <div class="bg-white border border-slate-200/70 rounded-2xl shadow-sm p-6">
            <h2 class="text-xs font-extrabold text-slate-500 uppercase tracking-widest mb-4">Add Variety & Price</h2>
            <form method="POST" action="{{ route('admin.crops.varieties.store') }}" class="flex flex-col gap-3">
                @csrf
                <select name="crop_id"
                    class="w-full border border-slate-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-[#3A7D44]/20 focus:border-[#3A7D44] bg-slate-50/50 text-slate-800 transition"
                    required>
                    <option value="" disabled selected>Select crop</option>
                    @foreach ($categories as $cat)
                        <optgroup label="{{ $cat->name }}">
                            @foreach ($cat->crops as $crop)
                                <option value="{{ $crop->id }}">{{ $crop->name }}</option>
                            @endforeach
                        </optgroup>
                    @endforeach
                </select>
                <input type="text" name="name" placeholder="Variety (e.g. Cavendish)"
                    class="w-full border border-slate-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-[#3A7D44]/20 focus:border-[#3A7D44] bg-slate-50/50 text-slate-800 placeholder-slate-400 transition"
                    required />
                <input type="number" name="price_per_kg" placeholder="Price/kg (₱)" step="0.01" min="0"
                    class="w-full border border-slate-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-[#3A7D44]/20 focus:border-[#3A7D44] bg-slate-50/50 text-slate-800 placeholder-slate-400 transition"
                    required />
                <button type="submit"
                    class="w-full bg-[#3A7D44] text-white text-xs font-bold px-5 py-2.5 rounded-xl hover:bg-[#2E6336] transition shadow-sm">
                    Add Variety
                </button>
            </form>
        </div>
    </div>

    {{-- ================================================ --}}
    {{-- CATEGORIES + CROPS + VARIETIES TABLE --}}
    {{-- ================================================ --}}
    @foreach ($categories as $category)
    <div class="mb-6 bg-white border border-slate-200/70 rounded-2xl shadow-sm overflow-hidden">

        {{-- Category Header --}}
        <div class="flex items-center justify-between px-6 py-4 border-b border-slate-100 bg-slate-50/50">
            <div class="flex items-center gap-3">
                <div class="w-8 h-8 rounded-lg bg-gradient-to-tr from-[#3A7D44]/15 to-[#3A7D44]/10 border border-[#3A7D44]/20 flex items-center justify-center text-[10px] font-extrabold text-[#3A7D44] uppercase">{{ substr($category->name, 0, 2) }}</div>
                <div>
                    <span class="text-sm font-extrabold text-slate-800 uppercase tracking-widest">{{ $category->name }}</span>
                    @if ($category->description)
                        <span class="ml-2 text-[10px] text-slate-400 font-semibold">— {{ $category->description }}</span>
                    @endif
                </div>
            </div>
            <div class="flex items-center gap-2">
                <button onclick="toggleModal('edit-category-{{ $category->id }}')"
                    class="inline-flex items-center justify-center w-8 h-8 rounded-lg bg-[#3A7D44]/10 text-[#3A7D44] hover:bg-[#3A7D44]/15 dark:bg-[#3A7D44]/10 dark:hover:bg-[#3A7D44]/15 dark:text-[#3A7D44] transition"
                    title="Edit Category">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                    </svg>
                </button>
                <form method="POST" action="{{ route('admin.crops.categories.destroy', $category) }}">
                    @csrf @method('DELETE')
                    <button type="button"
                        onclick="swalConfirm(this.closest('form'), {title: 'Delete Category?', text: 'Delete this category? This cannot be undone.', confirmText: 'Yes, delete', icon: 'warning', confirmColor: '#ef4444'})"
                        class="inline-flex items-center justify-center w-8 h-8 rounded-lg bg-rose-50 text-rose-600 hover:bg-rose-100 dark:bg-rose-950/20 dark:hover:bg-rose-950/40 dark:text-rose-400 transition"
                        title="Delete Category">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                        </svg>
                    </button>
                </form>
            </div>
        </div>

        {{-- Crops & Varieties --}}
        @foreach ($category->crops as $crop)
        <div class="px-6 py-5 border-b border-slate-50 last:border-0">

            {{-- Crop Row --}}
            <div class="flex items-center justify-between mb-4">
                <div class="flex items-center gap-3">
                    <span class="text-sm font-bold text-slate-700">{{ $crop->name }}</span>
                    <span class="text-[10px] font-bold px-2 py-0.5 rounded-lg uppercase tracking-wide {{ $crop->is_active ? 'bg-[#3A7D44]/10 text-[#3A7D44]' : 'bg-slate-100 text-slate-400' }}">
                        {{ $crop->is_active ? 'Active' : 'Inactive' }}
                    </span>
                </div>
                <div class="flex items-center gap-2">
                    <button onclick="toggleModal('edit-crop-{{ $crop->id }}')"
                        class="inline-flex items-center justify-center w-8 h-8 rounded-lg bg-[#3A7D44]/10 text-[#3A7D44] hover:bg-[#3A7D44]/15 dark:bg-[#3A7D44]/10 dark:hover:bg-[#3A7D44]/15 dark:text-[#3A7D44] transition"
                        title="Edit Crop">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                        </svg>
                    </button>
                    <form method="POST" action="{{ route('admin.crops.destroy', $crop) }}">
                        @csrf @method('DELETE')
                        <button type="button"
                            onclick="swalConfirm(this.closest('form'), {title: 'Delete Crop?', text: 'Delete {{ addslashes($crop->name) }}?', confirmText: 'Yes, delete', icon: 'warning', confirmColor: '#ef4444'})"
                            class="inline-flex items-center justify-center w-8 h-8 rounded-lg bg-rose-50 text-rose-600 hover:bg-rose-100 dark:bg-rose-950/20 dark:hover:bg-rose-950/40 dark:text-rose-400 transition"
                            title="Delete Crop">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                            </svg>
                        </button>
                    </form>
                </div>
            </div>

            {{-- Variety Rows --}}
            @if ($crop->varieties->count())
            <div class="bg-slate-50/50 rounded-xl border border-slate-100 overflow-hidden">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-slate-100">
                            <th class="text-left px-4 py-2.5 text-[10px] font-extrabold text-slate-500 uppercase tracking-widest">Variety</th>
                            <th class="text-left px-4 py-2.5 text-[10px] font-extrabold text-slate-500 uppercase tracking-widest">Price/kg</th>
                            <th class="text-left px-4 py-2.5 text-[10px] font-extrabold text-slate-500 uppercase tracking-widest">Status</th>
                            <th class="text-right px-4 py-2.5 text-[10px] font-extrabold text-slate-500 uppercase tracking-widest">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($crop->varieties as $variety)
                        <tr class="border-b border-slate-50 last:border-0 hover:bg-white/50 transition">
                            <td class="px-4 py-2.5 text-slate-700 font-semibold text-sm">{{ $variety->name }}</td>
                            <td class="px-4 py-2.5 text-slate-700 font-mono text-sm font-bold">₱{{ number_format($variety->price_per_kg, 2) }}</td>
                            <td class="px-4 py-2.5">
                                <span class="text-[10px] font-bold px-2 py-0.5 rounded-lg uppercase tracking-wide {{ $variety->is_active ? 'bg-[#3A7D44]/10 text-[#3A7D44]' : 'bg-slate-100 text-slate-400' }}">
                                    {{ $variety->is_active ? 'Active' : 'Inactive' }}
                                </span>
                            </td>
                            <td class="px-4 py-2.5 text-right">
                                <div class="flex justify-end items-center gap-2">
                                    <button onclick="toggleModal('edit-variety-{{ $variety->id }}')"
                                        class="inline-flex items-center justify-center w-7 h-7 rounded-lg bg-[#3A7D44]/10 text-[#3A7D44] hover:bg-[#3A7D44]/15 dark:bg-[#3A7D44]/10 dark:hover:bg-[#3A7D44]/15 dark:text-[#3A7D44] transition"
                                        title="Edit Variety">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                                        </svg>
                                    </button>
                                    <form method="POST" action="{{ route('admin.crops.varieties.destroy', $variety) }}">
                                        @csrf @method('DELETE')
                                        <button type="button"
                                            onclick="swalConfirm(this.closest('form'), {title: 'Delete Variety?', text: 'Delete {{ addslashes($variety->name) }}?', confirmText: 'Yes, delete', icon: 'warning', confirmColor: '#ef4444'})"
                                            class="inline-flex items-center justify-center w-7 h-7 rounded-lg bg-rose-50 text-rose-600 hover:bg-rose-100 dark:bg-rose-950/20 dark:hover:bg-rose-950/40 dark:text-rose-400 transition"
                                            title="Delete Variety">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                            </svg>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>

                        {{-- Edit Variety Modal --}}
                        <div id="edit-variety-{{ $variety->id }}" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-slate-950/40 backdrop-blur-sm">
                            <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md p-7 border border-slate-100">
                                <h3 class="text-base font-extrabold text-slate-800 heading-font mb-5">Edit Variety — {{ $variety->name }}</h3>
                                <form method="POST" action="{{ route('admin.crops.varieties.update', $variety) }}">
                                    @csrf @method('PUT')
                                    <div class="mb-4">
                                        <label class="block text-[10px] font-extrabold text-slate-500 uppercase tracking-widest mb-1.5">Variety Name</label>
                                        <input type="text" name="name" value="{{ $variety->name }}"
                                            class="w-full border border-slate-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-[#3A7D44]/20 focus:border-[#3A7D44] bg-slate-50/50 text-slate-800 transition"
                                            required />
                                    </div>
                                    <div class="mb-4">
                                        <label class="block text-[10px] font-extrabold text-slate-500 uppercase tracking-widest mb-1.5">Price per kg (₱)</label>
                                        <input type="number" name="price_per_kg" value="{{ $variety->price_per_kg }}"
                                            step="0.01" min="0"
                                            class="w-full border border-slate-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-[#3A7D44]/20 focus:border-[#3A7D44] bg-slate-50/50 text-slate-800 transition"
                                            required />
                                    </div>
                                    <div class="mb-6">
                                        <label class="block text-[10px] font-extrabold text-slate-500 uppercase tracking-widest mb-1.5">Status</label>
                                        <select name="is_active"
                                            class="w-full border border-slate-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-[#3A7D44]/20 focus:border-[#3A7D44] bg-slate-50/50 text-slate-800 transition">
                                            <option value="1" {{ $variety->is_active ? 'selected' : '' }}>Active</option>
                                            <option value="0" {{ !$variety->is_active ? 'selected' : '' }}>Inactive</option>
                                        </select>
                                    </div>
                                    <div class="flex gap-3">
                                        <button type="submit"
                                            class="flex-1 bg-slate-800 text-white text-xs font-bold py-2.5 rounded-xl hover:bg-slate-700 transition shadow-sm">
                                            Save Changes
                                        </button>
                                        <button type="button" onclick="toggleModal('edit-variety-{{ $variety->id }}')"
                                            class="flex-1 bg-slate-100 text-slate-700 text-xs font-bold py-2.5 rounded-xl hover:bg-slate-200 transition">
                                            Cancel
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @else
                <p class="text-xs text-slate-400 font-semibold mt-1 italic">No varieties added yet.</p>
            @endif
        </div>

        {{-- Edit Crop Modal --}}
        <div id="edit-crop-{{ $crop->id }}" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-slate-950/40 backdrop-blur-sm">
            <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md p-7 border border-slate-100">
                <h3 class="text-base font-extrabold text-slate-800 heading-font mb-5">Edit Crop — {{ $crop->name }}</h3>
                <form method="POST" action="{{ route('admin.crops.update', $crop) }}">
                    @csrf @method('PUT')
                    <div class="mb-4">
                        <label class="block text-[10px] font-extrabold text-slate-500 uppercase tracking-widest mb-1.5">Category</label>
                        <select name="crop_category_id"
                            class="w-full border border-slate-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-[#3A7D44]/20 focus:border-[#3A7D44] bg-slate-50/50 text-slate-800 transition"
                            required>
                            @foreach ($categories as $cat)
                                <option value="{{ $cat->id }}" {{ $crop->crop_category_id === $cat->id ? 'selected' : '' }}>
                                    {{ $cat->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-4">
                        <label class="block text-[10px] font-extrabold text-slate-500 uppercase tracking-widest mb-1.5">Crop Name</label>
                        <input type="text" name="name" value="{{ $crop->name }}"
                            class="w-full border border-slate-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-[#3A7D44]/20 focus:border-[#3A7D44] bg-slate-50/50 text-slate-800 transition"
                            required />
                    </div>
                    <div class="mb-6">
                        <label class="block text-[10px] font-extrabold text-slate-500 uppercase tracking-widest mb-1.5">Status</label>
                        <select name="is_active"
                            class="w-full border border-slate-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-[#3A7D44]/20 focus:border-[#3A7D44] bg-slate-50/50 text-slate-800 transition">
                            <option value="1" {{ $crop->is_active ? 'selected' : '' }}>Active</option>
                            <option value="0" {{ !$crop->is_active ? 'selected' : '' }}>Inactive</option>
                        </select>
                    </div>
                    <div class="flex gap-3">
                        <button type="submit"
                            class="flex-1 bg-slate-800 text-white text-xs font-bold py-2.5 rounded-xl hover:bg-slate-700 transition shadow-sm">
                            Save Changes
                        </button>
                        <button type="button" onclick="toggleModal('edit-crop-{{ $crop->id }}')"
                            class="flex-1 bg-slate-100 text-slate-700 text-xs font-bold py-2.5 rounded-xl hover:bg-slate-200 transition">
                            Cancel
                        </button>
                    </div>
                </form>
            </div>
        </div>
        @endforeach

        {{-- Edit Category Modal --}}
        <div id="edit-category-{{ $category->id }}" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-slate-950/40 backdrop-blur-sm">
            <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md p-7 border border-slate-100">
                <h3 class="text-base font-extrabold text-slate-800 heading-font mb-5">Edit Category — {{ $category->name }}</h3>
                <form method="POST" action="{{ route('admin.crops.categories.update', $category) }}">
                    @csrf @method('PUT')
                    <div class="mb-4">
                        <label class="block text-[10px] font-extrabold text-slate-500 uppercase tracking-widest mb-1.5">Category Name</label>
                        <input type="text" name="name" value="{{ $category->name }}"
                            class="w-full border border-slate-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-[#3A7D44]/20 focus:border-[#3A7D44] bg-slate-50/50 text-slate-800 transition"
                            required />
                    </div>
                    <div class="mb-6">
                        <label class="block text-[10px] font-extrabold text-slate-500 uppercase tracking-widest mb-1.5">Description</label>
                        <input type="text" name="description" value="{{ $category->description }}"
                            class="w-full border border-slate-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-[#3A7D44]/20 focus:border-[#3A7D44] bg-slate-50/50 text-slate-800 transition" />
                    </div>
                    <div class="flex gap-3">
                        <button type="submit"
                            class="flex-1 bg-slate-800 text-white text-xs font-bold py-2.5 rounded-xl hover:bg-slate-700 transition shadow-sm">
                            Save Changes
                        </button>
                        <button type="button" onclick="toggleModal('edit-category-{{ $category->id }}')"
                            class="flex-1 bg-slate-100 text-slate-700 text-xs font-bold py-2.5 rounded-xl hover:bg-slate-200 transition">
                            Cancel
                        </button>
                    </div>
                </form>
            </div>
        </div>

    </div>
    @endforeach

</div>

<script>
function toggleModal(id) {
    const modal = document.getElementById(id);
    modal.classList.toggle('hidden');
}

// Close modal on backdrop click
document.querySelectorAll('[id^="edit-"]').forEach(modal => {
    modal.addEventListener('click', function (e) {
        if (e.target === this) toggleModal(this.id);
    });
});
</script>
</x-layout>
