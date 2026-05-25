@php
    $custodyLabel = $weapon->custodyStatusLabel();
    $canManageCustody = $weapon->activeClientAssignment && ! $pendingTransferForWeapon;
@endphp

<div class="space-y-4">
    <div class="rounded-lg border border-slate-200 bg-slate-50 p-4">
        <div class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('Estado de custodia / taller') }}</div>
        <div class="mt-1 text-sm font-medium text-slate-900">
            @if ($custodyLabel)
                {{ $custodyLabel }}
                @if ($weapon->activePostAssignment?->post?->name)
                    <span class="text-slate-600">— {{ $weapon->activePostAssignment->post->name }}</span>
                @endif
            @else
                {{ __('Operación en campo o sin puesto de custodia especial') }}
            @endif
        </div>
        <p class="mt-2 text-xs text-slate-500">
            {{ __('El armerillo es custodia sana del responsable. Para mantenimiento y armero sacan el arma de operación sin registrar novedad.') }}
        </p>
    </div>

    @if ($errors->has('custody'))
        <div class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">
            {{ $errors->first('custody') }}
        </div>
    @endif

    @if (! $canManageCustody)
        <p class="text-sm text-amber-700">{{ __('Asigne destino operativo y resuelva transferencias pendientes para usar custodia.') }}</p>
    @else
        <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
            <form method="POST" action="{{ route('weapons.custody.armerillo', $weapon) }}" class="rounded-lg border border-emerald-200 bg-emerald-50/50 p-3">
                @csrf
                <div class="text-sm font-semibold text-emerald-900">{{ __('Enviar a mi armerillo') }}</div>
                <p class="mt-1 text-xs text-emerald-800">{{ __('Queda operativa, lista para reasignar.') }}</p>
                <button type="submit" class="mt-3 w-full rounded-lg bg-emerald-600 px-3 py-2 text-xs font-bold text-white hover:bg-emerald-700">
                    {{ __('Armerillo') }}
                </button>
            </form>

            <form method="POST" action="{{ route('weapons.custody.para_mantenimiento', $weapon) }}" class="rounded-lg border border-amber-200 bg-amber-50 p-3">
                @csrf
                <div class="text-sm font-semibold text-amber-950">{{ __('Para mantenimiento') }}</div>
                <p class="mt-1 text-xs text-amber-900">{{ __('Armerillo del responsable; fuera de operación.') }}</p>
                <button
                    type="submit"
                    class="mt-3 w-full rounded-lg border border-[#fcd34d] bg-[#fde68a] px-3 py-2 text-xs font-bold text-[#78350f] shadow-sm hover:bg-[#fcd34d] focus:outline-none focus:ring-2 focus:ring-[#fbbf24] focus:ring-offset-1"
                >
                    {{ __('Para mantenimiento') }}
                </button>
            </form>
        </div>

        <div class="rounded-lg border border-violet-200 bg-violet-50/40 p-4 space-y-3">
            <div class="text-sm font-semibold text-violet-900">{{ __('Enviar a mantenimiento (armero)') }}</div>
            <form method="POST" action="{{ route('weapons.custody.armero', $weapon) }}" class="space-y-3">
                @csrf
                <div>
                    <label class="text-xs text-violet-800">{{ __('Armero / taller del responsable') }}</label>
                    <select name="post_id" required class="mt-1 block w-full rounded-md border-violet-200 text-sm">
                        <option value="">{{ __('Seleccione armero...') }}</option>
                        @foreach ($armeroPosts as $post)
                            <option value="{{ $post->id }}">{{ $post->name }}</option>
                        @endforeach
                    </select>
                </div>
                <button
                    type="submit"
                    class="w-full rounded-lg border border-[#085a93] bg-[#0b6fb6] px-3 py-2 text-xs font-bold text-white shadow-sm hover:bg-[#085a93] focus:outline-none focus:ring-2 focus:ring-[#0b6fb6] focus:ring-offset-1 disabled:cursor-not-allowed disabled:opacity-50"
                    @disabled($armeroPosts->isEmpty())
                >
                    {{ __('Enviar a armero') }}
                </button>
            </form>

            <details class="text-sm">
                <summary class="cursor-pointer font-semibold text-violet-900">{{ __('Registrar nuevo armero') }}</summary>
                <form method="POST" action="{{ route('weapons.custody.armero_posts.store', $weapon) }}" class="mt-3 space-y-2">
                    @csrf
                    <input type="text" name="name" required placeholder="{{ __('Nombre del armero o taller') }}" class="block w-full rounded-md border-violet-200 text-sm">
                    <input type="text" name="address" placeholder="{{ __('Dirección (opcional)') }}" class="block w-full rounded-md border-violet-200 text-sm">
                    <button type="submit" class="rounded-lg border border-violet-300 bg-white px-3 py-2 text-xs font-semibold text-violet-800 hover:bg-violet-50">
                        {{ __('Crear armero') }}
                    </button>
                </form>
            </details>
        </div>
    @endif
</div>
