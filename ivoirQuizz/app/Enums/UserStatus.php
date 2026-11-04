<?php

namespace App\Enums;

enum UserStatus: string
{
    case Active = 'active';
    case Suspended = 'suspended';
    case Disabled = 'disabled';

    public function label(): string
    {
        return match ($this) {
            self::Active => 'Actif',
            self::Suspended => 'Suspendu',
            self::Disabled => 'Désactivé',
        };
    }
}
