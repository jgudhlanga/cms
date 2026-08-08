<?php

declare(strict_types=1);

namespace App\Models\Institution;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;

class InstitutionFeature extends Model
{
    use BelongsToTenant;

    public const string ALLOW_ONLINE_CLEARANCE = 'allow_online_clearance';

    /**
     * @var array<string, bool>
     */
    public const array DEFAULT_FEATURES = [
        self::ALLOW_ONLINE_CLEARANCE => false,
    ];

    protected $fillable = [
        'tenant_id',
        'features',
    ];

    protected function casts(): array
    {
        return [
            'features' => 'array',
        ];
    }

    public function allowsOnlineClearance(): bool
    {
        return (bool) ($this->features[self::ALLOW_ONLINE_CLEARANCE] ?? false);
    }
}
