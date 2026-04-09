<x-app-layout>
    @php
        $pageTitle = Auth::user()->isResponsible() && !Auth::user()->isAdmin() ? __('Mis clientes') : __('Clientes');
        $clientCountLabel = number_format($clients->total()) . ' ' . ($clients->total() === 1 ? 'cliente' : 'clientes');
    @endphp

    <x-slot name="header">
        <div class="client-directory-header">
            <div class="client-directory-header__icon-shell" aria-hidden="true">
                <img src="{{ asset('images/icons/Clientes.png') }}" alt="" class="client-directory-header__icon">
            </div>

            <div class="client-directory-header__main">
                <div class="client-directory-header__row">
                    <div class="client-directory-header__intro">
                        <div class="client-directory-header__copy">
                            <h2 class="text-xl font-semibold leading-tight text-gray-800">
                                {{ $pageTitle }}
                            </h2>
                            <p class="mt-1 text-sm text-slate-500">
                                {{ __('Consulta y administra la base de clientes registrada en el sistema.') }}
                            </p>
                        </div>
                    </div>

                    <div class="client-directory-header__actions">
                        @can('create', App\Models\Client::class)
                            <a href="{{ route('clients.create') }}" class="client-directory-header__primary-action">
                                {{ __('Nuevo cliente') }}
                            </a>
                        @endcan
                    </div>
                </div>

                <div class="client-directory-header__row client-directory-header__row--bottom">
                    <form class="client-directory-header__search" role="search" onsubmit="return false;">
                        <input
                            id="clients-search"
                            type="search"
                            name="q"
                            value="{{ $search }}"
                            class="h-10 w-full rounded-xl border-slate-300 text-sm shadow-sm"
                            placeholder="{{ __('Buscar por razón social, NIT, contacto, correo o ciudad...') }}"
                        >
                    </form>

                    <div class="client-directory-header__tools">
                        <span id="clients-count" class="client-directory-header__counter">{{ $clientCountLabel }}</span>
                    </div>
                </div>
            </div>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="sj-page-shell sj-page-shell--wide space-y-4">
            @if (session('status'))
                <div class="rounded border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">
                    {{ session('status') }}
                </div>
            @endif

            @if ($errors->any())
                <div class="rounded border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                    <ul class="list-disc space-y-1 pl-4">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <section class="sj-panel sj-client-panel">
                <div id="clients-results">
                    @include('clients.partials.index_results', ['clients' => $clients, 'search' => $search])
                </div>
            </section>
        </div>
    </div>

    @push('scripts')
        <script>
            (() => {
                const input = document.getElementById('clients-search');
                const results = document.getElementById('clients-results');
                const count = document.getElementById('clients-count');

                if (!input || !results || !count) {
                    return;
                }

                const currentState = () => ({
                    q: input.value.trim(),
                });

                const applyStateToUrl = (url, { resetPage = false } = {}) => {
                    const state = currentState();
                    const page = resetPage ? '1' : (url.searchParams.get('page') || '1');

                    url.search = '';

                    if (state.q !== '') {
                        url.searchParams.set('q', state.q);
                    }

                    url.searchParams.set('page', page);
                };

                const closeExpandedRows = () => {
                    results.querySelectorAll('[data-client-row]').forEach((row) => {
                        row.classList.remove('is-open');

                        const toggle = row.querySelector('[data-client-toggle]');
                        if (toggle) {
                            toggle.setAttribute('aria-expanded', 'false');
                            toggle.setAttribute('aria-label', '{{ __('Mostrar detalle del cliente') }}');
                        }
                    });

                    results.querySelectorAll('[data-client-detail-row]').forEach((row) => {
                        row.classList.add('hidden');
                    });
                };

                const toggleExpandedRow = (summaryRow) => {
                    if (!summaryRow) {
                        return;
                    }

                    const clientId = summaryRow.dataset.clientId;
                    const detailRow = results.querySelector(`[data-client-detail-row][data-client-id="${clientId}"]`);
                    const toggle = summaryRow.querySelector('[data-client-toggle]');
                    const isOpen = summaryRow.classList.contains('is-open');

                    closeExpandedRows();

                    if (isOpen || !detailRow) {
                        return;
                    }

                    summaryRow.classList.add('is-open');
                    detailRow.classList.remove('hidden');

                    if (toggle) {
                        toggle.setAttribute('aria-expanded', 'true');
                        toggle.setAttribute('aria-label', '{{ __('Ocultar detalle del cliente') }}');
                    }
                };

                const updateList = async (url) => {
                    closeExpandedRows();

                    const response = await fetch(url, {
                        headers: { 'X-Requested-With': 'XMLHttpRequest' },
                    });

                    if (!response.ok) {
                        return;
                    }

                    const data = await response.json();
                    results.innerHTML = data.results;
                    count.textContent = data.countLabel;
                };

                let timer = null;
                input.addEventListener('input', () => {
                    const url = new URL(window.location.href);
                    applyStateToUrl(url, { resetPage: true });
                    window.history.replaceState({}, '', url.toString());

                    if (timer) {
                        clearTimeout(timer);
                    }

                    timer = setTimeout(() => {
                        updateList(url.toString());
                    }, 300);
                });

                results.addEventListener('click', (event) => {
                    const link = event.target.closest('a');
                    if (link && link.href && link.closest('.pagination, nav')) {
                        event.preventDefault();
                        const url = new URL(link.href);
                        applyStateToUrl(url);
                        window.history.replaceState({}, '', url.toString());
                        updateList(url.toString());
                        return;
                    }

                    if (event.target.closest('.sj-client-action--edit, .sj-client-action--delete, form')) {
                        return;
                    }

                    const summaryRow = event.target.closest('[data-client-row]');
                    if (!summaryRow) {
                        return;
                    }

                    toggleExpandedRow(summaryRow);
                });
            })();
        </script>
    @endpush

    <style>
        .client-directory-header {
            align-items: stretch;
            display: grid;
            gap: 0.55rem;
            grid-template-columns: 4.5rem minmax(0, 1fr);
        }

        .client-directory-header__row {
            align-items: flex-end;
            display: flex;
            gap: 1rem;
            justify-content: space-between;
        }

        .client-directory-header__row--bottom {
            align-items: flex-start;
        }

        .client-directory-header__main {
            display: flex;
            flex-direction: column;
            gap: 0.55rem;
        }

        .client-directory-header__copy {
            min-width: 0;
        }

        .client-directory-header__intro,
        .client-directory-header__search {
            flex: 1 1 auto;
            min-width: 0;
        }

        .client-directory-header__icon-shell {
            align-items: center;
            background: transparent;
            border: 0;
            border-radius: 0;
            box-shadow: none;
            display: inline-flex;
            height: 100%;
            justify-content: center;
            min-height: 6rem;
            overflow: visible;
            padding: 0;
            width: 4.5rem;
        }

        .client-directory-header__icon {
            display: block;
            height: 4.2rem;
            max-height: 100%;
            max-width: 100%;
            object-fit: contain;
            width: 4.2rem;
        }

        .client-directory-header__search {
            max-width: 42rem;
        }

        .client-directory-header__actions,
        .client-directory-header__tools {
            align-items: center;
            display: flex;
            flex: 0 0 auto;
            flex-wrap: wrap;
            gap: 0.5rem;
            justify-content: flex-end;
        }

        .client-directory-header__primary-action,
        .client-directory-header__counter {
            align-items: center;
            border-radius: 0.75rem;
            display: inline-flex;
            font-size: 0.875rem;
            font-weight: 600;
            height: 2.5rem;
        }

        .client-directory-header__primary-action {
            background: rgb(37 99 235);
            color: #fff;
            padding: 0 1rem;
            transition: 150ms ease;
        }

        .client-directory-header__primary-action:hover {
            background: rgb(29 78 216);
        }

        .client-directory-header__counter {
            background: #fff;
            border: 1px solid rgb(226 232 240);
            color: rgb(51 65 85);
            padding: 0 1rem;
        }

        @media (max-width: 1100px) {
            .client-directory-header {
                grid-template-columns: 1fr;
            }

            .client-directory-header__icon-shell {
                min-height: 4.5rem;
                width: 4rem;
            }

            .client-directory-header__row {
                align-items: stretch;
                flex-direction: column;
            }

            .client-directory-header__search {
                max-width: none;
            }

            .client-directory-header__actions,
            .client-directory-header__tools {
                justify-content: flex-start;
            }
        }
    </style>
</x-app-layout>
