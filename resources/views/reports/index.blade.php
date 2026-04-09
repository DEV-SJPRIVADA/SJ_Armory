<x-app-layout>
    <x-slot name="header">
        <div class="sj-section-header">
            <div class="sj-section-header__main">
                <p class="sj-section-header__eyebrow">{{ __('Centro de reportes') }}</p>
                <h2 class="sj-section-header__title">{{ __('Reportes') }}</h2>
                <p class="sj-section-header__subtitle">
                    {{ __('Centro gerencial para revisar estado, historial y novedades operativas.') }}
                </p>
            </div>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="sj-page-shell sj-page-shell--wide space-y-6">
            <div class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-3">
                <a href="{{ route('reports.assignments') }}" class="sj-report-card">
                    <span class="sj-report-card__eyebrow">{{ __('Consulta') }}</span>
                    <div class="sj-report-card__title">{{ __('Armas por cliente') }}</div>
                    <div class="sj-report-card__subtitle">{{ __('Destino activo y responsable asignado.') }}</div>
                </a>

                <a href="{{ route('reports.no_destination') }}" class="sj-report-card">
                    <span class="sj-report-card__eyebrow">{{ __('Control') }}</span>
                    <div class="sj-report-card__title">{{ __('Armas sin destino') }}</div>
                    <div class="sj-report-card__subtitle">{{ __('Inventario que requiere seguimiento operativo.') }}</div>
                </a>

                <a href="{{ route('reports.history') }}" class="sj-report-card">
                    <span class="sj-report-card__eyebrow">{{ __('Seguimiento') }}</span>
                    <div class="sj-report-card__title">{{ __('Historial por arma') }}</div>
                    <div class="sj-report-card__subtitle">{{ __('Asignaciones, documentos y lectura de novedades.') }}</div>
                </a>

                <a href="{{ route('reports.audit') }}" class="sj-report-card">
                    <span class="sj-report-card__eyebrow">{{ __('Trazabilidad') }}</span>
                    <div class="sj-report-card__title">{{ __('Auditoria reciente') }}</div>
                    <div class="sj-report-card__subtitle">{{ __('Ultimos cambios y actividad del sistema.') }}</div>
                </a>

                <a href="{{ route('alerts.documents') }}" class="sj-report-card">
                    <span class="sj-report-card__eyebrow">{{ __('Prevencion') }}</span>
                    <div class="sj-report-card__title">{{ __('Alertas documentales') }}</div>
                    <div class="sj-report-card__subtitle">{{ __('Vencimientos, revalidaciones y documentos vigentes.') }}</div>
                </a>

                <a href="{{ route('reports.weapon-incidents.index') }}" class="sj-report-card sj-report-card--accent">
                    <div class="sj-report-card__title">{{ __('Análisis Estratégico de Incidentes') }}</div>
                    <div class="sj-report-card__subtitle">{{ __('Distribución y caracterización de eventos por tipología, modalidad y medio empleado') }}</div>
                    <span class="sj-report-card__footer">{{ __('Ver análisis') }}</span>
                </a>
            </div>
        </div>
    </div>
</x-app-layout>
