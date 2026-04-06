@extends('layouts.app')

@section('title', $submission->nom)

@section('content')

<div style="display:flex;gap:1rem;align-items:center;margin-bottom:1.5rem;">
    <a href="{{ route('admin.dashboard') }}" class="btn btn-outline">&larr; Retour</a>
    <h2 style="font-size:18px;">{{ $submission->nom }} — {{ $submission->matricule }}</h2>
    @if($submission->status === 'submitted')
        <span class="badge badge-pending">En attente</span>
    @elseif($submission->status === 'graded')
        <span class="badge badge-graded">Corrigee</span>
    @else
        <span class="badge badge-sent">Note envoyee</span>
    @endif
</div>

<!-- Student Info -->
<div class="card">
    <div class="card-header"><h2>Informations de l'etudiant</h2></div>
    <div class="card-body">
        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:1rem;">
            <div>
                <div style="font-size:12px;color:#6b7280;">Nom</div>
                <div style="font-weight:500;">{{ $submission->nom }}</div>
            </div>
            <div>
                <div style="font-size:12px;color:#6b7280;">Email</div>
                <div>{{ $submission->email }}</div>
            </div>
            <div>
                <div style="font-size:12px;color:#6b7280;">Matricule</div>
                <div>{{ $submission->matricule }}</div>
            </div>
            <div>
                <div style="font-size:12px;color:#6b7280;">Groupe</div>
                <div>{{ $submission->groupe ?: '—' }}</div>
            </div>
            <div>
                <div style="font-size:12px;color:#6b7280;">Soumis le</div>
                <div>{{ $submission->created_at->format('d/m/Y H:i') }}</div>
            </div>
        </div>
    </div>
</div>

<!-- Scores -->
<div class="stats-grid">
    <div class="stat-card purple">
        <div class="stat-value">{{ number_format($submission->score_p1, 1) }}</div>
        <div class="stat-label">Partie I (/25)</div>
    </div>
    <div class="stat-card">
        <div class="stat-value" style="color:{{ $submission->note_p2 !== null ? '#1a1a2e' : '#d1d0c9' }}">
            {{ $submission->note_p2 !== null ? number_format($submission->note_p2, 1) : '—' }}
        </div>
        <div class="stat-label">Partie II (/25)</div>
    </div>
    <div class="stat-card">
        <div class="stat-value" style="color:{{ $submission->note_p3 !== null ? '#1a1a2e' : '#d1d0c9' }}">
            {{ $submission->note_p3 !== null ? number_format($submission->note_p3, 1) : '—' }}
        </div>
        <div class="stat-label">Partie III (/40)</div>
    </div>
    <div class="stat-card">
        <div class="stat-value" style="color:{{ $submission->note_p4 !== null ? '#1a1a2e' : '#d1d0c9' }}">
            {{ $submission->note_p4 !== null ? number_format($submission->note_p4, 1) : '—' }}
        </div>
        <div class="stat-label">Partie IV (/10)</div>
    </div>
    <div class="stat-card green">
        <div class="stat-value" style="color:{{ $submission->note_total !== null ? '#27500a' : '#d1d0c9' }}">
            {{ $submission->note_total !== null ? number_format($submission->note_total, 1) : '—' }}
        </div>
        <div class="stat-label">Total (/100)</div>
    </div>
</div>

@if($submission->appreciation)
<div class="card">
    <div class="card-header"><h2>Appreciation</h2></div>
    <div class="card-body">{{ $submission->appreciation }}</div>
</div>
@endif

<!-- VF Answers -->
<div class="card">
    <div class="card-header"><h2>Partie I.A — Vrai / Faux ({{ number_format($submission->score_vf, 1) }}/10)</h2></div>
    <div class="card-body">
        @php
            $vfCorrect = ['vf1'=>'FAUX','vf2'=>'FAUX','vf3'=>'VRAI','vf4'=>'VRAI','vf5'=>'FAUX','vf6'=>'VRAI','vf7'=>'FAUX','vf8'=>'FAUX','vf9'=>'VRAI','vf10'=>'VRAI'];
            $vfAnswers = $submission->answers_vf ?? [];
        @endphp
        <table>
            <thead><tr><th>Q</th><th>Reponse</th><th>Correct</th><th>Resultat</th></tr></thead>
            <tbody>
            @foreach($vfCorrect as $id => $correct)
                @php $ans = $vfAnswers[$id] ?? null; @endphp
                <tr>
                    <td>{{ strtoupper($id) }}</td>
                    <td>{{ $ans ?: '—' }}</td>
                    <td>{{ $correct }}</td>
                    <td>
                        @if($ans === $correct)
                            <span style="color:#27500a;font-weight:500;">+1</span>
                        @elseif($ans)
                            <span style="color:#791f1f;font-weight:500;">-0.5</span>
                        @else
                            <span style="color:#9ca3af;">0</span>
                        @endif
                    </td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
</div>

<!-- QCM Answers -->
<div class="card">
    <div class="card-header"><h2>Partie I.B — QCM ({{ number_format($submission->score_qcm, 1) }}/15)</h2></div>
    <div class="card-body">
        @php
            $qcmCorrect = ['qcm1'=>'C','qcm2'=>'B','qcm3'=>'C','qcm4'=>'C','qcm5'=>'C'];
            $qcmAnswers = $submission->answers_qcm ?? [];
        @endphp
        <table>
            <thead><tr><th>Q</th><th>Reponse</th><th>Correct</th><th>Resultat</th></tr></thead>
            <tbody>
            @foreach($qcmCorrect as $id => $correct)
                @php $ans = $qcmAnswers[$id] ?? null; @endphp
                <tr>
                    <td>{{ strtoupper($id) }}</td>
                    <td>{{ $ans ?: '—' }}</td>
                    <td>{{ $correct }}</td>
                    <td>
                        @if($ans === $correct)
                            <span style="color:#27500a;font-weight:500;">+3</span>
                        @elseif($ans)
                            <span style="color:#791f1f;font-weight:500;">0</span>
                        @else
                            <span style="color:#9ca3af;">0</span>
                        @endif
                    </td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
</div>

<!-- Open Answers -->
@php
    $openAnswers = $submission->open_answers ?? [];
    $details = $submission->detail_notes ?? [];
    $questions = [
        ['id'=>'s1','title'=>'II.1 — Numerisation vs Digitalisation vs Transformation Digitale','pts'=>5],
        ['id'=>'s2','title'=>'II.2 — Security by Design','pts'=>5],
        ['id'=>'s3','title'=>'II.3 — IAM et moindre privilege','pts'=>5],
        ['id'=>'s4','title'=>'II.4 — RGPD','pts'=>5],
        ['id'=>'s5','title'=>'II.5 — Lean Startup','pts'=>5],
        ['id'=>'c1','title'=>'III.1 — Audit de maturite digitale','pts'=>8],
        ['id'=>'c2','title'=>'III.2 — Risques critiques','pts'=>10],
        ['id'=>'c3','title'=>'III.3 — Architecture SI securisee','pts'=>12],
        ['id'=>'c4','title'=>'III.4 — Roadmap 24 mois','pts'=>10],
        ['id'=>'q4','title'=>'IV — Synthese et argumentation','pts'=>10],
    ];
@endphp

<div class="card">
    <div class="card-header"><h2>Parties II, III, IV — Reponses ouvertes</h2></div>
    <div class="card-body">
        @foreach($questions as $q)
        <div style="margin-bottom:1.5rem;padding-bottom:1.5rem;border-bottom:0.5px solid #e0ded6;">
            <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:0.5rem;">
                <strong style="font-size:14px;">{{ $q['title'] }} ({{ $q['pts'] }} pts)</strong>
                @if(isset($details[$q['id']]))
                    <span style="font-weight:600;color:#534ab7;background:#eeedfe;padding:2px 10px;border-radius:6px;font-size:13px;">
                        {{ $details[$q['id']]['note'] ?? '—' }} / {{ $q['pts'] }}
                    </span>
                @endif
            </div>
            <div style="background:#f9f8f5;border-radius:8px;padding:0.75rem 1rem;font-size:13px;white-space:pre-wrap;line-height:1.6;">{{ $openAnswers[$q['id']] ?? '(pas de reponse)' }}</div>
            @if(isset($details[$q['id']]['feedback']))
                <div style="margin-top:0.5rem;font-size:12px;color:#534ab7;background:#eeedfe;padding:6px 10px;border-radius:6px;">
                    {{ $details[$q['id']]['feedback'] }}
                </div>
            @endif
        </div>
        @endforeach
    </div>
</div>

@endsection
