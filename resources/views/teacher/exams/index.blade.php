@extends('layouts.app')
@section('content')
<div class="flex items-center justify-between mb-6">
    <h1 class="text-2xl font-bold text-slate-800">Mes examens</h1>
    <a href="{{ route('teacher.exams.create') }}" class="rounded-md bg-indigo-600 px-4 py-2 text-white font-medium hover:bg-indigo-700">Nouvel examen</a>
</div>

<div class="bg-white rounded-lg shadow-sm border border-slate-200 overflow-hidden">
    <table class="min-w-full divide-y divide-slate-200 text-sm">
        <thead class="bg-slate-50 text-xs font-semibold uppercase text-slate-500">
            <tr>
                <th class="px-4 py-3 text-left">Titre</th>
                <th class="px-4 py-3 text-left">Statut</th>
                <th class="px-4 py-3 text-right">Questions</th>
                <th class="px-4 py-3 text-right">Étudiants</th>
                <th class="px-4 py-3 text-right">Modifié</th>
                <th class="px-4 py-3 text-right">Actions</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-slate-100">
            @forelse($exams as $exam)
            <tr>
                <td class="px-4 py-3 font-medium">
                    <a href="{{ route('teacher.exams.edit', $exam) }}" class="hover:text-indigo-600">{{ $exam->title }}</a>
                </td>
                <td class="px-4 py-3">{{ $exam->status->label() }}</td>
                <td class="px-4 py-3 text-right">{{ $exam->questions_count }}</td>
                <td class="px-4 py-3 text-right">{{ $exam->assignments_count }}</td>
                <td class="px-4 py-3 text-right text-slate-500">{{ $exam->updated_at->diffForHumans() }}</td>
                <td class="px-4 py-3 text-right space-x-2">
                    <a href="{{ route('teacher.exams.edit', $exam) }}" class="text-indigo-600 hover:underline">Éditer</a>
                    <a href="{{ route('teacher.exams.students.index', $exam) }}" class="text-indigo-600 hover:underline">Étudiants</a>
                    @if($exam->status->value === 'published' || $exam->status->value === 'closed')
                        <a href="{{ route('teacher.exams.monitor', $exam) }}" class="text-indigo-600 hover:underline">Monitor</a>
                        <a href="{{ route('teacher.exams.grading', $exam) }}" class="text-indigo-600 hover:underline">Correction</a>
                    @endif
                </td>
            </tr>
            @empty
            <tr><td colspan="6" class="px-4 py-6 text-center text-slate-500">Aucun examen pour le moment.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>

<div class="mt-4">{{ $exams->links() }}</div>
@endsection
