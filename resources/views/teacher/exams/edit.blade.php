@extends('layouts.app')
@section('content')
<div class="flex items-center justify-between mb-6">
    <h1 class="text-2xl font-bold text-slate-800">{{ $exam->title }} <span class="ml-2 text-sm text-slate-500">[{{ $exam->status->label() }}]</span></h1>
    <div class="flex gap-2">
        <a href="{{ route('teacher.exams.students.index', $exam) }}" class="rounded border border-slate-300 px-3 py-1.5 text-sm">Étudiants</a>
        @if($exam->status->value === 'draft')
            <form method="POST" action="{{ route('teacher.exams.publish', $exam) }}" onsubmit="return confirm('Publier l\'examen et envoyer les liens aux étudiants ?')">
                @csrf
                <button class="rounded bg-green-600 px-3 py-1.5 text-sm text-white">Publier</button>
            </form>
        @endif
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <div class="lg:col-span-2 space-y-4">
        <div class="bg-white border border-slate-200 rounded-lg p-4">
            <h2 class="text-lg font-semibold mb-2">Sections</h2>
            <div id="sections">
                @foreach($exam->sections as $section)
                <div class="border border-slate-200 rounded-md p-4 mb-3" data-section-id="{{ $section->id }}">
                    <div class="flex items-center justify-between mb-2">
                        <h3 class="font-semibold">{{ $section->title }}</h3>
                        <form method="POST" action="{{ route('teacher.sections.destroy', $section) }}" onsubmit="return confirm('Supprimer cette section ?')">
                            @csrf @method('DELETE')
                            <button class="text-rose-600 text-sm hover:underline">Supprimer</button>
                        </form>
                    </div>
                    @if($section->instructions)
                        <p class="text-sm text-slate-500 mb-3">{{ $section->instructions }}</p>
                    @endif

                    @foreach($section->questions as $question)
                        @include('components.exam-builder.question-display', ['question' => $question])
                    @endforeach

                    <details class="mt-3">
                        <summary class="text-sm text-indigo-600 cursor-pointer">+ Ajouter une question</summary>
                        @include('components.exam-builder.question-form', ['section' => $section])
                    </details>
                </div>
                @endforeach
            </div>

            <form method="POST" action="{{ route('teacher.exams.sections.store', $exam) }}" class="mt-4 flex gap-2">
                @csrf
                <input type="text" name="title" required placeholder="Titre de la nouvelle section" class="flex-1 rounded-md border-slate-300"/>
                <button class="rounded bg-indigo-600 px-3 py-1.5 text-sm text-white">Ajouter</button>
            </form>
        </div>
    </div>

    <aside class="space-y-4">
        @include('components.exam-builder.security-panel', ['exam' => $exam])
    </aside>
</div>

@vite(['resources/js/exam-builder.js'])
@endsection
