@extends('layouts.guest')
@section('content')
<h2 class="text-lg font-medium text-slate-700 mb-4">Inscription professeur</h2>
<form method="POST" action="{{ route('register') }}" class="space-y-4">
    @csrf
    <div>
        <label class="block text-sm font-medium text-slate-700">Nom complet</label>
        <input type="text" name="name" required value="{{ old('name') }}" class="mt-1 w-full rounded-md border-slate-300 shadow-sm"/>
    </div>
    <div>
        <label class="block text-sm font-medium text-slate-700">Email</label>
        <input type="email" name="email" required value="{{ old('email') }}" class="mt-1 w-full rounded-md border-slate-300 shadow-sm"/>
    </div>
    <div>
        <label class="block text-sm font-medium text-slate-700">Mot de passe</label>
        <input type="password" name="password" required class="mt-1 w-full rounded-md border-slate-300 shadow-sm"/>
    </div>
    <div>
        <label class="block text-sm font-medium text-slate-700">Confirmer le mot de passe</label>
        <input type="password" name="password_confirmation" required class="mt-1 w-full rounded-md border-slate-300 shadow-sm"/>
    </div>
    <p class="text-xs text-slate-500">Votre compte sera activé après validation par l'administrateur.</p>
    <button type="submit" class="w-full rounded-md bg-indigo-600 px-4 py-2 text-white font-medium hover:bg-indigo-700">S'inscrire</button>
</form>
<p class="text-sm text-center text-slate-500 mt-4">
    Déjà inscrit ? <a href="{{ route('login') }}" class="text-indigo-600 hover:underline">Se connecter</a>
</p>
@endsection
