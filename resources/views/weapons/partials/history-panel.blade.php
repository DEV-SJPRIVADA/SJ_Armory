@php
    if (! $weapon->relationLoaded('histories')) {
        $weapon->load([
            'histories' => fn ($query) => $query->orderByDesc('created_at')->orderByDesc('id'),
            'histories.user',
        ]);
    }
    $historyEntries = $weapon->histories;
    $kindLabels = \App\Models\WeaponHistory::kindLabels();
@endphp

<div class="sj-weapon-detail-notes__list flex-1 pr-1 -mr-1 space-y-3">
    @forelse ($historyEntries as $entry)
        <article class="bg-white rounded-lg border border-gray-200 p-4">
            <div class="flex flex-wrap items-center justify-between gap-2 mb-2">
                <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-semibold bg-indigo-100 text-indigo-800 border border-indigo-200">
                    {{ $kindLabels[$entry->kind] ?? $entry->kind }}
                </span>
                <time class="text-xs text-gray-500" datetime="{{ $entry->created_at->toIso8601String() }}">
                    {{ $entry->created_at->format('d/m/Y H:i') }}
                </time>
            </div>
            @if ($entry->user)
                <p class="text-xs text-gray-500 mb-2">{{ $entry->user->name }}</p>
            @endif
            <div class="text-sm text-gray-700 whitespace-pre-line leading-relaxed">{{ $entry->body }}</div>
        </article>
    @empty
        @if ($weapon->notes)
            <div class="bg-white rounded-lg border border-gray-200 p-4">
                <p class="text-xs text-gray-500 mb-2">{{ __('Nota heredada (antes del historial)') }}</p>
                <div class="text-sm text-gray-700 whitespace-pre-line leading-relaxed">{{ $weapon->notes }}</div>
            </div>
        @else
            <div class="text-center py-8 text-gray-500">
                <svg class="h-12 w-12 mx-auto text-gray-300 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                </svg>
                <p class="text-lg font-medium">{{ __('Sin notas registradas') }}</p>
                <p class="text-sm mt-1">{{ __('El historial se irá llenando con asignaciones, novedades y actualizaciones del arma.') }}</p>
            </div>
        @endif
    @endforelse
</div>
