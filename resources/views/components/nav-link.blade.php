@props(['active'])

@php
$classes = ($active ?? false)
            ? 'sj-active inline-flex items-center h-16 px-1 border-b-2 text-sm font-semibold leading-5 whitespace-nowrap focus:outline-none transition duration-150 ease-in-out'
            : 'inline-flex items-center h-16 px-1 border-b-2 border-transparent text-sm font-semibold leading-5 whitespace-nowrap text-slate-100 hover:text-white hover:border-white/40 focus:outline-none transition duration-150 ease-in-out';
@endphp

<a {{ $attributes->merge(['class' => $classes]) }}>
    {{ $slot }}
</a>




