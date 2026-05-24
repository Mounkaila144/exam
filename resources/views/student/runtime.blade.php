@extends('layouts.exam', ['bodyClass' => 'exam-runtime'])
@section('content')
<div x-data="examRuntime({{ Js::from([
    'token' => $assignment->access_token,
    'remainingSeconds' => $remaining_seconds,
    'security' => $security_settings,
    'assignmentId' => $assignment->id,
    'urls' => [
        'heartbeat' => route('student.api.heartbeat', ['token' => $assignment->access_token]),
        'answers' => route('student.api.answers', ['token' => $assignment->access_token]),
        'incidents' => route('student.api.incidents', ['token' => $assignment->access_token]),
        'submit' => url()->signedRoute('student.exam.submit', ['token' => $assignment->access_token]),
        'reverbAppKey' => env('REVERB_APP_KEY'),
        'reverbHost' => env('REVERB_HOST'),
        'reverbPort' => env('REVERB_PORT'),
        'reverbScheme' => env('REVERB_SCHEME'),
    ],
    'initialAnswers' => $submission->answers ?? [],
]) }})" x-init="init()" class="min-h-full flex flex-col">

    <header class="bg-slate-800 px-6 py-3 flex items-center justify-between border-b border-slate-700">
        <div>
            <h1 class="text-lg font-semibold text-slate-50">{{ $exam->title }}</h1>
            <p class="text-xs text-slate-400">{{ $assignment->student_name }}</p>
        </div>
        <div class="flex items-center gap-4">
            <span class="text-2xl font-mono" :class="remaining <= 60 ? 'text-rose-400' : 'text-emerald-400'" x-text="formatTime(remaining)">--:--</span>
            <span class="text-xs px-2 py-1 rounded" :class="saving ? 'bg-amber-500 text-slate-900' : 'bg-emerald-500/30 text-emerald-200'" x-text="saving ? 'Sauvegarde…' : 'Sauvegardé'"></span>
        </div>
    </header>

    <main class="flex-1 p-6 overflow-y-auto bg-white text-slate-800">
        <form @submit.prevent="submitExam" class="max-w-3xl mx-auto space-y-8">
            @foreach($exam->sections as $section)
            <section>
                <h2 class="text-xl font-bold border-b border-slate-200 pb-2">{{ $section->title }}</h2>
                @if($section->instructions)
                    <p class="text-sm text-slate-500 mt-1">{{ $section->instructions }}</p>
                @endif

                <div class="mt-4 space-y-6">
                    @foreach($section->questions as $question)
                    <div class="border border-slate-200 rounded-md p-4">
                        <p class="font-semibold">{{ $question->prompt }} <span class="text-xs text-slate-400 font-normal">({{ $question->points }} pts)</span></p>

                        @switch($question->type->value)
                            @case('vf')
                                <div class="flex gap-4 mt-3 text-sm">
                                    <label class="flex items-center gap-1"><input type="radio" name="q{{ $question->id }}" value="VRAI" :checked="answers['{{ $question->id }}'] === 'VRAI'" @change="setAnswer({{ $question->id }}, 'VRAI')"/> Vrai</label>
                                    <label class="flex items-center gap-1"><input type="radio" name="q{{ $question->id }}" value="FAUX" :checked="answers['{{ $question->id }}'] === 'FAUX'" @change="setAnswer({{ $question->id }}, 'FAUX')"/> Faux</label>
                                </div>
                                @break
                            @case('qcm')
                                <div class="mt-3 space-y-1 text-sm">
                                    @foreach($question->choices ?? [] as $choice)
                                        <label class="flex items-center gap-1">
                                            <input type="radio" name="q{{ $question->id }}" value="{{ $choice['key'] }}" :checked="answers['{{ $question->id }}'] === '{{ $choice['key'] }}'" @change="setAnswer({{ $question->id }}, '{{ $choice['key'] }}')"/>
                                            <span><strong>{{ $choice['key'] }}.</strong> {{ $choice['label'] }}</span>
                                        </label>
                                    @endforeach
                                </div>
                                @break
                            @case('short')
                                <input type="text" :value="answers['{{ $question->id }}'] || ''" @input.debounce.500ms="setAnswer({{ $question->id }}, $event.target.value)" class="mt-3 w-full rounded-md border-slate-300 text-sm"/>
                                @break
                            @case('essay')
                            @case('code')
                                <textarea rows="8" :value="answers['{{ $question->id }}'] || ''" @input.debounce.500ms="setAnswer({{ $question->id }}, $event.target.value)" class="mt-3 w-full rounded-md border-slate-300 font-mono text-sm"></textarea>
                                @break
                            @case('file_upload')
                                <p class="text-xs text-amber-700 mt-3">Upload de fichier — fonctionnalité disponible (à compléter selon configuration S3).</p>
                                @break
                        @endswitch
                    </div>
                    @endforeach
                </div>
            </section>
            @endforeach

            <div class="flex justify-end pt-6 border-t border-slate-200">
                <button type="submit" class="rounded-md bg-emerald-600 px-6 py-3 text-white font-semibold hover:bg-emerald-700" :disabled="submitting" @click="confirmSubmit($event)">
                    Soumettre l'examen
                </button>
            </div>
        </form>
    </main>
</div>
@endsection
