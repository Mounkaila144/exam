@php $url = url('/admin/teachers?status=pending'); @endphp
<p>Bonjour,</p>
<p>Un nouveau professeur s'est inscrit sur ExamGuard :</p>
<ul>
    <li><strong>Nom :</strong> {{ $teacher->name }}</li>
    <li><strong>Email :</strong> {{ $teacher->email }}</li>
    <li><strong>Date :</strong> {{ $teacher->created_at->format('d/m/Y H:i') }}</li>
</ul>
<p><a href="{{ $url }}">Valider l'inscription depuis la console admin</a></p>
