<!DOCTYPE html>
<html lang="fr" class="h-full bg-slate-50">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title ?? 'ExamGuard' }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="h-full text-slate-800 antialiased flex items-center justify-center">
<div class="w-full max-w-md p-8 bg-white rounded-xl shadow-md">
    <h1 class="text-2xl font-bold text-indigo-600 mb-6 text-center">ExamGuard</h1>
    @if(session('success'))
        <div class="mb-4 rounded-md bg-green-50 border border-green-200 px-3 py-2 text-sm text-green-800">{{ session('success') }}</div>
    @endif
    @if($errors->any())
        <div class="mb-4 rounded-md bg-rose-50 border border-rose-200 px-3 py-2 text-sm text-rose-800">
            <ul class="list-disc list-inside">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif
    @yield('content')
    {{ $slot ?? '' }}
</div>
</body>
</html>
