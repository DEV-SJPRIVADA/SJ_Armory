window.dashboardMonitor = ({ initialData, dataUrl }) => ({
    dashboard: initialData,
    dataUrl,
    renewalYear: initialData?.renewal_chart?.selected_year ? String(initialData.renewal_chart.selected_year) : '',
    currentTime: null,
    clockTimer: null,
    refreshTimer: null,
    realtimeDebounceTimer: null,
    isRefreshing: false,

    init() {
        this.setDashboard(this.dashboard);

        this.clockTimer = window.setInterval(() => {
            if (this.currentTime) {
                this.currentTime = new Date(this.currentTime.getTime() + 1000);
            }
        }, 1000);

        this.bindDashboardRealtime();

        // Polling desactivado - Migrado a Realtime (ver bindDashboardRealtime)

        window.addEventListener('beforeunload', () => {
            window.clearInterval(this.clockTimer);
            window.clearInterval(this.refreshTimer);
            if (this.realtimeDebounceTimer) {
                window.clearTimeout(this.realtimeDebounceTimer);
            }
        }, { once: true });
    },

    bindDashboardRealtime() {
        if (!window.Echo) {
            return;
        }

        const pairs = [
            ['weapons.updates', 'WeaponChanged'],
            ['clients.updates', 'ClientChanged'],
            ['transfers.updates', 'TransferChanged'],
            ['assignments.updates', 'AssignmentChanged'],
            ['maps.updates', 'MapDataChanged'],
        ];

        const scheduleRefresh = () => {
            if (document.visibilityState === 'hidden') {
                return;
            }
            if (this.realtimeDebounceTimer) {
                window.clearTimeout(this.realtimeDebounceTimer);
            }
            this.realtimeDebounceTimer = window.setTimeout(() => {
                this.realtimeDebounceTimer = null;
                this.refreshMetrics();
            }, 400);
        };

        pairs.forEach(([channel, eventName]) => {
            try {
                window.Echo.private(channel).listen(eventName, scheduleRefresh);
            } catch (error) {
                console.warn('No se pudo suscribir al canal en tiempo real.', channel, error);
            }
        });
    },

    async refreshMetrics() {
        if (this.isRefreshing) {
            return;
        }

        this.isRefreshing = true;

        try {
            const response = await window.axios.get(this.dataUrl, {
                params: this.renewalYear ? { renewal_year: this.renewalYear } : {},
            });
            this.setDashboard(response.data);
        } catch (error) {
            console.error('No se pudo actualizar el dashboard.', error);
        } finally {
            this.isRefreshing = false;
        }
    },

    setDashboard(payload) {
        this.dashboard = payload;
        this.renewalYear = payload?.renewal_chart?.selected_year ? String(payload.renewal_chart.selected_year) : '';
        this.currentTime = payload?.as_of ? new Date(payload.as_of) : new Date();
    },

    async applyRenewalYear(year) {
        this.renewalYear = String(year);
        await this.refreshMetrics();
    },

    formatNumber(value) {
        return new Intl.NumberFormat('es-CO').format(value ?? 0);
    },

    formattedAsOf() {
        if (!this.currentTime) {
            return '';
        }

        return new Intl.DateTimeFormat('es-CO', {
            day: 'numeric',
            month: 'long',
            year: 'numeric',
            hour: '2-digit',
            minute: '2-digit',
            second: '2-digit',
            hour12: true,
            timeZone: 'America/Bogota',
        }).format(this.currentTime);
    },

    barWidth(value, max, min = 10) {
        if (!value || !max) {
            return 0;
        }

        return Math.max(min, Math.round((value / max) * 100));
    },

    columnHeight(value, max, minVisible = 11) {
        const numericValue = Number(value ?? 0);
        const numericMax = Number(max ?? 0);

        if (!numericValue || !numericMax) {
            return 0;
        }

        const proportionalHeight = (numericValue / numericMax) * 100;

        return Math.max(minVisible, Math.min(100, Number(proportionalHeight.toFixed(2))));
    },
});
