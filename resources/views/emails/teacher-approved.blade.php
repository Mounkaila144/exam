@php $url = url('/login'); @endphp
<p>Bonjour {{ $teacher->name }},</p>
<p>Votre compte ExamGuard vient d'être activé par l'administrateur.</p>
<p>Vous pouvez désormais vous connecter et commencer à créer vos examens :</p>
<p><a href="{{ $url }}">Accéder à ExamGuard</a></p>
