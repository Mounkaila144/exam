const INCIDENT_TYPES = {
    TAB_BLUR: 'tab_blur',
    FULLSCREEN_EXIT: 'fullscreen_exit',
    FULLSCREEN_DENIED: 'fullscreen_denied',
    COPY_ATTEMPT: 'copy_attempt',
    PASTE_ATTEMPT: 'paste_attempt',
    CUT_ATTEMPT: 'cut_attempt',
    CONTEXT_MENU_ATTEMPT: 'context_menu_attempt',
    DEVTOOLS_SHORTCUT: 'devtools_shortcut',
    DEVTOOLS_DETECTED: 'devtools_detected',
};

export function examRuntime(config) {
    return {
        token: config.token,
        urls: config.urls,
        security: config.security,
        assignmentId: config.assignmentId,
        answers: { ...(config.initialAnswers || {}) },
        remaining: config.remainingSeconds,
        saving: false,
        submitting: false,
        locked: false,
        hiddenAt: null,

        init() {
            this.startHeartbeat();
            this.attachVisibilityListeners();
            this.attachInputBlockers();
            this.attachDevToolsDetection();
            this.attachFullscreenListeners();
            this.requestFullscreen();
            this.restoreFromLocalStorage();
        },

        startHeartbeat() {
            setInterval(async () => {
                try {
                    const { data } = await window.axios.get(this.urls.heartbeat);
                    this.remaining = data.data?.remaining_seconds ?? this.remaining - 10;
                    if (this.remaining <= 0) this.submitExam(true);
                } catch (e) {
                    this.remaining = Math.max(0, this.remaining - 10);
                }
            }, 10000);

            setInterval(() => { this.remaining = Math.max(0, this.remaining - 1); }, 1000);
        },

        formatTime(s) {
            s = Math.max(0, parseInt(s, 10) || 0);
            const h = String(Math.floor(s / 3600)).padStart(2, '0');
            const m = String(Math.floor((s % 3600) / 60)).padStart(2, '0');
            const sec = String(Math.floor(s % 60)).padStart(2, '0');
            return `${h}:${m}:${sec}`;
        },

        async setAnswer(qid, value) {
            this.answers[qid] = value;
            this.saveDraftLocally();
            this.saving = true;
            try {
                await window.axios.post(this.urls.answers, { question_id: qid, value });
            } catch (e) {
                if (e.response?.status === 423) { this.locked = true; setTimeout(() => window.location.reload(), 1200); }
                if (e.response?.status === 409 && e.response?.data?.error === 'exam_expired') this.submitExam(true);
            } finally { this.saving = false; }
        },

        saveDraftLocally() {
            try { localStorage.setItem(`examguard.draft.${this.token}`, JSON.stringify(this.answers)); } catch (e) {}
        },

        restoreFromLocalStorage() {
            try {
                const raw = localStorage.getItem(`examguard.draft.${this.token}`);
                if (!raw) return;
                const parsed = JSON.parse(raw);
                Object.entries(parsed).forEach(([qid, value]) => {
                    if (this.answers[qid] === undefined || this.answers[qid] === null || this.answers[qid] === '') this.answers[qid] = value;
                });
            } catch (e) {}
        },

        confirmSubmit(e) { if (!confirm('Soumettre définitivement votre examen ?')) e.preventDefault(); },

        async submitExam(auto = false) {
            if (this.submitting) return;
            this.submitting = true;
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = this.urls.submit;
            const csrf = document.querySelector('meta[name="csrf-token"]')?.content;
            const tokenInput = document.createElement('input');
            tokenInput.type = 'hidden';
            tokenInput.name = '_token';
            tokenInput.value = csrf;
            form.appendChild(tokenInput);
            document.body.appendChild(form);
            form.submit();
        },

        async reportIncident(type, payload = {}) {
            try { await window.axios.post(this.urls.incidents, { type, payload }); }
            catch (e) { if (e.response?.status === 423) { this.locked = true; setTimeout(() => window.location.reload(), 1200); } }
        },

        attachVisibilityListeners() {
            document.addEventListener('visibilitychange', () => {
                if (document.visibilityState === 'hidden') this.hiddenAt = Date.now();
                else if (this.hiddenAt) {
                    const duration = Date.now() - this.hiddenAt;
                    this.hiddenAt = null;
                    this.reportIncident(INCIDENT_TYPES.TAB_BLUR, { duration_ms: duration });
                }
            });
            window.addEventListener('blur', () => {
                if (!this.hiddenAt) this.reportIncident(INCIDENT_TYPES.TAB_BLUR, { source: 'window_blur' });
            });
        },

        attachFullscreenListeners() {
            if (!this.security.enforce_fullscreen) return;
            document.addEventListener('fullscreenchange', () => {
                if (!document.fullscreenElement) {
                    this.reportIncident(INCIDENT_TYPES.FULLSCREEN_EXIT, { method: 'unknown' });
                    setTimeout(() => this.requestFullscreen(), 200);
                }
            });
        },

        async requestFullscreen() {
            if (!this.security.enforce_fullscreen) return;
            try { await document.documentElement.requestFullscreen(); }
            catch (e) { this.reportIncident(INCIDENT_TYPES.FULLSCREEN_DENIED, { reason: e.message }); }
        },

        attachInputBlockers() {
            if (this.security.block_copy_paste) {
                document.addEventListener('copy', (e) => { e.preventDefault(); this.reportIncident(INCIDENT_TYPES.COPY_ATTEMPT, { selection_length: (document.getSelection()?.toString() || '').length }); });
                document.addEventListener('cut', (e) => { e.preventDefault(); this.reportIncident(INCIDENT_TYPES.CUT_ATTEMPT); });
                document.addEventListener('paste', (e) => { e.preventDefault(); this.reportIncident(INCIDENT_TYPES.PASTE_ATTEMPT); });
            }
            if (this.security.block_right_click) {
                document.addEventListener('contextmenu', (e) => { e.preventDefault(); this.reportIncident(INCIDENT_TYPES.CONTEXT_MENU_ATTEMPT); });
            }
            if (this.security.block_devtools_shortcuts) {
                document.addEventListener('keydown', (e) => {
                    const combo = `${e.ctrlKey ? 'Ctrl+' : ''}${e.shiftKey ? 'Shift+' : ''}${e.key}`;
                    const blocked = ['F12', 'F11'].includes(e.key)
                        || (e.ctrlKey && e.shiftKey && ['I','J','C','K'].includes(e.key.toUpperCase()))
                        || (e.ctrlKey && ['U','S','P'].includes(e.key.toUpperCase()))
                        || e.key === 'PrintScreen';
                    if (blocked) { e.preventDefault(); this.reportIncident(INCIDENT_TYPES.DEVTOOLS_SHORTCUT, { key: combo }); }
                });
            }
        },

        attachDevToolsDetection() {
            if (!this.security.detect_devtools_open) return;
            setInterval(() => {
                const hDiff = window.outerHeight - window.innerHeight;
                const wDiff = window.outerWidth - window.innerWidth;
                if (hDiff > 200 || wDiff > 200) this.reportIncident(INCIDENT_TYPES.DEVTOOLS_DETECTED, { heuristic: 'window_size_diff', hDiff, wDiff });
            }, 2000);
        },
    };
}
