<div class="bg-white overflow-hidden shadow-lg rounded-xl border border-gray-200">
    <div class="bg-gradient-to-r from-gray-50 to-white border-b border-gray-200 px-6 py-5">
        <div class="flex items-center justify-between">
            <div>
                <h3 class="text-xl font-bold text-gray-900 flex items-center gap-3">
                    <div class="bg-indigo-100 p-2 rounded-lg">
                        <svg class="h-6 w-6 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                    {{ __('Información del arma') }}
                </h3>
                <p class="mt-1 text-sm text-gray-600">{{ __('Datos básicos y permisos del arma registrada') }}</p>
            </div>
            <div class="text-sm text-gray-500">
                {{ __('Última actualización:') }} {{ $weapon->updated_at->format('Y-m-d') }}
            </div>
        </div>
    </div>
    
    <div class="p-6">
        <!-- Row 1: Identification, Characteristics, Permissions (3 columns on desktop, 2 on tablet) -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-6">
            <!-- Identification Card -->
            <div class="bg-gray-50 rounded-xl border border-gray-200 p-5">
                <div class="flex items-center gap-3 mb-4">
                    <div class="bg-white p-2 rounded-lg border border-gray-300">
                        <svg class="h-5 w-5 text-gray-700" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V8a2 2 0 00-2-2h-5m-4 0V5a2 2 0 114 0v1m-4 0a2 2 0 104 0m-5 8a2 2 0 100-4 2 2 0 000 4zm0 0c1.306 0 2.417.835 2.83 2M9 14a3.001 3.001 0 00-2.83 2M15 11h3m-3 4h2"></path>
                        </svg>
                    </div>
                    <h4 class="text-lg font-semibold text-gray-900">{{ __('Identificación') }}</h4>
                </div>
                
                <div class="space-y-5">
                    <div class="bg-white rounded-lg border border-gray-200 p-4">
                        <div class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">{{ __('Código interno') }}</div>
                        <div class="text-2xl font-bold text-gray-900">{{ $weapon->internal_code }}</div>
                    </div>
                    <div class="bg-white rounded-lg border border-gray-200 p-4">
                        <div class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">{{ __('Número de serie') }}</div>
                        <div class="text-xl font-semibold text-gray-900">{{ $weapon->serial_number }}</div>
                    </div>
                </div>
            </div>

            <!-- Characteristics Card -->
            <div class="bg-gray-50 rounded-xl border border-gray-200 p-5">
                <div class="flex items-center gap-3 mb-4">
                    <div class="bg-white p-2 rounded-lg border border-gray-300">
                        <svg class="h-5 w-5 text-gray-700" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                        </svg>
                    </div>
                    <h4 class="text-lg font-semibold text-gray-900">{{ __('Características') }}</h4>
                </div>
                
                <div class="grid grid-cols-2 gap-4">
                    <div class="bg-white rounded-lg border border-gray-200 p-4">
                        <div class="text-xs font-semibold text-gray-500 mb-2">{{ __('Tipo') }}</div>
                        <div class="font-bold">
                            <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-bold bg-blue-100 text-blue-800 border border-blue-300">
                                {{ $weapon->weapon_type }}
                            </span>
                        </div>
                    </div>
                    <div class="bg-white rounded-lg border border-gray-200 p-4">
                        <div class="text-xs font-semibold text-gray-500 mb-2">{{ __('Calibre') }}</div>
                        <div class="text-lg font-bold text-gray-900">{{ $weapon->caliber }}</div>
                    </div>
                    <div class="col-span-2 bg-white rounded-lg border border-gray-200 p-4">
                        <div class="text-xs font-semibold text-gray-500 mb-2">{{ __('Marca') }}</div>
                        <div class="text-lg font-bold text-gray-900">{{ $weapon->brand }}</div>
                    </div>
                </div>
            </div>

            <!-- Permissions Card -->
            <div class="bg-gray-50 rounded-xl border border-gray-200 p-5">
                <div class="flex items-center gap-3 mb-4">
                    <div class="bg-white p-2 rounded-lg border border-gray-300">
                        <svg class="h-5 w-5 text-gray-700" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path>
                        </svg>
                    </div>
                    <h4 class="text-lg font-semibold text-gray-900">{{ __('Permisos') }}</h4>
                </div>
                
                <div class="grid grid-cols-2 gap-4">
                    <div class="bg-white rounded-lg border border-gray-200 p-4">
                        <div class="text-xs font-semibold text-gray-500 mb-2">{{ __('Tipo de permiso') }}</div>
                        <div class="font-bold">
                            @if ($weapon->permit_type === 'porte')
                                <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-bold bg-indigo-100 text-indigo-800 border border-indigo-300">
                                    {{ __('Porte') }}
                                </span>
                            @elseif ($weapon->permit_type === 'tenencia')
                                <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-bold bg-blue-100 text-blue-800 border border-blue-300">
                                    {{ __('Tenencia') }}
                                </span>
                            @else
                                <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-bold bg-gray-100 text-gray-800 border border-gray-300">
                                    -
                                </span>
                            @endif
                        </div>
                    </div>
                    <div class="bg-white rounded-lg border border-gray-200 p-4">
                        <div class="text-xs font-semibold text-gray-500 mb-2">{{ __('Número de permiso') }}</div>
                        <div class="text-lg font-bold text-gray-900">{{ $weapon->permit_number ?: '-' }}</div>
                    </div>
                    <div class="bg-white rounded-lg border border-gray-200 p-4">
                        <div class="text-xs font-semibold text-gray-500 mb-2">{{ __('Fecha de vencimiento') }}</div>
                        <div class="font-bold">
                            @php
                                $expiryDate = $weapon->permit_expires_at?->format('Y-m-d');
                                $isExpired = $weapon->permit_expires_at && $weapon->permit_expires_at->isPast();
                            @endphp
                            @if ($expiryDate)
                                <div class="flex items-center gap-2">
                                    <span class="{{ $isExpired ? 'text-red-600' : 'text-green-600' }}">{{ $expiryDate }}</span>
                                    @if ($isExpired)
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-bold bg-red-100 text-red-800 border border-red-300">
                                            {{ __('Vencido') }}
                                        </span>
                                    @else
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-bold bg-green-100 text-green-800 border border-green-300">
                                            {{ __('Vigente') }}
                                        </span>
                                    @endif
                                </div>
                            @else
                                <span class="text-gray-900">-</span>
                            @endif
                        </div>
                    </div>
                    <div class="bg-white rounded-lg border border-gray-200 p-4">
                        <div class="text-xs font-semibold text-gray-500 mb-2">{{ __('Foto del permiso') }}</div>
                        <div class="text-lg font-bold text-gray-900">{{ $weapon->permitFile?->original_name ?? '-' }}</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Row 2: Ownership and Notes (2 columns) -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <!-- Ownership Card -->
            <div class="bg-gray-50 rounded-xl border border-gray-200 p-5">
                <div class="flex items-center gap-3 mb-4">
                    <div class="bg-white p-2 rounded-lg border border-gray-300">
                        <svg class="h-5 w-5 text-gray-700" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path>
                        </svg>
                    </div>
                    <h4 class="text-lg font-semibold text-gray-900">{{ __('Propiedad') }}</h4>
                </div>
                
                <div class="space-y-4">
                    <div class="bg-white rounded-lg border border-gray-200 p-4">
                        <div class="text-xs font-semibold text-gray-500 mb-2">{{ __('Tipo de propiedad') }}</div>
                        <div class="font-bold">
                            @php
                                $ownershipType = $ownershipTypes[$weapon->ownership_type] ?? $weapon->ownership_type;
                                $ownershipColor = match($weapon->ownership_type) {
                                    'propia' => 'bg-green-100 text-green-800 border-green-300',
                                    'arrendada' => 'bg-yellow-100 text-yellow-800 border-yellow-300',
                                    'comodato' => 'bg-purple-100 text-purple-800 border-purple-300',
                                    default => 'bg-gray-100 text-gray-800 border-gray-300',
                                };
                            @endphp
                            <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-bold {{ $ownershipColor }} border">
                                {{ $ownershipType }}
                            </span>
                        </div>
                    </div>
                    <div class="bg-white rounded-lg border border-gray-200 p-4">
                        <div class="text-xs font-semibold text-gray-500 mb-2">{{ __('Entidad de propiedad') }}</div>
                        <div class="text-lg font-bold text-gray-900">{{ $weapon->ownership_entity ?: '-' }}</div>
                    </div>
                    <div class="bg-white rounded-lg border border-gray-200 p-4">
                        <div class="text-xs font-semibold text-gray-500 mb-2">{{ __('Responsable') }}</div>
                        <div class="text-lg font-bold text-gray-900">{{ $weapon->activeClientAssignment?->responsible?->name ?? '-' }}</div>
                    </div>
                </div>
            </div>

            <!-- Notes Card -->
            <div class="bg-gray-50 rounded-xl border border-gray-200 p-5">
                <div class="flex items-center gap-3 mb-4">
                    <div class="bg-white p-2 rounded-lg border border-gray-300">
                        <svg class="h-5 w-5 text-gray-700" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                        </svg>
                    </div>
                    <h4 class="text-lg font-semibold text-gray-900">{{ __('Notas') }}</h4>
                </div>
                
                <div class="bg-white rounded-lg border border-gray-200 p-4">
                    <div class="text-sm text-gray-700 whitespace-pre-line leading-relaxed">
                        @if ($weapon->notes)
                            {{ $weapon->notes }}
                        @else
                            <div class="text-center py-8 text-gray-500">
                                <svg class="h-12 w-12 mx-auto text-gray-300 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                </svg>
                                <p class="text-lg font-medium">{{ __('Sin notas registradas') }}</p>
                                <p class="text-sm mt-1">{{ __('No hay notas adicionales para esta arma.') }}</p>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>