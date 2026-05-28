<x-app-layout header-compact>
    <x-slot name="header">
        <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
            <h1 class="text-lg font-semibold leading-tight text-gray-900">{{ __('Detalle de arma') }}</h1>
            <div class="flex flex-wrap items-center gap-2">
                @can('update', $weapon)
                    <a href="{{ route('weapons.edit', $weapon) }}" class="inline-flex items-center px-2.5 py-1 bg-indigo-600 text-white text-sm font-medium rounded-lg hover:bg-indigo-700 transition">
                        {{ __('Editar arma') }}
                    </a>
                @endcan
                <a href="{{ route('weapons.index') }}" class="inline-flex items-center px-2.5 py-1 bg-white text-gray-700 text-sm font-medium rounded-lg hover:bg-gray-50 transition border border-gray-300">
                    {{ __('Volver al listado') }}
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-6">
        <div class="max-w-full mx-auto px-4 sm:px-6 lg:px-8">
            @if (session('status'))
                <div class="rounded-lg bg-green-50 border border-green-200 p-4 mb-6 text-sm text-green-700">
                    {{ session('status') }}
                </div>
            @endif

            @if ($pendingTransferForWeapon ?? null)
                @php
                    $pendingTransferForWeapon->loadMissing(['requestedBy', 'toUser']);
                @endphp
                <div class="mb-6 rounded-lg border border-amber-300 bg-amber-50 p-4 text-sm text-amber-950" role="status">
                    <p class="font-semibold">{{ __('Transferencia pendiente') }}</p>
                    <p class="mt-2">
                        @if (auth()->user()?->isAdmin())
                            {{ __('Esta arma está en transferencia pendiente. Enviada por :from; debe aceptarla :to.', [
                                'from' => $pendingTransferForWeapon->requestedBy?->name ?? __('—'),
                                'to' => $pendingTransferForWeapon->toUser?->name ?? __('—'),
                            ]) }}
                        @else
                            {{ __('Esta arma tiene una transferencia pendiente de aceptación. No puede modificar su destino ni sus asignaciones hasta que se resuelva.') }}
                        @endif
                    </p>
                    <a href="{{ route('transfers.index') }}" class="mt-3 inline-block font-medium text-amber-900 underline hover:no-underline">{{ __('Ir a transferencias') }}</a>
                </div>
            @endif

            <p class="mb-4 text-right text-xs text-gray-500">
                {{ __('Última actualización:') }} {{ $weapon->updated_at->format('Y-m-d') }}
            </p>

            <div class="sj-weapon-detail-layout grid grid-cols-1 gap-5 lg:grid-cols-2 lg:items-stretch">
                <div class="sj-weapon-detail-col-left flex min-h-0 flex-col gap-5 lg:h-full">
                    <div class="sj-weapon-detail-col-left__meta shrink-0 space-y-5">
                        @include('weapons.partials.show.characteristics')
                        @include('weapons.partials.show.permits')
                        @include('weapons.partials.show.ownership')
                    </div>
                    <div class="sj-weapon-detail-col-left__panels flex min-h-0 flex-col gap-5 lg:flex-1">
                        @include('weapons.partials.show.notes')
                        @include('weapons.partials.documents', ['embedded' => true])
                    </div>
                </div>

                <div class="sj-weapon-detail-col-right flex flex-col gap-5">
                    @if (Auth::user()->isAdmin() || Auth::user()->isResponsible())
                        <section class="sj-weapon-detail-section">
                            <h4 class="sj-weapon-detail-section__title">{{ __('Destino operativo') }}</h4>
                            @include('weapons.partials.assignment_client')
                        </section>

                        <section class="sj-weapon-detail-section">
                            <h4 class="sj-weapon-detail-section__title">{{ __('Asignación interna') }}</h4>
                            <div class="space-y-4">
                                @include('weapons.partials.assignment_custody')
                                @include('weapons.partials.assignment_internal')
                            </div>
                        </section>
                    @endif
                </div>
            </div>

            <div class="mt-6">
                @include('weapons.partials.photos', ['compact' => true])
            </div>
        </div>
    </div>
</x-app-layout>
