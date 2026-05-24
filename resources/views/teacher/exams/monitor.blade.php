@extends('layouts.app')
@section('content')
<div class="flex items-center justify-between mb-6">
    <div>
        <a href="{{ route('teacher.exams.edit', $exam) }}" class="text-sm text-indigo-600">&larr; Retour à l'examen</a>
        <h1 class="text-2xl font-bold text-slate-800">Monitor live — {{ $exam->title }}</h1>
    </div>
</div>

<div x-data="liveMonitor({{ Js::from([
    'examId' => $exam->id,
    'reverbAppKey' => env('REVERB_APP_KEY'),
    'reverbHost' => env('REVERB_HOST'),
    'reverbPort' => env('REVERB_PORT'),
    'reverbScheme' => env('REVERB_SCHEME'),
    'initialAssignments' => $assignments->map(fn($a) => [
        'id' => $a->id,
        'name' => $a->student_name,
        'matricule' => $a->student_matricule,
        'status' => $a->statusLabel(),
        'incidentsCount' => $a->incidents_count,
        'lockedReason' => $a->locked_reason,
        'openedAt' => $a->opened_at?->format('H:i:s'),
    ])->values(),
    'initialIncidents' => $incidents,
    'unlockUrlPattern' => route('teacher.assignments.unlock', ['assignment' => '__ID__']),
]) }})" x-init="init()" class="grid grid-cols-1 lg:grid-cols-3 gap-6">

    <div class="lg:col-span-2 bg-white border border-slate-200 rounded-lg p-4">
        <h2 class="text-lg font-semibold mb-3">Étudiants</h2>
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
            <template x-for="a in assignments" :key="a.id">
                <div class="border rounded-md p-3 text-sm"
                     :class="{
                         'border-slate-200 bg-slate-50': a.status === 'en attente',
                         'border-emerald-300 bg-emerald-50': a.status === 'en cours',
                         'border-rose-400 bg-rose-50': a.status === 'verrouillé',
                         'border-indigo-300 bg-indigo-50': a.status === 'soumis',
                     }">
                    <p class="font-semibold" x-text="a.name"></p>
                    <p class="text-xs text-slate-500" x-text="a.matricule || ''"></p>
                    <div class="flex items-center justify-between mt-2">
                        <span class="text-xs px-1.5 py-0.5 rounded bg-white border" x-text="a.status"></span>
                        <span class="text-xs text-rose-700" x-text="a.incidentsCount + ' inc.'"></span>
                    </div>
                    <template x-if="a.status === 'verrouillé'">
                        <button class="mt-2 w-full text-xs rounded bg-emerald-600 text-white px-2 py-1 hover:bg-emerald-700" @click="unlock(a)">
                            Redonner l'accès
                        </button>
                    </template>
                </div>
            </template>
        </div>
    </div>

    <aside class="bg-white border border-slate-200 rounded-lg p-4">
        <h2 class="text-lg font-semibold mb-3">Flux d'incidents</h2>
        <ul class="divide-y divide-slate-100 text-sm max-h-[600px] overflow-y-auto">
            <template x-for="i in incidents" :key="i.id">
                <li class="py-2">
                    <div class="flex justify-between items-baseline">
                        <span class="font-medium" x-text="i.studentName"></span>
                        <span class="text-xs text-slate-400" x-text="formatTime(i.occurredAt)"></span>
                    </div>
                    <span class="text-xs px-1.5 py-0.5 rounded"
                          :class="i.severity === 'critical' ? 'bg-rose-100 text-rose-800' : (i.severity === 'warning' ? 'bg-yellow-100 text-yellow-800' : 'bg-slate-100 text-slate-700')"
                          x-text="i.typeLabel"></span>
                </li>
            </template>
            <li x-show="incidents.length === 0" class="py-4 text-center text-slate-400 text-xs">Aucun incident pour le moment.</li>
        </ul>
    </aside>
</div>

@vite(['resources/js/live-monitor.js'])
@endsection
