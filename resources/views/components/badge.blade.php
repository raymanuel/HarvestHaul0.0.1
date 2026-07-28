@props([
    'status' => null,
    'label' => null,
    'color' => null,
    'dot' => false,
])

@php
    $statusMap = [
        'verified'       => ['label' => 'Verified',    'classes' => 'bg-[#3A7D44]/10 text-[#3A7D44] dark:bg-[#3A7D44]/10 dark:text-[#3A7D44]'],
        'active'         => ['label' => 'Active',      'classes' => 'bg-[#3A7D44]/10 text-[#3A7D44] dark:bg-[#3A7D44]/10 dark:text-[#3A7D44]'],
        'approved'       => ['label' => 'Approved',    'classes' => 'bg-[#3A7D44]/10 text-[#3A7D44] dark:bg-[#3A7D44]/10 dark:text-[#3A7D44]'],
        'completed'      => ['label' => 'Completed',   'classes' => 'bg-[#3A7D44]/10 text-[#3A7D44] dark:bg-[#3A7D44]/10 dark:text-[#3A7D44]'],
        'paid'           => ['label' => 'Paid',        'classes' => 'bg-[#3A7D44]/10 text-[#3A7D44] dark:bg-[#3A7D44]/10 dark:text-[#3A7D44]'],
        'pending'        => ['label' => 'Pending',     'classes' => 'bg-amber-50 text-amber-700 dark:bg-amber-950/20 dark:text-amber-400'],
        'unconfirmed'    => ['label' => 'Unconfirmed', 'classes' => 'bg-amber-50 text-amber-700 dark:bg-amber-950/20 dark:text-amber-400'],
        'submitted'      => ['label' => 'Submitted',   'classes' => 'bg-amber-50 text-amber-700 dark:bg-amber-950/20 dark:text-amber-400'],
        'ready'          => ['label' => 'Ready',       'classes' => 'bg-amber-50 text-amber-700 dark:bg-amber-950/20 dark:text-amber-400'],
        'partial_sale'   => ['label' => 'Partial Sale','classes' => 'bg-amber-50 text-amber-700 dark:bg-amber-950/20 dark:text-amber-400'],
        'rejected'       => ['label' => 'Rejected',    'classes' => 'bg-red-50 text-red-600 dark:bg-red-950/20 dark:text-red-400'],
        'inactive'       => ['label' => 'Inactive',    'classes' => 'bg-red-50 text-red-600 dark:bg-red-950/20 dark:text-red-400'],
        'cancelled'      => ['label' => 'Cancelled',   'classes' => 'bg-red-50 text-red-600 dark:bg-red-950/20 dark:text-red-400'],
        'archived'       => ['label' => 'Archived',    'classes' => 'bg-slate-100 text-slate-500 dark:bg-slate-900/50 dark:text-slate-400'],
        'in_transit'     => ['label' => 'In Transit',  'classes' => 'bg-[#1F4D25]/10 text-[#1F4D25] dark:bg-[#1F4D25]/10 dark:text-[#1F4D25]'],
        'default'        => ['label' => 'Unknown',     'classes' => 'bg-slate-100 text-slate-500 dark:bg-slate-900/50 dark:text-slate-400'],
    ];

    $colorMap = [
        'green'  => 'bg-[#3A7D44]/10 text-[#3A7D44] dark:bg-[#3A7D44]/10 dark:text-[#3A7D44]',
        'amber'  => 'bg-amber-50 text-amber-700 dark:bg-amber-950/20 dark:text-amber-400',
        'red'    => 'bg-red-50 text-red-600 dark:bg-red-950/20 dark:text-red-400',
        'slate'  => 'bg-slate-100 text-slate-500 dark:bg-slate-900/50 dark:text-slate-400',
        'brand'  => 'bg-[#3A7D44]/10 text-[#3A7D44] dark:bg-[#3A7D44]/10 dark:text-[#3A7D44]',
        'harvest'=> 'bg-[#C8A415]/10 text-[#9A7D10] dark:bg-[#C8A415]/10 dark:text-[#C8A415]',
    ];

    $dotColors = [
        'verified' => '#3A7D44', 'active' => '#3A7D44', 'approved' => '#3A7D44',
        'completed' => '#3A7D44', 'paid' => '#3A7D44',
        'pending' => '#D97706', 'unconfirmed' => '#D97706', 'submitted' => '#D97706',
        'ready' => '#D97706', 'partial_sale' => '#D97706',
        'rejected' => '#DC2626', 'inactive' => '#DC2626', 'cancelled' => '#DC2626',
        'archived' => '#94A3B8', 'in_transit' => '#1F4D25',
    ];

    $resolved = $statusMap[$status] ?? $statusMap['default'];
    $displayLabel = $label ?? $resolved['label'];
    $classes = $color ? ($colorMap[$color] ?? $resolved['classes']) : $resolved['classes'];
    $dotColor = $dotColors[$status] ?? '#94A3B8';
@endphp

<span class="{{ $classes }} text-[10px] font-bold px-2.5 py-1 rounded-lg uppercase tracking-wide inline-flex items-center gap-1.5">
    @if($dot)
        <span class="w-1.5 h-1.5 rounded-full shrink-0" style="background-color: {{ $dotColor }}"></span>
    @endif
    {{ $displayLabel }}
</span>
