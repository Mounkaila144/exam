@extends('layouts.guest')
@section('content')
<h2 class="text-lg font-medium text-slate-700 mb-2">Inscription enregistrée</h2>
<p class="text-sm text-slate-600 mb-4">
    Votre compte a bien été créé. Il sera activé par l'administrateur, qui vous notifiera par email une fois validé.
</p>
<a href="{{ route('login') }}" class="block text-center text-indigo-600 hover:underline text-sm">Retour à la connexion</a>
@endsection
