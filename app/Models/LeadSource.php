<?php

namespace App\Models;

/**
 * Supported lead sources. These values are mirrored in the
 * "options" table under the LEAD_SOURCE category.
 */
class LeadSource
{
    public const WEBSITE = 'WEBSITE';

    public const REFERRAL = 'REFERRAL';

    public const PHONE = 'PHONE';

    public const EMAIL = 'EMAIL';

    public const CAMPAIGN = 'CAMPAIGN';

    public const OTHER = 'OTHER';

    /**
     * All valid lead sources.
     *
     * @return list<string>
     */
    public static function all(): array
    {
        return [self::WEBSITE, self::REFERRAL, self::PHONE, self::EMAIL, self::CAMPAIGN, self::OTHER];
    }
}
