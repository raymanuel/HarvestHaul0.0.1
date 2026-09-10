@props(['status'])

@php
    $statusClasses = [
        'active'         => 'bg-[#16283C]/10 text-[#16283C] border-[#16283C]/10 dark:bg-gold-light/10 dark:text-[#D7BC7A] dark:border-gold-light/20',
        'negotiating'    => 'bg-[var(--color-warning-bg)] text-[var(--color-warning-text)] border-[var(--color-warning-border)]',
        'partially_sold' => 'bg-[var(--color-warning-bg)] text-[var(--color-warning-text)] border-[var(--color-warning-border)]',
        'sold'           => 'bg-indigo-50 text-indigo-700 border-indigo-500/10 dark:bg-indigo-950/20 dark:text-indigo-400',
        'booked'         => 'bg-purple-50 text-purple-700 border-purple-500/10 dark:bg-purple-950/20 dark:text-purple-400',
        'assigned'       => 'bg-purple-50 text-purple-700 border-purple-500/10 dark:bg-purple-950/20 dark:text-purple-400',
        'in_progress'    => 'bg-orange-50 text-orange-700 border-orange-500/10 dark:bg-orange-950/20 dark:text-orange-400',
        'completed'      => 'bg-brand-dark/10 text-brand-dark border-brand-dark/10 dark:bg-slate-800 dark:text-[#E9EEF4] dark:border-slate-700',
        'cancelled'      => 'bg-rose-50 text-rose-700 border-rose-500/10 dark:bg-rose-950/20 dark:text-rose-400',
    ];
    $label = match($status) {
        'active'         => 'Active',
        'negotiating'    => 'Under Negotiation',
        'partially_sold' => 'Partial Sale',
        'sold'           => 'Sold',
        'booked'         => 'Booked',
        'assigned'       => 'Assigned',
        'in_progress'    => 'In Transit',
        'completed'      => 'Completed',
        'cancelled'      => 'Cancelled',
        default          => ucfirst($status),
    };
@endphp

<span {{ $attributes->merge(['class' => ($statusClasses[$status] ?? $statusClasses['active']) . ' text-[10px] font-bold px-2.5 py-1 rounded-md uppercase tracking-wider border']) }}>{{ $label }}</span>
