@if ($clients->count() > 0)
    <div class="sj-client-table-wrap hidden md:block">
        <table class="sj-client-table">
            <thead>
                <tr>
                    <th>{{ __('Cliente') }}</th>
                    <th>{{ __('NIT') }}</th>
                    <th>{{ __('Contacto') }}</th>
                    <th>{{ __('Ubicación') }}</th>
                    <th class="sj-client-col-actions">{{ __('Acciones') }}</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($clients as $client)
                    @php
                        $location = collect([$client->city, $client->department])->filter()->implode(', ');
                        $address = collect([$client->address, $client->neighborhood])->filter()->implode(' / ');
                        $hasContact = filled($client->contact_name) || filled($client->email);
                    @endphp
                    <tr class="sj-client-row sj-client-row--summary" data-client-row data-client-id="{{ $client->id }}">
                        <td class="sj-client-cell sj-client-cell--client">
                            <div class="sj-client-row__lead">
                                <button
                                    type="button"
                                    class="sj-client-toggle"
                                    data-client-toggle
                                    aria-expanded="false"
                                    aria-controls="client-detail-{{ $client->id }}"
                                    aria-label="{{ __('Mostrar detalle del cliente') }}"
                                >
                                    <span class="sj-client-toggle__caret" aria-hidden="true"></span>
                                </button>
                                <span class="sj-client-primary sj-client-primary--truncate">{{ $client->name }}</span>
                            </div>
                        </td>
                        <td class="sj-client-cell">
                            <span class="sj-client-secondary sj-client-secondary--mono">{{ $client->nit ?: '-' }}</span>
                        </td>
                        <td class="sj-client-cell">
                            <span class="sj-client-badge {{ $hasContact ? 'sj-client-badge--green' : 'sj-client-badge--slate' }}">
                                {{ $hasContact ? __('Con contacto') : __('Sin contacto') }}
                            </span>
                        </td>
                        <td class="sj-client-cell">
                            <span class="sj-client-secondary">{{ $client->city ?: __('Sin ciudad') }}</span>
                        </td>
                        <td class="sj-client-cell sj-client-cell--actions">
                            <div class="sj-client-actions sj-client-actions--end">
                                @can('update', $client)
                                    <a href="{{ route('clients.edit', $client) }}" class="sj-client-action sj-client-action--edit">
                                        {{ __('Editar') }}
                                    </a>
                                @endcan
                                @can('delete', $client)
                                    <form action="{{ route('clients.destroy', $client) }}" method="POST" class="inline">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="sj-client-action sj-client-action--delete" onclick="return confirm(@js(__('¿Eliminar cliente?')))">
                                            {{ __('Eliminar') }}
                                        </button>
                                    </form>
                                @endcan
                            </div>
                        </td>
                    </tr>
                    <tr
                        id="client-detail-{{ $client->id }}"
                        class="sj-client-detail-row hidden"
                        data-client-detail-row
                        data-client-id="{{ $client->id }}"
                    >
                        <td class="sj-client-detail-cell">
                            <div class="sj-client-detail__item">
                                <div class="sj-client-col__label">{{ __('Dirección') }}</div>
                                <div class="sj-client-secondary">{{ $address ?: __('Sin dirección registrada') }}</div>
                            </div>
                        </td>
                        <td class="sj-client-detail-cell">
                            <div class="sj-client-detail__item">
                                <div class="sj-client-col__label">{{ __('Representante legal') }}</div>
                                <div class="sj-client-secondary">{{ $client->legal_representative ?: __('Sin representante legal registrado') }}</div>
                            </div>
                        </td>
                        <td class="sj-client-detail-cell">
                            <div class="sj-client-detail__item">
                                <div class="sj-client-col__label">{{ __('Contacto') }}</div>
                                <div class="sj-client-secondary">{{ $client->contact_name ?: __('Sin contacto registrado') }}</div>
                                <div class="sj-client-muted">{{ $client->email ?: __('Sin correo registrado') }}</div>
                            </div>
                        </td>
                        <td class="sj-client-detail-cell">
                            <div class="sj-client-detail__item">
                                <div class="sj-client-col__label">{{ __('Ubicación') }}</div>
                                <div class="sj-client-secondary">{{ $location ?: __('Ubicación pendiente') }}</div>
                            </div>
                        </td>
                        <td class="sj-client-detail-cell sj-client-detail-cell--actions"></td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <div class="sj-client-cards md:hidden">
        @foreach ($clients as $client)
            @php
                $location = collect([$client->city, $client->department])->filter()->implode(', ');
                $address = collect([$client->address, $client->neighborhood])->filter()->implode(' / ');
            @endphp
            <article class="sj-client-card">
                <div class="sj-client-card__top">
                    <div>
                        <div class="sj-client-col__label">{{ __('Cliente') }}</div>
                        <h3 class="sj-client-card__title">{{ $client->name }}</h3>
                    </div>
                    <span class="sj-client-badge sj-client-badge--blue">{{ $client->city ?: __('Sin ciudad') }}</span>
                </div>

                <div class="sj-client-card__body">
                    <div class="sj-client-card__meta">
                        <span class="sj-client-col__label">{{ __('NIT') }}</span>
                        <span class="sj-client-primary">{{ $client->nit ?: '-' }}</span>
                    </div>
                    <div class="sj-client-card__meta">
                        <span class="sj-client-col__label">{{ __('Contacto') }}</span>
                        <span class="sj-client-secondary">{{ $client->contact_name ?: __('Sin contacto') }}</span>
                    </div>
                    <div class="sj-client-card__meta">
                        <span class="sj-client-col__label">{{ __('Correo') }}</span>
                        <span class="sj-client-secondary">{{ $client->email ?: __('Sin correo registrado') }}</span>
                    </div>
                    <div class="sj-client-card__meta">
                        <span class="sj-client-col__label">{{ __('Ubicación') }}</span>
                        <span class="sj-client-secondary">{{ $location ?: __('Ubicación pendiente') }}</span>
                    </div>
                    <div class="sj-client-card__meta">
                        <span class="sj-client-col__label">{{ __('Dirección') }}</span>
                        <span class="sj-client-secondary">{{ $address ?: __('Sin dirección registrada') }}</span>
                    </div>
                </div>

                <div class="sj-client-card__actions">
                    @can('update', $client)
                        <a href="{{ route('clients.edit', $client) }}" class="sj-client-action sj-client-action--edit">
                            {{ __('Editar') }}
                        </a>
                    @endcan
                    @can('delete', $client)
                        <form action="{{ route('clients.destroy', $client) }}" method="POST" class="inline">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="sj-client-action sj-client-action--delete" onclick="return confirm(@js(__('¿Eliminar cliente?')))">
                                {{ __('Eliminar') }}
                            </button>
                        </form>
                    @endcan
                </div>
            </article>
        @endforeach
    </div>

    <div class="mt-6">
        {{ $clients->links() }}
    </div>
@else
    <div class="sj-client-empty">
        <div class="sj-client-empty__title">
            {{ $search !== '' ? __('No se encontraron clientes.') : __('No hay clientes registrados.') }}
        </div>
        <p class="sj-client-empty__copy">
            {{ $search !== '' ? __('Ajusta el término de búsqueda para ver resultados por razón social, NIT, contacto, correo o ciudad.') : __('Cuando registres clientes, aquí verás su información principal organizada para edición y seguimiento.') }}
        </p>

        @if ($search === '' && auth()->user()?->can('create', App\Models\Client::class))
            <div>
                <a href="{{ route('clients.create') }}" class="sj-client-action sj-client-action--edit">
                    {{ __('Crear primer cliente') }}
                </a>
            </div>
        @endif
    </div>
@endif
