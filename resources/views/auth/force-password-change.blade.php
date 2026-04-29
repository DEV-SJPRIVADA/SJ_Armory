<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Cambio de contraseña obligatorio') }}
        </h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <p class="text-sm text-gray-600 mb-6">
                        {{ __('Por seguridad debe definir su propia contraseña. Esta será la única vez que el sistema le solicitará este cambio.') }}
                    </p>

                    <form method="POST" action="{{ route('password.force.update') }}" class="space-y-6">
                        @csrf
                        @method('PUT')

                        <x-password-reveal-input
                            label="{{ __('Nueva contraseña') }}"
                            name="password"
                            id="force_password"
                            autocomplete="new-password"
                            required
                        />

                        <x-password-reveal-input
                            label="{{ __('Confirmar contraseña') }}"
                            name="password_confirmation"
                            id="force_password_confirmation"
                            autocomplete="new-password"
                            required
                        />

                        <div class="flex justify-end">
                            <x-primary-button>
                                {{ __('Establecer contraseña') }}
                            </x-primary-button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
