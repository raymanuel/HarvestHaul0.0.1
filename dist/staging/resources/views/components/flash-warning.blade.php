@props(['message' => null])

@php($msg = $message ?? session('warning'))
@if($msg)
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            window.swalToast && window.swalToast('warning', @js($msg));
        });
    </script>
@endif
