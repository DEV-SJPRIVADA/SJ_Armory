<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Editar usuario') }}
        </h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg"
                 x-data="{ generateTemp: {{ old('generate_temporary_password') ? 'true' : 'false' }} }">
                <div class="p-6 text-gray-900">
                    <form method="POST" action="{{ route('users.update', $user) }}" class="grid grid-cols-1 gap-4 md:grid-cols-2">
                        @csrf
                        @method('PUT')

                        <div>
                            <x-input-label for="name" :value="__('Nombre')" />
                            <x-text-input id="name" name="name" type="text" class="mt-1 block w-full" value="{{ old('name', $user->name) }}" required />
                            <x-input-error :messages="$errors->get('name')" class="mt-2" />
                        </div>

                        <div>
                            <x-input-label for="email" :value="__('Correo electrónico')" />
                            <x-text-input id="email" name="email" type="email" class="mt-1 block w-full" value="{{ old('email', $user->email) }}" required />
                            <x-input-error :messages="$errors->get('email')" class="mt-2" />
                        </div>

                        <div>
                            <x-input-label for="role" :value="__('Responsable')" />
                            <select id="role" name="role" class="mt-1 block w-full rounded-md border-gray-300" required>
                                @foreach ($roles as $value => $label)
                                    <option value="{{ $value }}" @selected(old('role', $user->role) === $value)>{{ $label }}</option>
                                @endforeach
                            </select>
                            <x-input-error :messages="$errors->get('role')" class="mt-2" />
                        </div>

                        <div>
                            <x-input-label for="position_id" :value="__('Cargo')" />
                            <select id="position_id" name="position_id" class="mt-1 block w-full rounded-md border-gray-300">
                                <option value="">{{ __('Seleccione') }}</option>
                                @foreach ($positions as $position)
                                    <option value="{{ $position->id }}" @selected(old('position_id', $user->position_id) == $position->id)>{{ $position->name }}</option>
                                @endforeach
                            </select>
                            <x-input-error :messages="$errors->get('position_id')" class="mt-2" />
                        </div>

                        <div>
                            <x-input-label for="responsibility_level_id" :value="__('Nivel de responsabilidad')" />
                            <select id="responsibility_level_id" name="responsibility_level_id" class="mt-1 block w-full rounded-md border-gray-300">
                                <option value="">{{ __('Seleccione') }}</option>
                                @foreach ($responsibilityLevels as $level)
                                    <option value="{{ $level->id }}" @selected(old('responsibility_level_id', $user->responsibility_level_id) == $level->id)>
                                        {{ $level->level }} - {{ $level->name }}
                                    </option>
                                @endforeach
                            </select>
                            <x-input-error :messages="$errors->get('responsibility_level_id')" class="mt-2" />
                        </div>

                        <div>
                            <x-input-label for="is_active" :value="__('Estado activo')" />
                            <select id="is_active" name="is_active" class="mt-1 block w-full rounded-md border-gray-300">
                                <option value="1" @selected(old('is_active', (string) $user->is_active) === '1')>{{ __('Activo') }}</option>
                                <option value="0" @selected(old('is_active', (string) $user->is_active) === '0')>{{ __('Inactivo') }}</option>
                            </select>
                            <x-input-error :messages="$errors->get('is_active')" class="mt-2" />
                        </div>

                        <div class="md:col-span-2 rounded-md border border-gray-200 bg-gray-50 px-4 py-3">
                            <label class="flex items-start gap-3 cursor-pointer">
                                <input
                                    type="checkbox"
                                    name="generate_temporary_password"
                                    value="1"
                                    class="mt-1 rounded border-gray-300"
                                    x-model="generateTemp"
                                />
                                <span class="text-sm text-gray-700">
                                    {{ __('Generar nueva contraseña temporal y exigir que el usuario la cambie al iniciar sesión') }}
                                </span>
                            </label>
                        </div>

                        <div class="md:col-span-2 text-xs text-gray-500" x-show="generateTemp" x-transition>
                            {{ __('Si marca esta opción, las contraseñas escritas abajo se ignoran. Recibirá la nueva clave para copiar en la siguiente pantalla.') }}
                        </div>

                        <div class="md:col-span-2 grid grid-cols-1 gap-4 md:grid-cols-2" x-bind:class="generateTemp ? 'opacity-50 pointer-events-none' : ''">
                            <x-password-reveal-input
                                label="{{ __('Contraseña (opcional)') }}"
                                name="password"
                                id="password"
                                autocomplete="new-password"
                            />

                            <x-password-reveal-input
                                label="{{ __('Confirmar contraseña') }}"
                                name="password_confirmation"
                                id="password_confirmation"
                                autocomplete="new-password"
                            />
                        </div>

                        <div class="md:col-span-2 flex justify-end gap-2">
                            <a href="{{ route('users.index') }}" class="text-sm text-gray-600 hover:text-gray-900">
                                {{ __('Cancelar') }}
                            </a>
                            <x-primary-button>
                                {{ __('Guardar cambios') }}
                            </x-primary-button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>




