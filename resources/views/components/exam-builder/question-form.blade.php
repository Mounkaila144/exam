@php /** @var \App\Models\ExamSection $section */ @endphp
<form method="POST" action="{{ route('teacher.sections.questions.store', $section) }}" class="space-y-3 mt-3 p-3 border border-dashed border-slate-300 rounded"
      x-data="{ type: 'vf', choices: [{key:'A', label:''},{key:'B', label:''}] }">
    @csrf

    <div class="flex gap-3">
        <select name="type" x-model="type" class="rounded-md border-slate-300 text-sm">
            <option value="vf">Vrai / Faux</option>
            <option value="qcm">QCM</option>
            <option value="short">Réponse courte</option>
            <option value="essay">Dissertation</option>
            <option value="code">Code</option>
            <option value="file_upload">Dépôt de fichier</option>
        </select>
        <input type="number" step="0.5" min="0" name="points" value="1" required class="w-24 rounded-md border-slate-300 text-sm" placeholder="Points"/>
    </div>

    <textarea name="prompt" rows="2" required placeholder="Énoncé de la question" class="w-full rounded-md border-slate-300 text-sm"></textarea>
    <textarea name="bareme_text" rows="2" placeholder="Barème / consignes pour Claude (optionnel)" class="w-full rounded-md border-slate-300 text-sm"></textarea>

    <template x-if="type === 'vf'">
        <div class="flex gap-4 text-sm items-center">
            <label class="flex items-center gap-1"><input type="radio" name="correct" value="VRAI"/> Vrai</label>
            <label class="flex items-center gap-1"><input type="radio" name="correct" value="FAUX"/> Faux</label>
            <input type="number" step="0.1" name="penalty" placeholder="Pénalité (optionnel)" class="w-32 rounded-md border-slate-300 text-sm"/>
        </div>
    </template>

    <template x-if="type === 'qcm'">
        <div class="space-y-2">
            <template x-for="(choice, idx) in choices" :key="idx">
                <div class="flex items-center gap-2">
                    <input type="text" :name="'choices['+idx+'][key]'" x-model="choice.key" class="w-16 rounded-md border-slate-300 text-sm"/>
                    <input type="text" :name="'choices['+idx+'][label]'" x-model="choice.label" required class="flex-1 rounded-md border-slate-300 text-sm" placeholder="Libellé"/>
                    <label class="text-xs flex items-center gap-1"><input type="radio" name="correct" :value="choice.key"/> Bonne réponse</label>
                    <button type="button" @click="choices.splice(idx,1)" class="text-rose-500 text-xs">✕</button>
                </div>
            </template>
            <button type="button" @click="choices.length < 6 && choices.push({key:String.fromCharCode(65+choices.length), label:''})" class="text-xs text-indigo-600">+ Choix</button>
        </div>
    </template>

    <template x-if="type === 'code'">
        <input type="text" name="language_hint" placeholder="Langage (ex: python, javascript)" class="rounded-md border-slate-300 text-sm w-48"/>
    </template>

    <template x-if="type === 'essay'">
        <div class="flex gap-2">
            <input type="number" name="min_words" placeholder="Min mots" class="w-28 rounded-md border-slate-300 text-sm"/>
            <input type="number" name="max_words" placeholder="Max mots" class="w-28 rounded-md border-slate-300 text-sm"/>
        </div>
    </template>

    <template x-if="type === 'file_upload'">
        <div class="flex gap-2 items-center">
            <input type="text" name="accepted_extensions[]" placeholder="pdf, docx, png..." class="rounded-md border-slate-300 text-sm flex-1"/>
            <input type="number" min="1" max="50" name="max_size_mb" value="5" class="w-24 rounded-md border-slate-300 text-sm"/>
            <span class="text-xs text-slate-500">Mo</span>
        </div>
    </template>

    <button class="rounded bg-indigo-600 px-3 py-1.5 text-sm text-white">Ajouter la question</button>
</form>
