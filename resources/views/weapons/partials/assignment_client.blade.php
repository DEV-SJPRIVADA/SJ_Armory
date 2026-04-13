<div class="space-y-4">
    <!-- Current Assignment Status -->
    <div class="bg-white rounded-lg border border-gray-200 p-4">
        <div class="text-xs font-semibold text-gray-500 mb-2">{{ __('Cliente actual') }}</div>
        <div class="text-lg font-bold text-gray-900">
            {{ $weapon->activeClientAssignment?->client?->name ?? __('Sin destino') }}
        </div>
    </div>
    
    <!-- Assignment Form -->
    <div class="bg-white rounded-lg border border-gray-200 p-4">
        <form method="POST" action="{{ route('weapons.client_assignments.store', $weapon) }}" class="space-y-4" id="destination-operational-form">
            @csrf

            <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
                <div>
                    <label class="text-sm text-gray-600">{{ __('Cliente') }}</label>

                    <div class="relative mt-1">
                        <select name="client_id" id="destination-client-select" class="hidden" required>
                            <option value="">{{ __('Seleccione') }}</option>
                            @foreach ($clientOptions as $client)
                                @php
                                    $responsibleMeta = $clientResponsibleMap[$client->id] ?? ['id' => null, 'name' => null];
                                @endphp
                                <option
                                    value="{{ $client->id }}"
                                    data-responsible-id="{{ $responsibleMeta['id'] ?? '' }}"
                                    data-responsible-name="{{ $responsibleMeta['name'] ?? '' }}"
                                    @selected(old('client_id', $weapon->activeClientAssignment?->client_id) == $client->id)
                                >
                                    {{ $client->name }}
                                </option>
                            @endforeach
                        </select>

                        <input
                            type="text"
                            id="destination-client-search"
                            class="block w-full rounded-md border-gray-300 pr-10 text-sm shadow-sm"
                            placeholder="{{ __('Buscar cliente...') }}"
                            autocomplete="off"
                            spellcheck="false"
                            role="combobox"
                            aria-expanded="false"
                            aria-controls="destination-client-options"
                        >

                        <button
                            type="button"
                            id="destination-client-toggle"
                            class="absolute inset-y-0 right-0 flex items-center px-3 text-gray-500"
                            aria-label="{{ __('Mostrar clientes') }}"
                        >
                            <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                <path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 011.06.02L10 11.168l3.71-3.938a.75.75 0 111.08 1.04l-4.25 4.51a.75.75 0 01-1.08 0l-4.25-4.51a.75.75 0 01.02-1.06z" clip-rule="evenodd" />
                            </svg>
                        </button>

                        <div
                            id="destination-client-options"
                            class="absolute left-0 right-0 z-20 mt-2 hidden max-h-72 overflow-y-auto rounded-md border border-slate-200 bg-white py-1 shadow-xl"
                            role="listbox"
                        ></div>
                    </div>

                    <x-input-error :messages="$errors->get('client_id')" class="mt-2" />
                </div>

                <div>
                    <label class="text-sm text-gray-600">{{ __('Responsable') }}</label>
                    <div id="destination-responsible-display" class="mt-1 rounded-md border border-gray-300 bg-gray-50 px-3 py-2 text-sm text-gray-700">
                        {{ $weapon->activeClientAssignment?->responsible?->name ?? __('Sin responsable asignado') }}
                    </div>
                    <input type="hidden" id="destination-responsible-id" name="responsible_user_id" value="{{ $weapon->activeClientAssignment?->responsible_user_id }}">
                </div>

                <div class="md:col-span-3">
                    <label class="text-sm text-gray-600">{{ __('Observaciones') }}</label>
                    <input type="text" name="reason" class="mt-1 block w-full rounded-md border-gray-300 text-sm" />
                    <x-input-error :messages="$errors->get('reason')" class="mt-2" />
                </div>
            </div>

            <div class="flex justify-end gap-2">
                <button type="submit" class="text-sm text-white bg-indigo-600 hover:bg-indigo-700 px-4 py-2 rounded">
                    {{ __('Actualizar destino') }}
                </button>
            </div>
        </form>
    </div>
</div>

<div id="missing-responsible-modal" class="fixed inset-0 z-[1400] hidden items-center justify-center bg-black/50 p-4">
    <div class="w-full max-w-md rounded-lg bg-white p-5 shadow-xl">
        <h4 class="text-base font-semibold text-gray-900">{{ __('Atención') }}</h4>
        <p class="mt-2 text-sm text-gray-700">{{ __('Primero debe realizar la asignación del responsable.') }}</p>
        <div class="mt-4 flex justify-end">
            <button type="button" id="missing-responsible-modal-close" class="rounded-md bg-indigo-600 px-4 py-2 text-sm text-white hover:bg-indigo-700">
                {{ __('Aceptar') }}
            </button>
        </div>
    </div>
</div>

<script>
    (() => {
        const form = document.getElementById('destination-operational-form');
        const clientSelect = document.getElementById('destination-client-select');
        const clientSearch = document.getElementById('destination-client-search');
        const clientToggle = document.getElementById('destination-client-toggle');
        const clientOptionsPanel = document.getElementById('destination-client-options');
        const responsibleDisplay = document.getElementById('destination-responsible-display');
        const responsibleIdInput = document.getElementById('destination-responsible-id');
        const modal = document.getElementById('missing-responsible-modal');
        const closeBtn = document.getElementById('missing-responsible-modal-close');

        if (!form || !clientSelect || !clientSearch || !clientToggle || !clientOptionsPanel || !responsibleDisplay || !responsibleIdInput || !modal || !closeBtn) {
            return;
        }

        const availableOptions = Array.from(clientSelect.options).filter((option) => option.value !== '');

        const normalizeText = (value) => String(value || '')
            .normalize('NFD')
            .replace(/[\u0300-\u036f]/g, '')
            .toLowerCase();

        const showModal = () => {
            modal.classList.remove('hidden');
            modal.classList.add('flex');
        };

        const hideModal = () => {
            modal.classList.add('hidden');
            modal.classList.remove('flex');
        };

        const syncResponsible = () => {
            const option = clientSelect.selectedOptions?.[0];
            if (!option || !option.value) {
                responsibleDisplay.textContent = @json(__('Sin responsable asignado'));
                responsibleIdInput.value = '';
                return false;
            }

            const responsibleId = option.dataset.responsibleId || '';
            const responsibleName = option.dataset.responsibleName || '';

            responsibleIdInput.value = responsibleId;
            responsibleDisplay.textContent = responsibleName || @json(__('Sin responsable asignado'));

            return responsibleId !== '';
        };

        const closeClientOptions = () => {
            clientOptionsPanel.classList.add('hidden');
            clientSearch.setAttribute('aria-expanded', 'false');
        };

        const openClientOptions = () => {
            clientOptionsPanel.classList.remove('hidden');
            clientSearch.setAttribute('aria-expanded', 'true');
        };

        const syncSearchFromSelection = () => {
            const option = clientSelect.selectedOptions?.[0];
            clientSearch.value = option?.value ? option.textContent.trim() : '';
        };

        const renderClientOptions = (term = '') => {
            const normalizedTerm = normalizeText(term);
            const filtered = normalizedTerm === ''
                ? availableOptions
                : availableOptions.filter((option) => normalizeText(option.textContent).includes(normalizedTerm));

            if (filtered.length === 0) {
                clientOptionsPanel.innerHTML = `<div class="px-3 py-2 text-sm text-slate-500">{{ __('No se encontraron clientes.') }}</div>`;
                openClientOptions();
                return;
            }

            clientOptionsPanel.innerHTML = filtered.map((option) => {
                const isSelected = clientSelect.value === option.value;
                const selectedMarkup = isSelected
                    ? '<span class="text-xs font-semibold uppercase tracking-wide">Actual</span>'
                    : '';

                return `
                    <button
                        type="button"
                        class="flex w-full items-center justify-between px-3 py-2 text-left text-sm transition hover:bg-slate-50 ${isSelected ? 'bg-indigo-50 text-indigo-700' : 'text-slate-700'}"
                        data-client-value="${option.value}"
                    >
                        <span>${option.textContent.trim()}</span>
                        ${selectedMarkup}
                    </button>
                `;
            }).join('');

            openClientOptions();
        };

        const applySelectedClient = (value) => {
            clientSelect.value = value;
            syncSearchFromSelection();

            if (!syncResponsible()) {
                showModal();
            }

            closeClientOptions();
        };

        clientSearch.addEventListener('focus', () => {
            renderClientOptions(clientSearch.value);
        });

        clientSearch.addEventListener('input', () => {
            const exactMatch = availableOptions.find((option) => option.textContent.trim() === clientSearch.value.trim());

            if (exactMatch) {
                clientSelect.value = exactMatch.value;
                syncResponsible();
            } else {
                clientSelect.value = '';
                syncResponsible();
            }

            renderClientOptions(clientSearch.value);
        });

        clientToggle.addEventListener('click', () => {
            if (clientOptionsPanel.classList.contains('hidden')) {
                renderClientOptions(clientSearch.value);
                clientSearch.focus();
                return;
            }

            closeClientOptions();
        });

        clientOptionsPanel.addEventListener('click', (event) => {
            const optionButton = event.target.closest('[data-client-value]');
            if (!optionButton) {
                return;
            }

            applySelectedClient(optionButton.dataset.clientValue);
        });

        form.addEventListener('submit', (event) => {
            if (!syncResponsible()) {
                event.preventDefault();
                showModal();
            }
        });

        closeBtn.addEventListener('click', hideModal);
        modal.addEventListener('click', (event) => {
            if (event.target === modal) {
                hideModal();
            }
        });

        document.addEventListener('click', (event) => {
            if (!event.target.closest('#destination-client-search') &&
                !event.target.closest('#destination-client-toggle') &&
                !event.target.closest('#destination-client-options')) {
                closeClientOptions();
                syncSearchFromSelection();
            }
        });

        syncSearchFromSelection();
        syncResponsible();

        @if ($errors->has('client_id') && str_contains((string) $errors->first('client_id'), 'Primero debe realizar la asignación del responsable.'))
            showModal();
        @endif
    })();
</script>
