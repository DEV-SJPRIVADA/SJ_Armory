<x-app-layout>
    <div class="max-w-7xl mx-auto px-4 py-6 sm:px-6 lg:px-8">
        <div
            class="sj-dashboard"
            x-data="dashboardMonitor({
                initialData: @js($dashboard),
                dataUrl: '{{ route('dashboard.metrics') }}',
            })"
            x-init="init()"
        >
            <section class="sj-dashboard-hero">
                <div>
                    <p class="sj-dashboard-hero__eyebrow">Centro de monitoreo</p>
                    <h1 class="sj-dashboard-hero__title">SJ Seguridad Privada LTDA</h1>
                    <p class="sj-dashboard-hero__subtitle">
                        Vista global del sistema. Consolida inventario, renovación documental,
                        transferencias y novedades operativas en una sola vista.
                    </p>
                </div>

                <div class="sj-dashboard-hero__meta">
                    <div class="sj-dashboard-stamp">
                        <span class="sj-dashboard-stamp__label">Actualizado</span>
                        <span class="sj-dashboard-stamp__value" x-text="formattedAsOf()"></span>
                    </div>

                    <div class="sj-dashboard-meta">
                        <template x-for="item in dashboard.meta" :key="item.label">
                            <div class="sj-metric-chip">
                                <span class="sj-metric-chip__label" x-text="item.label"></span>
                                <span class="sj-metric-chip__value" x-text="formatNumber(item.value)"></span>
                            </div>
                        </template>
                    </div>
                </div>
            </section>

            <section class="sj-dashboard-kpis sj-dashboard-kpis--compact">
                <template x-for="item in dashboard.kpis" :key="item.label">
                    <article class="sj-kpi-card" :class="`sj-kpi-card--${item.tone}`">
                        <div class="sj-kpi-card__label" x-text="item.label"></div>
                        <div class="sj-kpi-card__value" x-text="formatNumber(item.value)"></div>
                        <div class="sj-kpi-card__helper" x-text="item.helper"></div>
                    </article>
                </template>
            </section>

            <section class="sj-dashboard-grid sj-dashboard-grid--primary">
                <article class="sj-panel sj-panel--responsibles">
                    <div class="sj-panel__head">
                        <div>
                            <div class="sj-form-section__title">Responsables</div>
                            <h2 class="sj-panel__title">Armas por responsable</h2>
                        </div>
                    </div>

                    <template x-if="dashboard.responsible_chart.items.length">
                        <div class="sj-bar-list">
                            <template x-for="item in dashboard.responsible_chart.items" :key="item.label">
                                <div class="sj-bar-list__row">
                                    <div class="sj-bar-list__top">
                                        <span class="sj-bar-list__label" x-text="item.label"></span>
                                        <span class="sj-bar-list__value" x-text="formatNumber(item.value)"></span>
                                    </div>
                                    <div class="sj-bar-list__track">
                                        <div
                                            class="sj-bar-list__fill"
                                            :style="`width: ${barWidth(item.value, dashboard.responsible_chart.max)}%`"
                                        ></div>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </template>

                    <template x-if="!dashboard.responsible_chart.items.length">
                        <p class="sj-panel__empty">No hay responsables con armas visibles dentro del alcance actual.</p>
                    </template>
                </article>

                <article class="sj-panel sj-panel--risk-summary">
                    <div class="sj-panel__head">
                        <div>
                            <div class="sj-form-section__title">Riesgo</div>
                            <h2 class="sj-panel__title">Estado documental</h2>
                        </div>
                    </div>

                    <div class="sj-donut-card">
                        <div class="sj-donut-wrap">
                            <div class="sj-donut" :style="dashboard.risk_chart.donut_style">
                                <div class="sj-donut__center">
                                    <span class="sj-donut__total" x-text="formatNumber(dashboard.risk_chart.total)"></span>
                                    <span class="sj-donut__caption">Documentos</span>
                                </div>
                            </div>
                        </div>

                        <div class="sj-legend">
                            <template x-for="item in dashboard.risk_chart.items" :key="item.label">
                                <div class="sj-legend__item">
                                    <span class="sj-legend__swatch" :style="`background:${item.color}`"></span>
                                    <div class="sj-legend__text">
                                        <span class="sj-legend__label" x-text="item.label"></span>
                                        <span class="sj-legend__value" x-text="formatNumber(item.value)"></span>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </div>
                </article>
            </section>

            <section class="sj-dashboard-grid sj-dashboard-grid--secondary">
                <article class="sj-panel sj-panel--renewal-chart">
                    <div class="sj-panel__head">
                        <div>
                            <div class="sj-form-section__title">Planeación</div>
                            <h2 class="sj-panel__title">Renovaciones por mes</h2>
                        </div>

                        <template x-if="dashboard.renewal_chart.years.length">
                            <label class="sj-dashboard-filter">
                                <span class="sj-dashboard-filter__label">Año</span>
                                <select
                                    class="sj-dashboard-filter__select"
                                    :value="String(renewalYear)"
                                    @change="applyRenewalYear($event.target.value)"
                                >
                                    <template x-for="year in dashboard.renewal_chart.years" :key="year">
                                        <option
                                            :value="String(year)"
                                            :selected="String(year) === String(renewalYear)"
                                            x-text="year"
                                        ></option>
                                    </template>
                                </select>
                            </label>
                        </template>
                    </div>

                    <template x-if="dashboard.renewal_chart.items.length">
                        <div class="sj-column-chart-wrap">
                            <div class="sj-column-chart">
                                <template x-for="item in dashboard.renewal_chart.items" :key="item.key || item.label">
                                    <div class="sj-column-chart__item">
                                        <div class="sj-column-chart__value" x-text="formatNumber(item.value)"></div>
                                        <div class="sj-column-chart__track">
                                            <div
                                                class="sj-column-chart__bar"
                                                :style="`height: ${columnHeight(item.value, dashboard.renewal_chart.max)}%`"
                                            ></div>
                                        </div>
                                        <div class="sj-column-chart__label" x-text="item.label"></div>
                                    </div>
                                </template>
                            </div>
                        </div>
                    </template>

                    <template x-if="!dashboard.renewal_chart.items.length">
                        <p class="sj-panel__empty">No hay renovaciones registradas para el año seleccionado.</p>
                    </template>
                </article>

                <article class="sj-panel sj-panel--incidents">
                    <div class="sj-panel__head">
                        <div>
                            <div class="sj-form-section__title">Novedades</div>
                            <h2 class="sj-panel__title">Incidencias activas</h2>
                        </div>
                    </div>

                    <div class="sj-stat-list sj-stat-list--compact">
                        <template x-for="item in dashboard.incident_chart.items" :key="item.label">
                            <a class="sj-stat-list__item block hover:bg-slate-50" :href="item.url">
                                <div class="sj-stat-list__meta">
                                    <span class="sj-stat-list__dot" :style="`background:${item.color}`"></span>
                                    <span class="sj-stat-list__label" x-text="item.label"></span>
                                </div>
                                <span class="sj-stat-list__value" x-text="formatNumber(item.value)"></span>
                            </a>
                        </template>
                    </div>
                </article>
            </section>

            <section class="sj-dashboard-grid sj-dashboard-grid--tertiary">
                <article class="sj-panel">
                    <div class="sj-panel__head">
                        <div>
                            <div class="sj-form-section__title">Transferencias</div>
                            <h2 class="sj-panel__title">Estados del flujo</h2>
                        </div>
                    </div>

                    <div class="sj-bar-list sj-bar-list--compact">
                        <template x-for="item in dashboard.transfer_chart.items" :key="item.label">
                            <div class="sj-bar-list__row">
                                <div class="sj-bar-list__top">
                                    <span class="sj-bar-list__label" x-text="item.label"></span>
                                    <span class="sj-bar-list__value" x-text="formatNumber(item.value)"></span>
                                </div>
                                <div class="sj-bar-list__track sj-bar-list__track--soft">
                                    <div
                                        class="sj-bar-list__fill sj-bar-list__fill--custom"
                                        :style="`width: ${barWidth(item.value, dashboard.transfer_chart.max)}%; background:${item.color}`"
                                    ></div>
                                </div>
                            </div>
                        </template>
                    </div>
                </article>

                <article class="sj-panel">
                    <div class="sj-panel__head">
                        <div>
                            <div class="sj-form-section__title">Operación</div>
                            <h2 class="sj-panel__title">Distribución interna</h2>
                        </div>
                    </div>

                    <div class="sj-bar-list sj-bar-list--compact">
                        <template x-for="item in dashboard.operational_chart.items" :key="item.label">
                            <div class="sj-bar-list__row">
                                <div class="sj-bar-list__top">
                                    <span class="sj-bar-list__label" x-text="item.label"></span>
                                    <span class="sj-bar-list__value" x-text="formatNumber(item.value)"></span>
                                </div>
                                <div class="sj-bar-list__track sj-bar-list__track--soft">
                                    <div
                                        class="sj-bar-list__fill sj-bar-list__fill--custom"
                                        :style="`width: ${barWidth(item.value, dashboard.operational_chart.max)}%; background:${item.color}`"
                                    ></div>
                                </div>
                            </div>
                        </template>
                    </div>
                </article>
            </section>
        </div>
    </div>
</x-app-layout>


