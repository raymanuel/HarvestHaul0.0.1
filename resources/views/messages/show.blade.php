@php
    $firstWithContext = $messages->first(fn ($m) => $m->context_type);
    $contextLabel = $firstWithContext?->contextLabel();
@endphp

<x-layout title="Message {{ $conversation->name }} — HarvestHaul">
    <x-page-header variant="back-link" title="{{ $conversation->name }}" :back-href="route('messages.index')" back-label="← Back to Messages" />

    @if($contextLabel)
        <p class="text-xs font-bold text-slate-500 dark:text-slate-400 mb-4">Re: {{ $contextLabel }}</p>
    @endif

    <x-card>
        <div id="message-thread" class="space-y-3 mb-5 max-h-[28rem] overflow-y-auto" data-after="{{ $messages->last()->id ?? 0 }}">
            @forelse($messages as $message)
                @php $mine = $message->sender_id === Auth::id(); @endphp
                <div class="flex {{ $mine ? 'justify-end' : 'justify-start' }}">
                    <div class="max-w-[75%] rounded-2xl px-4 py-2.5 text-sm {{ $mine ? 'bg-brand-700 text-white' : 'bg-slate-100 dark:bg-slate-700 text-slate-800 dark:text-slate-100' }}">
                        <p>{{ $message->body }}</p>
                        <p class="text-[10px] mt-1 opacity-70">{{ $message->created_at->format('M d, g:i A') }}</p>
                    </div>
                </div>
            @empty
                <x-empty-state type="first-use" title="No messages yet" description="Send the first message to start the conversation." />
            @endforelse
        </div>

        <form method="POST" action="{{ route('messages.store', $conversation) }}" class="flex gap-3">
            @csrf
            @if(request()->query('context_type'))
                <input type="hidden" name="context_type" value="{{ request()->query('context_type') }}">
                <input type="hidden" name="context_id" value="{{ request()->query('context_id') }}">
            @endif
            <textarea name="body" rows="2" required maxlength="4000" placeholder="Type a message…"
                class="flex-1 border border-slate-200 dark:border-slate-600 rounded-xl px-4 py-2.5 text-sm bg-slate-50/50 dark:bg-slate-700/50 text-slate-800 dark:text-white"></textarea>
            <x-button variant="primary" size="sm">Send</x-button>
        </form>
    </x-card>

    @push('scripts')
        <script>
            (function () {
                var thread = document.getElementById('message-thread');
                thread.scrollTop = thread.scrollHeight;

                var pollUrl = '{{ route('messages.poll', $conversation) }}';
                setInterval(function () {
                    var after = thread.dataset.after || 0;
                    fetch(pollUrl + '?after=' + after)
                        .then(function (r) { return r.json(); })
                        .then(function (data) {
                            if (data.has_new) {
                                window.location.reload();
                            }
                        })
                        .catch(function () {});
                }, 8000);
            })();
        </script>
    @endpush
</x-layout>
