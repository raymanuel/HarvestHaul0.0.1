<x-layout>
<div class="w-full max-w-2xl">

    <header class="pt-8 mb-8">
        <a href="{{ route('dashboard') }}" class="text-sm text-gray-400 hover:text-gray-600 mb-4 inline-block">
            ← Back to Dashboard
        </a>
        <h1 class="text-3xl font-bold text-gray-900 mb-2">Post New Harvest</h1>
        <p class="text-gray-500">Fill in your crop details. Once posted, your farm will appear on the logistics map for pickup.</p>
    </header>

    {{-- Validation Errors --}}
    @if ($errors->any())
        <div class="mb-6 bg-red-50 border border-red-200 text-red-700 rounded-xl px-5 py-4 text-sm">
            <p class="font-semibold mb-1">Please fix the following:</p>
            <ul class="list-disc list-inside space-y-1">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="bg-white rounded-2xl border border-gray-200 shadow-sm p-8">
        <form method="POST" action="{{ route('harvests.store') }}">
            @csrf

            {{-- Crop Type --}}
            <div class="mb-6">
                <label class="block text-sm font-semibold text-gray-700 mb-2">
                    Crop Type <span class="text-red-400">*</span>
                </label>
                <input type="text" name="crop_type" value="{{ old('crop_type') }}"
                    placeholder="e.g. Pineapple, Corn, Banana, Rice"
                    class="w-full border border-gray-300 rounded-xl px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-[#2D8A37] focus:border-transparent"
                    required />
            </div>

            {{-- Quantity --}}
            <div class="mb-6">
                <label class="block text-sm font-semibold text-gray-700 mb-2">
                    Quantity (kg) <span class="text-red-400">*</span>
                </label>
                <input type="number" name="quantity_kg" value="{{ old('quantity_kg') }}"
                    placeholder="e.g. 500" min="1" step="0.01"
                    class="w-full border border-gray-300 rounded-xl px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-[#2D8A37] focus:border-transparent"
                    required />
            </div>

            {{-- Notes --}}
            <div class="mb-8">
                <label class="block text-sm font-semibold text-gray-700 mb-2">
                    Pickup Notes <span class="text-gray-400 font-normal">(optional)</span>
                </label>
                <textarea name="notes" rows="4"
                    placeholder="e.g. Use the side gate, available after 8am, call before arrival"
                    class="w-full border border-gray-300 rounded-xl px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-[#2D8A37] focus:border-transparent resize-none">{{ old('notes') }}</textarea>
            </div>

            {{-- Info Banner --}}
            <div class="mb-6 bg-green-50 border border-green-200 rounded-xl px-4 py-3 flex gap-3 items-start">
                <span class="text-lg">📍</span>
                <p class="text-sm text-green-700">
                    Once posted, your farm location will be <strong>visible on the logistics map</strong> for pickup coordination. You can remove the listing anytime from your dashboard.
                </p>
            </div>

            {{-- Actions --}}
            <div class="flex gap-3">
                <button type="submit"
                    class="flex-1 bg-[#2D8A37] text-white font-bold py-3 rounded-xl hover:bg-opacity-90 transition shadow-md">
                    Post Listing
                </button>
                <a href="{{ route('dashboard') }}"
                    class="flex-1 text-center bg-slate-100 text-slate-700 font-bold py-3 rounded-xl hover:bg-slate-200 transition">
                    Cancel
                </a>
            </div>
        </form>
    </div>
</div>
</x-layout>
