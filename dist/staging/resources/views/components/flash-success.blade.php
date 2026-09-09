@props(['message' => null, 'nextSteps' => null])

@php
    $msg = $message ?? session('success');
    $steps = $nextSteps ?? session('next_steps');
    $cta = isset($steps['cta']['label'], $steps['cta']['url'])
        ? ['label' => $steps['cta']['label'], 'url' => $steps['cta']['url']]
        : null;
@endphp
@if($msg)
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            window.swalToast && window.swalToast('success', @js($msg), @json($cta));
        });
    </script>
@endif
