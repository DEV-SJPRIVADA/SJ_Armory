<x-app-layout>
    <x-slot name="header">
        <div class="sj-section-header">
            <div class="sj-section-header__main">
                <h2 class="sj-section-header__title">{{ __('Transferencias') }}</h2>
            </div>

            <div class="sj-section-header__actions">
                <a href="{{ route('dashboard') }}" class="text-sm text-gray-600 hover:text-gray-900">
                    {{ __('Volver') }}
                </a>
                @if ($canManageTransfers)
                    <button type="button" class="text-sm font-medium text-indigo-600 hover:text-indigo-900"
                        x-data
                        x-on:click.prevent="$dispatch('open-modal', 'bulk-transfer')">
                        {{ __('Enviar') }}
                    </button>
                @endif
            </div>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="sj-page-shell sj-page-shell--wide space-y-6">
            @if (session('status'))
                <div class="rounded bg-green-50 p-3 text-sm text-green-700">
                    {{ session('status') }}
                </div>
            @endif

            @if (session('transfer_flash_error'))
                <div class="rounded-md border border-amber-200 bg-amber-50 p-4 text-sm text-amber-900" role="alert">
                    {{ session('transfer_flash_error') }}
                </div>
            @endif

            @if (session('reopen_accept_transfer') && $errors->any())
                <div id="sj-accept-transfer-alert"
                    class="rounded-md border border-red-200 bg-red-50 p-4 text-sm text-red-900"
                    role="alert">
                    <p class="font-medium text-red-950">{{ __('No se pudo completar la aceptación') }}</p>
                    <ul class="mt-2 list-disc space-y-1 ps-5">
                        @foreach ($errors->all() as $message)
                            <li>{{ $message }}</li>
                        @endforeach
                    </ul>
                    <div class="mt-4 flex flex-wrap gap-2">
                        @if ($canManageTransfers)
                            <button type="button" id="sj-reopen-accept-modal"
                                class="rounded bg-emerald-600 px-3 py-1.5 text-sm font-medium text-white hover:bg-emerald-700">
                                {{ __('Volver a seleccionar') }}
                            </button>
                        @endif
                        <button type="button" id="sj-dismiss-accept-alert"
                            class="rounded border border-red-300 bg-white px-3 py-1.5 text-sm font-medium text-red-800 hover:bg-red-50">
                            {{ __('Cerrar aviso') }}
                        </button>
                    </div>
                </div>
            @endif

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <form method="GET" action="{{ route('transfers.index') }}" class="grid grid-cols-1 gap-3 md:grid-cols-3">
                        <div>
                            <label class="text-sm text-gray-600">{{ __('Buscar') }}</label>
                            <input type="text" name="q" value="{{ $search }}" class="mt-1 block w-full rounded-md border-gray-300 text-sm" placeholder="{{ __('Código o cliente') }}">
                        </div>
                        <div>
                            <label class="text-sm text-gray-600">{{ __('Estado') }}</label>
                            <select name="status" class="mt-1 block w-full rounded-md border-gray-300 text-sm">
                                <option value="pending" @selected($status === 'pending')>{{ __('Pendiente') }}</option>
                                <option value="accepted" @selected($status === 'accepted')>{{ __('Aceptada') }}</option>
                                <option value="rejected" @selected($status === 'rejected')>{{ __('Rechazada') }}</option>
                                <option value="cancelled" @selected($status === 'cancelled')>{{ __('Cancelada') }}</option>
                            </select>
                        </div>
                        <div class="flex items-end gap-2">
                            <button type="submit" class="text-sm text-white bg-indigo-600 hover:bg-indigo-700 px-3 py-2 rounded">
                                {{ __('Filtrar') }}
                            </button>
                            <a href="{{ route('transfers.index') }}" class="text-sm text-gray-600 hover:text-gray-900">
                                {{ __('Limpiar') }}
                            </a>
                        </div>
                    </form>
                </div>
            </div>

            @php
                $statusLabels = [
                    'pending' => __('Pendientes'),
                    'accepted' => __('Aceptadas'),
                    'rejected' => __('Rechazadas'),
                    'cancelled' => __('Canceladas'),
                ];
                $statusLabel = $statusLabels[$status] ?? __('Pendientes');
            @endphp

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <h3 class="text-lg font-semibold">
                        {{ $status === 'pending' ? __('Pendientes por aceptar') : __('Recibidas') . ' - ' . $statusLabel }}
                    </h3>
                    <div class="overflow-x-auto">
                    <table class="mt-3 min-w-full divide-y divide-gray-200 text-sm">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-3 py-2 text-left font-medium text-gray-600">{{ __('Arma') }}</th>
                                <th class="px-3 py-2 text-left font-medium text-gray-600">{{ __('Cliente origen') }}</th>
                                <th class="px-3 py-2 text-left font-medium text-gray-600">{{ __('Remitente') }}</th>
                                <th class="px-3 py-2 text-left font-medium text-gray-600">{{ __('Cliente destino') }}</th>
                                <th class="px-3 py-2 text-right font-medium text-gray-600">{{ __('Acciones') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            @forelse ($incoming as $transfer)
                                <tr>
                                    <td class="px-3 py-2">
                                        <a href="{{ route('weapons.show', $transfer->weapon) }}" class="text-indigo-600 hover:text-indigo-900">
                                            {{ $transfer->weapon?->internal_code ?? $transfer->weapon_id }}
                                        </a>
                                    </td>
                                    <td class="px-3 py-2">{{ $transfer->fromClient?->name ?? __('Sin destino') }}</td>
                                    <td class="px-3 py-2">{{ $transfer->fromUser?->name }}</td>
                                    <td class="px-3 py-2">
                                        @if ($transfer->newClient)
                                            {{ $transfer->newClient->name }}
                                        @elseif ($transfer->status === \App\Models\WeaponTransfer::STATUS_PENDING)
                                            <span class="text-gray-500">{{ __('Pendiente de asignar') }}</span>
                                        @else
                                            —
                                        @endif
                                    </td>
                                    <td class="px-3 py-2 text-right space-x-2">
                                        @if ($status === 'pending' && $canManageTransfers)
                                            <button type="button"
                                                class="text-emerald-600 hover:text-emerald-900"
                                                data-transfer-id="{{ $transfer->id }}"
                                                data-transfer-action="{{ route('transfers.accept', $transfer) }}"
                                                data-transfer-code="{{ $transfer->weapon?->internal_code ?? $transfer->weapon_id }}"
                                                data-allowed-client-ids="{{ $transfer->toUser?->clients->pluck('id')->join(',') }}"
                                                x-data
                                                x-on:click.prevent="$dispatch('open-modal', 'accept-transfer')">
                                                {{ __('Aceptar') }}
                                            </button>
                                            <form action="{{ route('transfers.reject', $transfer) }}" method="POST" class="inline">
                                                @csrf
                                                @method('PATCH')
                                                <button type="submit" class="text-red-600 hover:text-red-900">
                                                    {{ __('Rechazar') }}
                                                </button>
                                            </form>
                                        @else
                                            <span class="text-gray-500">{{ __('Sin acciones') }}</span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="px-3 py-6 text-center text-gray-500">
                                        {{ __('No hay transferencias para este filtro.') }}
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                    </div>
                </div>
            </div>

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <h3 class="text-lg font-semibold">{{ __('Transferencias enviadas') }} - {{ $statusLabel }}</h3>
                    <div class="overflow-x-auto">
                    <table class="mt-3 min-w-full divide-y divide-gray-200 text-sm">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-3 py-2 text-left font-medium text-gray-600">{{ __('Arma') }}</th>
                                <th class="px-3 py-2 text-left font-medium text-gray-600">{{ __('Cliente origen') }}</th>
                                <th class="px-3 py-2 text-left font-medium text-gray-600">{{ __('Destinatario') }}</th>
                                <th class="px-3 py-2 text-left font-medium text-gray-600">{{ __('Cliente destino') }}</th>
                                <th class="px-3 py-2 text-left font-medium text-gray-600">{{ __('Fecha') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            @forelse ($outgoing as $transfer)
                                <tr>
                                    <td class="px-3 py-2">
                                        <a href="{{ route('weapons.show', $transfer->weapon) }}" class="text-indigo-600 hover:text-indigo-900">
                                            {{ $transfer->weapon?->internal_code ?? $transfer->weapon_id }}
                                        </a>
                                    </td>
                                    <td class="px-3 py-2">{{ $transfer->fromClient?->name ?? __('Sin destino') }}</td>
                                    <td class="px-3 py-2">{{ $transfer->toUser?->name }}</td>
                                    <td class="px-3 py-2">
                                        @if ($transfer->newClient)
                                            {{ $transfer->newClient->name }}
                                        @elseif ($transfer->status === \App\Models\WeaponTransfer::STATUS_PENDING)
                                            <span class="text-gray-500">{{ __('Pendiente de asignar') }}</span>
                                        @else
                                            —
                                        @endif
                                    </td>
                                    <td class="px-3 py-2">{{ $transfer->requested_at?->format('Y-m-d H:i') }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="px-3 py-6 text-center text-gray-500">
                                        {{ __('No hay transferencias para este filtro.') }}
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @if ($canManageTransfers)
    <x-modal name="bulk-transfer" maxWidth="2xl">
        <div class="p-6 text-gray-900">
            <div class="flex items-center justify-between">
                <h3 class="text-lg font-semibold">{{ __('Transferir armas') }}</h3>
                <button type="button" class="text-sm text-gray-500 hover:text-gray-700"
                    x-on:click="$dispatch('close-modal', 'bulk-transfer')">
                    {{ __('Cerrar') }}
                </button>
            </div>

            <form method="POST" action="{{ route('transfers.bulk') }}" class="mt-4 space-y-5">
                @csrf

                <div class="flex flex-wrap items-center justify-between gap-3">
                    <div class="text-sm text-gray-700">
                        {{ __('Seleccione las armas que desea transferir.') }}
                    </div>
                    <div class="flex items-center gap-3">
                        <div class="text-xs text-gray-500">
                            {{ __('Seleccionadas:') }} <span id="selected-count">0</span>
                        </div>
                        <label class="flex items-center gap-2 text-sm text-gray-600">
                            <input id="select-all" type="checkbox" class="rounded border-gray-300">
                            {{ __('Seleccionar todas') }}
                        </label>
                    </div>
                </div>

                <div>
                    <input id="weapons-filter" type="search" class="w-full rounded-md border-gray-300 text-sm"
                        placeholder="{{ __('Filtrar armas...') }}">
                </div>

                <div class="max-h-72 overflow-auto rounded border border-gray-200">
                    <table class="min-w-full divide-y divide-gray-200 text-sm">
                        <thead class="bg-gray-50 sticky top-0 z-10">
                            <tr>
                                <th class="px-3 py-2 text-left font-medium text-gray-600">{{ __('Seleccionar') }}</th>
                                <th class="px-3 py-2 text-left font-medium text-gray-600">{{ __('Código interno') }}</th>
                                <th class="px-3 py-2 text-left font-medium text-gray-600">{{ __('Cliente') }}</th>
                                <th class="px-3 py-2 text-left font-medium text-gray-600">{{ __('Responsable') }}</th>
                                <th class="px-3 py-2 text-left font-medium text-gray-600">{{ __('Serie') }}</th>
                                <th class="px-3 py-2 text-left font-medium text-gray-600">{{ __('Tipo') }}</th>
                            </tr>
                        </thead>
                        <tbody id="weapons-list" class="divide-y divide-gray-200">
                            @forelse ($weapons as $weapon)
                                <tr data-search="{{ strtolower(($weapon->internal_code ?? '') . ' ' . ($weapon->serial_number ?? '') . ' ' . ($weapon->weapon_type ?? '') . ' ' . ($weapon->activeClientAssignment?->client?->name ?? '') . ' ' . ($weapon->activeClientAssignment?->responsible?->name ?? '')) }}"
                                    data-responsible-id="{{ $weapon->activeClientAssignment?->responsible_user_id }}">
                                    <td class="px-3 py-2">
                                        <input type="checkbox" name="weapon_ids[]" value="{{ $weapon->id }}" @checked(in_array((string) $weapon->id, old('weapon_ids', []), true)) class="weapon-checkbox rounded border-gray-300">
                                    </td>
                                    <td class="px-3 py-2">{{ $weapon->internal_code }}</td>
                                    <td class="px-3 py-2">{{ $weapon->activeClientAssignment?->client?->name ?? __('Sin destino') }}</td>
                                    <td class="px-3 py-2">
                                        {{ $weapon->activeClientAssignment?->responsible?->name ?? '-' }}
                                        <span class="ml-1 text-xs text-red-500 hidden" data-disabled-hint>
                                            {{ __('Ya pertenece al destinatario') }}
                                        </span>
                                    </td>
                                    <td class="px-3 py-2">{{ $weapon->serial_number }}</td>
                                    <td class="px-3 py-2">{{ $weapon->weapon_type }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="px-3 py-6 text-center text-gray-500">
                                        {{ __('No hay armas disponibles para transferir.') }}
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <x-input-error :messages="$errors->get('weapon_ids')" class="mt-2" />

                <div class="space-y-4">
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div class="sm:col-span-1">
                            <label class="block text-sm font-medium text-gray-700">{{ __('Destinatario') }}</label>
                            <select name="to_user_id" id="bulk-to-user" class="mt-1 block w-full rounded-md border-gray-300 text-sm" required>
                                <option value="">{{ __('Seleccione') }}</option>
                                @foreach ($transferRecipients as $recipient)
                                    <option value="{{ $recipient->id }}"
                                        @selected(old('to_user_id') == $recipient->id)>
                                        {{ $recipient->name }}
                                    </option>
                                @endforeach
                            </select>
                            <p class="mt-1 text-xs text-gray-500">
                                {{ __('El cliente y el puesto (o trabajador) de destino los asigna quien acepta la transferencia.') }}
                            </p>
                            <x-input-error :messages="$errors->get('to_user_id')" class="mt-2" />
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700">{{ __('Fecha y hora') }}</label>
                            <div class="mt-1 rounded-md border border-gray-300 bg-gray-50 px-3 py-2 text-sm text-gray-700">
                                {{ now()->format('Y-m-d H:i') }}
                            </div>
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700">{{ __('Observaciones') }}</label>
                        <textarea name="note" rows="2" spellcheck="true" class="mt-1 block w-full rounded-md border-gray-300 text-sm">{{ old('note') }}</textarea>
                        <x-input-error :messages="$errors->get('note')" class="mt-2" />
                    </div>
                </div>

                <div class="flex justify-end gap-3">
                    <button type="button" class="text-sm text-gray-600 hover:text-gray-900"
                        x-on:click="$dispatch('close-modal', 'bulk-transfer')">
                        {{ __('Cancelar') }}
                    </button>
                    <button type="submit" class="text-sm text-white bg-indigo-600 hover:bg-indigo-700 px-4 py-2 rounded">
                        {{ __('Transferir') }}
                    </button>
                </div>
            </form>
        </div>
    </x-modal>

    <x-modal name="accept-transfer" maxWidth="2xl">
        <div class="p-6 text-gray-900">
            <div class="flex items-center justify-between">
                <h3 class="text-lg font-semibold">{{ __('Asignar destino operativo') }}</h3>
                <button type="button" class="text-sm text-gray-500 hover:text-gray-700"
                    x-on:click="$dispatch('close-modal', 'accept-transfer')">
                    {{ __('Cerrar') }}
                </button>
            </div>
            <p id="accept-transfer-code" class="mt-1 text-sm text-gray-600"></p>

            <form method="POST" id="accept-transfer-form" class="mt-4 space-y-5">
                @csrf
                @method('PATCH')

                <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
                    <div>
                        <label class="text-sm text-gray-600">{{ __('Cliente') }}</label>
                        <select name="client_id" id="accept-client" class="mt-1 block w-full rounded-md border-gray-300 text-sm" required>
                            <option value="">{{ __('Seleccione') }}</option>
                            @foreach ($acceptClients as $client)
                                <option value="{{ $client->id }}">{{ $client->name }}</option>
                            @endforeach
                        </select>
                        <x-input-error :messages="$errors->get('client_id')" class="mt-2" />
                    </div>

                    <div>
                        <label class="text-sm text-gray-600">{{ __('Puesto (opcional)') }}</label>
                        <select name="post_id" id="accept-post" class="mt-1 block w-full rounded-md border-gray-300 text-sm">
                            <option value="">{{ __('Seleccione') }}</option>
                            @foreach ($acceptPosts as $post)
                                <option value="{{ $post->id }}" data-client-id="{{ $post->client_id }}">{{ $post->name }}</option>
                            @endforeach
                        </select>
                        <x-input-error :messages="$errors->get('post_id')" class="mt-2" />
                    </div>

                    <div>
                        <label class="text-sm text-gray-600">{{ __('Trabajador (opcional)') }}</label>
                        <select name="worker_id" id="accept-worker" class="mt-1 block w-full rounded-md border-gray-300 text-sm">
                            <option value="">{{ __('Seleccione') }}</option>
                            @foreach ($acceptWorkers as $worker)
                                <option value="{{ $worker->id }}" data-client-id="{{ $worker->client_id }}">{{ $worker->name }}</option>
                            @endforeach
                        </select>
                        <x-input-error :messages="$errors->get('worker_id')" class="mt-2" />
                    </div>

                    <div class="md:col-span-3">
                        <p class="text-xs text-gray-500">
                            {{ __('Puede elegir solo puesto, solo trabajador, o ambos: el trabajador queda asignado al arma y, si hay puesto, la ubicación en mapa sigue las coordenadas del puesto.') }}
                        </p>
                    </div>
                </div>

                <div class="flex justify-end gap-3">
                    <button type="button" class="text-sm text-gray-600 hover:text-gray-900"
                        x-on:click="$dispatch('close-modal', 'accept-transfer')">
                        {{ __('Cancelar') }}
                    </button>
                    <button type="submit" class="text-sm text-white bg-emerald-600 hover:bg-emerald-700 px-4 py-2 rounded">
                        {{ __('Aceptar transferencia') }}
                    </button>
                </div>
            </form>
        </div>
    </x-modal>
    @endif

@if ($canManageTransfers)
<script>
    (() => {
        const reopenAcceptTransfer = @json(session('reopen_accept_transfer'));
        const oldAcceptTransfer = {
            client_id: @json(old('client_id')),
            post_id: @json(old('post_id')),
            worker_id: @json(old('worker_id')),
        };

        @if ($errors->has('weapon_ids') || $errors->has('to_user_id') || $errors->has('note'))
            window.dispatchEvent(new CustomEvent('open-modal', { detail: 'bulk-transfer' }));
        @endif

        const selectAll = document.getElementById('select-all');
        const list = document.getElementById('weapons-list');
        const count = document.getElementById('selected-count');
        const filter = document.getElementById('weapons-filter');
        const recipientSelect = document.getElementById('bulk-to-user');

        if (list && count) {
        const updateCount = () => {
            const selected = list.querySelectorAll('.weapon-checkbox:checked').length;
            count.textContent = selected;
        };

        if (selectAll) {
            selectAll.addEventListener('change', () => {
                list.querySelectorAll('.weapon-checkbox').forEach((checkbox) => {
                    if (checkbox.disabled) {
                        return;
                    }
                    checkbox.checked = selectAll.checked;
                });
                validateSelectedAgainstRecipient();
            });
        }

        if (filter) {
            filter.addEventListener('input', () => {
                const term = filter.value.trim().toLowerCase();
                list.querySelectorAll('tr').forEach((row) => {
                    const haystack = row.dataset.search || '';
                    row.style.display = haystack.includes(term) ? '' : 'none';
                });
            });
        }

        const validateSelectedAgainstRecipient = () => {
            if (!recipientSelect) return;
            const recipientId = recipientSelect.value;
            list.querySelectorAll('tr').forEach((row) => {
                const responsibleId = row.dataset.responsibleId || '';
                const checkbox = row.querySelector('.weapon-checkbox');
                const hint = row.querySelector('[data-disabled-hint]');
                const isSelected = checkbox && checkbox.checked;
                const conflict = recipientId && responsibleId === recipientId && isSelected;

                if (conflict && checkbox) {
                    checkbox.checked = false;
                }

                if (hint) {
                    hint.classList.toggle('hidden', !conflict);
                }
            });
            updateCount();
        };

        if (recipientSelect) {
            recipientSelect.addEventListener('change', validateSelectedAgainstRecipient);
        }

        list.addEventListener('change', (event) => {
            if (event.target.classList.contains('weapon-checkbox')) {
                validateSelectedAgainstRecipient();
            }
        });

        updateCount();
        }

        const acceptForm = document.getElementById('accept-transfer-form');
        const acceptCode = document.getElementById('accept-transfer-code');
        const acceptClient = document.getElementById('accept-client');
        const acceptPost = document.getElementById('accept-post');
        const acceptWorker = document.getElementById('accept-worker');

        const filterAcceptClientOptionsForRecipient = (allowedCsv) => {
            if (!acceptClient) {
                return;
            }
            const allowed = allowedCsv
                ? String(allowedCsv).split(',').map((id) => id.trim()).filter(Boolean)
                : [];
            acceptClient.querySelectorAll('option').forEach((option) => {
                if (!option.value) {
                    option.hidden = false;
                    return;
                }
                if (allowed.length === 0) {
                    option.hidden = true;
                    return;
                }
                option.hidden = !allowed.includes(option.value);
            });
        };

        document.querySelectorAll('[data-transfer-action]').forEach((button) => {
            button.addEventListener('click', () => {
                if (acceptForm) {
                    acceptForm.action = button.dataset.transferAction || '';
                }
                if (acceptCode) {
                    const code = button.dataset.transferCode || '';
                    acceptCode.textContent = code ? `Arma: ${code}` : '';
                }
                filterAcceptClientOptionsForRecipient(button.dataset.allowedClientIds || '');
                if (acceptClient) {
                    acceptClient.value = '';
                }
                if (acceptPost) {
                    acceptPost.value = '';
                }
                if (acceptWorker) {
                    acceptWorker.value = '';
                }
                filterDependentOptions(acceptPost, '');
                filterDependentOptions(acceptWorker, '');
            });
        });

        const filterDependentOptions = (selectEl, clientId) => {
            if (!selectEl) {
                return;
            }
            const cid = clientId ? String(clientId) : '';
            selectEl.querySelectorAll('option').forEach((option) => {
                if (!option.value) {
                    option.hidden = false;

                    return;
                }
                if (!cid) {
                    option.hidden = true;

                    return;
                }
                const optionClient = option.dataset.clientId ? String(option.dataset.clientId) : '';
                option.hidden = optionClient !== cid;
            });
        };

        if (acceptClient) {
            acceptClient.addEventListener('change', () => {
                const clientId = acceptClient.value;
                if (!clientId) {
                    if (acceptPost) {
                        acceptPost.value = '';
                    }
                    if (acceptWorker) {
                        acceptWorker.value = '';
                    }
                }
                filterDependentOptions(acceptPost, clientId);
                filterDependentOptions(acceptWorker, clientId);
            });
        }

        const applyReopenAcceptTransfer = () => {
            if (!reopenAcceptTransfer || !acceptForm) {
                return;
            }
            acceptForm.action = reopenAcceptTransfer.action || '';
            if (acceptCode) {
                const code = reopenAcceptTransfer.weapon_code || '';
                acceptCode.textContent = code ? `{{ __('Arma') }}: ${code}` : '';
            }
            filterAcceptClientOptionsForRecipient(reopenAcceptTransfer.allowed_client_ids || '');
            if (acceptClient) {
                acceptClient.value = oldAcceptTransfer.client_id ? String(oldAcceptTransfer.client_id) : '';
            }
            if (acceptPost) {
                acceptPost.value = oldAcceptTransfer.post_id ? String(oldAcceptTransfer.post_id) : '';
            }
            if (acceptWorker) {
                acceptWorker.value = oldAcceptTransfer.worker_id ? String(oldAcceptTransfer.worker_id) : '';
            }
            const clientId = acceptClient && acceptClient.value ? acceptClient.value : '';
            filterDependentOptions(acceptPost, clientId);
            filterDependentOptions(acceptWorker, clientId);
            window.dispatchEvent(new CustomEvent('open-modal', { detail: 'accept-transfer' }));
        };

        const reopenBtn = document.getElementById('sj-reopen-accept-modal');
        if (reopenBtn) {
            reopenBtn.addEventListener('click', () => {
                applyReopenAcceptTransfer();
                document.getElementById('sj-accept-transfer-alert')?.remove();
            });
        }

        filterDependentOptions(acceptPost, acceptClient && acceptClient.value ? acceptClient.value : '');
        filterDependentOptions(acceptWorker, acceptClient && acceptClient.value ? acceptClient.value : '');
    })();
</script>
@endif

@if (session('reopen_accept_transfer') && $errors->any())
<script>
    (() => {
        document.getElementById('sj-dismiss-accept-alert')?.addEventListener('click', () => {
            document.getElementById('sj-accept-transfer-alert')?.remove();
        });
    })();
</script>
@endif
</x-app-layout>
