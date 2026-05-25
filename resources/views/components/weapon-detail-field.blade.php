@props(['label'])

<div {{ $attributes->class([]) }}>
    <div class="text-xs font-medium text-gray-500 mb-1">{{ $label }}</div>
    <div class="sj-weapon-detail-field">
        {{ $slot }}
    </div>
</div>
