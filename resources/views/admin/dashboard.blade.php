@extends('layouts.app')

@section('title', 'Tableau de bord')

@section('content')

<!-- Stats -->
<div class="stats-grid">
    <div class="stat-card purple">
        <div class="stat-value">{{ $stats['total'] }}</div>
        <div class="stat-label">Copies totales</div>
    </div>
    <div class="stat-card orange">
        <div class="stat-value">{{ $stats['pending'] }}</div>
        <div class="stat-label">En attente</div>
    </div>
    <div class="stat-card blue">
        <div class="stat-value">{{ $stats['graded'] }}</div>
        <div class="stat-label">Corrigees</div>
    </div>
    <div class="stat-card green">
        <div class="stat-value">{{ $stats['sent'] }}</div>
        <div class="stat-label">Notes envoyees</div>
    </div>
    <div class="stat-card">
        <div class="stat-value">{{ number_format($stats['avg_p1'] ?? 0, 1) }}</div>
        <div class="stat-label">Moy. Partie I (/25)</div>
    </div>
    <div class="stat-card">
        <div class="stat-value">{{ $stats['avg_total'] ? number_format($stats['avg_total'], 1) : '—' }}</div>
        <div class="stat-label">Moy. Totale (/100)</div>
    </div>
</div>

<!-- Toolbar -->
<div class="toolbar">
    <form action="{{ route('admin.export') }}" method="POST" id="export-form" style="display:inline;">
        @csrf
        <input type="hidden" name="ids" id="export-ids">
        <button type="submit" class="btn btn-purple" onclick="setExportIds()">
            Exporter pour Claude
        </button>
    </form>
    <button class="btn btn-outline" onclick="document.getElementById('import-modal').classList.add('show')">
        Importer les notes (JSON)
    </button>
    <form action="{{ route('admin.sendAllGrades') }}" method="POST" style="display:inline;" onsubmit="return confirm('Envoyer les notes a tous les etudiants corriges ?')">
        @csrf
        <button type="submit" class="btn btn-success">
            Envoyer toutes les notes
        </button>
    </form>
    <label style="font-size:13px;color:#6b7280;display:flex;align-items:center;gap:6px;margin-left:auto;">
        <input type="checkbox" id="select-all" onclick="toggleAll(this)"> Tout selectionner
    </label>
</div>

<!-- Submissions table -->
<div class="card">
    <table>
        <thead>
            <tr>
                <th class="checkbox-cell"></th>
                <th>Nom</th>
                <th>Matricule</th>
                <th>Groupe</th>
                <th>P1 (/25)</th>
                <th>P2 (/25)</th>
                <th>P3 (/40)</th>
                <th>P4 (/10)</th>
                <th>Total (/100)</th>
                <th>Statut</th>
                <th>Date</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            @forelse($submissions as $sub)
            <tr>
                <td class="checkbox-cell">
                    <input type="checkbox" class="sub-check" value="{{ $sub->id }}">
                </td>
                <td style="font-weight:500;">{{ $sub->nom }}</td>
                <td>{{ $sub->matricule }}</td>
                <td>{{ $sub->groupe ?: '—' }}</td>
                <td>{{ number_format($sub->score_p1, 1) }}</td>
                <td>{{ $sub->note_p2 !== null ? number_format($sub->note_p2, 1) : '—' }}</td>
                <td>{{ $sub->note_p3 !== null ? number_format($sub->note_p3, 1) : '—' }}</td>
                <td>{{ $sub->note_p4 !== null ? number_format($sub->note_p4, 1) : '—' }}</td>
                <td style="font-weight:600;color:{{ $sub->note_total !== null ? '#3c3489' : '#9ca3af' }}">
                    {{ $sub->note_total !== null ? number_format($sub->note_total, 1) : '—' }}
                </td>
                <td>
                    @if($sub->status === 'submitted')
                        <span class="badge badge-pending">En attente</span>
                    @elseif($sub->status === 'graded')
                        <span class="badge badge-graded">Corrigee</span>
                    @else
                        <span class="badge badge-sent">Envoyee</span>
                    @endif
                </td>
                <td style="font-size:12px;color:#6b7280;">{{ $sub->created_at->format('d/m H:i') }}</td>
                <td>
                    <div class="actions">
                        <a href="{{ route('admin.show', $sub) }}" class="btn btn-outline btn-sm">Voir</a>
                        @if($sub->isGraded() && $sub->status !== 'sent')
                        <form action="{{ route('admin.sendGrade', $sub) }}" method="POST" style="display:inline;">
                            @csrf
                            <button class="btn btn-success btn-sm" onclick="return confirm('Envoyer la note a {{ $sub->nom }} ?')">Envoyer</button>
                        </form>
                        @endif
                    </div>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="12" style="text-align:center;padding:2rem;color:#9ca3af;">
                    Aucune copie soumise pour le moment.
                </td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>

<!-- Import Modal -->
<div class="modal-overlay" id="import-modal">
    <div class="modal">
        <h3>Importer les notes depuis Claude</h3>
        <p style="font-size:13px;color:#6b7280;margin-bottom:1rem;">
            Collez le JSON retourne par Claude apres la correction. Le format attendu est celui indique dans l'export.
        </p>
        <form action="{{ route('admin.import') }}" method="POST">
            @csrf
            <textarea name="grades_json" class="import-area" placeholder='Collez le JSON ici...

{
  "etudiants": [
    {
      "matricule": "20240001",
      "nom": "...",
      "note_p2": 18.5,
      ...
    }
  ]
}'></textarea>
            <div style="display:flex;gap:8px;justify-content:flex-end;margin-top:1rem;">
                <button type="button" class="btn btn-outline" onclick="document.getElementById('import-modal').classList.remove('show')">Annuler</button>
                <button type="submit" class="btn btn-primary">Importer les notes</button>
            </div>
        </form>
    </div>
</div>

@endsection

@section('scripts')
<script>
function toggleAll(el) {
    document.querySelectorAll('.sub-check').forEach(cb => cb.checked = el.checked);
}
function setExportIds() {
    const checked = [...document.querySelectorAll('.sub-check:checked')].map(cb => cb.value);
    document.getElementById('export-ids').value = checked.join(',');
}
</script>
@endsection
