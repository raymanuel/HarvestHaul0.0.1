@props(['message' => null])

@php($msg = $message ?? session('error'))
@if($msg)
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            window.swalToast && window.swalToast('error', @js($msg));
        });
    </script>
@endif
