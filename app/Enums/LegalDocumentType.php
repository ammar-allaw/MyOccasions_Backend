<?php

namespace App\Enums;

enum LegalDocumentType: string
{
    case PrivacyPolicy = 'privacy_policy';
    case TermsOfUse = 'terms_of_use';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
