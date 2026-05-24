<!DOCTYPE html>
<html lang="fr" class="h-full bg-slate-50">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? 'ExamGuard' }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="h-full text-slate-800 antialiased">

@auth
    <header class="bg-white border-b border-slate-200">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 flex items-center justify-between h-16">
            <div class="flex items-center gap-8">
                <a href="{{ route('home') }}" class="text-xl font-semibold text-indigo-600">ExamGuard</a>
                <nav class="flex items-center gap-6 text-sm font-medium">
                    @if(auth()->user()->isAdmin())
                        <a href="{{ route('admin.dashboard') }}" class="hover:text-indigo-600">Tableau de bord</a>
                        <a href="{{ route('admin.teachers.index') }}" class="hover:text-indigo-600">Professeurs</a>
                        <a href="{{ route('admin.settings.edit') }}" class="hover:text-indigo-600">Paramètres</a>
                        <a href="{{ route('admin.usage.index') }}" class="hover:text-indigo-600">Consommation API</a>
                    @else
                        <a href="{{ route('teacher.dashboard') }}" class="hover:text-indigo-600">Tableau de bord</a>
                        <a href="{{ route('teacher.exams.index') }}" class="hover:text-indigo-600">Mes examens</a>
                    @endif
                </nav>
            </div>
            <div class="flex items-center gap-4 text-sm">
                <span class="text-slate-500">{{ auth()->user()->name }}</span>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="text-slate-600 hover:text-rose-600">Déconnexion</button>
                </form>
            </div>
        </div>
    </header>
@endauth

<main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    @if(session('success'))
        <div class="mb-4 rounded-md bg-green-50 border border-green-200 px-4 py-2 text-sm text-green-800">
            {{ session('success') }}
        </div>
    @endif
    @if(session('error'))
        <div class="mb-4 rounded-md bg-rose-50 border border-rose-200 px-4 py-2 text-sm text-rose-800">
            {{ session('error') }}
        </div>
    @endif
    @if($errors->any())
        <div class="mb-4 rounded-md bg-rose-50 border border-rose-200 px-4 py-2 text-sm text-rose-800">
            <ul class="list-disc list-inside">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    {{ $slot ?? '' }}
    @yield('content')
</main>

</body>
</html>
