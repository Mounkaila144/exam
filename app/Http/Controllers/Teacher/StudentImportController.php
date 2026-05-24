<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Models\Exam;
use App\Services\Exam\AssignmentTokenGenerator;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class StudentImportController extends Controller
{
    public function __construct(private readonly AssignmentTokenGenerator $tokens)
    {
    }

    public function store(Exam $exam, Request $request): RedirectResponse
    {
        $this->authorize('update', $exam);

        $request->validate([
            'csv' => ['required', 'file', 'mimes:csv,txt', 'max:2048'],
        ]);

        $file = $request->file('csv');
        $handle = fopen($file->getRealPath(), 'r');
        if (! $handle) {
            return back()->withErrors(['csv' => 'Impossible de lire le fichier.']);
        }

        $header = fgetcsv($handle);
        $header = array_map(fn ($h) => strtolower(trim((string) $h)), $header ?: []);
        $required = ['name', 'email'];
        foreach ($required as $col) {
            if (! in_array($col, $header, true)) {
                fclose($handle);

                return back()->withErrors(['csv' => "Colonne manquante : {$col}"]);
            }
        }

        $imported = 0;
        $skipped = 0;
        $errors = [];
        $line = 1;

        DB::transaction(function () use ($exam, $handle, $header, &$imported, &$skipped, &$errors, &$line) {
            while (($row = fgetcsv($handle)) !== false) {
                $line++;
                $assoc = array_combine($header, array_pad($row, count($header), null));

                $validator = Validator::make($assoc, [
                    'name' => ['required', 'string', 'max:255'],
                    'email' => ['required', 'email', 'max:255'],
                ]);

                if ($validator->fails()) {
                    $errors[] = "ligne {$line} : ".$validator->errors()->first();
                    continue;
                }

                if ($exam->assignments()->where('student_email', $assoc['email'])->exists()) {
                    $skipped++;
                    continue;
                }

                $exam->assignments()->create([
                    'student_name' => $assoc['name'],
                    'student_email' => $assoc['email'],
                    'student_matricule' => $assoc['matricule'] ?? null,
                    'student_group' => $assoc['groupe'] ?? ($assoc['group'] ?? null),
                    'access_token' => $this->tokens->makeToken(),
                ]);
                $imported++;
            }
        });

        fclose($handle);

        $message = "$imported étudiants importés, $skipped déjà présents.";
        if (! empty($errors)) {
            $message .= ' '.count($errors).' lignes ignorées : '.implode(' | ', array_slice($errors, 0, 5));
        }

        return back()->with('success', $message);
    }
}
