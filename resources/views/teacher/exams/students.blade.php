@extends('layouts.app')
@section('content')
<div class="flex items-center justify-between mb-6">
    <div>
        <a href="{{ route('teacher.exams.edit', $exam) }}" class="text-sm text-indigo-600">&larr; Retour à l'examen</a>
        <h1 class="text-2xl font-bold text-slate-800">Étudiants — {{ $exam->title }}</h1>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <div class="lg:col-span-2">
        <div class="bg-white border border-slate-200 rounded-lg overflow-hidden">
            <table class="min-w-full divide-y divide-slate-200 text-sm">
                <thead class="bg-slate-50 text-xs uppercase text-slate-500"><tr>
                    <th class="px-4 py-3 text-left">Nom</th>
                    <th class="px-4 py-3 text-left">Email</th>
                    <th class="px-4 py-3 text-left">Matricule</th>
                    <th class="px-4 py-3 text-left">Groupe</th>
                    <th class="px-4 py-3 text-left">Statut</th>
                    <th class="px-4 py-3"></th>
                </tr></thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($assignments as $a)
                    <tr>
                        <td class="px-4 py-3 font-medium">{{ $a->student_name }}</td>
                        <td class="px-4 py-3 text-slate-600">{{ $a->student_email }}</td>
                        <td class="px-4 py-3">{{ $a->student_matricule }}</td>
                        <td class="px-4 py-3">{{ $a->student_group }}</td>
                        <td class="px-4 py-3">{{ $a->statusLabel() }}</td>
                        <td class="px-4 py-3 text-right">
                            @if(! $a->opened_at)
                            <form method="POST" action="{{ route('teacher.students.destroy', $a) }}" onsubmit="return confirm('Retirer cet étudiant ?')">
                                @csrf @method('DELETE')
                                <button class="text-rose-600 text-xs">Retirer</button>
                            </form>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="6" class="px-4 py-6 text-center text-slate-500">Aucun étudiant inscrit.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        {{ $assignments->links() }}
    </div>

    <aside class="space-y-4">
        <form method="POST" action="{{ route('teacher.exams.students.store', $exam) }}" class="bg-white border border-slate-200 rounded-lg p-4 text-sm space-y-2">
            @csrf
            <h2 class="text-md font-semibold">Ajouter manuellement</h2>
            <input type="text" name="student_name" required placeholder="Nom" class="w-full rounded border-slate-300"/>
            <input type="email" name="student_email" required placeholder="Email" class="w-full rounded border-slate-300"/>
            <input type="text" name="student_matricule" placeholder="Matricule (optionnel)" class="w-full rounded border-slate-300"/>
            <input type="text" name="student_group" placeholder="Groupe (optionnel)" class="w-full rounded border-slate-300"/>
            <button class="rounded bg-indigo-600 px-3 py-1.5 text-white">Ajouter</button>
        </form>

        <form method="POST" action="{{ route('teacher.exams.students.import', $exam) }}" enctype="multipart/form-data" class="bg-white border border-slate-200 rounded-lg p-4 text-sm space-y-2">
            @csrf
            <h2 class="text-md font-semibold">Importer un CSV</h2>
            <p class="text-xs text-slate-500">Colonnes attendues : <code>name,email,matricule,groupe</code></p>
            <input type="file" name="csv" accept=".csv,text/csv" required class="w-full text-sm"/>
            <button class="rounded bg-indigo-600 px-3 py-1.5 text-white">Importer</button>
        </form>
    </aside>
</div>
@endsection
