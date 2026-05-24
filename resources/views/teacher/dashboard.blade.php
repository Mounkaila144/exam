@extends('layouts.app')
@section('content')
<div class="flex items-center justify-between mb-6">
    <h1 class="text-2xl font-bold text-slate-800">Mon tableau de bord</h1>
    <a href="{{ route('teacher.exams.create') }}" class="rounded-md bg-indigo-600 px-4 py-2 text-white font-medium hover:bg-indigo-700">Nouvel examen</a>
</div>

<div class="bg-white rounded-lg shadow-sm border border-slate-200 p-6">
    <h2 class="text-lg font-semibold text-slate-700 mb-4">Examens récents</h2>
    @if($exams->isEmpty())
        <p class="text-slate-500 text-sm">Aucun examen pour le moment. Créez votre premier examen.</p>
    @else
        <ul class="divide-y divide-slate-100 text-sm">
            @foreach($exams as $exam)
            <li class="py-3 flex items-center justify-between">
                <div>
                    <a href="{{ route('teacher.exams.edit', $exam) }}" class="font-medium text-slate-800 hover:text-indigo-600">{{ $exam->title }}</a>
                    <p class="text-xs text-slate-500">{{ $exam->status->label() }} · {{ $exam->duration_minutes }} min · {{ $exam->assignments_count }} étudiants</p>
                </div>
                <span class="text-xs text-slate-400">{{ $exam->updated_at->diffForHumans() }}</span>
            </li>
            @endforeach
        </ul>
    @endif
</div>
@endsection
