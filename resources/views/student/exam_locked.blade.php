@extends('layouts.exam')
@section('content')
<div class="min-h-full flex items-center justify-center p-6">
    <div class="max-w-md w-full bg-white text-slate-800 rounded-xl shadow p-8 text-center">
        <h1 class="text-2xl font-bold text-rose-600">⏸ Examen en pause</h1>
        <p class="mt-3 text-sm text-slate-600">Votre copie a été automatiquement mise en pause suite à un incident.</p>
        @isset($assignment)
            @if($assignment->locked_reason)
                <p class="mt-1 text-xs text-slate-500">Motif : {{ $assignment->locked_reason }}</p>
            @endif
        @endisset
        <p class="mt-4 text-sm text-slate-700">Le professeur a été notifié et pourra réautoriser l'accès depuis son dashboard.</p>
        <button class="mt-6 w-full rounded-md bg-indigo-600 px-4 py-2 text-white text-sm hover:bg-indigo-700" onclick="window.location.reload()">
            Vérifier le statut
        </button>
    </div>
</div>
@endsection
