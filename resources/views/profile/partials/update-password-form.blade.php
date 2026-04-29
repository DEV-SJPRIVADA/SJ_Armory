<section>
    <header>
        <h2 class="text-lg font-medium text-gray-900">
            {{ __('Actualizar contraseña') }}
        </h2>

        <p class="mt-1 text-sm text-gray-600">
            {{ __('Asegúrate de usar una contraseña larga y segura.') }}
        </p>
    </header>

    <form method="post" action="{{ route('password.update') }}" class="mt-6 space-y-6">
        @csrf
        @method('put')

        <x-password-reveal-input
            label="{{ __('Contraseña actual') }}"
            name="current_password"
            id="update_password_current_password"
            autocomplete="current-password"
            error-bag="updatePassword"
        />

        <x-password-reveal-input
            label="{{ __('Nueva contraseña') }}"
            name="password"
            id="update_password_password"
            autocomplete="new-password"
            error-bag="updatePassword"
        />

        <x-password-reveal-input
            label="{{ __('Confirmar contraseña') }}"
            name="password_confirmation"
            id="update_password_password_confirmation"
            autocomplete="new-password"
            error-bag="updatePassword"
            error-key="password_confirmation"
        />

        <div class="flex items-center gap-4">
            <x-primary-button>{{ __('Guardar') }}</x-primary-button>

            @if (session('status') === 'password-updated')
                <p
                    x-data="{ show: true }"
                    x-show="show"
                    x-transition
                    x-init="setTimeout(() => show = false, 2000)"
                    class="text-sm text-gray-600"
                >{{ __('Guardado.') }}</p>
            @endif
        </div>
    </form>
</section>
