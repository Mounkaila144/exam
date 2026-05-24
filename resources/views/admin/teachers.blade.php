@extends('layouts.app')
@section('content')
<div class="flex items-center justify-between mb-6">
    <h1 class="text-2xl font-bold text-slate-800">Professeurs</h1>
    <div class="flex gap-2 text-sm">
        @foreach(['pending' => 'En attente', 'active' => 'Actifs', 'disabled' => 'Désactivés', 'all' => 'Tous'] as $key => $label)
            <a href="{{ route('admin.teachers.index', ['status' => $key]) }}"
               class="px-3 py-1 rounded {{ $activeStatus === $key ? 'bg-indigo-600 text-white' : 'bg-white border border-slate-300 text-slate-700' }}">
                {{ $label }}
            </a>
        @endforeach
    </div>
</div>

<div class="bg-white rounded-lg shadow-sm border border-slate-200 overflow-hidden">
    <table class="min-w-full divide-y divide-slate-200">
        <thead class="bg-slate-50 text-left text-xs font-semibold uppercase text-slate-500">
            <tr>
                <th class="px-4 py-3">Nom</th>
                <th class="px-4 py-3">Email</th>
                <th class="px-4 py-3">Inscrit le</th>
                <th class="px-4 py-3">Statut</th>
                <th class="px-4 py-3 text-right">Actions</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-slate-100 text-sm">
            @forelse($teachers as $teacher)
            <tr>
                <td class="px-4 py-3 font-medium">{{ $teacher->name }}</td>
                <td class="px-4 py-3 text-slate-600">{{ $teacher->email }}</td>
                <td class="px-4 py-3 text-slate-500">{{ $teacher->created_at->format('d/m/Y') }}</td>
                <td class="px-4 py-3">
                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs {{ $teacher->status->colorClass() }}">
                        {{ $teacher->status->label() }}
                    </span>
                </td>
                <td class="px-4 py-3 text-right">
                    <div class="flex justify-end gap-2">
                        @if($teacher->status->value !== 'active')
                            <form method="POST" action="{{ route('admin.teachers.approve') }}">
                                @csrf
                                <input type="hidden" name="teacher_id" value="{{ $teacher->id }}"/>
                                <button class="text-green-700 hover:underline">Activer</button>
                            </form>
                        @endif
                        @if($teacher->status->value !== 'disabled')
                            <form method="POST" action="{{ route('admin.teachers.disable') }}">
                                @csrf
                                <input type="hidden" name="teacher_id" value="{{ $teacher->id }}"/>
                                <button class="text-rose-600 hover:underline">Désactiver</button>
                            </form>
                        @endif
                    </div>
                </td>
            </tr>
            @empty
            <tr><td colspan="5" class="px-4 py-6 text-center text-slate-500">Aucun professeur dans cette catégorie.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>

<div class="mt-4">{{ $teachers->links() }}</div>
@endsection
