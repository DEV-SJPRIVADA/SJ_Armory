<x-app-layout>
    <x-slot name="header">
        <div class="sj-section-header">
            <div class="sj-section-header__main">
                <h2 class="sj-section-header__title">{{ __('Usuarios') }}</h2>
            </div>

            <div class="sj-section-header__actions">
                <a href="{{ route('users.create') }}" class="text-sm font-medium text-indigo-600 hover:text-indigo-900">
                    {{ __('Nuevo usuario') }}
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-8" x-data="{
        showClientsModal: false,
        modalUserName: '',
        modalClients: [],
        showSendCredentialsModal: false,
        sendCredUserId: null,
        sendCredName: '',
        sendCredEmail: '',
        appBaseUrl: @json(rtrim(url('/'), '/')),
        sendCredUrlTemplate: @json(route('users.send-access-credentials', ['user' => '__ID__'])),
        openSendCred(id, name, email) {
            this.sendCredUserId = id;
            this.sendCredName = name;
            this.sendCredEmail = email;
            this.showSendCredentialsModal = true;
        },
        closeSendCred() {
            this.showSendCredentialsModal = false;
            this.sendCredUserId = null;
            this.sendCredName = '';
            this.sendCredEmail = '';
        },
        sendCredAction() {
            return this.sendCredUserId
                ? this.sendCredUrlTemplate.replace('__ID__', String(this.sendCredUserId))
                : '#';
        }
    }">
        <div class="sj-page-shell sj-page-shell--wide space-y-6">
            @if ($errors->has('email'))
                <div class="rounded border border-red-200 bg-red-50 p-3 text-sm text-red-800">
                    {{ $errors->first('email') }}
                </div>
            @endif
            @if (session('generated_temporary_password'))
                <div
                    class="rounded border border-amber-200 bg-amber-50 p-4 text-sm text-amber-950 space-y-3"
                    x-data="{ copied: false }"
                >
                    @if (session('status'))
                        <p class="font-medium">{{ session('status') }}</p>
                    @endif
                    <p>{{ __('Contraseña temporal (cópiela ahora; no se volverá a mostrar)') }}</p>
                    <div class="flex flex-wrap items-center gap-2">
                        <code id="sj-temp-user-password" class="flex-1 min-w-[12rem] rounded bg-white px-3 py-2 text-xs tracking-wide border border-amber-100 break-all">
                            {{ session('generated_temporary_password') }}
                        </code>
                        <button
                            type="button"
                            class="rounded-md bg-amber-700 px-3 py-2 text-xs font-semibold text-white hover:bg-amber-800"
                            @click="
                                const el = document.getElementById('sj-temp-user-password');
                                if (el) { navigator.clipboard.writeText(el.textContent.trim()); copied = true; setTimeout(() => copied = false, 2000); }
                            "
                        >
                            <span x-text="copied ? @js(__('Copiado')) : @js(__('Copiar'))"></span>
                        </button>
                    </div>
                </div>
            @elseif (session('status'))
                <div class="rounded bg-green-50 p-3 text-sm text-green-700">
                    {{ session('status') }}
                </div>
            @endif

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 text-sm">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-3 py-2 text-left font-medium text-gray-600">{{ __('Nombre') }}</th>
                                    <th class="px-3 py-2 text-left font-medium text-gray-600">{{ __('Correo electrónico') }}</th>
                                    <th class="px-3 py-2 text-left font-medium text-gray-600">{{ __('Responsable') }}</th>
                                    <th class="px-3 py-2 text-left font-medium text-gray-600">{{ __('Cargo') }}</th>
                                    <th class="px-3 py-2 text-left font-medium text-gray-600">{{ __('Nivel de responsabilidad') }}</th>
                                    <th class="px-3 py-2 text-left font-medium text-gray-600">{{ __('Clientes asignados') }}</th>
                                    <th class="px-3 py-2 text-left font-medium text-gray-600">{{ __('Estado activo') }}</th>
                                    <th class="px-3 py-2 text-right font-medium text-gray-600">{{ __('Acciones') }}</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200">
                                @forelse ($users as $user)
                                    <tr>
                                        <td class="px-3 py-2">{{ $user->name }}</td>
                                        <td class="px-3 py-2">{{ $user->email }}</td>
                                        <td class="px-3 py-2">{{ $roles[$user->role] ?? $user->role }}</td>
                                        <td class="px-3 py-2">{{ $user->position?->name ?? '-' }}</td>
                                        <td class="px-3 py-2">
                                            {{ $user->responsibilityLevel?->level ? $user->responsibilityLevel->level . ' - ' . $user->responsibilityLevel->name : '-' }}
                                        </td>
                                        <td class="px-3 py-2">
                                            <button
                                                type="button"
                                                class="text-xs font-medium text-indigo-600 hover:text-indigo-900"
                                                @click='
                                                    modalUserName = @js($user->name);
                                                    modalClients = @js($user->clients->pluck('name')->values());
                                                    showClientsModal = true;
                                                '
                                            >
                                                {{ __('Ver clientes') }}
                                            </button>
                                        </td>
                                        <td class="px-3 py-2">
                                            <span class="{{ $user->is_active ? 'text-green-700' : 'text-gray-500' }}">
                                                {{ $user->is_active ? __('Activo') : __('Inactivo') }}
                                            </span>
                                        </td>
                                        <td class="px-3 py-2 text-right space-x-2">
                                            <a href="{{ route('users.edit', $user) }}" class="text-indigo-600 hover:text-indigo-900">
                                                {{ __('Editar') }}
                                            </a>
                                            @if ($user->is_active)
                                                <button
                                                    type="button"
                                                    class="text-xs font-medium text-green-700 hover:text-green-900"
                                                    @click="openSendCred({{ $user->id }}, @js($user->name), @js($user->email))"
                                                >
                                                    {{ __('Enviar') }}
                                                </button>
                                            @endif
                                            <form method="POST" action="{{ route('users.status', $user) }}" class="inline">
                                                @csrf
                                                @method('PATCH')
                                                <input type="hidden" name="is_active" value="{{ $user->is_active ? 0 : 1 }}">
                                                <button class="text-xs text-amber-600 hover:text-amber-900">
                                                    {{ $user->is_active ? __('Desactivar') : __('Activar') }}
                                                </button>
                                            </form>
                                            <form method="POST" action="{{ route('users.destroy', $user) }}" class="inline">
                                                @csrf
                                                @method('DELETE')
                                                <button class="text-xs text-red-600 hover:text-red-900" onclick="return confirm(@js(__('¿Eliminar usuario?')))">
                                                    {{ __('Eliminar') }}
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="8" class="px-3 py-6 text-center text-gray-500">
                                            {{ __('Sin usuarios registrados.') }}
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <div class="mt-4">
                        {{ $users->links() }}
                    </div>
                </div>
            </div>
        </div>

        <div
            x-show="showSendCredentialsModal"
            x-cloak
            x-transition.opacity
            class="fixed inset-0 z-[1400] flex items-center justify-center bg-black/50 p-4"
            @click.self="closeSendCred()"
        >
            <div class="w-full max-w-lg rounded-lg bg-white shadow-xl">
                <div class="border-b border-gray-200 px-5 py-4">
                    <h3 class="text-base font-semibold text-gray-900">{{ __('Confirmar envío de credenciales') }}</h3>
                    <p class="mt-1 text-sm text-gray-600">
                        {{ __('Correo destino') }}: <span class="font-medium text-gray-900" x-text="sendCredEmail"></span>
                    </p>
                </div>
                <div class="max-h-[70vh] overflow-y-auto px-5 py-4 space-y-3 text-sm text-gray-700">
                    <p>{{ __('Se enviará un correo a este usuario con la siguiente información:') }}</p>
                    <ul class="list-disc space-y-2 pl-5">
                        <li>
                            {{ __('Enlace de acceso a la aplicación:') }}
                            <code class="rounded bg-gray-100 px-1.5 py-0.5 text-xs" x-text="appBaseUrl"></code>
                        </li>
                        <li>
                            {{ __('Usuario (correo electrónico):') }}
                            <span class="font-medium" x-text="sendCredEmail"></span>
                        </li>
                        <li>{{ __('Una contraseña temporal nueva; si el usuario ya tenía una, dejará de ser válida.') }}</li>
                        <li>{{ __('Instrucciones indicando que al iniciar sesión por primera vez deberá cambiar la contraseña de forma obligatoria.') }}</li>
                    </ul>
                    <p class="text-amber-800 bg-amber-50 border border-amber-100 rounded-md px-3 py-2 text-xs">
                        {{ __('¿Desea continuar?') }}
                    </p>
                </div>
                <div class="flex items-center justify-end gap-2 border-t border-gray-200 px-5 py-4">
                    <button
                        type="button"
                        class="rounded-md border border-gray-300 bg-white px-3 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50"
                        @click="closeSendCred()"
                    >
                        {{ __('Cancelar') }}
                    </button>
                    <form
                        method="POST"
                        class="inline"
                        x-bind:action="sendCredAction()"
                    >
                        @csrf
                        <x-primary-button type="submit" class="text-sm" x-bind:disabled="!sendCredUserId">
                            {{ __('Enviar correo') }}
                        </x-primary-button>
                    </form>
                </div>
            </div>
        </div>

        <div
            x-show="showClientsModal"
            x-cloak
            x-transition.opacity
            class="fixed inset-0 z-[1300] flex items-center justify-center bg-black/50 p-4"
            @click.self="showClientsModal = false"
        >
            <div class="w-full max-w-lg rounded-lg bg-white shadow-xl">
                <div class="flex items-center justify-between border-b border-gray-200 px-5 py-4">
                    <h3 class="text-base font-semibold text-gray-900">
                        {{ __('Clientes asignados') }}:
                        <span class="font-medium" x-text="modalUserName"></span>
                    </h3>
                    <button
                        type="button"
                        class="text-lg leading-none text-gray-500 hover:text-gray-700"
                        @click="showClientsModal = false"
                        aria-label="{{ __('Cerrar') }}"
                    >
                        X
                    </button>
                </div>
                <div class="max-h-80 overflow-y-auto px-5 py-4">
                    <ul class="space-y-2 text-sm text-gray-700" x-show="modalClients.length > 0">
                        <template x-for="(clientName, idx) in modalClients" :key="idx">
                            <li class="rounded border border-gray-200 px-3 py-2" x-text="clientName"></li>
                        </template>
                    </ul>
                    <p class="text-sm text-gray-500" x-show="modalClients.length === 0">
                        {{ __('Sin clientes asignados.') }}
                    </p>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
