<x-layout title="Customers">

    <div class="w-full max-w-6xl mx-auto pb-12">

        <header class="pt-8 mb-6 border-b border-slate-200/80 dark:border-slate-700/80 pb-5">
            <a href="{{ route('dashboard') }}" class="text-sm text-slate-400 hover:text-slate-600 dark:hover:text-slate-300 mb-4 inline-block font-semibold transition">
                ← Back to Dashboard
            </a>
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-bold text-slate-900 dark:text-white tracking-tight heading-font">Customers</h1>
                    <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">Your buyers — send crop orders and assign trucks to their locations.</p>
                </div>
                <div>
                    <x-button tag="a" :href="route('coop.customers.create')" size="lg">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
                        </svg>
                        Add Customer
                    </x-button>
                </div>
            </div>
        </header>

        <x-flash-success />
        <x-flash-error />

        @if($customers->isEmpty())
            <div class="bg-white dark:bg-slate-800 border border-slate-200/70 dark:border-slate-700/80 rounded-2xl shadow-sm p-12 text-center">
                <p class="text-4xl mb-4 font-bold text-slate-300 dark:text-slate-600">—</p>
                <p class="text-slate-800 dark:text-slate-200 font-bold text-base mb-1 heading-font">No Customers Yet</p>
                <p class="text-slate-500 dark:text-slate-400 font-medium text-xs max-w-sm mx-auto">
                    Add your first customer (a grocery, restaurant, or processor) to start distributing.
                </p>
                <a href="{{ route('coop.customers.create') }}" class="mt-5 inline-block text-xs font-bold text-[#16283C] dark:text-[#D7BC7A] hover:underline transition">
                    Create first customer <span>→</span>
                </a>
            </div>
        @else
            <x-data-table :empty-message="'No customers yet.'">
                <x-slot:header>
                    <th class="px-4 py-3 text-[10px] font-extrabold text-slate-500 dark:text-slate-500 uppercase tracking-widest">Customer</th>
                    <th class="px-4 py-3 text-[10px] font-extrabold text-slate-500 dark:text-slate-500 uppercase tracking-widest">Type</th>
                    <th class="px-4 py-3 text-[10px] font-extrabold text-slate-500 dark:text-slate-500 uppercase tracking-widest">Contact</th>
                    <th class="px-4 py-3 text-[10px] font-extrabold text-slate-500 dark:text-slate-500 uppercase tracking-widest">Address</th>
                    <th class="px-4 py-3 text-[10px] font-extrabold text-slate-500 dark:text-slate-500 uppercase tracking-widest">Orders</th>
                    <th class="px-4 py-3 text-[10px] font-extrabold text-slate-500 dark:text-slate-500 uppercase tracking-widest text-right">Actions</th>
                </x-slot:header>

                @foreach($customers as $customer)
                    <tr class="hover:bg-slate-50/50 dark:hover:bg-slate-900/40 transition">
                        <td class="px-4 py-3">
                            <div class="flex items-center gap-3">
                                <div class="w-8 h-8 rounded-md bg-gradient-to-tr from-slate-100 to-slate-50 dark:from-slate-700 dark:to-slate-800 border border-slate-200 dark:border-slate-700 flex items-center justify-center text-[10px] font-extrabold text-slate-600 dark:text-slate-300 uppercase shrink-0">{{ substr($customer->name, 0, 2) }}</div>
                                <span class="font-bold text-slate-800 dark:text-slate-200 text-sm">{{ $customer->name }}</span>
                            </div>
                        </td>
                        <td class="px-4 py-3 text-slate-500 dark:text-slate-400 text-xs font-medium">{{ $customer->business_type ?? '—' }}</td>
                        <td class="px-4 py-3 text-slate-500 dark:text-slate-400 text-xs font-medium">{{ $customer->contact ?? '—' }}</td>
                        <td class="px-4 py-3 text-slate-500 dark:text-slate-400 text-xs font-medium">{{ $customer->address ?? '—' }}</td>
                        <td class="px-4 py-3 text-slate-500 dark:text-slate-400 text-xs font-semibold">{{ $customer->outbound_orders_count }}</td>
                        <td class="px-4 py-3 text-right">
                            <div class="flex items-center justify-end gap-2">
                                <a href="{{ route('coop.customers.edit', $customer) }}" class="inline-flex items-center justify-center px-3 py-2 bg-[#16283C]/10 text-[#16283C] hover:bg-[#16283C]/15 dark:bg-[#16283C]/10 dark:hover:bg-[#16283C]/15 dark:text-[#D7BC7A] rounded-xl text-xs font-bold transition cursor-pointer">Edit</a>
                                <form action="{{ route('coop.customers.destroy', $customer) }}" method="POST" class="inline" id="remove-customer-form-{{ $customer->id }}">
                                    @csrf
                                    @method('DELETE')
                                    <button type="button" onclick="swalConfirm(document.getElementById('remove-customer-form-{{ $customer->id }}'), {title: 'Remove Customer?', text: 'Remove {{ addslashes($customer->name) }} from your customer list? This cannot be undone.', confirmText: 'Yes, remove', icon: 'warning', confirmColor: '#ef4444'})" class="inline-flex items-center justify-center px-3 py-2 bg-red-50 text-red-600 hover:bg-red-100 dark:bg-red-950/20 dark:hover:bg-red-950/40 dark:text-red-400 rounded-xl text-xs font-bold transition cursor-pointer">
                                        Remove
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @endforeach
            </x-data-table>
        @endif

    </div>

</x-layout>