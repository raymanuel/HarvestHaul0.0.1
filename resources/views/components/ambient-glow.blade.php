@props([
    'color' => 'brand',
    'size' => 'lg',
])

@if($size === 'lg')
    <div class="absolute top-0 right-1/4 w-96 h-96 rounded-full bg-{{ $color }}/5 blur-[120px] pointer-events-none z-0"></div>
    <div class="absolute top-1/3 left-1/3 w-[500px] h-[500px] rounded-full bg-{{ $color }}-dark/5 blur-[150px] pointer-events-none z-0"></div>
@else
    <div class="absolute -right-6 -bottom-6 w-24 h-24 bg-{{ $color }}/5 rounded-full group-hover:scale-150 transition-all duration-500"></div>
@endif
