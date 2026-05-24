@extends('layouts.exam')
@section('content')
<div class="min-h-full flex items-center justify-center p-6">
    <div class="max-w-md w-full bg-white text-slate-800 rounded-xl shadow p-8 text-center">
        <h1 class="text-2xl font-bold text-emerald-600">✓ Examen soumis</h1>
        <p class="mt-3 text-sm text-slate-600">Merci {{ $assignment->student_name }}, votre copie a bien été enregistrée.</p>
        <p class="mt-2 text-xs text-slate-400">Soumis le {{ $assignment->submitted_at?->format('d/m/Y H:i:s') }}</p>
    </div>
</div>
@endsection
