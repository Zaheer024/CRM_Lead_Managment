<?php

namespace App\Models;

/**
 * Supported follow-up statuses. These values are mirrored in the
 * "options" table under the FOLLOWUP_STATUS category.
 */
class FollowupStatus
{
    public const PENDING = 'PENDING';

    public const COMPLETED = 'COMPLETED';

    public const CANCELLED = 'CANCELLED';

    /**
     * All valid follow-up statuses.
     *
     * @return list<string>
     */
    public static function all(): array
    {
        return [self::PENDING, self::COMPLETED, self::CANCELLED];
    }
}
