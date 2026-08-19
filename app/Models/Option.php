<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Supports lookup-driven configuration such as lead statuses,
 * lead sources and follow-up statuses that are maintained through
 * the "options" table.
 */
class Option extends Model
{
    use HasFactory;

    public const CATEGORY_LEAD_STATUS = 'LEAD_STATUS';

    public const CATEGORY_LEAD_SOURCE = 'LEAD_SOURCE';

    public const CATEGORY_FOLLOWUP_STATUS = 'FOLLOWUP_STATUS';

    public const CATEGORY_USER_STATUS = 'USER_STATUS';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = ['category', 'value', 'label', 'sort_order', 'status'];

    /**
     * Scope a query to options of a given category.
     */
    public function scopeCategory($query, string $category)
    {
        return $query->where('category', $category);
    }

    /**
     * Scope a query to active options only.
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'ACTIVE');
    }
}
