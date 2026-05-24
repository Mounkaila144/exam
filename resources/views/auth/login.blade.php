@extends('layouts.guest')
@section('content')
<h2 class="text-lg font-medium text-slate-700 mb-4">Connexion</h2>
<form method="POST" action="{{ route('login') }}" class="space-y-4">
    @csrf
    <div>
        <label class="block text-sm font-medium text-slate-700">Email</label>
        <input type="email" name="email" required autofocus value="{{ old('email') }}" class="mt-1 w-full rounded-md border-slate-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"/>
    </div>
    <div>
        <label class="block text-sm font-medium text-slate-700">Mot de passe</label>
        <input type="password" name="password" required class="mt-1 w-full rounded-md border-slate-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"/>
    </div>
    <label class="flex items-center gap-2 text-sm text-slate-600">
        <input type="checkbox" name="remember" value="1" class="rounded border-slate-300"/> Se souvenir de moi
    </label>
    <button type="submit" class="w-full rounded-md bg-indigo-600 px-4 py-2 text-white font-medium hover:bg-indigo-700">Connexion</button>
</form>
<p class="text-sm text-center text-slate-500 mt-4">
    Pas de compte ? <a href="{{ route('register') }}" class="text-indigo-600 hover:underline">S'inscrire</a>
</p>
@endsection
