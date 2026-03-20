<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Nuevo trabajador') }}
        </h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <form method="POST" action="{{ route('workers.store') }}" class="sj-form-panel">
                        @csrf

                        <section class="sj-form-section">
                            <div class="sj-form-section__title">Identificación</div>
                            <div class="sj-form-grid sj-form-grid--two">
                                <div>
                                    <x-input-label for="client_id" :value="__('Cliente')" />
                                    <select id="client_id" name="client_id" class="mt-1 block w-full rounded-md border-gray-300 text-sm" required>
                                        <option value="">{{ __('Seleccione') }}</option>
                                        @foreach ($clients as $client)
                                            <option value="{{ $client->id }}" @selected(old('client_id') == $client->id)>
                                                {{ $client->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                    <x-input-error :messages="$errors->get('client_id')" class="mt-2" />
                                </div>

                                <div>
                                    <x-input-label for="name" :value="__('Nombre')" />
                                    <x-text-input id="name" name="name" type="text" class="mt-1 block w-full" value="{{ old('name') }}" required />
                                    <x-input-error :messages="$errors->get('name')" class="mt-2" />
                                </div>

                                <div>
                                    <x-input-label for="document" :value="__('Cédula')" />
                                    <x-text-input id="document" name="document" type="text" class="mt-1 block w-full" value="{{ old('document') }}" />
                                    <x-input-error :messages="$errors->get('document')" class="mt-2" />
                                </div>
                            </div>
                        </section>

                        <section class="sj-form-section">
                            <div class="sj-form-section__title">Asignación</div>
                            <div class="sj-form-grid sj-form-grid--two">
                                <div>
                                    <x-input-label for="role" :value="__('Rol')" />
                                    <select id="role" name="role" class="mt-1 block w-full rounded-md border-gray-300 text-sm" required>
                                        <option value="">{{ __('Seleccione') }}</option>
                                        @foreach ($roles as $value => $label)
                                            <option value="{{ $value }}" @selected(old('role') == $value)>{{ $label }}</option>
                                        @endforeach
                                    </select>
                                    <x-input-error :messages="$errors->get('role')" class="mt-2" />
                                </div>

                                <div>
                                    <x-input-label for="responsible_user_id" :value="__('Responsable')" />
                                    <select id="responsible_user_id" name="responsible_user_id" class="mt-1 block w-full rounded-md border-gray-300 text-sm">
                                        <option value="">{{ __('Sin responsable') }}</option>
                                        @foreach ($responsibles as $responsible)
                                            <option value="{{ $responsible->id }}" @selected(old('responsible_user_id') == $responsible->id)>
                                                {{ $responsible->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                    <x-input-error :messages="$errors->get('responsible_user_id')" class="mt-2" />
                                </div>
                            </div>
                        </section>

                        <section class="sj-form-section">
                            <div class="sj-form-section__title">Notas</div>
                            <div>
                                <x-input-label for="notes" :value="__('Notas')" />
                                <textarea id="notes" name="notes" class="mt-1 block w-full rounded-md border-gray-300 text-sm" rows="3">{{ old('notes') }}</textarea>
                                <x-input-error :messages="$errors->get('notes')" class="mt-2" />
                            </div>
                        </section>

                        <div class="sj-form-actions">
                            <a href="{{ route('workers.index') }}" class="text-sm text-gray-600 hover:text-gray-900">
                                {{ __('Cancelar') }}
                            </a>
                            <x-primary-button>
                                {{ __('Guardar') }}
                            </x-primary-button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
