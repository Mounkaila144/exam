@extends('layouts.app')
@section('content')
<h1 class="text-2xl font-bold text-slate-800 mb-6">Paramètres plateforme</h1>

<div class="bg-white rounded-lg shadow-sm border border-slate-200 p-6 max-w-2xl">
    <h2 class="text-lg font-semibold text-slate-700">Clé API Anthropic</h2>
    <p class="text-sm text-slate-500 mt-1">
        Cette clé est partagée par tous les professeurs et utilisée pour la correction Claude automatique.
        Le coût est imputé à votre compte Anthropic.
    </p>

    <form method="POST" action="{{ route('admin.settings.update') }}" class="mt-6 space-y-4">
        @csrf
        @method('PUT')
        <div>
            <label class="block text-sm font-medium text-slate-700">Clé API</label>
            <input type="password" name="api_key" placeholder="{{ $hasKey ? '•••••• (clé configurée)' : 'sk-ant-...' }}" required
                   class="mt-1 w-full rounded-md border-slate-300 shadow-sm" autocomplete="off"/>
        </div>
        <div>
            <label class="block text-sm font-medium text-slate-700">Modèle Claude</label>
            <input type="text" name="model" value="{{ old('model', $model) }}" class="mt-1 w-full rounded-md border-slate-300 shadow-sm"/>
        </div>
        <div class="flex gap-2">
            <button class="rounded-md bg-indigo-600 px-4 py-2 text-white font-medium hover:bg-indigo-700">Enregistrer & tester</button>
            @if($hasKey)
                <form method="POST" action="{{ route('admin.settings.test') }}">
                    @csrf
                    <button class="rounded-md border border-slate-300 px-4 py-2 text-slate-700">Tester la clé actuelle</button>
                </form>
            @endif
        </div>
    </form>
</div>
@endsection
