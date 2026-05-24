@php /** @var \App\Models\Question $question */ @endphp
<div class="border border-slate-100 rounded p-3 mb-2 bg-slate-50">
    <div class="flex items-center justify-between text-xs text-slate-500 mb-1">
        <span class="font-semibold uppercase">{{ $question->type->label() }} · {{ $question->points }} pts</span>
        <form method="POST" action="{{ route('teacher.questions.destroy', $question) }}" onsubmit="return confirm('Supprimer cette question ?')">
            @csrf @method('DELETE')
            <button class="text-rose-600 hover:underline">Supprimer</button>
        </form>
    </div>
    <p class="text-sm">{{ $question->prompt }}</p>
    @if($question->choices)
        <ul class="text-xs mt-2 list-disc list-inside text-slate-600">
            @foreach($question->choices as $choice)
                <li>{{ $choice['key'] }} — {{ $choice['label'] }}</li>
            @endforeach
        </ul>
    @endif
</div>
