<x-layout title="Crops — HarvestHaul">
    <x-page-header title="Crop Matrix" :showDate="true" />

    <p class="text-sm text-slate-500 dark:text-slate-400 mb-8 max-w-2xl">
        Categories hold crops; crops hold varieties. Farmers pick from these lists when they file a haul request.
    </p>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <x-card>
            <x-section-label title="Categories" width="w-16" />

            <form method="POST" action="{{ route('admin.crops.categories.store') }}" class="space-y-3 mb-5">
                @csrf
                <x-input name="name" label="New Category" required placeholder="e.g. Vegetables" />
                <x-button variant="primary" size="sm" full>Add Category</x-button>
            </form>

            @foreach($categories as $category)
                <div class="flex items-center justify-between gap-2 py-2 border-t border-slate-100 dark:border-slate-700/60">
                    <span class="text-sm font-bold text-slate-800 dark:text-slate-100">{{ $category->name }}</span>
                    <div class="flex items-center gap-3 shrink-0">
                        <x-modal triggerLabel="Edit" triggerClass="text-[11px] font-bold text-brand-700 dark:text-gold-light hover:underline cursor-pointer">
                            <h2 class="text-lg font-bold text-slate-900 dark:text-white mb-4">Edit {{ $category->name }}</h2>
                            <form method="POST" action="{{ route('admin.crops.categories.update', $category) }}" class="space-y-4">
                                @csrf
                                @method('PUT')
                                <x-input name="name" label="Name" required :value="$category->name" />
                                <div>
                                    <label class="block text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-1.5">Description</label>
                                    <textarea name="description" rows="2" maxlength="500" class="w-full border border-slate-200 dark:border-slate-600 rounded-xl px-4 py-2.5 text-sm bg-slate-50/50 dark:bg-slate-700/50 text-slate-800 dark:text-white">{{ $category->description }}</textarea>
                                </div>
                                <x-select name="status" label="Status" required :placeholder="null" :value="$category->status" :options="['active' => 'Active', 'inactive' => 'Inactive']" />
                                <div class="flex justify-end gap-3 pt-1">
                                    <button type="button" data-modal-close class="px-4 py-2 rounded-lg text-sm font-semibold text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-700">Cancel</button>
                                    <x-button variant="primary" size="sm">Save Changes</x-button>
                                </div>
                            </form>
                        </x-modal>
                        <form method="POST" action="{{ route('admin.crops.categories.destroy', $category) }}" id="cat-{{ $category->id }}">
                            @csrf
                            @method('DELETE')
                            <button type="button"
                                onclick="swalConfirm(document.getElementById('cat-{{ $category->id }}'), {title:'Delete Category', text:'Delete {{ $category->name }}? This only works when it has no crops.', icon:'warning', confirmText:'Yes, delete', cancelText:'Cancel', confirmColor:'#ef4444'})"
                                class="text-[11px] font-bold text-[var(--color-error-text)] hover:underline cursor-pointer">Delete</button>
                        </form>
                    </div>
                </div>
            @endforeach

            <form method="POST" action="{{ route('admin.crops.store') }}" class="space-y-3 mt-5 pt-5 border-t border-slate-100 dark:border-slate-700/60">
                @csrf
                <x-section-label title="Add Crop" width="w-12" />
                <x-select name="crop_category_id" label="Category" required :placeholder="null"
                    :options="$categories->pluck('name', 'id')->all()" />
                <x-input name="name" label="Crop Name" required placeholder="e.g. Eggplant" />
                <x-button variant="primary" size="sm" full>Add Crop</x-button>
            </form>
        </x-card>

        <div class="lg:col-span-2 space-y-5">
            @if($categories->isEmpty())
                <x-empty-state type="first-use" title="No categories yet" description="Add a category first, then crops under it." />
            @else
                @foreach($categories as $category)
                    <x-card>
                        <x-section-label :title="$category->name" width="w-16" />

                        @if($category->crops->isEmpty())
                            <p class="text-xs text-slate-400">No crops under this category yet.</p>
                        @else
                            <div class="space-y-4">
                                @foreach($category->crops as $crop)
                                    <div class="rounded-xl border border-slate-200/70 dark:border-slate-700 p-4">
                                        <div class="flex items-center justify-between gap-3">
                                            <div>
                                                <p class="text-sm font-bold text-slate-800 dark:text-slate-100">{{ $crop->name }}</p>
                                                @if($crop->description)
                                                    <p class="text-xs text-slate-500 dark:text-slate-400">{{ $crop->description }}</p>
                                                @endif
                                            </div>
                                            <div class="flex items-center gap-3 shrink-0">
                                                <x-badge :status="$crop->status" dot />
                                                <x-modal triggerLabel="Edit" triggerClass="text-[11px] font-bold text-brand-700 dark:text-gold-light hover:underline cursor-pointer">
                                                    <h2 class="text-lg font-bold text-slate-900 dark:text-white mb-4">Edit {{ $crop->name }}</h2>
                                                    <form method="POST" action="{{ route('admin.crops.update', $crop) }}" class="space-y-4">
                                                        @csrf
                                                        @method('PUT')
                                                        <x-select name="crop_category_id" label="Category" required :placeholder="null" :value="$crop->crop_category_id" :options="$categories->pluck('name', 'id')->all()" />
                                                        <x-input name="name" label="Crop Name" required :value="$crop->name" />
                                                        <div>
                                                            <label class="block text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-1.5">Description</label>
                                                            <textarea name="description" rows="2" maxlength="500" class="w-full border border-slate-200 dark:border-slate-600 rounded-xl px-4 py-2.5 text-sm bg-slate-50/50 dark:bg-slate-700/50 text-slate-800 dark:text-white">{{ $crop->description }}</textarea>
                                                        </div>
                                                        <x-select name="status" label="Status" required :placeholder="null" :value="$crop->status" :options="['active' => 'Active', 'inactive' => 'Inactive']" />
                                                        <div class="flex justify-end gap-3 pt-1">
                                                            <button type="button" data-modal-close class="px-4 py-2 rounded-lg text-sm font-semibold text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-700">Cancel</button>
                                                            <x-button variant="primary" size="sm">Save Changes</x-button>
                                                        </div>
                                                    </form>
                                                </x-modal>
                                                <form method="POST" action="{{ route('admin.crops.destroy', $crop) }}" id="crop-{{ $crop->id }}">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="button"
                                                        onclick="swalConfirm(document.getElementById('crop-{{ $crop->id }}'), {title:'Delete Crop', text:'Delete {{ $crop->name }}? This only works when it has no varieties.', icon:'warning', confirmText:'Yes, delete', cancelText:'Cancel', confirmColor:'#ef4444'})"
                                                        class="text-[11px] font-bold text-[var(--color-error-text)] hover:underline cursor-pointer">Delete</button>
                                                </form>
                                            </div>
                                        </div>

                                        @if($crop->varieties->isNotEmpty())
                                            <ul class="mt-3 space-y-1.5">
                                                @foreach($crop->varieties as $variety)
                                                    <li class="flex items-center justify-between gap-3 text-[11px] bg-slate-100 dark:bg-slate-700/50 px-2.5 py-1.5 rounded-lg">
                                                        <span class="font-semibold text-slate-600 dark:text-slate-300">
                                                            {{ $variety->name }} · ₱{{ number_format((float) $variety->price_per_kg, 2) }}/kg
                                                            <x-badge :status="$variety->status" dot class="ml-1" />
                                                        </span>
                                                        <span class="flex items-center gap-2 shrink-0">
                                                            <x-modal triggerLabel="Edit" triggerClass="font-bold text-brand-700 dark:text-gold-light hover:underline cursor-pointer">
                                                                <h2 class="text-lg font-bold text-slate-900 dark:text-white mb-4">Edit {{ $variety->name }}</h2>
                                                                <form method="POST" action="{{ route('admin.crops.varieties.update', $variety) }}" class="space-y-4">
                                                                    @csrf
                                                                    @method('PUT')
                                                                    <x-input name="name" label="Variety" required :value="$variety->name" />
                                                                    <x-input name="price_per_kg" label="Price / kg" type="number" step="0.01" required :value="$variety->price_per_kg" />
                                                                    <div>
                                                                        <label class="block text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-1.5">Description</label>
                                                                        <textarea name="description" rows="2" maxlength="500" class="w-full border border-slate-200 dark:border-slate-600 rounded-xl px-4 py-2.5 text-sm bg-slate-50/50 dark:bg-slate-700/50 text-slate-800 dark:text-white">{{ $variety->description }}</textarea>
                                                                    </div>
                                                                    <x-select name="status" label="Status" required :placeholder="null" :value="$variety->status" :options="['active' => 'Active', 'inactive' => 'Inactive']" />
                                                                    <div class="flex justify-end gap-3 pt-1">
                                                                        <button type="button" data-modal-close class="px-4 py-2 rounded-lg text-sm font-semibold text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-700">Cancel</button>
                                                                        <x-button variant="primary" size="sm">Save Changes</x-button>
                                                                    </div>
                                                                </form>
                                                            </x-modal>
                                                            <form method="POST" action="{{ route('admin.crops.varieties.destroy', $variety) }}" id="variety-{{ $variety->id }}">
                                                                @csrf
                                                                @method('DELETE')
                                                                <button type="button"
                                                                    onclick="swalConfirm(document.getElementById('variety-{{ $variety->id }}'), {title:'Delete Variety', text:'Delete {{ $variety->name }}?', icon:'warning', confirmText:'Yes, delete', cancelText:'Cancel', confirmColor:'#ef4444'})"
                                                                    class="font-bold text-[var(--color-error-text)] hover:underline cursor-pointer">Delete</button>
                                                            </form>
                                                        </span>
                                                    </li>
                                                @endforeach
                                            </ul>
                                        @endif

                                        <details class="mt-3">
                                            <summary class="text-[11px] font-bold text-brand-700 dark:text-gold-light cursor-pointer">Add Variety</summary>
                                            <form method="POST" action="{{ route('admin.crops.varieties.store') }}" class="grid grid-cols-1 sm:grid-cols-3 gap-3 mt-3">
                                                @csrf
                                                <input type="hidden" name="crop_id" value="{{ $crop->id }}" />
                                                <x-input name="name" label="Variety" required />
                                                <x-input name="price_per_kg" label="Price / kg" type="number" step="0.01" required />
                                                <div class="flex items-end"><x-button variant="secondary" size="sm" full>Add</x-button></div>
                                            </form>
                                        </details>
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    </x-card>
                @endforeach
            @endif
        </div>
    </div>
</x-layout>
