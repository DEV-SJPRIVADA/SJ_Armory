<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold text-gray-800">
            {{ $temporaryPhotoUser->exists ? __('Editar usuario temporal') : __('Crear usuario temporal') }}
        </h2>
    </x-slot>

    <div class="py-6">
        <div class="mx-auto max-w-lg sm:px-6 lg:px-8">
            @if ($errors->any())
                <div class="mb-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">
                    <p class="font-semibold">{{ __('No se pudo guardar. Revise los campos.') }}</p>
                    <ul class="mt-2 list-disc pl-5">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form method="POST" action="{{ $temporaryPhotoUser->exists ? route('revista-armas.temporary-users.update', $temporaryPhotoUser) : route('revista-armas.temporary-users.store') }}" class="space-y-4 rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
                @csrf
                @if ($temporaryPhotoUser->exists)
                    @method('PUT')
                @endif

                @if (! $isAdmin)
                    <input type="hidden" name="owner_responsible_user_id" value="{{ auth()->id() }}">
                    <p class="text-sm text-slate-600">{{ __('Este colaborador temporal quedará asociado a su cartera como responsable dueño.') }}</p>
                @endif

                @if ($isAdmin)
                    <div>
                        <label for="owner_responsible_user_id" class="block text-sm font-medium text-slate-700">{{ __('Responsable dueño') }}</label>
                        <select name="owner_responsible_user_id" id="owner_responsible_user_id" required class="mt-1 w-full rounded-lg border-slate-300 text-sm @error('owner_responsible_user_id') border-red-500 @enderror">
                            @foreach ($responsibles as $responsible)
                                <option value="{{ $responsible->id }}" @selected(old('owner_responsible_user_id', $temporaryPhotoUser->owner_responsible_user_id) == $responsible->id)>{{ $responsible->name }}</option>
                            @endforeach
                        </select>
                        @error('owner_responsible_user_id')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                @endif

                <div>
                    <label for="name" class="block text-sm font-medium text-slate-700">{{ __('Nombre') }}</label>
                    <input type="text" name="name" id="name" value="{{ old('name', $temporaryPhotoUser->name) }}" required class="mt-1 w-full rounded-lg border-slate-300 text-sm @error('name') border-red-500 @enderror">
                    @error('name')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <label for="email" class="block text-sm font-medium text-slate-700">{{ __('Correo') }}</label>
                    <input type="email" name="email" id="email" value="{{ old('email', $temporaryPhotoUser->email) }}" required class="mt-1 w-full rounded-lg border-slate-300 text-sm @error('email') border-red-500 @enderror">
                    @error('email')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div class="flex justify-end gap-2">
                    <a href="{{ route('revista-armas.temporary-users.index') }}" class="rounded-lg border border-slate-300 px-3 py-2 text-sm font-semibold text-slate-700">{{ __('Cancelar') }}</a>
                    <button type="submit" class="rounded-lg bg-[#0b6fb6] px-3 py-2 text-sm font-bold text-white">{{ __('Guardar') }}</button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
