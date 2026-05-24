# ExamGuard — Setup d'implémentation

Toutes les stories des 5 epics ont été codées (squelette Laravel + Blade + Alpine + Reverb + Web Push + Claude API). Le code est en place mais **les dépendances doivent être installées et la base initialisée** avant de pouvoir lancer le serveur.

## 1. Bring-up local (Windows / bash)

```bash
# Installer les dépendances
composer install
npm install

# Configurer l'environnement
cp .env.example .env
php artisan key:generate

# Démarrer Postgres + Redis + MinIO
docker compose up -d

# Initialiser la base (PostgreSQL)
php artisan migrate --seed

# Générer les clés VAPID (Web Push)
php artisan vapid:generate

# Créer le compte administrateur
php artisan admin:make --email=mounkaila144@gmail.com --name="Mounkaila"

# Lancer le stack dev (serveur + queue + reverb + vite)
composer dev
```

Le seeder par défaut crée déjà un admin `admin@examguard.local / password` ; remplacez-le ou laissez-le pour le dev.

## 2. Architecture livrée

### Epic 1 — Plateforme
- Migration `alter_users_add_role_and_status` + enums `UserRole`, `UserStatus`.
- Middlewares `EnsureAdminRole`, `EnsureTeacherIsActive`.
- Commands `admin:make`, `vapid:generate`.
- Auth Breeze-equivalent en Blade pur (`LoginController`, `RegisterController`).
- Mailables : `NewTeacherSignedUpMailable`, `TeacherApprovedMailable`.
- Admin: dashboard, teachers list, platform settings, api usage.
- `PlatformApiKeyVault` (Crypt::encryptString + live ping).

### Epic 2 — Builder
- Tables : `exams`, `exam_sections`, `questions`, `exam_assignments`.
- Enums `ExamStatus`, `QuestionType`, `SubmissionStatus`.
- `ExamBuilderController` — CRUD examens, sections (AJAX), questions (6 types).
- `ExamStudentController` + `StudentImportController` (CSV).
- `ExamPublisherService` — validation + `SendAssignmentEmailJob`.
- `AssignmentTokenGenerator` — signed URL Laravel.
- Command `exam:close-expired` schedulée toutes les 5 min.
- Vues `teacher/exams/{index,create,edit,students}.blade.php` + components `exam-builder/*`.

### Epic 3 — Runtime étudiant
- Tables : `submissions`, `incidents`, `attachments`.
- Middlewares `ResolveExamAssignment`, `ExamIsLive` (gère locked/submitted/window).
- Controllers `Student\ExamEntryController`, `ExamRuntimeController`, `ExamAnswerController`, `ExamHeartbeatController`, `ExamSubmitController`, `IncidentReportController`.
- `ExamTimerService` (serveur = source de vérité).
- `IncidentRecorder` + `ExamLockService` (verrouillage idempotent + broadcast).
- `resources/js/exam-runtime.js` — Alpine runtime complet : autosave debounce, heartbeat, fullscreen, visibility/blur, copy/paste/right-click/devtools shortcuts, devtools heuristic, localStorage fallback.
- Vues `student/{entry,runtime,submitted,exam_locked,exam_not_available,exam_already_submitted}.blade.php` + `layouts/exam.blade.php`.

### Epic 4 — Live + notifications
- Events broadcastable : `StudentJoined`, `StudentSubmitted`, `StudentLocked`, `StudentLockedForStudent`, `IncidentRecorded`, `ExamUnlockedByTeacher`.
- `routes/channels.php` — autorisation `exam.{id}.monitor` (teacher only).
- `ExamMonitorController` + view + `resources/js/live-monitor.js` (Echo + Reverb).
- `AssignmentController::unlock` + Policy `ExamAssignmentPolicy::unlock`.
- Tables : `push_subscriptions`.
- `PushSubscriptionController` + `WebPushChannel` + `WebPushService` (`minishlink/web-push`).
- Notification `IncidentRaisedNotification` (canaux `WebPushChannel` + `mail`) avec throttling 1 mail/min par assignment.
- Service worker `public/sw.js` + `resources/js/push-subscribe.js`.

### Epic 5 — Correction & notes
- Domain pur `AutoGrader` + `ScoreCalculator` (testable hors Laravel).
- `AutoGradingService` — appelé synchrone à la soumission.
- `ClaudeExportFormatter` (markdown) + vue `grading-export`.
- `GradeImportService` (parse JSON → `manual_score`, `total_score`, `claude_grade_details`).
- `ClaudeApiClient` (Guzzle wrapper, timeout configurable, headers Anthropic).
- `DispatchClaudeGradingJob` (queue Redis, tries=3, backoff [30,120,300], log `api_usage_logs`).
- `GradeMailable` + `SendGradeEmailJob` + `SendBulkGradeEmailsJob`.
- `GradingController` orchestre tout (export, import, dispatch API, send individual/bulk).
- Admin `ApiUsageController` agrège par mois + par prof.

## 3. Tests fournis

```bash
php artisan test
```

- `tests/Unit/Domain/AutoGraderTest.php` — V/F + QCM nominal + cas vides.
- `tests/Feature/Security/ApiKeyVaultTest.php` — round-trip chiffrement.
- `tests/Feature/Security/ExamLockTest.php` — 1ʳᵉ infraction critique → lock, idempotence, unlock journalisé.
- `tests/Feature/Security/TokenSignatureTest.php` — signed URL valide / tampered URL rejetée.

À ajouter (gap connu) : `ExamBuilderTest::test_can_create_all_question_types`, `StudentImportTest`, `IncidentReportTest`, `GradingControllerTest::test_import_grades_from_claude_json`.

## 4. Gaps documentés (rappel des décisions du PM)

- **Politique de rétention RGPD** non rédigée (NFR9) — à faire avant déploiement réel.
- **Quotas API par prof** non implémentés (V2).
- **Tests E2E Playwright** non livrés (V2 — tests Feature PHPUnit couvrent le MVP).
- **Mode Safari** : best-effort, Fullscreen API capricieuse.
- **Upload S3 effectif** des fichiers étudiants (`file_upload`) : UI laisse un placeholder ; le pipeline backend (signed URL → S3) reste à brancher.

## 5. Limites du scaffolding initial

- `composer install` et `npm install` n'ont **pas** été exécutés (environnement local non muté). Le code compile une fois les dépendances installées.
- Aucune migration n'a été exécutée sur la base existante : `migrate:fresh` ou `migrate` requis au premier démarrage.
- L'ancien examen "Transformation Digitale M2 SRS" et ses contrôleurs ont été **supprimés** (Story 1.1). Le pattern auto-correction et le formattage Claude sont préservés dans `AutoGrader` / `ClaudeExportFormatter`.
