@props([
    'status' => null,
    'label' => null,
    'color' => null,
    'dot' => false,
])

@php
    $successTint = 'bg-[var(--color-success-bg)] text-[var(--color-success-text)] border border-[var(--color-success-border)] dark:bg-[var(--color-success-bg-dark)] dark:text-[var(--color-success-text-dark)] dark:border-[var(--color-success-border-dark)]';
    $warningTint = 'bg-[var(--color-warning-bg)] text-[var(--color-warning-text)] border border-[var(--color-warning-border)] dark:bg-[var(--color-warning-bg-dark)] dark:text-[var(--color-warning-text-dark)] dark:border-[var(--color-warning-border-dark)]';
    $errorTint   = 'bg-[var(--color-error-bg)] text-[var(--color-error-text)] border border-[var(--color-error-border)] dark:bg-[var(--color-error-bg-dark)] dark:text-[var(--color-error-text-dark)] dark:border-[var(--color-error-border-dark)]';
    $infoTint    = 'bg-[var(--color-info-bg)] text-[var(--color-info-text)] border border-[var(--color-info-border)] dark:bg-[var(--color-info-bg-dark)] dark:text-[var(--color-info-text-dark)] dark:border-[var(--color-info-border-dark)]';
    $slateTint   = 'bg-slate-100 text-slate-500 dark:bg-slate-900/50 dark:text-slate-400 border border-slate-200 dark:border-slate-700';
    $brandTint   = 'bg-brand-700/10 text-brand-700 dark:bg-brand-500/10 dark:text-brand-500 border border-brand-700/20 dark:border-brand-500/20';
    $harvestTint = 'bg-harvest/10 text-harvest-dark dark:bg-harvest/10 dark:text-harvest-light border border-harvest/20 dark:border-harvest/20';

    $statusMap = [
        'verified'       => ['label' => 'Verified',    'classes' => $successTint],
        'active'         => ['label' => 'Active',      'classes' => $successTint],
        'approved'       => ['label' => 'Approved',    'classes' => $successTint],
        'completed'      => ['label' => 'Completed',   'classes' => $brandTint],
        'paid'           => ['label' => 'Paid',        'classes' => $successTint],
        'pending'        => ['label' => 'Pending',     'classes' => $warningTint],
        'unconfirmed'    => ['label' => 'Unconfirmed', 'classes' => $warningTint],
        'submitted'      => ['label' => 'Submitted',   'classes' => $warningTint],
        'requires_revision' => ['label' => 'Needs Revision', 'classes' => $warningTint],
        'ready'          => ['label' => 'Ready',       'classes' => $warningTint],
        'partial_sale'   => ['label' => 'Partial Sale','classes' => $warningTint],
        'rejected'       => ['label' => 'Rejected',    'classes' => $errorTint],
        'inactive'       => ['label' => 'Inactive',    'classes' => $errorTint],
        'cancelled'      => ['label' => 'Cancelled',   'classes' => $errorTint],
        'failed'         => ['label' => 'Failed',      'classes' => $errorTint],
        'skipped'        => ['label' => 'Skipped',     'classes' => $slateTint],
        'arrived'        => ['label' => 'Arrived',     'classes' => $infoTint],
        'picked_up'      => ['label' => 'Picked Up',   'classes' => $brandTint],
        'scheduled'      => ['label' => 'Scheduled',   'classes' => $infoTint],
        'archived'       => ['label' => 'Archived',    'classes' => $slateTint],
        'in_transit'     => ['label' => 'In Transit',  'classes' => $infoTint],
        'default'        => ['label' => 'Unknown',     'classes' => $slateTint],
    ];

    $colorMap = [
        'green'  => $successTint,
        'amber'  => $warningTint,
        'red'    => $errorTint,
        'slate'  => $slateTint,
        'brand'  => $brandTint,
        'harvest'=> $harvestTint,
    ];

    $dotColors = [
        'verified' => '#16A34A', 'active' => '#16A34A', 'approved' => '#16A34A',
        'completed' => '#16283C', 'paid' => '#16A34A',
        'pending' => '#D97706', 'unconfirmed' => '#D97706', 'submitted' => '#D97706',
        'ready' => '#D97706', 'partial_sale' => '#D97706',
        'rejected' => '#DC2626', 'inactive' => '#DC2626', 'cancelled' => '#DC2626',
        'archived' => '#94A3B8', 'in_transit' => '#2563EB',
    ];

    $statusKey = $status instanceof \BackedEnum ? $status->value : $status;
    $resolved = $statusMap[$statusKey] ?? $statusMap['default'];
    $displayLabel = $label ?? $resolved['label'];
    $classes = $color ? ($colorMap[$color] ?? $resolved['classes']) : $resolved['classes'];
    $dotColor = $dotColors[$statusKey] ?? '#94A3B8';
@endphp

<span class="{{ $classes }} text-[10px] font-bold px-2.5 py-1 rounded-md uppercase tracking-wide inline-flex items-center gap-1.5">
    @if($dot)
        <span class="w-1.5 h-1.5 rounded-full shrink-0" style="background-color: {{ $dotColor }}"></span>
    @endif
    {{ $displayLabel }}
</span>
