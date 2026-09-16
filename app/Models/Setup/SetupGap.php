<?php

declare(strict_types=1);

namespace App\Models\Setup;

use App\Enums\Setup\SetupGapCheckEnum;
use App\Enums\Setup\SetupGapSeverityEnum;
use App\Models\Institution\InstitutionDepartment;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * An open (or recently closed) setup problem raised by the nightly scan.
 *
 * @mixin Builder
 */
class SetupGap extends Model
{
    protected $table = 'setup_gaps';

    protected $fillable = [
        'tenant_id',
        'check_key',
        'institution_department_id',
        'severity',
        'fingerprint',
        'title',
        'body',
        'url',
        'meta',
        'detected_at',
        'last_seen_at',
        'resolved_at',
        'notified_at',
    ];

    protected function casts(): array
    {
        return [
            'check_key' => SetupGapCheckEnum::class,
            'severity' => SetupGapSeverityEnum::class,
            'meta' => 'array',
            'detected_at' => 'datetime',
            'last_seen_at' => 'datetime',
            'resolved_at' => 'datetime',
            'notified_at' => 'datetime',
        ];
    }

    public function institutionDepartment(): BelongsTo
    {
        return $this->belongsTo(InstitutionDepartment::class);
    }

    public function scopeOpen(Builder $query): Builder
    {
        return $query->whereNull('resolved_at');
    }

    public function scopeForTenant(Builder $query, int $tenantId): Builder
    {
        return $query->where('tenant_id', $tenantId);
    }

    /**
     * Gaps a department user may see: their own departments, never another department's setup.
     *
     * @param  list<int>  $institutionDepartmentIds
     */
    public function scopeForDepartments(Builder $query, array $institutionDepartmentIds): Builder
    {
        return $query->whereIn('institution_department_id', $institutionDepartmentIds);
    }
}
