@extends('layouts.app')

@section('title', 'Export pour Claude')

@section('content')

<div class="card">
    <div class="card-header" style="display:flex;align-items:center;justify-content:space-between;">
        <div>
            <h2>Export pour Claude — {{ $submissions->count() }} copies</h2>
            <p style="font-size:12px;color:#6b7280;margin-top:2px;">Copiez tout le texte ci-dessous et collez-le dans le chat Claude</p>
        </div>
        <div class="actions">
            <button class="btn btn-purple" onclick="copyToClipboard()">Copier tout</button>
            <a href="{{ route('admin.dashboard') }}" class="btn btn-outline">Retour</a>
        </div>
    </div>
    <div class="card-body">
        <div style="background:#eeedfe;border-radius:8px;padding:1rem;margin-bottom:1rem;font-size:13px;color:#3c3489;">
            <strong>Mode d'emploi :</strong><br>
            1. Cliquez sur "Copier tout" ci-dessus<br>
            2. Allez sur <strong>claude.ai</strong> et collez le contenu dans le chat<br>
            3. Claude va corriger toutes les copies et retourner un JSON<br>
            4. Copiez le JSON retourne par Claude<br>
            5. Revenez ici, cliquez sur "Importer les notes" dans le dashboard et collez le JSON
        </div>
        <textarea class="export-area" id="export-text" readonly>{{ $output }}</textarea>
    </div>
</div>

@endsection

@section('scripts')
<script>
function copyToClipboard() {
    const el = document.getElementById('export-text');
    el.select();
    el.setSelectionRange(0, 99999999);
    navigator.clipboard.writeText(el.value).then(() => {
        const btn = event.target;
        btn.textContent = 'Copie !';
        btn.style.background = '#3b6d11';
        setTimeout(() => { btn.textContent = 'Copier tout'; btn.style.background = ''; }, 2000);
    });
}
</script>
@endsection
