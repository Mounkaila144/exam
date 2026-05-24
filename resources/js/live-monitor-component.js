export function liveMonitor(config) {
    return {
        examId: config.examId,
        assignments: [...(config.initialAssignments || [])],
        incidents: [...(config.initialIncidents || [])],
        unlockUrlPattern: config.unlockUrlPattern,

        init() {
            // Reverb subscription (best-effort — works if VITE_REVERB_* configured and Reverb server runs)
            if (config.reverbAppKey && window.Echo) {
                try {
                    const channel = window.Echo.private(`exam.${this.examId}.monitor`);
                    channel.listen('.StudentJoined', (e) => this.updateAssignment(e.assignmentId, { status: 'en cours' }));
                    channel.listen('.StudentSubmitted', (e) => this.updateAssignment(e.assignmentId, { status: 'soumis' }));
                    channel.listen('.StudentLocked', (e) => this.updateAssignment(e.assignmentId, { status: 'verrouillé', lockedReason: e.reason }));
                    channel.listen('.IncidentRecorded', (e) => this.pushIncident(e));
                    channel.listen('.ExamUnlocked', (e) => this.updateAssignment(e.assignmentId, { status: 'en cours', lockedReason: null }));
                } catch (e) { console.warn('Echo subscription skipped:', e.message); }
            }
        },

        pushIncident(e) {
            this.incidents = [{
                id: Date.now(),
                assignmentId: e.assignmentId,
                studentName: e.studentName,
                type: e.type,
                typeLabel: e.summary,
                severity: e.severity,
                occurredAt: e.occurredAt,
            }, ...this.incidents].slice(0, 100);
            this.updateAssignment(e.assignmentId, (a) => ({ incidentsCount: (a.incidentsCount || 0) + 1 }));
        },

        updateAssignment(id, patch) {
            this.assignments = this.assignments.map(a => a.id !== id ? a : { ...a, ...(typeof patch === 'function' ? patch(a) : patch) });
        },

        formatTime(iso) {
            try { return new Date(iso).toLocaleTimeString('fr-FR'); } catch (e) { return iso; }
        },

        async unlock(assignment) {
            if (!confirm(`Redonner l'accès à ${assignment.name} ?`)) return;
            const comment = prompt('Commentaire (optionnel) :') || '';
            const url = this.unlockUrlPattern.replace('__ID__', assignment.id);
            try {
                await window.axios.post(url, { comment });
                this.updateAssignment(assignment.id, { status: 'en cours', lockedReason: null });
            } catch (e) { alert('Erreur : '+ (e.response?.data?.message || e.message)); }
        },
    };
}
