<x-app-layout>
    <x-slot name="header">
        <div class="sj-section-header portfolio-header">
            <div class="sj-section-header__main">
                <p class="sj-section-header__eyebrow">{{ __('Cartera operativa') }}</p>
                <h2 class="sj-section-header__title">{{ __('Asignaciones por usuario') }}</h2>
                <p class="sj-section-header__subtitle">
                    {{ __('Administra la cartera de clientes por responsable y consulta su carga operativa antes de gestionar cambios o transferencias.') }}
                </p>
            </div>

            <div class="portfolio-header__stats">
                <span class="portfolio-header__stat">
                    <strong>{{ $responsibles->count() }}</strong>
                    {{ __('responsables') }}
                </span>
                <span class="portfolio-header__stat">
                    <strong>{{ $responsibles->sum('clients_count') }}</strong>
                    {{ __('clientes asignados') }}
                </span>
                <span class="portfolio-header__stat">
                    <strong>{{ $responsibles->sum('active_weapons_count') }}</strong>
                    {{ __('armas activas') }}
                </span>
            </div>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="sj-page-shell sj-page-shell--wide">
            @if (session('status'))
                <div class="mb-4 rounded-2xl border border-green-200 bg-green-50 p-3 text-sm text-green-700">
                    {{ session('status') }}
                </div>
            @endif

            <section class="portfolio-panel">
                <div class="portfolio-table-shell">
                    <table class="portfolio-table">
                        <thead>
                            <tr>
                                <th>{{ __('Usuario') }}</th>
                                <th>{{ __('Correo electrónico') }}</th>
                                <th>{{ __('Carga operativa') }}</th>
                                <th>{{ __('Acciones') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($responsibles as $responsible)
                                <tr>
                                    <td>
                                        <div class="portfolio-user">
                                            <span class="portfolio-user__name">{{ $responsible->name }}</span>
                                            <span class="portfolio-user__role">
                                                {{ $responsible->isAdmin() ? __('Administrador') : __('Responsable') }}
                                            </span>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="portfolio-user__email">{{ $responsible->email }}</span>
                                    </td>
                                    <td>
                                        <div class="portfolio-load">
                                            <span class="portfolio-load__badge">
                                                <strong>{{ $responsible->clients_count }}</strong>
                                                {{ __('clientes') }}
                                            </span>
                                            <span class="portfolio-load__badge portfolio-load__badge--accent">
                                                <strong>{{ $responsible->active_weapons_count }}</strong>
                                                {{ __('armas activas') }}
                                            </span>
                                        </div>
                                    </td>
                                    <td class="portfolio-table__actions">
                                        <a href="{{ route('portfolios.edit', $responsible) }}" class="portfolio-action">
                                            {{ __('Gestionar') }}
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="portfolio-table__empty">
                                        {{ __('No hay usuarios registrados.') }}
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </section>
        </div>
    </div>
</x-app-layout>

<style>
    .portfolio-header {
        align-items: flex-start;
    }

    .portfolio-header__stats {
        align-items: center;
        display: flex;
        flex-wrap: wrap;
        gap: 0.75rem;
        justify-content: flex-end;
    }

    .portfolio-header__stat {
        align-items: center;
        background: #fff;
        border: 1px solid rgb(203 213 225);
        border-radius: 9999px;
        color: rgb(51 65 85);
        display: inline-flex;
        font-size: 0.9rem;
        font-weight: 600;
        gap: 0.45rem;
        min-height: 2.75rem;
        padding: 0 1rem;
    }

    .portfolio-header__stat strong {
        color: rgb(15 23 42);
        font-size: 1rem;
    }

    .portfolio-panel {
        background: #fff;
        border: 1px solid rgb(203 213 225);
        border-radius: 0;
        box-shadow: 0 18px 40px rgba(15, 23, 42, 0.08);
        overflow: hidden;
    }

    .portfolio-table-shell {
        overflow-x: auto;
        padding: 0;
    }

    .portfolio-table {
        border-collapse: separate;
        border-spacing: 0;
        min-width: 100%;
        width: 100%;
    }

    .portfolio-table thead th {
        background: linear-gradient(180deg, #223469 0%, #162457 100%);
        color: rgb(239 246 255);
        font-size: 0.76rem;
        font-weight: 800;
        letter-spacing: 0.12em;
        padding: 0.95rem 1rem;
        text-align: left;
        text-transform: uppercase;
        white-space: nowrap;
    }

    .portfolio-table thead th:last-child {
        text-align: right;
    }

    .portfolio-table tbody td {
        border-bottom: 1px solid rgb(226 232 240);
        color: rgb(30 41 59);
        font-size: 0.95rem;
        padding: 1rem;
        vertical-align: middle;
    }

    .portfolio-table tbody tr:hover td {
        background: rgb(248 250 252);
    }

    .portfolio-user {
        display: grid;
        gap: 0.2rem;
    }

    .portfolio-user__name {
        color: rgb(15 23 42);
        font-size: 1rem;
        font-weight: 700;
    }

    .portfolio-user__role,
    .portfolio-user__email {
        color: rgb(82 99 122);
        font-size: 0.88rem;
    }

    .portfolio-load {
        display: flex;
        flex-wrap: wrap;
        gap: 0.6rem;
    }

    .portfolio-load__badge {
        align-items: center;
        background: rgb(248 250 252);
        border: 1px solid rgb(203 213 225);
        border-radius: 9999px;
        color: rgb(51 65 85);
        display: inline-flex;
        font-size: 0.88rem;
        font-weight: 600;
        gap: 0.45rem;
        min-height: 2.4rem;
        padding: 0 0.9rem;
    }

    .portfolio-load__badge--accent {
        background: rgb(239 246 255);
        border-color: rgb(191 219 254);
        color: rgb(29 78 216);
    }

    .portfolio-load__badge strong {
        color: inherit;
        font-size: 0.96rem;
    }

    .portfolio-table__actions {
        text-align: right;
    }

    .portfolio-action {
        align-items: center;
        background: rgb(37 99 235);
        border-radius: 0.9rem;
        color: #fff;
        display: inline-flex;
        font-size: 0.9rem;
        font-weight: 700;
        min-height: 2.65rem;
        padding: 0 1rem;
        transition: 150ms ease;
    }

    .portfolio-action:hover {
        background: rgb(29 78 216);
    }

    .portfolio-table__empty {
        color: rgb(82 99 122);
        padding: 2rem 1rem;
        text-align: center;
    }

    @media (max-width: 900px) {
        .portfolio-header__stats {
            justify-content: flex-start;
        }

        .portfolio-table thead th:last-child,
        .portfolio-table__actions {
            text-align: left;
        }
    }
</style>
