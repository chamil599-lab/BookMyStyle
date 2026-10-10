<?php

namespace App\Enums\Enums;

enum ServiceEnum: string
{
    case Draft = 'draft';
    case Published = 'published';

    public static function labels(): array
    {
        return[
            self::Draft->value => 'Draft',
            self::Published->value => 'Published',
        ];

    }
    public static function colors(): array
    {
        return[
            self::Draft->value => 'gray',
            self::Published->value => 'green',
        ];

    }
}
