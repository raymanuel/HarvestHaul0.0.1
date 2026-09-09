@props(['tabs' => []])

<div {{ $attributes->merge(['class' => 'flex gap-1 mb-8 bg-slate-100 dark:bg-slate-800/60 rounded-xl p-1 w-fit border border-slate-200/70 dark:border-slate-700/80']) }}>
    @foreach($tabs as $tab)
        <a href="{{ $tab['url'] }}"
           class="px-5 py-2.5 rounded-xl text-sm font-bold transition {{ $tab['active'] ? 'bg-white dark:bg-slate-700 text-slate-800 dark:text-white shadow-sm' : 'text-slate-500 dark:text-slate-400 hover:text-slate-700 dark:hover:text-slate-300' }}">
            {{ $tab['label'] }}
        </a>
    @endforeach
</div>
