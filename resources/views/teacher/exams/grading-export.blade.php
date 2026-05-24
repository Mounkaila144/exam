@extends('layouts.app')
@section('content')
<div class="flex items-center justify-between mb-6">
    <h1 class="text-2xl font-bold text-slate-800">Export Claude — {{ $exam->title }}</h1>
    <a href="{{ route('teacher.exams.grading', $exam) }}" class="text-sm text-indigo-600">&larr; Retour</a>
</div>

<p class="text-sm text-slate-600 mb-3">
    Copiez ce markdown et collez-le dans une conversation Claude (<a href="https://claude.ai" class="text-indigo-600" target="_blank">claude.ai</a>).
    Récupérez le JSON renvoyé par Claude et collez-le dans l'écran "Correction" pour appliquer les notes.
</p>

<div x-data="{
    copy() {
        navigator.clipboard.writeText(this.$refs.md.value);
        this.copied = true;
        setTimeout(() => this.copied = false, 1500);
    },
    copied: false
}" class="space-y-2">
    <button @click="copy()" class="rounded bg-indigo-600 px-3 py-1.5 text-white text-sm">
        <span x-show="!copied">Copier le markdown</span>
        <span x-show="copied">✓ Copié !</span>
    </button>
    <textarea x-ref="md" rows="20" readonly class="w-full font-mono text-xs rounded-md border-slate-300">{{ $markdown }}</textarea>
</div>
@endsection
