@props([
    'label',
    'name',
    'id' => null,
    'value' => '',
    'autocomplete' => 'new-password',
    'required' => false,
    'errorBag' => null,
    'errorKey' => null,
])

@php
    $id = $id ?? $name;
    $errorKey = $errorKey ?? $name;
    $errorMessages = $errorBag
        ? $errors->getBag($errorBag)->get($errorKey)
        : $errors->get($errorKey);
@endphp

<div>
    <x-input-label :for="$id" :value="$label" />
    <div class="relative mt-1" x-data="{ show: false }">
        <input
            id="{{ $id }}"
            name="{{ $name }}"
            value="{{ $value }}"
            x-bind:type="show ? 'text' : 'password'"
            autocomplete="{{ $autocomplete }}"
            spellcheck="false"
            @if ($required) required @endif
            class="border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm block w-full pr-10"
        />
        <button
            type="button"
            class="absolute inset-y-0 right-0 flex items-center pr-3 text-gray-500 hover:text-gray-700 focus:outline-none"
            @click="show = !show"
            x-bind:aria-label="show ? {{ json_encode(__('Ocultar contraseña')) }} : {{ json_encode(__('Mostrar contraseña')) }}"
        >
            <svg class="h-5 w-5" x-bind:class="{ 'hidden': show }" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z" />
                <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
            </svg>
            <svg class="h-5 w-5" x-bind:class="{ 'hidden': !show }" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M3.98 8.223A10.477 10.477 0 0 0 1.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.451 10.451 0 0 1 12 4.5c4.756 0 8.773 3.162 10.065 7.498a10.522 10.522 0 0 1-4.293 5.774M6.228 6.228 3 3m12 12 3 3m-5.47-5.47-4.5-4.5-4.5 4.5" />
            </svg>
        </button>
    </div>
    <x-input-error :messages="$errorMessages" class="mt-2" />
</div>
