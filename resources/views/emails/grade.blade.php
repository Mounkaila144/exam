<div style="font-family:system-ui,sans-serif;max-width:600px;margin:0 auto;color:#1a1a1a;">
    <div style="background:#1a1a2e;color:white;padding:20px 24px;border-radius:12px 12px 0 0;">
        <h2 style="margin:0;font-size:18px;">Resultat — Examen Transformation Digitale</h2>
        <p style="margin:4px 0 0;opacity:0.7;font-size:13px;">Master 2 SRS</p>
    </div>
    <div style="background:white;border:1px solid #e5e7eb;padding:24px;">
        <p>Bonjour <strong>{{ $submission->nom }}</strong>,</p>
        <p style="margin-top:12px;">Voici votre note pour l'examen de Transformation Digitale :</p>

        <div style="text-align:center;margin:24px 0;">
            <div style="display:inline-block;background:#eeedfe;border-radius:50%;width:100px;height:100px;line-height:100px;font-size:28px;font-weight:700;color:#3c3489;">
                {{ number_format($submission->note_total, 1) }}
            </div>
            <div style="margin-top:8px;color:#6b7280;font-size:14px;">/ 100</div>
        </div>

        <table style="width:100%;border-collapse:collapse;font-size:14px;margin:20px 0;">
            <tr style="border-bottom:1px solid #e5e7eb;">
                <td style="padding:8px;">Partie I (V/F + QCM)</td>
                <td style="padding:8px;text-align:right;font-weight:600;">{{ number_format($submission->score_p1, 1) }} / 25</td>
            </tr>
            <tr style="border-bottom:1px solid #e5e7eb;">
                <td style="padding:8px;">Partie II (Reponses courtes)</td>
                <td style="padding:8px;text-align:right;font-weight:600;">{{ $submission->note_p2 !== null ? number_format($submission->note_p2, 1) : '—' }} / 25</td>
            </tr>
            <tr style="border-bottom:1px solid #e5e7eb;">
                <td style="padding:8px;">Partie III (Etude de cas)</td>
                <td style="padding:8px;text-align:right;font-weight:600;">{{ $submission->note_p3 !== null ? number_format($submission->note_p3, 1) : '—' }} / 40</td>
            </tr>
            <tr style="border-bottom:1px solid #e5e7eb;">
                <td style="padding:8px;">Partie IV (Synthese)</td>
                <td style="padding:8px;text-align:right;font-weight:600;">{{ $submission->note_p4 !== null ? number_format($submission->note_p4, 1) : '—' }} / 10</td>
            </tr>
            <tr style="background:#eeedfe;">
                <td style="padding:10px;font-weight:600;color:#3c3489;">Total</td>
                <td style="padding:10px;text-align:right;font-weight:700;font-size:16px;color:#3c3489;">{{ number_format($submission->note_total, 1) }} / 100</td>
            </tr>
        </table>

        @if($submission->appreciation)
        <div style="background:#f9f8f5;border-left:3px solid #534ab7;padding:12px 16px;margin-top:20px;border-radius:0 8px 8px 0;">
            <div style="font-size:12px;color:#9ca3af;margin-bottom:4px;">Appreciation</div>
            <div style="font-size:14px;color:#374151;">{{ $submission->appreciation }}</div>
        </div>
        @endif

        @if($submission->detail_notes)
        <h3 style="font-size:15px;margin-top:24px;margin-bottom:12px;">Detail par question</h3>
        @foreach($submission->detail_notes as $qId => $detail)
        <div style="margin-bottom:8px;font-size:13px;">
            <strong>{{ strtoupper($qId) }}</strong>: {{ $detail['note'] ?? '—' }}/{{ $detail['max'] ?? '?' }}
            @if(isset($detail['feedback']))
                — {{ $detail['feedback'] }}
            @endif
        </div>
        @endforeach
        @endif
    </div>
</div>
