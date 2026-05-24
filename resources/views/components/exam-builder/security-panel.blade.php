@php
    /** @var \App\Models\Exam $exam */
    $s = array_merge(\App\Models\Exam::DEFAULT_SECURITY_SETTINGS, $exam->security_settings ?? []);
    $disabled = ! $exam->isDraft();
@endphp
<form method="POST" action="{{ route('teacher.exams.security.update', $exam) }}" class="bg-white border border-slate-200 rounded-lg p-4 text-sm">
    @csrf @method('PATCH')
    <h2 class="text-lg font-semibold mb-3">Sécurité de l'examen</h2>
    @if($disabled)
        <p class="text-xs text-amber-700 mb-3">L'examen est publié — paramètres figés.</p>
    @endif

    @foreach([
        'enforce_fullscreen' => 'Plein écran forcé',
        'lock_on_first_offense' => 'Verrouiller à la 1ʳᵉ infraction majeure',
        'block_copy_paste' => 'Bloquer copier / coller',
        'block_right_click' => 'Bloquer clic droit',
        'block_devtools_shortcuts' => 'Bloquer raccourcis DevTools (F12, Ctrl+Shift+I…)',
        'detect_devtools_open' => 'Détecter l\'ouverture des DevTools',
        'lock_on_ip_change' => 'Verrouiller si l\'IP change',
    ] as $key => $label)
        <label class="flex items-center justify-between py-1.5 border-b border-slate-100 last:border-0">
            <span>{{ $label }}</span>
            <input type="checkbox" name="{{ $key }}" value="1" {{ ($s[$key] ?? false) ? 'checked' : '' }} {{ $disabled ? 'disabled' : '' }} class="rounded border-slate-300"/>
        </label>
    @endforeach

    <label class="flex items-center justify-between py-2">
        <span>Verrouiller après N infractions (si pas 1ʳᵉ)</span>
        <input type="number" min="1" max="99" name="lock_on_offense_count" value="{{ $s['lock_on_offense_count'] ?? 3 }}" {{ $disabled ? 'disabled' : '' }} class="w-20 rounded border-slate-300"/>
    </label>

    @if(! $disabled)
        <button class="mt-3 rounded bg-indigo-600 px-3 py-1.5 text-white">Enregistrer</button>
    @endif
</form>
