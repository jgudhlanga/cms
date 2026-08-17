<?php

declare(strict_types=1);

namespace App\Models\Students;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class StudentIdCardSetting extends Model implements HasMedia
{
    use BelongsToTenant, HasFactory, InteractsWithMedia, LogsActivity;

    public const LOGO_COLLECTION = 'logo';

    public const SIGNATURE_COLLECTION = 'principal-signature';

    public const FALLBACK_LOGO_PATH = 'assets/images/logo.png';

    protected $fillable = [
        'tenant_id',
        'institution_name',
        'website',
        'return_name',
        'return_address',
        'return_phone',
    ];

    /**
     * @return array<string, string>
     */
    public static function defaultAttributes(): array
    {
        return [
            'institution_name' => (string) config('id_cards.return.name', 'Harare Polytechnic'),
            'website' => (string) config('id_cards.website', 'www.hrepoly.ac.zw'),
            'return_name' => (string) config('id_cards.return.name', 'Harare Polytechnic'),
            'return_address' => (string) config('id_cards.return.address', 'P.O. Box CY 407, Causeway, Harare'),
            'return_phone' => (string) config('id_cards.return.phone', '0867 700 0343'),
        ];
    }

    public static function resolveForTenant(?int $tenantId = null): self
    {
        $tenantId ??= Auth::user()?->tenant_id;

        if ($tenantId === null) {
            return (new self)->forceFill(self::defaultAttributes());
        }

        return self::query()->firstOrCreate(
            ['tenant_id' => $tenantId],
            self::defaultAttributes(),
        );
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection(self::LOGO_COLLECTION)
            ->singleFile()
            ->acceptsMimeTypes(['image/jpeg', 'image/png']);

        $this->addMediaCollection(self::SIGNATURE_COLLECTION)
            ->singleFile()
            ->acceptsMimeTypes(['image/jpeg', 'image/png']);
    }

    public function logoUrl(): string
    {
        $media = $this->getFirstMedia(self::LOGO_COLLECTION);
        if ($media instanceof Media) {
            return $media->getFullUrl();
        }

        return '/'.self::FALLBACK_LOGO_PATH;
    }

    public function signatureUrl(): ?string
    {
        $media = $this->getFirstMedia(self::SIGNATURE_COLLECTION);

        return $media instanceof Media ? $media->getFullUrl() : null;
    }

    public function logoPath(): ?string
    {
        return $this->mediaPath(self::LOGO_COLLECTION)
            ?? (is_file(public_path(self::FALLBACK_LOGO_PATH)) ? public_path(self::FALLBACK_LOGO_PATH) : null);
    }

    public function signaturePath(): ?string
    {
        return $this->mediaPath(self::SIGNATURE_COLLECTION);
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable()
            ->useLogName('StudentIdCardSetting')
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    private function mediaPath(string $collection): ?string
    {
        $media = $this->getFirstMedia($collection);
        if (! $media instanceof Media) {
            return null;
        }

        $path = $media->getPath();

        return is_string($path) && is_file($path) ? $path : null;
    }
}
