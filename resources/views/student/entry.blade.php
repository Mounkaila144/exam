@extends('layouts.exam')
@section('content')
<div class="min-h-full flex items-center justify-center p-6">
    <div class="max-w-2xl w-full bg-white text-slate-800 rounded-xl shadow-xl p-8">
        <h1 class="text-2xl font-bold">{{ $exam->title }}</h1>
        <p class="text-slate-500 mt-1">Étudiant : <strong>{{ $assignment->student_name }}</strong></p>

        <dl class="grid grid-cols-2 gap-4 mt-6 text-sm">
            <div><dt class="text-slate-500">Durée</dt><dd class="font-semibold">{{ $exam->duration_minutes }} min</dd></div>
            <div><dt class="text-slate-500">Questions</dt><dd class="font-semibold">{{ $exam->sections->sum(fn($s) => $s->questions->count()) }}</dd></div>
            @if($exam->opens_at)<div><dt class="text-slate-500">Ouvre</dt><dd>{{ $exam->opens_at->format('d/m/Y H:i') }}</dd></div>@endif
            @if($exam->closes_at)<div><dt class="text-slate-500">Ferme</dt><dd>{{ $exam->closes_at->format('d/m/Y H:i') }}</dd></div>@endif
        </dl>

        @if($exam->description)
            <p class="mt-4 text-sm text-slate-700">{{ $exam->description }}</p>
        @endif

        <div class="mt-6 rounded-md bg-amber-50 border border-amber-200 p-4 text-sm text-amber-900">
            <h2 class="font-semibold mb-1">⚠ Examen sous surveillance technique</h2>
            <ul class="list-disc list-inside space-y-0.5">
                <li>L'examen démarre en plein écran obligatoire.</li>
                <li>Toute sortie d'onglet, fenêtre ou plein écran déclenche un verrouillage automatique.</li>
                <li>Le copier / coller, le clic droit et les raccourcis DevTools sont désactivés.</li>
                <li>Toutes vos actions sont journalisées (timestamp, IP, événements).</li>
                <li>En cas de verrouillage par erreur, le professeur peut vous redonner l'accès.</li>
                <li>Navigateurs supportés : Chrome / Edge / Firefox récents.</li>
            </ul>
        </div>

        <form method="POST" action="{{ url()->signedRoute('student.exam.start', ['token' => $assignment->access_token]) }}" class="mt-6">
            @csrf
            <button type="submit" class="w-full rounded-md bg-indigo-600 px-4 py-3 text-white font-semibold hover:bg-indigo-700">
                {{ $assignment->opened_at ? 'Reprendre l\'examen' : 'Démarrer l\'examen' }}
            </button>
        </form>
    </div>
</div>
@endsection
