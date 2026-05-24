@extends('layouts.app')
@section('content')
<h1 class="text-2xl font-bold text-slate-800 mb-6">Console administrateur</h1>

<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
    <div class="bg-white rounded-lg shadow-sm p-4 border border-slate-200">
        <h3 class="text-xs font-semibold uppercase text-slate-500">Professeurs</h3>
        <div class="mt-2 text-sm">
            <div class="flex justify-between"><span>En attente</span><span class="font-semibold text-yellow-700">{{ $teachersWidget['pending'] }}</span></div>
            <div class="flex justify-between"><span>Actifs</span><span class="font-semibold text-green-700">{{ $teachersWidget['active'] }}</span></div>
            <div class="flex justify-between"><span>Désactivés</span><span class="font-semibold text-rose-700">{{ $teachersWidget['disabled'] }}</span></div>
        </div>
    </div>
    <div class="bg-white rounded-lg shadow-sm p-4 border border-slate-200">
        <h3 class="text-xs font-semibold uppercase text-slate-500">Examens publiés ce mois</h3>
        <p class="mt-2 text-3xl font-bold text-indigo-600">{{ $examsPublishedThisMonth }}</p>
    </div>
    <div class="bg-white rounded-lg shadow-sm p-4 border border-slate-200">
        <h3 class="text-xs font-semibold uppercase text-slate-500">Consommation API ce mois</h3>
        <p class="mt-2 text-3xl font-bold text-indigo-600">${{ number_format(($apiUsage->cost_cents ?? 0) / 100, 2) }}</p>
        <p class="text-xs text-slate-500">{{ number_format($apiUsage->tokens_in ?? 0) }} in / {{ number_format($apiUsage->tokens_out ?? 0) }} out</p>
    </div>
    <div class="bg-white rounded-lg shadow-sm p-4 border border-slate-200">
        <h3 class="text-xs font-semibold uppercase text-slate-500">Incidents ce mois</h3>
        <p class="mt-2 text-3xl font-bold text-rose-600">{{ $incidentsThisMonth }}</p>
    </div>
</div>
@endsection
