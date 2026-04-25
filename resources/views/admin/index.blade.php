<x-layout>
<div class="w-full max-w-6xl mx-auto px-4 py-10">

    <header class="mb-10">
        <h1 class="text-3xl font-bold text-gray-900">Crop Manager</h1>
        <p class="text-gray-400 text-sm mt-1">Manage crop categories, crops, and variety pricing for the logistics platform.</p>
    </header>

    {{-- Flash Messages --}}
    @if (session('success'))
        <div class="mb-6 bg-green-50 border border-green-200 text-green-700 text-sm rounded-xl px-5 py-3">
            {{ session('success') }}
        </div>
    @endif
    @if (session('error'))
        <div class="mb-6 bg-red-50 border border-red-200 text-red-700 text-sm rounded-xl px-5 py-3">
            {{ session('error') }}
        </div>
    @endif

    {{-- ================================================ --}}
    {{-- ADD CATEGORY --}}
    {{-- ================================================ --}}
    <section class="mb-10 bg-white border border-gray-200 rounded-2xl p-6 shadow-sm">
        <h2 class="text-lg font-semibold text-gray-800 mb-4">Add New Category</h2>
        <form method="POST" action="{{ route('admin.crops.categories.store') }}" class="flex flex-col sm:flex-row gap-3">
            @csrf
            <input type="text" name="name" placeholder="Category name (e.g. Fruits)"
                class="flex-1 border border-gray-300 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-gray-400"
                required />
            <input type="text" name="description" placeholder="Description (optional)"
                class="flex-1 border border-gray-300 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-gray-400" />
            <button type="submit"
                class="bg-gray-900 text-white text-sm font-semibold px-6 py-2.5 rounded-xl hover:bg-gray-700 transition">
                Add Category
            </button>
        </form>
        @error('name') <p class="text-red-500 text-xs mt-2">{{ $message }}</p> @enderror
    </section>

    {{-- ================================================ --}}
    {{-- ADD CROP --}}
    {{-- ================================================ --}}
    <section class="mb-10 bg-white border border-gray-200 rounded-2xl p-6 shadow-sm">
        <h2 class="text-lg font-semibold text-gray-800 mb-4">Add New Crop</h2>
        <form method="POST" action="{{ route('admin.crops.store') }}" class="flex flex-col sm:flex-row gap-3">
            @csrf
            <select name="crop_category_id"
                class="flex-1 border border-gray-300 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-gray-400"
                required>
                <option value="" disabled selected>Select category</option>
                @foreach ($categories as $cat)
                    <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                @endforeach
            </select>
            <input type="text" name="name" placeholder="Crop name (e.g. Pineapple)"
                class="flex-1 border border-gray-300 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-gray-400"
                required />
            <button type="submit"
                class="bg-gray-900 text-white text-sm font-semibold px-6 py-2.5 rounded-xl hover:bg-gray-700 transition">
                Add Crop
            </button>
        </form>
    </section>

    {{-- ================================================ --}}
    {{-- ADD VARIETY --}}
    {{-- ================================================ --}}
    <section class="mb-10 bg-white border border-gray-200 rounded-2xl p-6 shadow-sm">
        <h2 class="text-lg font-semibold text-gray-800 mb-4">Add New Variety & Price</h2>
        <form method="POST" action="{{ route('admin.crops.varieties.store') }}" class="flex flex-col sm:flex-row gap-3">
            @csrf
            <select name="crop_id"
                class="flex-1 border border-gray-300 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-gray-400"
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
                class="flex-1 border border-gray-300 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-gray-400"
                required />
            <input type="number" name="price_per_kg" placeholder="Price/kg (₱)" step="0.01" min="0"
                class="w-40 border border-gray-300 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-gray-400"
                required />
            <button type="submit"
                class="bg-[#2D8A37] text-white text-sm font-semibold px-6 py-2.5 rounded-xl hover:bg-opacity-90 transition">
                Add Variety
            </button>
        </form>
    </section>

    {{-- ================================================ --}}
    {{-- CATEGORIES + CROPS + VARIETIES TABLE --}}
    {{-- ================================================ --}}
    @foreach ($categories as $category)
    <section class="mb-8 bg-white border border-gray-200 rounded-2xl shadow-sm overflow-hidden">

        {{-- Category Header --}}
        <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100 bg-gray-50">
            <div>
                <span class="text-sm font-bold text-gray-800 uppercase tracking-widest">{{ $category->name }}</span>
                @if ($category->description)
                    <span class="ml-2 text-xs text-gray-400">— {{ $category->description }}</span>
                @endif
            </div>
            <div class="flex gap-2">
                {{-- Edit Category --}}
                <button onclick="toggleModal('edit-category-{{ $category->id }}')"
                    class="text-xs text-gray-500 hover:text-gray-800 border border-gray-200 rounded-lg px-3 py-1.5 transition">
                    Edit
                </button>
                {{-- Delete Category --}}
                <form method="POST" action="{{ route('admin.crops.categories.destroy', $category) }}">
                    @csrf @method('DELETE')
                    <button type="submit"
                        onclick="return confirm('Delete this category? This cannot be undone.')"
                        class="text-xs text-red-500 hover:text-red-700 border border-red-100 rounded-lg px-3 py-1.5 transition">
                        Delete
                    </button>
                </form>
            </div>
        </div>

        {{-- Crops & Varieties --}}
        @foreach ($category->crops as $crop)
        <div class="px-6 py-4 border-b border-gray-50 last:border-0">

            {{-- Crop Row --}}
            <div class="flex items-center justify-between mb-3">
                <div class="flex items-center gap-2">
                    <span class="text-sm font-semibold text-gray-700">{{ $crop->name }}</span>
                    <span class="text-xs px-2 py-0.5 rounded-full {{ $crop->is_active ? 'bg-green-50 text-green-600' : 'bg-gray-100 text-gray-400' }}">
                        {{ $crop->is_active ? 'Active' : 'Inactive' }}
                    </span>
                </div>
                <div class="flex gap-2">
                    <button onclick="toggleModal('edit-crop-{{ $crop->id }}')"
                        class="text-xs text-gray-500 hover:text-gray-800 border border-gray-200 rounded-lg px-3 py-1 transition">
                        Edit
                    </button>
                    <form method="POST" action="{{ route('admin.crops.destroy', $crop) }}">
                        @csrf @method('DELETE')
                        <button type="submit"
                            onclick="return confirm('Delete {{ $crop->name }}?')"
                            class="text-xs text-red-500 hover:text-red-700 border border-red-100 rounded-lg px-3 py-1 transition">
                            Delete
                        </button>
                    </form>
                </div>
            </div>

            {{-- Variety Rows --}}
            @if ($crop->varieties->count())
            <table class="w-full text-sm">
                <thead>
                    <tr class="text-xs text-gray-400 uppercase tracking-wider border-b border-gray-100">
                        <th class="text-left py-1.5 font-medium">Variety</th>
                        <th class="text-left py-1.5 font-medium">Price/kg</th>
                        <th class="text-left py-1.5 font-medium">Status</th>
                        <th class="text-right py-1.5 font-medium">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($crop->varieties as $variety)
                    <tr class="border-b border-gray-50 last:border-0">
                        <td class="py-2 text-gray-700">{{ $variety->name }}</td>
                        <td class="py-2 text-gray-700 font-mono">₱{{ number_format($variety->price_per_kg, 2) }}</td>
                        <td class="py-2">
                            <span class="text-xs px-2 py-0.5 rounded-full {{ $variety->is_active ? 'bg-green-50 text-green-600' : 'bg-gray-100 text-gray-400' }}">
                                {{ $variety->is_active ? 'Active' : 'Inactive' }}
                            </span>
                        </td>
                        <td class="py-2 text-right">
                            <div class="flex justify-end gap-2">
                                <button onclick="toggleModal('edit-variety-{{ $variety->id }}')"
                                    class="text-xs text-gray-500 hover:text-gray-800 border border-gray-200 rounded-lg px-3 py-1 transition">
                                    Edit
                                </button>
                                <form method="POST" action="{{ route('admin.crops.varieties.destroy', $variety) }}">
                                    @csrf @method('DELETE')
                                    <button type="submit"
                                        onclick="return confirm('Delete {{ $variety->name }}?')"
                                        class="text-xs text-red-500 hover:text-red-700 border border-red-100 rounded-lg px-3 py-1 transition">
                                        Delete
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>

                    {{-- Edit Variety Modal --}}
                    <div id="edit-variety-{{ $variety->id }}" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-black/40">
                        <div class="bg-white rounded-2xl shadow-xl w-full max-w-md p-6">
                            <h3 class="text-base font-bold text-gray-800 mb-4">Edit Variety — {{ $variety->name }}</h3>
                            <form method="POST" action="{{ route('admin.crops.varieties.update', $variety) }}">
                                @csrf @method('PUT')
                                <div class="mb-4">
                                    <label class="block text-xs font-semibold text-gray-600 mb-1">Variety Name</label>
                                    <input type="text" name="name" value="{{ $variety->name }}"
                                        class="w-full border border-gray-300 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-gray-400"
                                        required />
                                </div>
                                <div class="mb-4">
                                    <label class="block text-xs font-semibold text-gray-600 mb-1">Price per kg (₱)</label>
                                    <input type="number" name="price_per_kg" value="{{ $variety->price_per_kg }}"
                                        step="0.01" min="0"
                                        class="w-full border border-gray-300 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-gray-400"
                                        required />
                                </div>
                                <div class="mb-6">
                                    <label class="block text-xs font-semibold text-gray-600 mb-1">Status</label>
                                    <select name="is_active"
                                        class="w-full border border-gray-300 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-gray-400">
                                        <option value="1" {{ $variety->is_active ? 'selected' : '' }}>Active</option>
                                        <option value="0" {{ !$variety->is_active ? 'selected' : '' }}>Inactive</option>
                                    </select>
                                </div>
                                <div class="flex gap-3">
                                    <button type="submit"
                                        class="flex-1 bg-gray-900 text-white text-sm font-bold py-2.5 rounded-xl hover:bg-gray-700 transition">
                                        Save Changes
                                    </button>
                                    <button type="button" onclick="toggleModal('edit-variety-{{ $variety->id }}')"
                                        class="flex-1 bg-gray-100 text-gray-700 text-sm font-bold py-2.5 rounded-xl hover:bg-gray-200 transition">
                                        Cancel
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                    @endforeach
                </tbody>
            </table>
            @else
                <p class="text-xs text-gray-400 mt-1">No varieties added yet.</p>
            @endif
        </div>

        {{-- Edit Crop Modal --}}
        <div id="edit-crop-{{ $crop->id }}" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-black/40">
            <div class="bg-white rounded-2xl shadow-xl w-full max-w-md p-6">
                <h3 class="text-base font-bold text-gray-800 mb-4">Edit Crop — {{ $crop->name }}</h3>
                <form method="POST" action="{{ route('admin.crops.update', $crop) }}">
                    @csrf @method('PUT')
                    <div class="mb-4">
                        <label class="block text-xs font-semibold text-gray-600 mb-1">Category</label>
                        <select name="crop_category_id"
                            class="w-full border border-gray-300 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-gray-400"
                            required>
                            @foreach ($categories as $cat)
                                <option value="{{ $cat->id }}" {{ $crop->crop_category_id === $cat->id ? 'selected' : '' }}>
                                    {{ $cat->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-4">
                        <label class="block text-xs font-semibold text-gray-600 mb-1">Crop Name</label>
                        <input type="text" name="name" value="{{ $crop->name }}"
                            class="w-full border border-gray-300 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-gray-400"
                            required />
                    </div>
                    <div class="mb-6">
                        <label class="block text-xs font-semibold text-gray-600 mb-1">Status</label>
                        <select name="is_active"
                            class="w-full border border-gray-300 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-gray-400">
                            <option value="1" {{ $crop->is_active ? 'selected' : '' }}>Active</option>
                            <option value="0" {{ !$crop->is_active ? 'selected' : '' }}>Inactive</option>
                        </select>
                    </div>
                    <div class="flex gap-3">
                        <button type="submit"
                            class="flex-1 bg-gray-900 text-white text-sm font-bold py-2.5 rounded-xl hover:bg-gray-700 transition">
                            Save Changes
                        </button>
                        <button type="button" onclick="toggleModal('edit-crop-{{ $crop->id }}')"
                            class="flex-1 bg-gray-100 text-gray-700 text-sm font-bold py-2.5 rounded-xl hover:bg-gray-200 transition">
                            Cancel
                        </button>
                    </div>
                </form>
            </div>
        </div>
        @endforeach

        {{-- Edit Category Modal --}}
        <div id="edit-category-{{ $category->id }}" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-black/40">
            <div class="bg-white rounded-2xl shadow-xl w-full max-w-md p-6">
                <h3 class="text-base font-bold text-gray-800 mb-4">Edit Category — {{ $category->name }}</h3>
                <form method="POST" action="{{ route('admin.crops.categories.update', $category) }}">
                    @csrf @method('PUT')
                    <div class="mb-4">
                        <label class="block text-xs font-semibold text-gray-600 mb-1">Category Name</label>
                        <input type="text" name="name" value="{{ $category->name }}"
                            class="w-full border border-gray-300 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-gray-400"
                            required />
                    </div>
                    <div class="mb-6">
                        <label class="block text-xs font-semibold text-gray-600 mb-1">Description</label>
                        <input type="text" name="description" value="{{ $category->description }}"
                            class="w-full border border-gray-300 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-gray-400" />
                    </div>
                    <div class="flex gap-3">
                        <button type="submit"
                            class="flex-1 bg-gray-900 text-white text-sm font-bold py-2.5 rounded-xl hover:bg-gray-700 transition">
                            Save Changes
                        </button>
                        <button type="button" onclick="toggleModal('edit-category-{{ $category->id }}')"
                            class="flex-1 bg-gray-100 text-gray-700 text-sm font-bold py-2.5 rounded-xl hover:bg-gray-200 transition">
                            Cancel
                        </button>
                    </div>
                </form>
            </div>
        </div>

    </section>
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
