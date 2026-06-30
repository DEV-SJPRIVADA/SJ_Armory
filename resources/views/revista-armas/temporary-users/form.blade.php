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

                    <div class="rounded-lg border border-slate-200 bg-slate-50 p-4 space-y-3">
                        <label class="flex items-start gap-3">
                            <input
                                type="checkbox"
                                name="is_shared"
                                value="1"
                                id="is_shared"
                                class="mt-1 rounded border-slate-300 text-[#0b6fb6] focus:ring-[#0b6fb6]"
                                @checked(old('is_shared', $temporaryPhotoUser->is_shared))
                            >
                            <span>
                                <span class="block text-sm font-semibold text-slate-800">{{ __('Permitir uso por varios responsables') }}</span>
                                <span class="mt-1 block text-xs text-slate-600">{{ __('Los supervisores compartidos pueden recibir armas de distintas zonas con un solo acceso y código.') }}</span>
                            </span>
                        </label>

                        <div id="shared-responsibles-wrap" class="@unless(old('is_shared', $temporaryPhotoUser->is_shared)) hidden @endunless">
                            <label for="authorized_responsible_ids" class="block text-sm font-medium text-slate-700">{{ __('Responsables autorizados') }}</label>
                            <select
                                name="authorized_responsible_ids[]"
                                id="authorized_responsible_ids"
                                multiple
                                size="6"
                                class="mt-1 w-full rounded-lg border-slate-300 text-sm @error('authorized_responsible_ids') border-red-500 @enderror @error('authorized_responsible_ids.*') border-red-500 @enderror"
                            >
                                @php
                                    $selectedIds = collect(old('authorized_responsible_ids', $temporaryPhotoUser->exists ? $temporaryPhotoUser->authorizedResponsibles->pluck('id')->all() : []))
                                        ->map(fn ($id) => (int) $id)
                                        ->all();
                                @endphp
                                @foreach ($responsibles as $responsible)
                                    <option value="{{ $responsible->id }}" @selected(in_array($responsible->id, $selectedIds, true))>{{ $responsible->name }}</option>
                                @endforeach
                            </select>
                            <p class="mt-1 text-xs text-slate-500">{{ __('Seleccione los responsables adicionales que podrán asignar armas. El dueño siempre conserva acceso.') }}</p>
                            @error('authorized_responsible_ids')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
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

    @if ($isAdmin)
        @push('scripts')
        <script>
            (() => {
                const sharedToggle = document.getElementById('is_shared');
                const sharedWrap = document.getElementById('shared-responsibles-wrap');
                if (!sharedToggle || !sharedWrap) return;

                sharedToggle.addEventListener('change', () => {
                    sharedWrap.classList.toggle('hidden', !sharedToggle.checked);
                });
            })();
        </script>
        @endpush
    @endif
</x-app-layout>
