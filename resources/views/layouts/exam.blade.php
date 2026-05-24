<!DOCTYPE html>
<html lang="fr" class="h-full bg-slate-900 text-slate-50">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? 'Examen — ExamGuard' }}</title>
    @vite(['resources/css/app.css', 'resources/js/exam-runtime.js'])
    <style>
        body.exam-runtime { user-select: none; -webkit-user-select: none; }
    </style>
</head>
<body class="h-full antialiased {{ $bodyClass ?? '' }}">
    {{ $slot ?? '' }}
    @yield('content')
</body>
</html>
