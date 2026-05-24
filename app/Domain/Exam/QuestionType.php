<?php

namespace App\Domain\Exam;

enum QuestionType: string
{
    case VF = 'vf';
    case QCM = 'qcm';
    case SHORT = 'short';
    case ESSAY = 'essay';
    case CODE = 'code';
    case FILE_UPLOAD = 'file_upload';

    public function label(): string
    {
        return match ($this) {
            self::VF => 'Vrai / Faux',
            self::QCM => 'QCM',
            self::SHORT => 'Réponse courte',
            self::ESSAY => 'Dissertation',
            self::CODE => 'Question de code',
            self::FILE_UPLOAD => 'Dépôt de fichier',
        };
    }

    public function isAutoGradable(): bool
    {
        return in_array($this, [self::VF, self::QCM], true);
    }
}
