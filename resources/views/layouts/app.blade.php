<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Exam Platform') — Administration</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: system-ui, -apple-system, sans-serif; background: #f5f4f0; color: #1a1a1a; font-size: 15px; line-height: 1.6; }

        .navbar { background: #1a1a2e; color: white; padding: 0.75rem 1.5rem; display: flex; align-items: center; justify-content: space-between; position: sticky; top: 0; z-index: 100; }
        .navbar h1 { font-size: 15px; font-weight: 500; }
        .navbar-links { display: flex; gap: 1rem; align-items: center; }
        .navbar-links a { color: rgba(255,255,255,0.7); text-decoration: none; font-size: 13px; padding: 4px 12px; border-radius: 6px; transition: all 0.2s; }
        .navbar-links a:hover, .navbar-links a.active { color: white; background: rgba(255,255,255,0.15); }

        .container { max-width: 1200px; margin: 0 auto; padding: 2rem 1rem; }

        .card { background: white; border-radius: 12px; border: 0.5px solid #e0ded6; overflow: hidden; margin-bottom: 1.5rem; }
        .card-header { padding: 1rem 1.5rem; border-bottom: 0.5px solid #e0ded6; background: #f9f8f5; }
        .card-header h2 { font-size: 16px; font-weight: 500; color: #374151; }
        .card-body { padding: 1.5rem; }

        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 1rem; margin-bottom: 1.5rem; }
        .stat-card { background: white; border-radius: 12px; border: 0.5px solid #e0ded6; padding: 1.25rem; text-align: center; }
        .stat-value { font-size: 28px; font-weight: 600; color: #1a1a2e; }
        .stat-label { font-size: 12px; color: #6b7280; text-transform: uppercase; letter-spacing: 0.5px; margin-top: 4px; }
        .stat-card.purple { border-left: 3px solid #534ab7; }
        .stat-card.green { border-left: 3px solid #3b6d11; }
        .stat-card.orange { border-left: 3px solid #ba7517; }
        .stat-card.blue { border-left: 3px solid #185fa5; }

        table { width: 100%; border-collapse: collapse; font-size: 14px; }
        th { text-align: left; padding: 0.75rem 1rem; background: #f9f8f5; border-bottom: 0.5px solid #e0ded6; font-weight: 500; color: #6b7280; font-size: 12px; text-transform: uppercase; letter-spacing: 0.5px; }
        td { padding: 0.75rem 1rem; border-bottom: 0.5px solid #e0ded6; }
        tr:hover { background: #faf9f7; }

        .badge { display: inline-block; padding: 3px 10px; border-radius: 12px; font-size: 11px; font-weight: 500; }
        .badge-pending { background: #faeeda; color: #854f0b; }
        .badge-graded { background: #eeedfe; color: #3c3489; }
        .badge-sent { background: #eaf3de; color: #27500a; }

        .btn { display: inline-flex; align-items: center; gap: 6px; padding: 8px 16px; border-radius: 8px; font-size: 13px; font-weight: 500; cursor: pointer; border: none; transition: all 0.15s; text-decoration: none; }
        .btn-primary { background: #1a1a2e; color: white; }
        .btn-primary:hover { background: #2d2d4e; }
        .btn-success { background: #3b6d11; color: white; }
        .btn-success:hover { background: #2d5a0a; }
        .btn-purple { background: #534ab7; color: white; }
        .btn-purple:hover { background: #3c3489; }
        .btn-outline { background: white; color: #374151; border: 0.5px solid #d1d0c9; }
        .btn-outline:hover { border-color: #534ab7; color: #534ab7; }
        .btn-sm { padding: 4px 10px; font-size: 12px; }
        .btn-danger { background: #e24b4a; color: white; }

        .actions { display: flex; gap: 0.5rem; flex-wrap: wrap; }

        .alert { padding: 0.875rem 1rem; border-radius: 8px; font-size: 13px; margin-bottom: 1rem; display: flex; gap: 10px; align-items: center; }
        .alert-success { background: #eaf3de; color: #27500a; border: 0.5px solid #b2d99c; }
        .alert-error { background: #fcebeb; color: #791f1f; border: 0.5px solid #e8a3a3; }

        .export-area { width: 100%; min-height: 400px; font-family: 'Cascadia Code', 'Fira Code', monospace; font-size: 12px; padding: 1rem; border: 0.5px solid #d1d0c9; border-radius: 8px; background: #faf9f7; color: #1a1a1a; resize: vertical; line-height: 1.5; }

        .checkbox-cell { width: 40px; text-align: center; }
        .checkbox-cell input { width: 16px; height: 16px; cursor: pointer; }

        .toolbar { display: flex; gap: 0.75rem; align-items: center; flex-wrap: wrap; margin-bottom: 1rem; }

        .modal-overlay { display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.4); z-index: 200; align-items: center; justify-content: center; }
        .modal-overlay.show { display: flex; }
        .modal { background: white; border-radius: 12px; padding: 2rem; max-width: 700px; width: 90%; max-height: 80vh; overflow-y: auto; }
        .modal h3 { font-size: 18px; margin-bottom: 1rem; }

        .import-area { width: 100%; min-height: 300px; font-family: 'Cascadia Code', monospace; font-size: 12px; padding: 1rem; border: 0.5px solid #d1d0c9; border-radius: 8px; background: #faf9f7; resize: vertical; }

        @media (max-width: 768px) {
            .stats-grid { grid-template-columns: repeat(2, 1fr); }
            .toolbar { flex-direction: column; align-items: stretch; }
        }
    </style>
</head>
<body>

<div class="navbar">
    <h1>Exam Platform — Transformation Digitale</h1>
    <div class="navbar-links">
        <a href="{{ route('admin.dashboard') }}" class="{{ request()->routeIs('admin.dashboard') ? 'active' : '' }}">Tableau de bord</a>
        <span style="color:rgba(255,255,255,0.5);font-size:13px;">{{ Auth::user()->name }}</span>
        <form action="{{ route('logout') }}" method="POST" style="display:inline;">
            @csrf
            <button type="submit" style="background:rgba(255,255,255,0.1);border:none;color:rgba(255,255,255,0.7);padding:4px 12px;border-radius:6px;font-size:13px;cursor:pointer;">Deconnexion</button>
        </form>
    </div>
</div>

<div class="container">
    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="alert alert-error">{{ session('error') }}</div>
    @endif

    @yield('content')
</div>

@yield('scripts')
</body>
</html>
