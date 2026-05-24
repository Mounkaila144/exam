@extends('layouts.app')
@section('content')
<h1 class="text-2xl font-bold text-slate-800 mb-6">Consommation API Anthropic</h1>

<div class="bg-white rounded-lg shadow-sm border border-slate-200 overflow-hidden">
    <table class="min-w-full divide-y divide-slate-200 text-sm">
        <thead class="bg-slate-50 text-xs font-semibold uppercase text-slate-500">
            <tr>
                <th class="px-4 py-3 text-left">Mois</th>
                <th class="px-4 py-3 text-left">Professeur</th>
                <th class="px-4 py-3 text-right">Appels</th>
                <th class="px-4 py-3 text-right">Tokens in</th>
                <th class="px-4 py-3 text-right">Tokens out</th>
                <th class="px-4 py-3 text-right">Coût</th>
                <th class="px-4 py-3 text-right">Erreurs</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-slate-100">
            @forelse($rows as $row)
                <tr>
                    <td class="px-4 py-3">{{ $row->month }}</td>
                    <td class="px-4 py-3">{{ optional($row->teacher)->name ?? '—' }}</td>
                    <td class="px-4 py-3 text-right">{{ number_format($row->calls) }}</td>
                    <td class="px-4 py-3 text-right">{{ number_format($row->tokens_in) }}</td>
                    <td class="px-4 py-3 text-right">{{ number_format($row->tokens_out) }}</td>
                    <td class="px-4 py-3 text-right">${{ number_format($row->cost_cents / 100, 2) }}</td>
                    <td class="px-4 py-3 text-right text-rose-700">{{ $row->errors }}</td>
                </tr>
            @empty
                <tr><td colspan="7" class="px-4 py-6 text-center text-slate-500">Aucun appel API enregistré.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
@endsection
