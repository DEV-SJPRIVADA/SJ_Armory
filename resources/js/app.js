import './bootstrap';
import './dashboard';
import './realtime-portfolio-sync';
import './realtime-weapons-sync';
import './realtime-posts-workers-sync';
import './reports-incidents';
import './nav-notifications-realtime';
import { initWeaponsFilterDatePicker } from './weapons-filter-date';

document.addEventListener('DOMContentLoaded', () => {
    window.sjWeaponsPermitDatePicker = initWeaponsFilterDatePicker();
});

import Alpine from 'alpinejs';

window.Alpine = Alpine;

Alpine.start();
