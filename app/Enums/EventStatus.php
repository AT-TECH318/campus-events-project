<?php

namespace App\Enums;

enum EventStatus: string
{
    /** Not listed in upcoming or past results, and not open for registration. */
    case Draft = 'draft';

    /** Listed by date. Registration stays open until the deadline or capacity is reached. */
    case Published = 'published';

    /** Kept for the record. Hidden from upcoming and past results, and closed for registration. */
    case Cancelled = 'cancelled';

    public function description(): string
    {
        return match ($this) {
            self::Draft => 'Not listed publicly and not open for registration.',
            self::Published => 'Visible in listings. Registration is open until the deadline or capacity is reached.',
            self::Cancelled => 'Kept for the record. Hidden from upcoming and past lists, and closed for registration.',
        };
    }
}
