<x-layout>
    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg p-6">

                {{-- PHP Role Switcher Logic --}}
                @php
                    $role = auth()->user()->role;
                @endphp

                @switch($role)
                    @case('farmer')
                        @include('dashboards.farmer-view')
                        @break

                    @case('logistics_partner')
                        @include('dashboards.logistics-view')
                        @break

                    @case('driver')
                        @include('dashboards.driver-view')
                        @break

                    @case('admin')
                        @include('dashboards.admin-view')
                        @break

                    @default
                        <div class="text-center py-4">
                            <p class="text-red-500">Error: User role not recognized.</p>
                        </div>
                @endswitch

            </div>
        </div>
    </div>
</x-layout>
