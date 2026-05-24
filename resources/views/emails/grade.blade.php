@php $a = $submission->assignment; $exam = $a->exam; @endphp
<p>Bonjour {{ $a->student_name }},</p>
<p>Votre note pour l'examen <strong>{{ $exam->title }}</strong> est disponible :</p>
<p style="font-size:24px;font-weight:bold;color:#4f46e5;">
    {{ number_format((float) ($submission->total_score ?? ($submission->auto_score + ($submission->manual_score ?? 0))), 2) }}
</p>
@if($submission->claude_grade_details['appreciation'] ?? null)
    <p><em>{{ $submission->claude_grade_details['appreciation'] }}</em></p>
@endif

@if(! empty($submission->claude_grade_details['details']))
    <h3>Détail par question</h3>
    <ul>
        @foreach($submission->claude_grade_details['details'] as $qid => $detail)
            <li>
                <strong>{{ $qid }}</strong> — {{ $detail['note'] ?? '?' }}/{{ $detail['max'] ?? '?' }}
                @if(! empty($detail['feedback']))<br><em>{{ $detail['feedback'] }}</em>@endif
            </li>
        @endforeach
    </ul>
@endif
