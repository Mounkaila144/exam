@extends('layouts.app')
@section('content')
<div class="flex items-center justify-between mb-6">
    <div>
        <a href="{{ route('teacher.exams.edit', $exam) }}" class="text-sm text-indigo-600">&larr; Retour à l'examen</a>
        <h1 class="text-2xl font-bold text-slate-800">Correction — {{ $exam->title }}</h1>
    </div>
    <div class="flex gap-2 text-sm">
        <form method="POST" action="{{ route('teacher.exams.grading.export', $exam) }}">
            @csrf
            <button class="rounded border border-slate-300 px-3 py-1.5 text-slate-700">Exporter pour Claude (copy/paste)</button>
        </form>
        <form method="POST" action="{{ route('teacher.exams.grading.api', $exam) }}" onsubmit="return confirm('Lancer la correction Claude via API ? Coût estimatif visible après envoi.')">
            @csrf
            <button @disabled(! $hasApiKey) class="rounded bg-emerald-600 px-3 py-1.5 text-white disabled:opacity-50">Corriger avec Claude API</button>
        </form>
        <form method="POST" action="{{ route('teacher.exams.grading.send-all', $exam) }}" onsubmit="return confirm('Envoyer toutes les notes notées aux étudiants ?')">
            @csrf
            <button class="rounded bg-indigo-600 px-3 py-1.5 text-white">Envoyer toutes les notes</button>
        </form>
    </div>
</div>

@if(! $hasApiKey)
    <div class="mb-4 rounded-md bg-amber-50 border border-amber-200 px-4 py-2 text-sm text-amber-900">
        L'administrateur n'a pas configuré la clé API Anthropic. Utilisez le mode copy/paste pour le moment.
    </div>
@endif

<form method="POST" action="{{ route('teacher.exams.grading.import', $exam) }}" class="bg-white border border-slate-200 rounded-lg p-4 mb-6">
    @csrf
    <label class="block text-sm font-medium mb-2">Coller le JSON de notes renvoyé par Claude</label>
    <textarea name="grades_json" rows="6" class="w-full font-mono text-xs rounded-md border-slate-300"></textarea>
    <button class="mt-2 rounded bg-indigo-600 px-3 py-1.5 text-white text-sm">Importer les notes</button>
</form>

<div class="bg-white border border-slate-200 rounded-lg overflow-hidden">
    <table class="min-w-full divide-y divide-slate-200 text-sm">
        <thead class="bg-slate-50 text-xs uppercase text-slate-500"><tr>
            <th class="px-4 py-3 text-left">Étudiant</th>
            <th class="px-4 py-3 text-left">Email</th>
            <th class="px-4 py-3 text-left">Statut</th>
            <th class="px-4 py-3 text-right">Auto</th>
            <th class="px-4 py-3 text-right">Manuel</th>
            <th class="px-4 py-3 text-right">Total</th>
            <th class="px-4 py-3"></th>
        </tr></thead>
        <tbody class="divide-y divide-slate-100">
            @forelse($assignments as $a)
                <tr>
                    <td class="px-4 py-3 font-medium">{{ $a->student_name }}</td>
                    <td class="px-4 py-3 text-slate-600">{{ $a->student_email }}</td>
                    <td class="px-4 py-3">{{ $a->submission?->status->value ?? 'non démarré' }}</td>
                    <td class="px-4 py-3 text-right">{{ $a->submission?->auto_score ?? '—' }}</td>
                    <td class="px-4 py-3 text-right">{{ $a->submission?->manual_score ?? '—' }}</td>
                    <td class="px-4 py-3 text-right font-semibold">{{ $a->submission?->total_score ?? '—' }}</td>
                    <td class="px-4 py-3 text-right">
                        @if($a->submission && $a->submission->status->value === 'graded')
                            <form method="POST" action="{{ route('teacher.submissions.send-grade', $a->submission) }}">
                                @csrf
                                <button class="text-indigo-600 text-xs hover:underline">Envoyer la note</button>
                            </form>
                        @elseif($a->submission?->status->value === 'sent')
                            <span class="text-xs text-emerald-600">Envoyée le {{ $a->submission->sent_at?->format('d/m H:i') }}</span>
                        @endif
                    </td>
                </tr>
            @empty
                <tr><td colspan="7" class="px-4 py-6 text-center text-slate-500">Aucune copie pour le moment.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
@endsection
