<x-layout title="Import Farmers — Cooperative">
    <x-page-header variant="back-link" title="Import Farmers" :back-href="route('coop.farmers.index')" back-label="← Back to Farmer Membership" />

    <div class="max-w-2xl space-y-6">
        <x-card>
            <x-section-label title="Upload CSV" width="w-20" />
            <p class="text-sm text-slate-500 dark:text-slate-400 mb-4">
                Columns: <code>name, email, phone, farm_location, latitude, longitude</code>. Only <code>name</code> and <code>email</code> are required. Each imported farmer is approved immediately and gets an email to set their own password.
            </p>
            <a href="{{ route('coop.farmers.import.template') }}" class="text-xs font-bold text-brand-700 dark:text-gold-light hover:underline">Download CSV template</a>

            <form method="POST" action="{{ route('coop.farmers.import.store') }}" enctype="multipart/form-data" class="space-y-4 mt-4">
                @csrf
                <input type="file" name="file" accept=".csv,.txt" required
                       class="block w-full text-sm text-slate-600 dark:text-slate-300 file:mr-4 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-bold file:bg-slate-900 file:text-white dark:file:bg-white dark:file:text-slate-900" />
                @error('file')<p class="text-xs text-[var(--color-error-text)]">{{ $message }}</p>@enderror
                <x-button variant="primary" size="sm">Import Farmers</x-button>
            </form>
        </x-card>

        @if(session('importSkipped') && count(session('importSkipped')) > 0)
            <x-card>
                <x-section-label title="Skipped Rows" width="w-20" />
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm">
                        <thead>
                            <tr class="border-b border-slate-200 dark:border-slate-700/70 text-xs font-bold uppercase tracking-wide text-slate-500 dark:text-slate-400">
                                <th class="px-4 py-2">Row</th>
                                <th class="px-4 py-2">Email</th>
                                <th class="px-4 py-2">Reason</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                            @foreach(session('importSkipped') as $skip)
                                <tr>
                                    <td class="px-4 py-2 text-slate-600 dark:text-slate-300">{{ $skip['row'] }}</td>
                                    <td class="px-4 py-2 text-slate-600 dark:text-slate-300">{{ $skip['email'] }}</td>
                                    <td class="px-4 py-2 text-[var(--color-error-text)]">{{ $skip['reason'] }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <p class="text-xs text-slate-500 dark:text-slate-400 mt-3">Fix these rows and re-upload just them — already-imported farmers won't be duplicated.</p>
            </x-card>
        @endif
    </div>
</x-layout>
