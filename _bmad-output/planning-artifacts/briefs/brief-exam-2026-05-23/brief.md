---
title: "Product Brief: ExamGuard — Plateforme d'examens en ligne sécurisés"
status: draft
created: 2026-05-23
updated: 2026-05-23
---

# Product Brief: ExamGuard

> **Note** : "ExamGuard" est un codename de travail. Le nom final est à arrêter avant tout déploiement public.

## Executive Summary

**ExamGuard** est une plateforme SaaS d'examens en ligne destinée aux enseignants du supérieur, où chaque professeur dispose de son propre compte pour **concevoir, diffuser, surveiller et corriger ses examens** dans un environnement anti-triche temps réel. Les étudiants accèdent à l'examen via un **lien unique nominatif** (pas de compte à créer) et passent l'épreuve dans un cadre verrouillé : sortie d'onglet bloquée, plein écran forcé, raccourcis désactivés, lien à usage unique. À la moindre infraction, l'examen de l'étudiant est **verrouillé automatiquement**, le professeur est **alerté en direct** (notification push navigateur + email), et il peut **redonner l'accès manuellement** depuis son dashboard de surveillance en direct.

Le produit s'appuie sur un socle Laravel déjà éprouvé : auto-correction des V/F et QCM côté serveur, export structuré des réponses ouvertes pour correction par Claude (en copy/paste ou via l'API Anthropic, **mutualisée** au niveau plateforme et payée par l'administrateur), import des notes, envoi automatique des résultats par email. ExamGuard industrialise et multi-tenantise cette mécanique, et lui adjoint l'environnement sécurisé qui manque aujourd'hui aux examens distants : un cadre où le professeur sait, à chaque instant, ce qui se passe sur l'écran de chaque étudiant.

## The Problem

Les examens à distance ont explosé depuis 2020 mais la plupart des plateformes universitaires (Moodle, Google Forms, formulaires maison) souffrent de deux maux qui se renforcent :

- **La triche est triviale.** Un étudiant ouvre un second onglet, copie la question dans ChatGPT, colle la réponse. Le professeur ne voit rien. Aucune notification, aucun verrouillage, aucune trace. La parade actuelle — proctoring vidéo lourd type ProctorU — coûte cher, viole la vie privée, et n'est pas adapté à un cours de master.
- **La correction des réponses ouvertes est un goulot d'étranglement.** Un enseignant corrige 60 copies à la main. Avec l'arrivée de Claude / GPT, le potentiel de gain de temps est massif, mais les profs n'ont pas d'outil prêt-à-l'emploi : ils copient/collent manuellement chaque copie, ou bâtissent leurs propres scripts.

Mounkaila a déjà construit un outil pour son propre examen "Transformation Digitale M2 SRS" qui résout le second problème (export structuré → correction Claude → import des notes → envoi email). Le problème : c'est mono-prof, mono-examen, et **sans aucune mécanique anti-triche**. Le formulaire de l'étudiant est public, ouvert, sans verrou. N'importe qui peut tout faire pendant la session.

## The Solution

ExamGuard transforme l'outil existant en plateforme multi-enseignants avec trois couches :

**1. Compte enseignant + builder d'examens.** Le prof crée son compte (validation par l'administrateur), puis construit son examen via un **builder visuel section par section** (Partie I, II, III…). Il compose librement avec : V/F, QCM, réponses courtes, dissertation, question de code, dépôt de fichier. Il fixe la durée, le barème, et la liste des étudiants invités (par CSV ou ajout manuel).

**2. Environnement d'examen verrouillé.** Chaque étudiant reçoit un **lien unique nominatif à usage unique**. À l'ouverture, l'examen démarre en **plein écran forcé**, avec : détection sortie d'onglet (Visibility / blur), blocage copier-coller / clic droit / DevTools / raccourcis sensibles, compte à rebours **chronométré côté serveur** (immune à la triche client), journalisation de tous les événements. À la première infraction, l'examen est **gelé instantanément** : l'étudiant ne peut plus répondre tant que le prof ne réautorise pas.

**3. Surveillance live + correction assistée.** Pendant que l'examen tourne, le prof voit un **dashboard temps réel** : qui est connecté, qui est en cours, qui est verrouillé, le flux d'événements (sorties d'onglet, tentatives de raccourci, soumissions). À chaque incident il reçoit une **notification push navigateur + un email**. D'un clic, il peut redonner accès à un étudiant. Après la fin : V/F et QCM auto-corrigés, réponses ouvertes routées vers Claude en **mode hybride** — soit copy/paste vers chat.claude.ai, soit appel direct à l'API Anthropic. La clé API est **mutualisée au niveau plateforme** et provisionnée par l'administrateur ; chaque prof peut déclencher une correction API sans manipuler de clé. Notes importées, envoyées aux étudiants par email.

## What Makes This Different

- **Anti-triche pragmatique, pas Big Brother.** Pas de webcam, pas de capture d'écran intrusive, pas de logiciel à installer. Juste les outils navigateur exploités à fond (Visibility API, Fullscreen API, lien unique, chronomètre serveur, journal d'événements). Beaucoup moins anxiogène que le proctoring vidéo, suffisamment rigoureux pour un examen universitaire honnête.
- **Verrouillage + réautorisation manuelle = filet de sécurité, pas guillotine.** Un étudiant qui sort par accident (notif WhatsApp, mauvais clic) n'est pas pénalisé définitivement : le prof juge et réautorise. Cette nuance — verrouiller mais laisser le prof décider — est ce qui rend l'outil acceptable pour des enseignants qui ne veulent pas piéger leurs étudiants.
- **Correction Claude intégrée, pas bricolée.** Là où les profs jonglent aujourd'hui avec ChatGPT en parallèle de Moodle, ExamGuard structure le flux : prompt généré, copies formatées, JSON parsé, notes injectées. Mode hybride pour s'adapter aux profs qui ne veulent pas (ou ne peuvent pas) utiliser l'API.
- **Pas de compte étudiant.** L'étudiant clique sur son lien, c'est tout. Friction zéro côté usager final — la friction est entièrement absorbée par le prof, ce qui est la bonne répartition.

## Who This Serves

**Le professeur (utilisateur principal).** Un enseignant du supérieur (master surtout) qui fait passer des examens écrits à 20-200 étudiants. Il veut un outil qui (a) lui évite la fraude évidente, (b) lui économise la correction manuelle des réponses ouvertes, (c) ne demande pas une formation IT pour être utilisé. Il est mi-tech, sait remplir un formulaire complexe, mais ne va pas écrire de code.

**L'étudiant.** Reçoit un lien, passe son examen, reçoit sa note par email. C'est tout son parcours. Il ne crée jamais de compte. Il doit comprendre en 5 secondes que l'examen est sous surveillance — un bandeau explicite avant le démarrage suffit.

**L'administrateur de la plateforme.** Un rôle unique (Mounkaila au démarrage) qui : (1) valide les inscriptions des profs, (2) gère la clé API Anthropic **mutualisée** pour toute la plateforme et supporte son coût, (3) supervise l'activité globale (volumes, examens en cours, consommation API). Le périmètre est volontairement minimaliste au MVP : pas de console métier élaborée, juste les écrans nécessaires à la gestion des profs et au monitoring global.

## Success Criteria

- **Adoption** : `[ASSUMPTION]` 5 enseignants utilisent ExamGuard sur un examen réel dans les 3 mois suivant le MVP.
- **Anti-triche effective** : sur un examen pilote, ≥ 95 % des tentatives de sortie d'onglet déclenchent un verrouillage + alerte en moins de 2 secondes.
- **Gain de temps correction** : un prof corrige 60 copies de réponses ouvertes en moins de 30 minutes (vs. plusieurs heures aujourd'hui).
- **Friction étudiant** : aucun étudiant ne contacte le prof pour un problème d'accès au lien (mesure indirecte de la simplicité du parcours).
- **Fiabilité** : aucun examen perdu / corrompu sur les 3 premiers mois (durabilité des soumissions = critère non-négociable).
- **Coût API maîtrisé** : la consommation Anthropic est visible et plafonnable par l'admin (sinon le modèle "admin paie pour tous" devient un risque financier).

## Scope

### Dans le MVP

- Auth professeur (signup avec validation admin / login). Compte administrateur global unique.
- Builder d'examen visuel section par section, types : V/F, QCM, réponse courte, dissertation, question de code, dépôt de fichier joint.
- Paramétrage par examen : durée (configurable par le prof), date d'ouverture/fermeture, liste d'étudiants (import CSV + ajout manuel), barème par question.
- Génération de **liens uniques nominatifs à usage unique** envoyés par email aux étudiants.
- Environnement étudiant verrouillé : plein écran forcé, Visibility API, blocage copier/coller/clic droit/raccourcis/DevTools, chronomètre serveur, lien à usage unique.
- Verrouillage auto à la 1ère infraction majeure + déverrouillage manuel par le prof.
- Dashboard live prof avec WebSockets (Laravel Reverb) : présence, statut, événements, bouton "redonner accès".
- Alertes : notification push navigateur (Web Push) + email à chaque infraction.
- Correction Partie auto (V/F + QCM) côté serveur, configurable par question.
- Correction Claude hybride : export markdown pour copy/paste **OU** appel API Anthropic via **clé plateforme mutualisée gérée par l'admin**.
- Console admin minimale : validation des inscriptions profs, saisie/rotation de la clé API plateforme, monitoring consommation API.
- Import JSON des notes, envoi automatique des résultats aux étudiants par email.
- Journal d'audit complet par soumission (timestamps, incidents, IP, user-agent).

### Hors MVP (vision V2+)

- Proctoring webcam / capture d'écran périodique.
- Banque de questions partagée entre profs / templates d'examens.
- Analytics avancés (statistiques par question, détection d'anomalies sur les réponses).
- Mobile natif (mobile web suffit au MVP).
- Multi-tenant institutionnel (un admin par université, hiérarchie de rôles, branding par institution).
- Quotas / facturation à l'usage si le coût API mutualisé devient problématique.
- Internationalisation (UI multi-langues).

## Anti-Cheat Strategy

Le détail tactique vit dans `addendum.md` (combinaison de techniques Visibility API + Fullscreen API + chronomètre serveur + lien unique + journalisation + protections client). Principes directeurs ici :

- **Couches multiples, pas une silver bullet.** Aucune technique seule n'est infaillible côté navigateur ; c'est la combinaison qui rend la triche assez chère pour décourager les opportunistes.
- **Tout côté serveur pour ce qui compte.** Le chronomètre, la validation des soumissions, la détection de double-ouverture du lien — tout vit côté serveur. Le client n'est jamais source de vérité.
- **Visibilité totale pour le prof.** Chaque action suspecte est journalisée et streamée live. Si la prévention échoue, la détection ne doit pas.
- **Réversibilité humaine.** Le verrouillage est une mesure conservatoire, pas une sanction. Le prof reste juge.

## Technical Foundations

- **Socle existant à conserver** : Laravel 13 (PHP 8.3) + Tailwind 4 + Vite 8 + SQLite (`[ASSUMPTION]` à migrer vers PostgreSQL ou MySQL pour la concurrence multi-prof — voir Open Questions).
- **À ajouter au MVP** : Laravel Reverb (WebSockets natifs pour le live monitoring), Web Push (alertes navigateur), file storage S3-compatible pour les fichiers joints des questions de code / dépôt, queue worker (jobs d'envoi email + correction Claude API).
- **Refonte du modèle de données** : `exams`, `questions`, `exam_assignments` (lien unique), `submissions` (existant, à enrichir), `incidents` (journal d'événements), `users` (rôle: admin/teacher), `platform_settings` (clé API Claude mutualisée + paramètres globaux).
- **Sécurité examen** : middleware dédié sur les routes étudiant (validation token lien unique + IP/device binding optionnel + état de l'examen).

Détails techniques approfondis dans `addendum.md`.

## Risks & Open Questions

1. **Coût API Anthropic non plafonné.** L'admin paie pour tous les profs. Sans quotas par prof / par examen, un usage massif peut exploser la facture. **Mitigation MVP** : dashboard de consommation visible pour l'admin + estimation de coût affichée au prof avant chaque correction API. **V2** : quotas par prof / par mois.
2. **Robustesse anti-triche réelle.** Toutes les protections navigateur peuvent être contournées avec un second appareil (téléphone à côté). À assumer et communiquer : ExamGuard rend la triche difficile, pas impossible.
3. **RGPD / conformité.** Stockage de copies d'étudiants, journal d'incidents, IP → besoin d'une politique de rétention et de mentions légales claires. À traiter avant tout déploiement réel.
4. **Charge concurrente.** Combien d'étudiants peuvent passer un examen simultanément ? Le WebSocket + le chronomètre serveur tiennent jusqu'à quel volume ? À mesurer dès le MVP.
5. **Compatibilité navigateur.** Fullscreen API + Visibility API + Web Push = pas de support uniforme. Politique navigateurs supportés à arrêter (Chrome/Edge/Firefox récents probable, Safari plus capricieux).
6. **Inscription des profs : ouverte ou sur invitation ?** L'admin valide chaque inscription — mais selon le modèle de découverte (qui connaît ExamGuard ?), il faudra peut-être un workflow d'invitation ou un code de parrainage.
7. **Nom de produit** : "ExamGuard" est un codename. Le nom final à arrêter avant tout déploiement public ou support légal.

## Vision

À 12-18 mois, ExamGuard devient l'outil de référence pour les enseignants du supérieur francophone qui veulent organiser un examen écrit à distance sérieux **sans sacrifier la vie privée de leurs étudiants ni y passer leurs nuits**. Une banque de questions partageables entre profs, des analytics sur la qualité pédagogique des questions (taux de réussite, discriminance), une intégration multi-LLM (Claude, GPT, Mistral) pour la correction. À plus long terme, le déploiement institutionnel — une faculté entière, avec sa hiérarchie, ses chartes, sa facturation — devient le segment naturel.

Mais le MVP doit rester radicalement simple : un prof, son compte, son examen, ses étudiants, son dashboard live, sa correction assistée. Tout le reste attend.
