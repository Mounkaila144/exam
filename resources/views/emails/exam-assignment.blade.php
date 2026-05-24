<p>Bonjour {{ $assignment->student_name }},</p>
<p>Vous êtes inscrit(e) à l'examen <strong>{{ $exam->title }}</strong>.</p>
<ul>
    <li>Durée : {{ $exam->duration_minutes }} minutes</li>
    @if($exam->opens_at)
        <li>Ouverture : {{ $exam->opens_at->format('d/m/Y H:i') }}</li>
    @endif
    @if($exam->closes_at)
        <li>Fermeture : {{ $exam->closes_at->format('d/m/Y H:i') }}</li>
    @endif
</ul>
<p><a href="{{ $url }}" style="display:inline-block;padding:12px 24px;background:#4f46e5;color:#fff;text-decoration:none;border-radius:6px;">Accéder à mon examen</a></p>
<p style="color:#64748b;font-size:12px;">Ce lien est strictement personnel et à usage unique. Ne le partagez pas.</p>
