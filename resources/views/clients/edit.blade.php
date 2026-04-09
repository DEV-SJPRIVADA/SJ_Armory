<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Editar cliente') }}
        </h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <form method="POST" action="{{ route('clients.update', $client) }}" class="sj-form-panel" data-location-form>
                        <input type="hidden" name="coords_source" value="geocode" data-coords-source>
                        @csrf
                        @method('PUT')

                        <section class="sj-form-section">
                            <div class="sj-form-section__title">Datos básicos</div>
                            <div class="sj-form-grid sj-form-grid--two">
                                <div>
                                    <x-input-label for="name" :value="__('Razón social')" />
                                    <x-text-input id="name" name="name" type="text" class="mt-1 block w-full" value="{{ old('name', $client->name) }}" required />
                                    <x-input-error :messages="$errors->get('name')" class="mt-2" />
                                </div>

                                <div>
                                    <x-input-label for="nit" :value="__('NIT')" />
                                    <x-text-input id="nit" name="nit" type="text" class="mt-1 block w-full" value="{{ old('nit', $client->nit) }}" required />
                                    <x-input-error :messages="$errors->get('nit')" class="mt-2" />
                                </div>

                                <div>
                                    <x-input-label for="email" :value="__('Correo electrónico')" />
                                    <x-text-input id="email" name="email" type="email" class="mt-1 block w-full" value="{{ old('email', $client->email) }}" />
                                    <x-input-error :messages="$errors->get('email')" class="mt-2" />
                                </div>

                                <div>
                                    <x-input-label for="contact_name" :value="__('Contacto')" />
                                    <x-text-input id="contact_name" name="contact_name" type="text" class="mt-1 block w-full" value="{{ old('contact_name', $client->contact_name) }}" />
                                    <x-input-error :messages="$errors->get('contact_name')" class="mt-2" />
                                </div>

                                <div class="md:col-span-2">
                                    <x-input-label for="legal_representative" :value="__('Representante legal')" />
                                    <x-text-input id="legal_representative" name="legal_representative" type="text" class="mt-1 block w-full" value="{{ old('legal_representative', $client->legal_representative) }}" />
                                    <x-input-error :messages="$errors->get('legal_representative')" class="mt-2" />
                                </div>
                            </div>
                        </section>

                        <section class="sj-form-section">
                            <div class="sj-form-section__title">Ubicación</div>
                            <div class="sj-form-grid sj-form-grid--two-wide">
                                <div>
                                    <x-input-label for="address" :value="__('Dirección')" />
                                    <x-text-input id="address" name="address" data-address-input type="text" class="mt-1 block w-full" value="{{ old('address', $client->address) }}" placeholder="{{ __('Ej: Calle 10 # 25-30') }}" />
                                    <p class="sj-form-help">{{ __('Completa dirección, barrio, municipio y departamento para calcular la ubicación.') }}</p>
                                    <x-input-error :messages="$errors->get('address')" class="mt-2" />
                                </div>

                                <div>
                                    <x-input-label for="neighborhood" :value="__('Barrio')" />
                                    <x-text-input id="neighborhood" name="neighborhood" type="text" class="mt-1 block w-full" value="{{ old('neighborhood', $client->neighborhood) }}" data-neighborhood-input placeholder="{{ __('Ej: Centro') }}" />
                                    <x-input-error :messages="$errors->get('neighborhood')" class="mt-2" />
                                </div>

                                <div>
                                    <x-input-label for="department" :value="__('Departamento')" />
                                    <select id="department" name="department" class="mt-1 block w-full rounded-md border-gray-300 text-sm" data-department-select data-current="{{ old('department', $client->department) }}" required>
                                        <option value="">{{ __('Seleccione') }}</option>
                                    </select>
                                    <x-input-error :messages="$errors->get('department')" class="mt-2" />
                                </div>

                                <div>
                                    <x-input-label for="city" :value="__('Municipio')" />
                                    <select id="city" name="city" class="mt-1 block w-full rounded-md border-gray-300 text-sm" data-municipality-select data-current="{{ old('city', $client->city) }}" required>
                                        <option value="">{{ __('Seleccione') }}</option>
                                    </select>
                                    <x-input-error :messages="$errors->get('city')" class="mt-2" />
                                </div>

                                <div>
                                    <x-input-label for="latitude" :value="__('Latitud (auto)')" />
                                    <x-text-input id="latitude" name="latitude" type="text" class="mt-1 block w-full bg-gray-50" data-latitude-input value="{{ old('latitude', $client->latitude) }}" readonly />
                                </div>

                                <div>
                                    <x-input-label for="longitude" :value="__('Longitud (auto)')" />
                                    <x-text-input id="longitude" name="longitude" type="text" class="mt-1 block w-full bg-gray-50" data-longitude-input value="{{ old('longitude', $client->longitude) }}" readonly />
                                </div>

                                <p class="hidden rounded-md border border-amber-200 bg-amber-50 px-3 py-2 text-sm text-amber-800" data-geocode-notice></p>

                                <div class="flex items-end">
                                    <button type="button" class="sj-map-trigger" data-map-trigger aria-label="{{ __('Seleccionar en el mapa') }}" title="{{ __('Seleccionar en el mapa') }}">
                                        <img src="{{ asset('images/map/Mapa.jpg') }}" alt="" aria-hidden="true">
                                    </button>
                                </div>
                            </div>
                        </section>

                        <div class="sj-form-actions">
                            <a href="{{ route('clients.index') }}" class="text-sm text-gray-600 hover:text-gray-900">
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

@vite('resources/js/location-picker.js')

<div id="location-map-modal" class="fixed inset-0 z-[3000] hidden items-center justify-center bg-black/50 p-4">
    <div class="w-full max-w-4xl rounded-lg bg-white p-4 shadow-lg">
        <div class="flex items-center justify-between">
            <h3 class="text-lg font-semibold">{{ __('Seleccionar ubicación') }}</h3>
            <button type="button" class="text-gray-500 hover:text-gray-700" data-map-close>&times;</button>
        </div>
        <div id="location-map" class="mt-4 h-96 w-full rounded border"></div>
        <p class="mt-3 hidden text-sm text-red-600" data-map-error></p>
        <div class="mt-4 flex justify-end gap-2">
            <button type="button" class="rounded-md border border-gray-300 px-4 py-2 text-sm" data-map-close>{{ __('Cancelar') }}</button>
            <button type="button" class="rounded-md bg-blue-600 px-4 py-2 text-sm text-white disabled:cursor-not-allowed disabled:opacity-60" data-map-accept disabled>{{ __('Aceptar') }}</button>
        </div>
    </div>
</div>
