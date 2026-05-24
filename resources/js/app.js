import './bootstrap';
import Alpine from 'alpinejs';
import { examRuntime } from './exam-runtime-component';
import { liveMonitor } from './live-monitor-component';

window.Alpine = Alpine;
window.examRuntime = examRuntime;
window.liveMonitor = liveMonitor;

document.addEventListener('alpine:init', () => {
    Alpine.data('examRuntime', examRuntime);
    Alpine.data('liveMonitor', liveMonitor);
});

Alpine.start();
