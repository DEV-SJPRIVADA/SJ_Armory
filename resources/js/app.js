import './bootstrap';
import './dashboard';
import './realtime-portfolio-sync';
import './realtime-weapons-sync';
import './realtime-posts-workers-sync';
import './reports-incidents';
import './nav-notifications-realtime';

document.addEventListener('DOMContentLoaded', () => {
    const alertsPage = document.querySelector('[data-alerts-page]');
    if (alertsPage) {
        import('./alerts-documents-modal.js').then(({ initAlertsDocumentsPage }) => {
            initAlertsDocumentsPage(alertsPage);
        });
    }
});

import Alpine from 'alpinejs';

window.Alpine = Alpine;

Alpine.start();
