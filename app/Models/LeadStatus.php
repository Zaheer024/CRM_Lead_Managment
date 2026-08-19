<?php

namespace App\Models;

/**
 * Supported lead statuses. These values are mirrored in the
 * "options" table under the LEAD_STATUS category.
 */
class LeadStatus
{
    public const NEW = 'NEW';

    public const CONTACTED = 'CONTACTED';

    public const FOLLOW_UP = 'FOLLOW_UP';

    public const CONVERTED = 'CONVERTED';

    public const LOST = 'LOST';

    /**
     * All valid lead statuses.
     *
     * @return list<string>
     */
    public static function all(): array
    {
        return [self::NEW, self::CONTACTED, self::FOLLOW_UP, self::CONVERTED, self::LOST];
    }

    /**
     * Statuses that count as an "active" lead for duplicate prevention
     * purposes (NEW or FOLLOW_UP).
     *
     * @return list<string>
     */
    public static function activeStatuses(): array
    {
        return [self::NEW, self::FOLLOW_UP];
    }

    /**
     * Allowed status transitions.
     *
     * @return array<string, list<string>>
     */
    public static function transitions(): array
    {
        return [
            self::NEW => [self::CONTACTED, self::LOST],
            self::CONTACTED => [self::FOLLOW_UP, self::LOST],
            self::FOLLOW_UP => [self::CONTACTED, self::CONVERTED, self::LOST],
            self::CONVERTED => [],
            self::LOST => [],
        ];
    }

    /**
     * Determine whether moving from $from to $to is a valid transition.
     */
    public static function allows(string $from, string $to): bool
    {
        return in_array($to, self::transitions()[$from] ?? [], true);
    }
}
