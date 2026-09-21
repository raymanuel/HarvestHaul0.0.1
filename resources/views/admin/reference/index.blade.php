<x-layout title="Grades & Packaging — HarvestHaul">
    <x-page-header title="Grades & Packaging" :showDate="true" />

    <p class="text-sm text-slate-500 dark:text-slate-400 mb-8 max-w-2xl">
        These are the choices personnel and farmers pick from when recording harvests and receiving crops. Deactivate a value to hide it from new forms without deleting past records.
    </p>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <x-card>
            <x-section-label title="Crop Grades" width="w-16" />

            <form method="POST" action="{{ route('admin.reference.grades.store') }}" class="space-y-3 mb-5">
                @csrf
                <div class="flex flex-col sm:flex-row gap-3">
                    <input type="text" name="name" required placeholder="Grade name (e.g. Class A)"
                           class="flex-1 border border-slate-200 dark:border-slate-600 rounded-xl px-4 py-2.5 text-sm bg-slate-50/50 dark:bg-slate-700/50 text-slate-800 dark:text-white focus:outline-none focus:ring-2 focus:ring-brand-700/25" />
                    <input type="number" name="sort_order" min="0" placeholder="Order"
                           class="w-full sm:w-24 border border-slate-200 dark:border-slate-600 rounded-xl px-4 py-2.5 text-sm bg-slate-50/50 dark:bg-slate-700/50 text-slate-800 dark:text-white focus:outline-none focus:ring-2 focus:ring-brand-700/25" />
                </div>
                <input type="text" name="description" placeholder="What qualifies crop for this grade (optional)"
                       class="w-full border border-slate-200 dark:border-slate-600 rounded-xl px-4 py-2.5 text-sm bg-slate-50/50 dark:bg-slate-700/50 text-slate-800 dark:text-white focus:outline-none focus:ring-2 focus:ring-brand-700/25" />
                <div class="flex justify-end">
                    <x-button variant="primary" size="sm">Add Grade</x-button>
                </div>
            </form>

            @if($grades->isEmpty())
                <x-empty-state type="first-use" title="No grades yet" description="Add the first grade personnel can choose from." />
            @else
                <ul class="space-y-2">
                    @foreach($grades as $grade)
                        <li class="flex items-center justify-between gap-3 p-3 rounded-xl border border-slate-200/70 dark:border-slate-700">
                            <div>
                                <div class="flex items-center gap-3">
                                    <span class="text-sm font-bold text-slate-800 dark:text-slate-100">{{ $grade->name }}</span>
                                    @if($grade->code)<span class="text-[10px] font-mono text-slate-400">{{ $grade->code }}</span>@endif
                                    <x-badge :status="$grade->is_active ? 'active' : 'inactive'" dot />
                                </div>
                                @if($grade->description)
                                    <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">{{ $grade->description }}</p>
                                @endif
                            </div>
                            <div class="flex items-center gap-3 shrink-0">
                                <x-modal triggerLabel="Edit">
                                    <h2 class="text-lg font-bold text-slate-900 dark:text-white mb-4">Edit {{ $grade->name }}</h2>
                                    <form method="POST" action="{{ route('admin.reference.grades.update', $grade) }}" class="space-y-4">
                                        @csrf
                                        @method('PUT')
                                        <x-input name="name" label="Name" required :value="$grade->name" />
                                        <x-input name="code" label="Code" :value="$grade->code" />
                                        <div>
                                            <label for="description-{{ $grade->id }}" class="block text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-1.5">Description</label>
                                            <textarea name="description" id="description-{{ $grade->id }}" rows="3" maxlength="500" placeholder="What qualifies crop for this grade…" class="w-full border border-slate-200 dark:border-slate-600 rounded-xl px-4 py-2.5 text-sm bg-slate-50/50 dark:bg-slate-700/50 text-slate-800 dark:text-white">{{ $grade->description }}</textarea>
                                        </div>
                                        <x-input name="sort_order" type="number" min="0" label="Order" :value="$grade->sort_order" />
                                        <label class="flex items-center gap-2 text-sm text-slate-700 dark:text-slate-300">
                                            <input type="checkbox" name="is_active" value="1" @checked($grade->is_active)>
                                            Active
                                        </label>
                                        <div class="flex justify-end gap-3 pt-1">
                                            <button type="button" data-modal-close class="px-4 py-2 rounded-lg text-sm font-semibold text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-700">Cancel</button>
                                            <x-button variant="primary" size="sm">Save Changes</x-button>
                                        </div>
                                    </form>
                                </x-modal>

                                @if($grade->is_active)
                                    <form method="POST" action="{{ route('admin.reference.grades.destroy', $grade) }}" id="grade-{{ $grade->id }}">
                                        @csrf
                                        @method('DELETE')
                                        <button type="button"
                                            onclick="swalConfirm(document.getElementById('grade-{{ $grade->id }}'), {title:'Deactivate Grade', text:'Deactivate {{ $grade->name }}? It will no longer appear in new forms. Past records keep it.', icon:'warning', confirmText:'Yes, deactivate', cancelText:'Cancel', confirmColor:'#ef4444'})"
                                            class="text-[11px] font-bold text-[var(--color-error-text)] hover:underline cursor-pointer">Deactivate</button>
                                    </form>
                                @endif
                            </div>
                        </li>
                    @endforeach
                </ul>
            @endif
        </x-card>

        <x-card>
            <x-section-label title="Packaging Types" width="w-16" />

            <form method="POST" action="{{ route('admin.reference.packaging.store') }}" class="flex flex-col sm:flex-row gap-3 mb-5">
                @csrf
                <input type="text" name="name" required placeholder="Packaging name (e.g. Sack)"
                       class="flex-1 border border-slate-200 dark:border-slate-600 rounded-xl px-4 py-2.5 text-sm bg-slate-50/50 dark:bg-slate-700/50 text-slate-800 dark:text-white focus:outline-none focus:ring-2 focus:ring-brand-700/25" />
                <input type="number" name="sort_order" min="0" placeholder="Order"
                       class="w-full sm:w-24 border border-slate-200 dark:border-slate-600 rounded-xl px-4 py-2.5 text-sm bg-slate-50/50 dark:bg-slate-700/50 text-slate-800 dark:text-white focus:outline-none focus:ring-2 focus:ring-brand-700/25" />
                <x-button variant="primary" size="sm">Add Packaging</x-button>
            </form>

            @if($packagingTypes->isEmpty())
                <x-empty-state type="first-use" title="No packaging yet" description="Add the first packaging type farmers can choose." />
            @else
                <ul class="space-y-2">
                    @foreach($packagingTypes as $type)
                        <li class="flex items-center justify-between gap-3 p-3 rounded-xl border border-slate-200/70 dark:border-slate-700">
                            <div class="flex items-center gap-3">
                                <span class="text-sm font-bold text-slate-800 dark:text-slate-100">{{ $type->name }}</span>
                                <x-badge :status="$type->is_active ? 'active' : 'inactive'" dot />
                            </div>
                            <div class="flex items-center gap-3 shrink-0">
                                <x-modal triggerLabel="Edit">
                                    <h2 class="text-lg font-bold text-slate-900 dark:text-white mb-4">Edit {{ $type->name }}</h2>
                                    <form method="POST" action="{{ route('admin.reference.packaging.update', $type) }}" class="space-y-4">
                                        @csrf
                                        @method('PUT')
                                        <x-input name="name" label="Name" required :value="$type->name" />
                                        <x-input name="sort_order" type="number" min="0" label="Order" :value="$type->sort_order" />
                                        <label class="flex items-center gap-2 text-sm text-slate-700 dark:text-slate-300">
                                            <input type="checkbox" name="is_active" value="1" @checked($type->is_active)>
                                            Active
                                        </label>
                                        <div class="flex justify-end gap-3 pt-1">
                                            <button type="button" data-modal-close class="px-4 py-2 rounded-lg text-sm font-semibold text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-700">Cancel</button>
                                            <x-button variant="primary" size="sm">Save Changes</x-button>
                                        </div>
                                    </form>
                                </x-modal>

                                @if($type->is_active)
                                    <form method="POST" action="{{ route('admin.reference.packaging.destroy', $type) }}" id="packaging-{{ $type->id }}">
                                        @csrf
                                        @method('DELETE')
                                        <button type="button"
                                            onclick="swalConfirm(document.getElementById('packaging-{{ $type->id }}'), {title:'Deactivate Packaging', text:'Deactivate {{ $type->name }}? It will no longer appear in new forms. Past records keep it.', icon:'warning', confirmText:'Yes, deactivate', cancelText:'Cancel', confirmColor:'#ef4444'})"
                                            class="text-[11px] font-bold text-[var(--color-error-text)] hover:underline cursor-pointer">Deactivate</button>
                                    </form>
                                @endif
                            </div>
                        </li>
                    @endforeach
                </ul>
            @endif
        </x-card>
    </div>
</x-layout>
