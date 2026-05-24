@extends('layouts.app')
@section('content')
<h1 class="text-2xl font-bold text-slate-800 mb-6">Nouvel examen</h1>

<form method="POST" action="{{ route('teacher.exams.store') }}" class="bg-white p-6 rounded-lg shadow-sm border border-slate-200 max-w-2xl space-y-4">
    @csrf
    <div>
        <label class="block text-sm font-medium text-slate-700">Titre</label>
        <input type="text" name="title" required value="{{ old('title') }}" class="mt-1 w-full rounded-md border-slate-300 shadow-sm"/>
    </div>
    <div>
        <label class="block text-sm font-medium text-slate-700">Description</label>
        <textarea name="description" rows="3" class="mt-1 w-full rounded-md border-slate-300 shadow-sm">{{ old('description') }}</textarea>
    </div>
    <div>
        <label class="block text-sm font-medium text-slate-700">Durée (minutes)</label>
        <input type="number" min="1" name="duration_minutes" required value="{{ old('duration_minutes', 60) }}" class="mt-1 w-full rounded-md border-slate-300 shadow-sm"/>
    </div>
    <div class="grid grid-cols-2 gap-4">
        <div>
            <label class="block text-sm font-medium text-slate-700">Ouverture (optionnel)</label>
            <input type="datetime-local" name="opens_at" value="{{ old('opens_at') }}" class="mt-1 w-full rounded-md border-slate-300 shadow-sm"/>
        </div>
        <div>
            <label class="block text-sm font-medium text-slate-700">Fermeture (optionnel)</label>
            <input type="datetime-local" name="closes_at" value="{{ old('closes_at') }}" class="mt-1 w-full rounded-md border-slate-300 shadow-sm"/>
        </div>
    </div>
    <button class="rounded-md bg-indigo-600 px-4 py-2 text-white font-medium hover:bg-indigo-700">Créer l'examen</button>
</form>
@endsection
