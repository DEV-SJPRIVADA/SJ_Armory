window.dashboardMonitor = ({ initialData, dataUrl }) => ({
    dashboard: initialData,
    dataUrl,
    renewalYear: initialData?.renewal_chart?.selected_year ? String(initialData.renewal_chart.selected_year) : '',
    currentTime: null,
    clockTimer: null,
    refreshTimer: null,
    isRefreshing: false,

    init() {
        this.setDashboard(this.dashboard);

        this.clockTimer = window.setInterval(() => {
            if (this.currentTime) {
                this.currentTime = new Date(this.currentTime.getTime() + 1000);
            }
        }, 1000);

        this.refreshTimer = window.setInterval(() => {
            if (document.visibilityState === 'hidden') {
                return;
            }

            this.refreshMetrics();
        }, 15000);

        window.addEventListener('beforeunload', () => {
            window.clearInterval(this.clockTimer);
            window.clearInterval(this.refreshTimer);
        }, { once: true });
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

    columnHeight(value, max) {
        if (!value || !max) {
            return 0;
        }

        return Math.max(1, Math.round((value / max) * 100));
    },
});
