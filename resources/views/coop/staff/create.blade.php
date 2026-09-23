<x-layout title="Add Field Staff — Cooperative">
    <x-page-header variant="back-link" title="Add Field Staff" :back-href="route('coop.staff.index')" back-label="← Back to Field Staff" />

    <x-card class="max-w-2xl">
        <form method="POST" action="{{ route('coop.staff.store') }}">
            @csrf
            <div class="space-y-4">
                <x-input name="name" label="Full Name" required />
                <x-input name="email" label="Email" type="email" required autocomplete="off" />
                <x-input name="password" label="Temporary Password" type="password" show-toggle required autocomplete="new-password" />
                <x-input name="phone" label="Phone" />
                <p class="text-xs text-slate-500 dark:text-slate-400">This staff account is added directly to your cooperative and can record receiving right away. Share the login email and temporary password so they can sign in.</p>
            </div>
            <div class="flex justify-end gap-3 mt-6">
                <a href="{{ route('coop.staff.index') }}" class="px-4 py-2 rounded-xl text-xs font-bold text-slate-500 hover:text-slate-800">Cancel</a>
                <x-button variant="primary" size="sm">Add Staff</x-button>
            </div>
        </form>
    </x-card>
</x-layout>
