<x-app-layout>
    <x-slot name="header">
        <div class="sj-section-header portfolio-edit-header">
            <div class="sj-section-header__main">
                <p class="sj-section-header__eyebrow">{{ __('Cartera del responsable') }}</p>
                <h2 class="sj-section-header__title">{{ __('Asignaciones de') }} {{ $user->name }}</h2>
                <p class="sj-section-header__subtitle">
                    {{ __('Selecciona los clientes que estarán bajo su responsabilidad y gestiona transferencias sin perder visibilidad de los bloqueos operativos.') }}
                </p>
            </div>

            <div class="portfolio-edit-header__stats">
                <span class="portfolio-edit-header__stat">
                    <strong>{{ $assignedCount }}</strong>
                    {{ __('asignados') }}
                </span>
                <span class="portfolio-edit-header__stat">
                    <strong>{{ $blockedCount }}</strong>
                    {{ __('bloqueados') }}
                </span>
                <span class="portfolio-edit-header__stat">
                    <strong>{{ $availableCount }}</strong>
                    {{ __('disponibles') }}
                </span>
                <a href="{{ route('portfolios.index') }}" class="portfolio-edit-header__back">
                    {{ __('Volver al tablero') }}
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="sj-page-shell sj-page-shell--wide">
            @if ($errors->any())
                <div class="mb-4 rounded-2xl border border-red-200 bg-red-50 p-4 text-sm text-red-700">
                    <ul class="space-y-1">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div class="portfolio-edit-layout">
                <section class="portfolio-edit-panel">
                    <div class="portfolio-edit-panel__head">
                        <div>
                            <p class="portfolio-edit-panel__eyebrow">{{ __('Clientes del responsable') }}</p>
                            <h3 class="portfolio-edit-panel__title">{{ __('Gestión de cartera') }}</h3>
                            <p class="portfolio-edit-panel__subtitle">
                                {{ __('Usa el buscador y los filtros rápidos para revisar solo los clientes que te interesan antes de guardar cambios.') }}
                            </p>
                        </div>
                    </div>

                    <form method="POST" action="{{ route('portfolios.update', $user) }}" id="portfolio-update-form">
                        @csrf
                        @method('PUT')

                        <div class="portfolio-toolbar">
                            <div class="portfolio-toolbar__top">
                                <div class="portfolio-toolbar__search">
                                    <input
                                        id="portfolio-search"
                                        type="search"
                                        class="portfolio-search-input"
                                        placeholder="{{ __('Buscar cliente...') }}"
                                    >
                                </div>

                                <div class="portfolio-toolbar__meta">
                                    <span id="portfolio-visible-count" class="portfolio-toolbar__counter"></span>
                                    <button type="button" id="portfolio-select-visible" class="portfolio-toolbar__button portfolio-toolbar__button--primary">
                                        {{ __('Seleccionar visibles') }}
                                    </button>
                                    <button type="button" id="portfolio-clear-visible" class="portfolio-toolbar__button">
                                        {{ __('Limpiar visibles') }}
                                    </button>
                                </div>
                            </div>

                            <div class="portfolio-toolbar__filters" role="tablist" aria-label="{{ __('Filtro de clientes') }}">
                                <button type="button" class="portfolio-filter is-active" data-filter="all">{{ __('Todos') }}</button>
                                <button type="button" class="portfolio-filter" data-filter="assigned">{{ __('Asignados') }}</button>
                                <button type="button" class="portfolio-filter" data-filter="available">{{ __('Disponibles') }}</button>
                                <button type="button" class="portfolio-filter" data-filter="blocked">{{ __('Bloqueados') }}</button>
                            </div>
                        </div>

                        <div class="portfolio-cards" id="portfolio-cards">
                            @foreach ($clients as $client)
                                @php
                                    $isAssigned = in_array($client->id, $assigned, true);
                                    $blockedWeapons = (int) ($blockedClientCounts[$client->id] ?? 0);
                                    $state = $blockedWeapons > 0 ? 'blocked' : ($isAssigned ? 'assigned' : 'available');
                                @endphp
                                <label
                                    class="portfolio-card"
                                    data-client-card
                                    data-state="{{ $state }}"
                                    data-name="{{ \Illuminate\Support\Str::lower($client->name) }}"
                                >
                                    <span class="portfolio-card__checkbox">
                                        <input
                                            type="checkbox"
                                            name="clients[]"
                                            value="{{ $client->id }}"
                                            @checked($isAssigned)
                                            data-client-checkbox
                                        >
                                    </span>

                                    <span class="portfolio-card__body">
                                        <span class="portfolio-card__name">{{ $client->name }}</span>
                                        <span class="portfolio-card__meta">
                                            @if ($blockedWeapons > 0)
                                                <span class="portfolio-card__badge portfolio-card__badge--blocked">
                                                    {{ __('Tiene armas asignadas') }} ({{ $blockedWeapons }})
                                                </span>
                                            @elseif ($isAssigned)
                                                <span class="portfolio-card__badge portfolio-card__badge--assigned">
                                                    {{ __('Asignado') }}
                                                </span>
                                            @else
                                                <span class="portfolio-card__badge">
                                                    {{ __('Disponible') }}
                                                </span>
                                            @endif
                                        </span>
                                    </span>
                                </label>
                            @endforeach
                        </div>

                        <div class="portfolio-savebar">
                            <span id="portfolio-selected-count" class="portfolio-savebar__count"></span>
                            <div class="portfolio-savebar__actions">
                                <a href="{{ route('portfolios.index') }}" class="portfolio-savebar__link">
                                    {{ __('Cancelar') }}
                                </a>
                                <button type="submit" class="portfolio-savebar__primary">
                                    {{ __('Guardar asignaciones') }}
                                </button>
                            </div>
                        </div>
                    </form>
                </section>

                <aside class="portfolio-side-panel">
                    <div class="portfolio-side-panel__head">
                        <p class="portfolio-edit-panel__eyebrow">{{ __('Movimiento de cartera') }}</p>
                        <h3 class="portfolio-edit-panel__title">{{ __('Transferir asignaciones') }}</h3>
                        <p class="portfolio-edit-panel__subtitle">
                            {{ __('Transfiere los clientes seleccionados a otro responsable sin perder el detalle de cuántos van a salir de esta cartera.') }}
                        </p>
                    </div>

                    <form method="POST" action="{{ route('portfolios.transfer', $user) }}" class="portfolio-transfer-form" id="portfolio-transfer-form">
                        @csrf

                        <div>
                            <label for="portfolio-transfer-user" class="portfolio-transfer-form__label">{{ __('Usuario destino') }}</label>
                            <select id="portfolio-transfer-user" name="to_user_id" class="portfolio-transfer-form__select" required>
                                <option value="">{{ __('Seleccione') }}</option>
                                @foreach ($responsibles as $responsible)
                                    @if ($responsible->id !== $user->id)
                                        <option value="{{ $responsible->id }}">{{ $responsible->name }}</option>
                                    @endif
                                @endforeach
                            </select>
                        </div>

                        <div class="portfolio-transfer-summary">
                            <span class="portfolio-transfer-summary__label">{{ __('Clientes seleccionados para transferir') }}</span>
                            <strong id="portfolio-transfer-count">{{ __('0 clientes') }}</strong>
                        </div>

                        <button type="submit" class="portfolio-transfer-form__submit">
                            {{ __('Transferir asignaciones') }}
                        </button>
                    </form>
                </aside>
            </div>
        </div>
    </div>

    <div id="portfolio-transfer-modal" class="portfolio-modal hidden" aria-hidden="true">
        <div class="portfolio-modal__backdrop"></div>
        <div class="portfolio-modal__dialog" role="dialog" aria-modal="true" aria-labelledby="portfolio-transfer-modal-title">
            <div class="portfolio-modal__header">
                <div>
                    <h3 id="portfolio-transfer-modal-title" class="portfolio-modal__title">{{ __('Confirmar transferencia') }}</h3>
                    <p id="portfolio-transfer-modal-description" class="portfolio-modal__description"></p>
                </div>
                <button type="button" id="portfolio-transfer-modal-close" class="portfolio-modal__close" aria-label="{{ __('Cerrar') }}">
                    &times;
                </button>
            </div>

            <div class="portfolio-modal__body">
                <p id="portfolio-transfer-modal-summary" class="portfolio-modal__summary"></p>
            </div>

            <div class="portfolio-modal__footer">
                <button type="button" id="portfolio-transfer-modal-cancel" class="portfolio-modal__button portfolio-modal__button--ghost">
                    {{ __('Cancelar') }}
                </button>
                <button type="button" id="portfolio-transfer-modal-edit" class="portfolio-modal__button portfolio-modal__button--secondary">
                    {{ __('Editar selección') }}
                </button>
                <button type="button" id="portfolio-transfer-modal-confirm" class="portfolio-modal__button portfolio-modal__button--primary">
                    {{ __('Confirmar transferencia') }}
                </button>
            </div>
        </div>
    </div>
</x-app-layout>

<style>
    .portfolio-edit-header {
        align-items: flex-start;
    }

    .portfolio-edit-header__stats {
        align-items: center;
        display: flex;
        flex-wrap: wrap;
        gap: 0.75rem;
        justify-content: flex-end;
    }

    .portfolio-edit-header__stat,
    .portfolio-edit-header__back {
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

    .portfolio-edit-header__stat strong {
        color: rgb(15 23 42);
        font-size: 1rem;
    }

    .portfolio-edit-header__back:hover {
        background: rgb(248 250 252);
    }

    .portfolio-edit-layout {
        display: grid;
        gap: 1.25rem;
        grid-template-columns: minmax(0, 1.85fr) minmax(18rem, 0.95fr);
    }

    .portfolio-edit-panel,
    .portfolio-side-panel {
        background: #fff;
        border: 1px solid rgb(203 213 225);
        border-radius: 1.5rem;
        box-shadow: 0 18px 40px rgba(15, 23, 42, 0.08);
    }

    .portfolio-edit-panel__head,
    .portfolio-side-panel__head {
        border-bottom: 1px solid rgb(226 232 240);
        padding: 1.5rem 1.6rem 1.1rem;
    }

    .portfolio-edit-panel__eyebrow {
        color: #4b6280;
        font-size: 0.76rem;
        font-weight: 800;
        letter-spacing: 0.16em;
        margin: 0 0 0.4rem;
        text-transform: uppercase;
    }

    .portfolio-edit-panel__title {
        color: rgb(15 23 42);
        font-size: 1.25rem;
        font-weight: 800;
        margin: 0;
    }

    .portfolio-edit-panel__subtitle {
        color: rgb(82 99 122);
        font-size: 0.94rem;
        line-height: 1.5;
        margin: 0.45rem 0 0;
    }

    #portfolio-update-form {
        padding: 1.35rem 1.4rem 1.4rem;
    }

    .portfolio-toolbar {
        display: grid;
        gap: 0.9rem;
        margin-bottom: 1.15rem;
    }

    .portfolio-toolbar__top {
        align-items: center;
        display: grid;
        gap: 1rem;
        grid-template-columns: minmax(0, 0.95fr) minmax(0, 1.05fr);
    }

    .portfolio-search-input,
    .portfolio-transfer-form__select {
        border: 1px solid rgb(203 213 225);
        border-radius: 0.95rem;
        color: rgb(30 41 59);
        font-size: 0.94rem;
        min-height: 2.9rem;
        padding: 0 0.95rem;
        width: 100%;
    }

    .portfolio-toolbar__filters {
        display: flex;
        flex-wrap: wrap;
        gap: 0.55rem;
    }

    .portfolio-filter {
        align-items: center;
        background: #fff;
        border: 1px solid rgb(203 213 225);
        border-radius: 9999px;
        color: rgb(51 65 85);
        display: inline-flex;
        font-size: 0.88rem;
        font-weight: 700;
        min-height: 2.65rem;
        padding: 0 0.95rem;
    }

    .portfolio-filter.is-active {
        background: rgb(37 99 235);
        border-color: rgb(37 99 235);
        color: #fff;
    }

    .portfolio-toolbar__meta {
        align-items: center;
        display: flex;
        flex-wrap: wrap;
        gap: 0.55rem;
        justify-content: flex-end;
    }

    .portfolio-toolbar__counter,
    .portfolio-toolbar__button {
        align-items: center;
        border: 1px solid rgb(203 213 225);
        border-radius: 9999px;
        display: inline-flex;
        font-size: 0.88rem;
        font-weight: 700;
        min-height: 2.65rem;
        padding: 0 0.95rem;
    }

    .portfolio-toolbar__counter {
        background: rgb(248 250 252);
        color: rgb(71 85 105);
    }

    .portfolio-toolbar__button {
        background: #fff;
        color: rgb(51 65 85);
    }

    .portfolio-toolbar__button:hover {
        background: rgb(248 250 252);
    }

    .portfolio-toolbar__button--primary {
        background: rgb(239 246 255);
        border-color: rgb(191 219 254);
        color: rgb(29 78 216);
    }

    .portfolio-toolbar__button--primary:hover {
        background: rgb(219 234 254);
    }

    .portfolio-cards {
        display: grid;
        gap: 0.8rem;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        max-height: calc(100vh - 26rem);
        overflow: auto;
        padding-right: 0.2rem;
    }

    .portfolio-card {
        align-items: flex-start;
        background: #fff;
        border: 1px solid rgb(203 213 225);
        border-radius: 1rem;
        cursor: pointer;
        display: flex;
        gap: 0.8rem;
        min-height: 5.2rem;
        padding: 0.9rem 1rem;
        transition: 150ms ease;
    }

    .portfolio-card:hover {
        border-color: rgb(148 163 184);
        box-shadow: 0 8px 24px rgba(15, 23, 42, 0.08);
    }

    .portfolio-card.is-hidden {
        display: none;
    }

    .portfolio-card__checkbox {
        align-items: center;
        display: inline-flex;
        min-height: 1.5rem;
        padding-top: 0.1rem;
    }

    .portfolio-card__body {
        display: grid;
        gap: 0.45rem;
        min-width: 0;
    }

    .portfolio-card__name {
        color: rgb(15 23 42);
        font-size: 0.96rem;
        font-weight: 700;
        line-height: 1.35;
    }

    .portfolio-card__meta {
        display: flex;
        flex-wrap: wrap;
        gap: 0.5rem;
    }

    .portfolio-card__badge {
        align-items: center;
        background: rgb(248 250 252);
        border: 1px solid rgb(203 213 225);
        border-radius: 9999px;
        color: rgb(71 85 105);
        display: inline-flex;
        font-size: 0.78rem;
        font-weight: 700;
        min-height: 2rem;
        padding: 0 0.75rem;
    }

    .portfolio-card__badge--assigned {
        background: rgb(239 246 255);
        border-color: rgb(191 219 254);
        color: rgb(29 78 216);
    }

    .portfolio-card__badge--blocked {
        background: rgb(254 242 242);
        border-color: rgb(254 202 202);
        color: rgb(185 28 28);
    }

    .portfolio-savebar {
        align-items: center;
        background: rgb(248 250 252);
        border: 1px solid rgb(226 232 240);
        border-radius: 1rem;
        display: flex;
        flex-wrap: wrap;
        gap: 0.85rem;
        justify-content: space-between;
        margin-top: 1rem;
        padding: 0.9rem 1rem;
    }

    .portfolio-savebar__count {
        color: rgb(51 65 85);
        font-size: 0.9rem;
        font-weight: 700;
    }

    .portfolio-savebar__actions {
        align-items: center;
        display: flex;
        gap: 0.7rem;
    }

    .portfolio-savebar__link {
        color: rgb(71 85 105);
        font-size: 0.9rem;
        font-weight: 700;
    }

    .portfolio-savebar__primary,
    .portfolio-transfer-form__submit {
        align-items: center;
        background: rgb(37 99 235);
        border-radius: 0.95rem;
        color: #fff;
        display: inline-flex;
        font-size: 0.92rem;
        font-weight: 700;
        justify-content: center;
        min-height: 2.85rem;
        padding: 0 1.1rem;
        transition: 150ms ease;
    }

    .portfolio-savebar__primary:hover,
    .portfolio-transfer-form__submit:hover {
        background: rgb(29 78 216);
    }

    .portfolio-transfer-form {
        display: grid;
        gap: 1rem;
        padding: 1.35rem 1.35rem 1.45rem;
    }

    .portfolio-transfer-form__label {
        color: rgb(71 85 105);
        display: block;
        font-size: 0.85rem;
        font-weight: 700;
        margin-bottom: 0.45rem;
        text-transform: uppercase;
    }

    .portfolio-transfer-summary {
        background: rgb(248 250 252);
        border: 1px solid rgb(226 232 240);
        border-radius: 1rem;
        display: grid;
        gap: 0.2rem;
        padding: 0.9rem 1rem;
    }

    .portfolio-transfer-summary__label {
        color: rgb(82 99 122);
        font-size: 0.82rem;
        font-weight: 700;
        letter-spacing: 0.06em;
        text-transform: uppercase;
    }

    .portfolio-transfer-summary strong {
        color: rgb(15 23 42);
        font-size: 1.05rem;
        font-weight: 800;
    }

    .portfolio-modal {
        inset: 0;
        position: fixed;
        z-index: 180;
    }

    .portfolio-modal.hidden {
        display: none;
    }

    .portfolio-modal__backdrop {
        background: rgba(15, 23, 42, 0.48);
        inset: 0;
        position: absolute;
    }

    .portfolio-modal__dialog {
        background: #fff;
        border: 1px solid rgb(226 232 240);
        border-radius: 1.25rem;
        box-shadow: 0 24px 60px rgba(15, 23, 42, 0.22);
        inset: 50% auto auto 50%;
        max-width: 34rem;
        position: absolute;
        transform: translate(-50%, -50%);
        width: calc(100vw - 2rem);
    }

    .portfolio-modal__header {
        align-items: flex-start;
        border-bottom: 1px solid rgb(226 232 240);
        display: flex;
        gap: 1rem;
        justify-content: space-between;
        padding: 1.2rem 1.35rem 1rem;
    }

    .portfolio-modal__title {
        color: rgb(15 23 42);
        font-size: 1.05rem;
        font-weight: 800;
        margin: 0;
    }

    .portfolio-modal__description,
    .portfolio-modal__summary {
        color: rgb(71 85 105);
        font-size: 0.94rem;
        line-height: 1.55;
        margin: 0;
    }

    .portfolio-modal__body {
        padding: 1rem 1.35rem 0.4rem;
    }

    .portfolio-modal__close {
        align-items: center;
        background: transparent;
        border: 0;
        color: rgb(100 116 139);
        cursor: pointer;
        display: inline-flex;
        font-size: 1.75rem;
        height: 2.15rem;
        justify-content: center;
        line-height: 1;
        width: 2.15rem;
    }

    .portfolio-modal__footer {
        align-items: center;
        display: flex;
        flex-wrap: wrap;
        gap: 0.75rem;
        justify-content: flex-end;
        padding: 1rem 1.35rem 1.25rem;
    }

    .portfolio-modal__button {
        align-items: center;
        border-radius: 0.9rem;
        display: inline-flex;
        font-size: 0.92rem;
        font-weight: 700;
        justify-content: center;
        min-height: 2.75rem;
        padding: 0 1rem;
    }

    .portfolio-modal__button--ghost,
    .portfolio-modal__button--secondary {
        background: #fff;
        border: 1px solid rgb(203 213 225);
        color: rgb(51 65 85);
    }

    .portfolio-modal__button--primary {
        background: rgb(15 23 42);
        border: 1px solid rgb(15 23 42);
        color: #fff;
    }

    @media (max-width: 1100px) {
        .portfolio-edit-header__stats {
            justify-content: flex-start;
        }

        .portfolio-edit-layout {
            grid-template-columns: 1fr;
        }

        .portfolio-toolbar__top {
            grid-template-columns: 1fr;
        }

        .portfolio-toolbar__meta {
            justify-content: flex-start;
        }

        .portfolio-cards {
            grid-template-columns: 1fr;
            max-height: none;
        }

        .portfolio-savebar,
        .portfolio-savebar__actions,
        .portfolio-modal__footer {
            align-items: stretch;
            flex-direction: column;
        }

        .portfolio-savebar__primary,
        .portfolio-transfer-form__submit,
        .portfolio-modal__button {
            width: 100%;
        }
    }
</style>

<script>
    (() => {
        const searchInput = document.getElementById('portfolio-search');
        const cards = Array.from(document.querySelectorAll('[data-client-card]'));
        const filterButtons = Array.from(document.querySelectorAll('.portfolio-filter'));
        const visibleCount = document.getElementById('portfolio-visible-count');
        const selectedCount = document.getElementById('portfolio-selected-count');
        const transferCount = document.getElementById('portfolio-transfer-count');
        const selectVisible = document.getElementById('portfolio-select-visible');
        const clearVisible = document.getElementById('portfolio-clear-visible');
        const transferForm = document.getElementById('portfolio-transfer-form');
        const destinationSelect = document.getElementById('portfolio-transfer-user');
        const transferModal = document.getElementById('portfolio-transfer-modal');
        const transferModalSummary = document.getElementById('portfolio-transfer-modal-summary');
        const transferModalDescription = document.getElementById('portfolio-transfer-modal-description');
        const transferModalConfirm = document.getElementById('portfolio-transfer-modal-confirm');
        const transferModalCancel = document.getElementById('portfolio-transfer-modal-cancel');
        const transferModalEdit = document.getElementById('portfolio-transfer-modal-edit');
        const transferModalClose = document.getElementById('portfolio-transfer-modal-close');
        const transferModalBackdrop = transferModal?.querySelector('.portfolio-modal__backdrop');

        if (!searchInput || !cards.length || !visibleCount || !selectedCount || !transferCount || !selectVisible || !clearVisible || !transferForm || !destinationSelect || !transferModal || !transferModalSummary || !transferModalDescription || !transferModalConfirm || !transferModalCancel || !transferModalEdit || !transferModalClose || !transferModalBackdrop) {
            return;
        }

        let activeFilter = 'all';

        const checkboxForCard = (card) => card.querySelector('[data-client-checkbox]');

        const selectedCheckboxes = () => cards
            .map((card) => checkboxForCard(card))
            .filter((checkbox) => checkbox && checkbox.checked);

        const selectedCountLabel = (count) => count === 1
            ? '{{ __('1 cliente seleccionado') }}'
            : `${count} {{ __('clientes seleccionados') }}`;

        const transferCountLabel = (count) => count === 1
            ? '{{ __('1 cliente') }}'
            : `${count} {{ __('clientes') }}`;

        const applyFilters = () => {
            const searchTerm = searchInput.value.trim().toLowerCase();
            let visible = 0;

            cards.forEach((card) => {
                const matchesSearch = card.dataset.name.includes(searchTerm);
                const matchesFilter = activeFilter === 'all' || card.dataset.state === activeFilter;
                const shouldShow = matchesSearch && matchesFilter;
                card.classList.toggle('is-hidden', !shouldShow);

                if (shouldShow) {
                    visible += 1;
                }
            });

            visibleCount.textContent = visible === 1
                ? '{{ __('1 cliente visible') }}'
                : `${visible} {{ __('clientes visibles') }}`;
        };

        const syncSelectionCounters = () => {
            const count = selectedCheckboxes().length;
            selectedCount.textContent = selectedCountLabel(count);
            transferCount.textContent = transferCountLabel(count);
        };

        const closeTransferModal = () => {
            transferModal.classList.add('hidden');
            transferModal.setAttribute('aria-hidden', 'true');
        };

        searchInput.addEventListener('input', applyFilters);

        filterButtons.forEach((button) => {
            button.addEventListener('click', () => {
                activeFilter = button.dataset.filter || 'all';
                filterButtons.forEach((item) => item.classList.toggle('is-active', item === button));
                applyFilters();
            });
        });

        cards.forEach((card) => {
            checkboxForCard(card)?.addEventListener('change', syncSelectionCounters);
        });

        selectVisible.addEventListener('click', () => {
            cards
                .filter((card) => !card.classList.contains('is-hidden'))
                .forEach((card) => {
                    const checkbox = checkboxForCard(card);
                    if (checkbox) {
                        checkbox.checked = true;
                    }
                });
            syncSelectionCounters();
        });

        clearVisible.addEventListener('click', () => {
            cards
                .filter((card) => !card.classList.contains('is-hidden'))
                .forEach((card) => {
                    const checkbox = checkboxForCard(card);
                    if (checkbox) {
                        checkbox.checked = false;
                    }
                });
            syncSelectionCounters();
        });

        transferForm.addEventListener('submit', (event) => {
            event.preventDefault();

            const selected = selectedCheckboxes();
            if (selected.length === 0) {
                alert(@json(__('Seleccione al menos un cliente para transferir.')));
                return;
            }

            if (!destinationSelect.value) {
                destinationSelect.focus();
                return;
            }

            const destinationName = destinationSelect.selectedOptions?.[0]?.textContent?.trim() || @json(__('el nuevo usuario'));
            transferModalDescription.textContent = @json(__('Se transferirán los clientes seleccionados desde :user hacia :destination.'))
                .replace(':user', @json($user->name))
                .replace(':destination', destinationName);
            transferModalSummary.textContent = @json(__('¿Confirmas la transferencia de :count cliente(s)?'))
                .replace(':count', selected.length);

            transferModal.classList.remove('hidden');
            transferModal.setAttribute('aria-hidden', 'false');
        });

        transferModalConfirm.addEventListener('click', () => {
            transferForm.querySelectorAll('input[name="clients[]"]').forEach((input) => input.remove());

            selectedCheckboxes().forEach((checkbox) => {
                const hidden = document.createElement('input');
                hidden.type = 'hidden';
                hidden.name = 'clients[]';
                hidden.value = checkbox.value;
                transferForm.appendChild(hidden);
            });

            closeTransferModal();
            HTMLFormElement.prototype.submit.call(transferForm);
        });

        transferModalCancel.addEventListener('click', closeTransferModal);
        transferModalEdit.addEventListener('click', closeTransferModal);
        transferModalClose.addEventListener('click', closeTransferModal);
        transferModalBackdrop.addEventListener('click', closeTransferModal);

        document.addEventListener('keydown', (event) => {
            if (event.key === 'Escape' && !transferModal.classList.contains('hidden')) {
                closeTransferModal();
            }
        });

        applyFilters();
        syncSelectionCounters();
    })();
</script>
