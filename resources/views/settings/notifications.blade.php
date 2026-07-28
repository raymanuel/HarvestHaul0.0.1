<x-layout>
    <x-slot:title>Notification Settings</x-slot:title>

    <div class="max-w-2xl mx-auto px-4 py-8 space-y-6">
        <div>
            <h1 class="text-xl font-extrabold heading-font text-slate-800 dark:text-white">Notification Settings</h1>
            <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">Choose which notifications you want to receive.</p>
        </div>

        <x-flash-success />

        <form method="POST" action="{{ route('notifications.preferences.update') }}" class="space-y-3">
            @csrf
            @method('PUT')

            @php
                $labels = [
                    'logistics' => ['Logistics & Delivery', 'Driver arrivals, deliveries, route updates, pooling proposals'],
                    'negotiations' => ['Negotiations & Proposals', 'New B2B negotiations, counter-offers, proposal status changes'],
                    'payments' => ['Payments & Invoices', 'Payment receipts, invoice ready, quantity confirmations'],
                    'admin' => ['Admin & Verification', 'Identity/profile verification, document approvals'],
                    'weather' => ['Weather Alerts', 'Severe weather warnings for your area'],
                    'system' => ['System Notifications', 'Auto-expired proposals, auto-completed deliveries'],
                    'delays' => ['Delay Alerts', 'Delivery delay warnings and ETA updates'],
                ];
            @endphp

            @foreach($labels as $key => $label)
                <div class="glass-card rounded-xl p-4 flex items-center justify-between">
                    <div>
                        <p class="text-sm font-bold text-slate-800 dark:text-slate-200">{{ $label[0] }}</p>
                        <p class="text-[11px] text-slate-500 dark:text-slate-400 mt-0.5">{{ $label[1] }}</p>
                    </div>
                    <label class="relative inline-flex items-center cursor-pointer">
                        <input type="checkbox" name="preferences[{{ $key }}]" value="1"
                            class="sr-only peer"
                            {{ ($preferences[$key] ?? true) ? 'checked' : '' }}>
                        <div class="w-9 h-5 bg-slate-200 peer-focus:ring-2 peer-focus:ring-[#3A7D44]/20 dark:peer-focus:ring-[#3A7D44]/40 rounded-full peer dark:bg-slate-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:bg-[#3A7D44]"></div>
                    </label>
                </div>
            @endforeach

            <div class="pt-2">
                <button type="submit" class="w-full bg-[#3A7D44] hover:bg-[#2D5A27] text-white text-xs font-bold py-2.5 rounded-xl transition-all duration-200 active:scale-[0.98] cursor-pointer">
                    Save Preferences
                </button>
            </div>
        </form>
    </div>
</x-layout>
