<x-layout title="Messages — HarvestHaul">
    <x-page-header title="Messages" :showDate="true" />

    <x-card>
        @if($partners->isEmpty())
            <x-empty-state type="first-use" title="No one to message yet" description="Once you're affiliated with a cooperative (or have placed an order), you'll be able to message them here." />
        @else
            <ul class="divide-y divide-slate-100 dark:divide-slate-700/60">
                @foreach($partners as $partner)
                    <li>
                        <a href="{{ route('messages.show', $partner) }}" class="flex items-center justify-between gap-3 py-3 hover:bg-slate-50 dark:hover:bg-slate-800/60 -mx-2 px-2 rounded-lg transition">
                            <div>
                                <p class="text-sm font-bold text-slate-800 dark:text-slate-100">{{ $partner->name }}</p>
                                <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">
                                    {{ ucfirst(str_replace('_', ' ', $partner->role)) }}
                                    @unless($partner->has_thread) · No messages yet — start the conversation @endunless
                                </p>
                            </div>
                            @if($partner->unread_count > 0)
                                <span class="shrink-0 min-w-[1.5rem] h-6 px-1.5 flex items-center justify-center rounded-full text-xs font-bold bg-brand-700 text-white">{{ $partner->unread_count }}</span>
                            @endif
                        </a>
                    </li>
                @endforeach
            </ul>
        @endif
    </x-card>
</x-layout>
