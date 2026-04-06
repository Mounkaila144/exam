<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Connexion — Exam Platform</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: system-ui, -apple-system, sans-serif; background: #f5f4f0; color: #1a1a1a; min-height: 100vh; display: flex; align-items: center; justify-content: center; }

        .login-container { width: 100%; max-width: 400px; padding: 1rem; }

        .login-header { text-align: center; margin-bottom: 2rem; }
        .login-header h1 { font-size: 22px; font-weight: 600; color: #1a1a2e; margin-bottom: 4px; }
        .login-header p { font-size: 14px; color: #6b7280; }

        .login-card { background: white; border-radius: 12px; border: 0.5px solid #e0ded6; padding: 2rem; }

        .form-group { margin-bottom: 1.25rem; }
        .form-group label { display: block; font-size: 13px; font-weight: 500; color: #374151; margin-bottom: 6px; }
        .form-group input { width: 100%; padding: 10px 14px; border: 0.5px solid #d1d0c9; border-radius: 8px; font-size: 14px; background: #f9f8f5; color: #1a1a1a; outline: none; transition: border-color 0.2s; }
        .form-group input:focus { border-color: #534ab7; background: white; }
        .form-group input.error { border-color: #e24b4a; background: #fcebeb; }

        .remember-row { display: flex; align-items: center; gap: 8px; margin-bottom: 1.5rem; }
        .remember-row input { width: 16px; height: 16px; cursor: pointer; }
        .remember-row label { font-size: 13px; color: #6b7280; cursor: pointer; }

        .btn-login { width: 100%; padding: 12px; background: #1a1a2e; color: white; border: none; border-radius: 8px; font-size: 14px; font-weight: 500; cursor: pointer; transition: background 0.2s; }
        .btn-login:hover { background: #2d2d4e; }

        .alert-error { background: #fcebeb; color: #791f1f; border: 0.5px solid #e8a3a3; padding: 10px 14px; border-radius: 8px; font-size: 13px; margin-bottom: 1rem; }
    </style>
</head>
<body>

<div class="login-container">
    <div class="login-header">
        <h1>Exam Platform</h1>
        <p>Transformation Digitale — Administration</p>
    </div>

    <div class="login-card">
        @if($errors->any())
            <div class="alert-error">
                {{ $errors->first() }}
            </div>
        @endif

        <form method="POST" action="{{ route('login') }}">
            @csrf
            <div class="form-group">
                <label for="email">Email</label>
                <input type="email" id="email" name="email" value="{{ old('email') }}" placeholder="admin@exemple.com" required autofocus class="{{ $errors->has('email') ? 'error' : '' }}">
            </div>

            <div class="form-group">
                <label for="password">Mot de passe</label>
                <input type="password" id="password" name="password" placeholder="Votre mot de passe" required>
            </div>

            <div class="remember-row">
                <input type="checkbox" id="remember" name="remember">
                <label for="remember">Se souvenir de moi</label>
            </div>

            <button type="submit" class="btn-login">Se connecter</button>
        </form>
    </div>
</div>

</body>
</html>
