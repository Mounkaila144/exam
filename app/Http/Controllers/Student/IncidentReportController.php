<?php

namespace App\Http\Controllers\Student;

use App\Domain\Incident\IncidentType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Student\ReportIncidentRequest;
use App\Models\ExamAssignment;
use App\Services\Security\IncidentRecorder;
use Illuminate\Http\JsonResponse;

class IncidentReportController extends Controller
{
    public function store(ReportIncidentRequest $request, IncidentRecorder $recorder): JsonResponse
    {
        /** @var ExamAssignment $assignment */
        $assignment = $request->attributes->get('examAssignment');

        $type = IncidentType::from($request->string('type'));
        $payload = $request->array('payload') ?: [];

        $incident = $recorder->record($assignment, $type, $payload, $request);

        return response()->json([
            'ok' => true,
            'data' => [
                'incident_id' => $incident->id,
                'locked' => $assignment->fresh()->locked,
            ],
        ]);
    }
}
