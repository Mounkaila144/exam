<?php

namespace App\Domain\Incident;

enum IncidentType: string
{
    case TAB_BLUR = 'tab_blur';
    case FULLSCREEN_EXIT = 'fullscreen_exit';
    case FULLSCREEN_DENIED = 'fullscreen_denied';
    case COPY_ATTEMPT = 'copy_attempt';
    case PASTE_ATTEMPT = 'paste_attempt';
    case CUT_ATTEMPT = 'cut_attempt';
    case CONTEXT_MENU_ATTEMPT = 'context_menu_attempt';
    case DEVTOOLS_SHORTCUT = 'devtools_shortcut';
    case DEVTOOLS_DETECTED = 'devtools_detected';
    case LINK_REOPEN = 'link_reopen';
    case IP_CHANGE = 'ip_change';
    case MULTIPLE_SESSION = 'multiple_session';
    case UNLOCKED_BY_TEACHER = 'unlocked_by_teacher';

    public const MAJOR_OFFENSES = [
        self::TAB_BLUR,
        self::FULLSCREEN_EXIT,
        self::MULTIPLE_SESSION,
    ];

    public function isMajor(): bool
    {
        return in_array($this, self::MAJOR_OFFENSES, true);
    }

    public function severity(): IncidentSeverity
    {
        return match (true) {
            $this->isMajor() => IncidentSeverity::CRITICAL,
            $this === self::UNLOCKED_BY_TEACHER => IncidentSeverity::INFO,
            default => IncidentSeverity::WARNING,
        };
    }

    public function label(): string
    {
        return match ($this) {
            self::TAB_BLUR => 'Sortie d\'onglet',
            self::FULLSCREEN_EXIT => 'Sortie du plein écran',
            self::FULLSCREEN_DENIED => 'Plein écran refusé',
            self::COPY_ATTEMPT => 'Tentative de copie',
            self::PASTE_ATTEMPT => 'Tentative de collage',
            self::CUT_ATTEMPT => 'Tentative de couper',
            self::CONTEXT_MENU_ATTEMPT => 'Clic droit',
            self::DEVTOOLS_SHORTCUT => 'Raccourci DevTools',
            self::DEVTOOLS_DETECTED => 'DevTools détecté',
            self::LINK_REOPEN => 'Réouverture du lien',
            self::IP_CHANGE => 'Changement d\'IP',
            self::MULTIPLE_SESSION => 'Session multiple',
            self::UNLOCKED_BY_TEACHER => 'Déverrouillé par le professeur',
        };
    }
}
