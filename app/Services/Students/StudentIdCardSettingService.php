<?php

declare(strict_types=1);

namespace App\Services\Students;

use App\Models\Students\StudentIdCardSetting;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;

class StudentIdCardSettingService
{
    /**
     * @param  array{institution_name: string, website?: string|null, return_name: string, return_address: string, return_phone?: string|null}  $attributes
     */
    public function update(
        StudentIdCardSetting $settings,
        array $attributes,
        ?UploadedFile $logo = null,
        ?UploadedFile $signature = null,
    ): StudentIdCardSetting {
        return DB::transaction(function () use ($settings, $attributes, $logo, $signature): StudentIdCardSetting {
            $settings->update([
                'institution_name' => $attributes['institution_name'],
                'website' => $attributes['website'] ?? null,
                'return_name' => $attributes['return_name'],
                'return_address' => $attributes['return_address'],
                'return_phone' => $attributes['return_phone'] ?? null,
            ]);

            if ($logo instanceof UploadedFile) {
                $settings
                    ->addMedia($logo)
                    ->toMediaCollection(StudentIdCardSetting::LOGO_COLLECTION);
            }

            if ($signature instanceof UploadedFile) {
                $settings
                    ->addMedia($signature)
                    ->toMediaCollection(StudentIdCardSetting::SIGNATURE_COLLECTION);
            }

            return $settings->fresh() ?? $settings;
        });
    }
}
