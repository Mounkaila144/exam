# Addendum — Brief ExamGuard

Détail technique et tactique qui n'a pas sa place dans le brief mais qui doit voyager avec lui vers le PRD / l'architecture.

## 1. Stratégie anti-triche détaillée

### Couche A — Prévention côté navigateur (best effort)

| Vecteur | Technique | Limite connue |
|---|---|---|
| Changement d'onglet / fenêtre | `Page Visibility API` (event `visibilitychange`) + `window.blur` | Contournable avec un 2ᵉ écran ou un téléphone |
| Sortie du plein écran | `Fullscreen API` + `fullscreenchange` event, re-enter automatique 1× puis verrou | Refus utilisateur initial → impossible de démarrer l'examen |
| Copier / coller | `oncopy`, `oncut`, `onpaste`, `selectstart` désactivés, sélection CSS bloquée | Capture d'écran reste possible |
| Clic droit | `contextmenu` event preventDefault | Trivial à contourner via DevTools |
| Raccourcis sensibles | `keydown` filter sur F12, Ctrl+Shift+I/J/C, Ctrl+U, Ctrl+S, Ctrl+P, PrintScreen | DevTools déjà ouvert avant ouverture → non bloquable |
| DevTools | Détection via différence `window.outerHeight - innerHeight`, `debugger` trap | Tous contournables, mais lèvent un incident dans le journal |
| Plusieurs onglets de l'examen | Lien à usage unique côté serveur + Broadcast Channel API client | Session expirée → étudiant doit demander réautorisation au prof |

### Couche B — Application côté serveur (source de vérité)

- **Chronomètre serveur.** Le temps restant est calculé serveur à chaque tick (5–10 s). Le client n'affiche qu'un cache. Soumission après expiration = rejetée.
- **Lien unique nominatif à usage unique.** Token signé (signed URL Laravel) lié à `(exam_id, student_id, nonce)`. Première ouverture consomme le nonce ; les ouvertures suivantes sont refusées sauf réautorisation explicite par le prof.
- **Fingerprint device & IP**. Capturé à l'ouverture, journalisé. Pas de blocage strict (un étudiant peut changer de réseau légitimement) mais alerte si changement pendant la session.
- **Validation des soumissions.** Format, longueur max, type de fichier, timestamps cohérents.

### Couche C — Détection et réponse

- **Journal d'incidents** par soumission (timestamp + type + payload). Types : `tab_blur`, `fullscreen_exit`, `copy_attempt`, `devtools_detected`, `link_reopen`, `ip_change`, `multiple_session`, etc.
- **Verrouillage auto** à la 1ère infraction de type majeur (tab_blur, fullscreen_exit). Infractions mineures (clic droit, raccourci) → incident loggué mais examen continue.
- **Diffusion live** des incidents au dashboard prof via WebSocket (Laravel Reverb channel `exam.{id}.incidents`).
- **Notifications hors-tab** : Web Push (service worker pré-souscrit côté prof) + email (queue Laravel) pour redondance.

### Couche D — Acceptation utilisateur

- **Bandeau de consentement** explicite avant démarrage : "Cet examen est sous surveillance technique. Quitter la fenêtre verrouillera votre copie. Vous pouvez demander au professeur de la rouvrir."
- **Page d'examen verrouillé** côté étudiant : message clair "Votre copie est en pause. Le professeur a été notifié." + bouton "Demander la réautorisation".

## 2. Modèle de données cible (MVP)

```
users
  id, name, email, password, role (admin|teacher), status (pending|active|disabled), created_at, ...

platform_settings
  id, key, encrypted_value, updated_by (admin), updated_at
  # Stocke notamment la clé API Anthropic mutualisée, le quota global, etc.

api_usage_log
  id, teacher_id, exam_id, tokens_in, tokens_out, cost_estimate, occurred_at
  # Permet à l'admin de suivre la consommation par prof / par examen.

exams
  id, teacher_id, title, description, duration_minutes,
  opens_at, closes_at, status (draft|published|closed),
  settings_json (anti-cheat options par examen), created_at

questions
  id, exam_id, section, order, type (vf|qcm|short|essay|code|file_upload),
  prompt, choices_json (pour QCM/VF), correct_json (pour auto-grading),
  points, bareme_text, created_at

exam_assignments
  id, exam_id, student_email, student_name, student_matricule, student_group,
  access_token (unique, signed), opened_at, locked, locked_reason, submitted_at

submissions  (refonte de la table actuelle)
  id, exam_assignment_id, answers_json, auto_score, manual_score, total,
  status (in_progress|submitted|graded|sent), graded_at, sent_at

incidents
  id, exam_assignment_id, type, payload_json, occurred_at, ip, user_agent
```

## 3. Stack technique — additions au socle Laravel

- **Laravel Reverb** (websocket natif Laravel) → dashboard live, broadcasts incidents.
- **Web Push** : package `minishlink/web-push` côté Laravel + service worker côté prof.
- **File storage** : driver S3-compatible (MinIO local en dev) pour les fichiers joints des étudiants (questions `code` / `file_upload`).
- **Queue worker** : `database` en MVP, `redis` pour V2. Jobs : envoi email, appel API Anthropic, envoi notes étudiants.
- **DB** : migration SQLite → **PostgreSQL** recommandée pour la concurrence (verrous live, transactions sur le chronomètre).
- **Chiffrement clés API** : `Crypt::encryptString()` natif Laravel sur `api_keys.encrypted_key`.

## 4. Parcours utilisateur (high-level)

**Prof** : signup → admin valide son compte → crée un examen via builder → définit liste étudiants → clique "publier" → liens envoyés par mail → ouvre dashboard live → surveille → ferme l'examen → lance correction (export markdown ou API plateforme) → importe notes → envoie résultats.

**Étudiant** : reçoit email avec lien → clique → page d'accueil examen (consentement + plein écran) → démarre → répond → soumet → reçoit note par email plus tard.

**Admin** : valide les inscriptions profs → renseigne / fait tourner la clé API Anthropic mutualisée dans `platform_settings` → suit la consommation globale via `api_usage_log` (compteurs par prof, alerte si seuil de coût dépassé).

## 5. Détail "correction Claude hybride"

- **Mode 1 (copy/paste, défaut)** : préserve le flux actuel. Le prof clique "Exporter pour Claude", récupère un markdown formaté, le colle dans chat.claude.ai, copie le JSON renvoyé, le colle dans INSAS → notes importées.
- **Mode 2 (API directe)** : le prof clique "Corriger avec Claude API". Job en queue : appel API Anthropic avec la **clé plateforme mutualisée** (déchiffrée à la volée depuis `platform_settings`), parsing du JSON, insertion en base. Statut visible dans le dashboard. La consommation est journalisée dans `api_usage_log` au nom du prof appelant.
- **Garde-fou** : avant appel API, estimation du coût (tokens × prix) affichée au prof pour confirmation — l'admin paie, mais le prof voit ce qu'il consomme, ce qui responsabilise l'usage.

## 6. Migration depuis l'existant

**Décision : repart d'une base vide.** L'examen "Transformation Digitale M2 SRS" et les soumissions associées **ne sont pas migrés**. Le code existant sert de référence pour le pattern auto-correction V/F+QCM et pour le formatage de l'export Claude, mais le schéma DB est refait à neuf.

- Table `submissions` actuelle → **drop** et reconstruction selon le nouveau schéma (avec FK vers `exam_assignments`).
- Code de `AdminController::getQuestionsReference()` et `SubmissionController::store()` → utilisé comme **inspiration** pour la logique de correction auto, mais refactor complet en :
  - `TeacherAuthController` (signup/login prof)
  - `AdminController` (validation profs, gestion clé API plateforme, monitoring)
  - `ExamBuilderController` (CRUD examens + questions)
  - `StudentExamController` (parcours examen + middleware token unique)
  - `LiveMonitorController` (dashboard temps réel, broadcasts Reverb)
  - `IncidentController` (réception événements client + verrouillage)
  - `GradeController` (export Claude + import notes + envoi email)
- Routes publiques `/api/submit-exam` actuelles → **supprimées** (remplacées par routes signées token-based).
