<x-layout title="Users — HarvestHaul">
    <x-page-header title="Platform Users" :showDate="true">
        <x-button variant="primary" size="sm" tag="button" type="button" onclick="openModal('add-user-modal')">Add User</x-button>
    </x-page-header>

    <form method="GET" action="{{ route('admin.users.index') }}" class="flex flex-col sm:flex-row gap-3 mb-6">
        <input type="text" name="search" value="{{ $search }}" placeholder="Search by name or email"
               class="flex-1 border border-slate-200 dark:border-slate-600 rounded-xl px-4 py-2.5 text-sm bg-slate-50/50 dark:bg-slate-700/50 text-slate-800 dark:text-white focus:outline-none focus:ring-2 focus:ring-brand-700/25" />
        <select name="role" class="border border-slate-200 dark:border-slate-600 rounded-xl px-4 py-2.5 text-sm bg-slate-50/50 dark:bg-slate-700/50 text-slate-800 dark:text-white focus:outline-none focus:ring-2 focus:ring-brand-700/25">
            <option value="all" {{ $role === 'all' ? 'selected' : '' }}>All roles</option>
            @foreach($roles as $r)
                <option value="{{ $r->value }}" {{ $role === $r->value ? 'selected' : '' }}>{{ $r->label() }}</option>
            @endforeach
        </select>
        <x-button variant="secondary" size="sm" tag="button" type="submit">Filter</x-button>
    </form>

    @if($users->isEmpty())
        <x-empty-state type="no-results" title="No users found" description="Try a different search or role filter." />
    @else
        <div class="overflow-x-auto bg-white dark:bg-slate-800 border border-slate-200/70 dark:border-slate-700/80 rounded-2xl shadow-sm">
            <table class="w-full text-sm">
                <thead class="bg-slate-50 dark:bg-slate-900/30 text-left">
                    <tr class="text-[10px] font-bold uppercase tracking-wider text-slate-400">
                        <th class="px-5 py-3">Name</th>
                        <th class="px-5 py-3">Role</th>
                        <th class="px-5 py-3">Cooperative</th>
                        <th class="px-5 py-3">Status</th>
                        <th class="px-5 py-3 text-right">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-700/60">
                    @foreach($users as $user)
                        <tr>
                            <td class="px-5 py-3">
                                <p class="font-bold text-slate-800 dark:text-slate-100">{{ $user->name }}</p>
                                <p class="text-xs text-slate-500 dark:text-slate-400">{{ $user->email }}</p>
                            </td>
                            <td class="px-5 py-3 text-slate-600 dark:text-slate-300">{{ $user->roleLabel() }}</td>
                            <td class="px-5 py-3 text-slate-600 dark:text-slate-300">{{ $user->cooperative?->name ?? '—' }}</td>
                            <td class="px-5 py-3"><x-badge :status="$user->status" dot /></td>
                            <td class="px-5 py-3 text-right">
                                @unless($user->isSuperAdmin())
                                    <div class="flex justify-end gap-2">
                                        <x-modal triggerLabel="Edit" triggerClass="px-3.5 py-2 rounded-xl text-[11px] font-bold border border-slate-200 dark:border-slate-600 text-slate-600 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-700 transition">
                                            <h2 class="text-lg font-bold text-slate-900 dark:text-white mb-4">Edit {{ $user->name }}</h2>
                                            <form method="POST" action="{{ route('admin.users.update', $user) }}" class="space-y-4">
                                                @csrf
                                                @method('PUT')
                                                <x-input name="name" label="Full Name" :value="$user->name" required />
                                                <x-input name="email" label="Email" type="email" :value="$user->email" required />
                                                <x-input name="phone" label="Phone" :value="$user->phone" />
                                                <x-select name="cooperative_id" label="Cooperative" placeholder="— None —"
                                                    :options="$cooperatives->mapWithKeys(fn ($c) => [$c->id => $c->name])->all()"
                                                    :value="$user->cooperative_id" />
                                                <div class="flex justify-end gap-3 pt-1">
                                                    <button type="button" data-modal-close class="px-4 py-2 rounded-lg text-sm font-semibold text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-700">Cancel</button>
                                                    <x-button variant="primary" size="sm">Save Changes</x-button>
                                                </div>
                                            </form>
                                        </x-modal>

                                        <form method="POST" action="{{ route('admin.users.status', $user) }}" id="user-status-{{ $user->id }}">
                                            @csrf
                                            <button type="button"
                                                onclick="swalConfirm(document.getElementById('user-status-{{ $user->id }}'), {title:'{{ $user->status === 'active' ? 'Suspend Account' : 'Reactivate Account' }}', text:'{{ $user->status === 'active' ? 'Suspend '.$user->name.'? They can no longer sign in.' : 'Reactivate '.$user->name.'? They can sign in again.' }}', icon:'warning', confirmText:'{{ $user->status === 'active' ? 'Yes, suspend' : 'Yes, reactivate' }}', cancelText:'Cancel', confirmColor:'#ef4444'})"
                                                class="px-3.5 py-2 rounded-xl text-[11px] font-bold border transition cursor-pointer {{ $user->status === 'active' ? 'bg-[var(--color-error-bg)] text-[var(--color-error-text)] border-[var(--color-error-border)]' : 'bg-[var(--color-success-bg)] text-[var(--color-success-text)] border-[var(--color-success-border)]' }}">
                                                {{ $user->status === 'active' ? 'Suspend' : 'Reactivate' }}
                                            </button>
                                        </form>
                                    </div>
                                @endunless
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="mt-6">{{ $users->links() }}</div>
    @endif

    <x-modal id="add-user-modal" title="Add User">
        <form method="POST" action="{{ route('admin.users.store') }}">
            @csrf
            <div class="space-y-4">
                <x-input name="name" label="Full Name" required />
                <x-input name="email" label="Email" type="email" required autocomplete="off" />
                <x-input name="phone" label="Phone" />
                <x-input name="password" label="Temporary Password" type="password" show-toggle required autocomplete="new-password" />
                <x-select name="role" label="Role" :required="true" :placeholder="null" :options="[
                    'coop_admin' => 'Cooperative Admin',
                    'field_receiving' => 'Field / Receiving Personnel',
                    'delivery_personnel' => 'Delivery Personnel',
                    'farmer' => 'Farmer',
                ]" />
                <x-select name="cooperative_id" label="Cooperative" :required="true" :placeholder="null"
                    :options="$cooperatives->mapWithKeys(fn ($c) => [$c->id => $c->name])->all()" />
                <p class="text-xs text-slate-500 dark:text-slate-400">Tell the person their login email and temporary password. They can change the password after signing in.</p>
            </div>
            <div class="flex justify-end gap-3 mt-5">
                <button type="button" onclick="closeModal('add-user-modal')" class="px-4 py-2 rounded-xl text-xs font-bold text-slate-500 hover:text-slate-800">Cancel</button>
                <x-button variant="primary" size="sm">Create User</x-button>
            </div>
        </form>
    </x-modal>
</x-layout>
