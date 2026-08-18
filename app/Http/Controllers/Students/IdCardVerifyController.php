<?php

declare(strict_types=1);

namespace App\Http\Controllers\Students;

use App\Enums\Students\IdCardRequestStatusEnum;
use App\Http\Controllers\Controller;
use App\Http\Resources\Students\StudentIdCardSettingResource;
use App\Models\Students\StudentIdCardRequest;
use App\Models\Students\StudentIdCardSetting;
use App\Support\Students\StudentIdCardFace;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class IdCardVerifyController extends Controller
{
    /**
     * @var list<IdCardRequestStatusEnum>
     */
    private const VALID_STATUSES = [
        IdCardRequestStatusEnum::APPROVED,
        IdCardRequestStatusEnum::PRINTED,
        IdCardRequestStatusEnum::ISSUED,
    ];

    public function __invoke(Request $request, string $serial): Response
    {
        $cardRequest = StudentIdCardRequest::query()
            ->with([
                ...StudentIdCardFace::requestRelations(),
                'photo',
            ])
            ->where('serial_number', $serial)
            ->first();

        $settings = StudentIdCardSetting::resolveForTenant($cardRequest?->tenant_id);

        if (! $cardRequest instanceof StudentIdCardRequest) {
            return $this->render($settings, 'invalid', null, null);
        }

        $expiry = ($cardRequest->printed_at ?? $cardRequest->reviewed_at ?? $cardRequest->created_at)
            ?->copy()
            ->endOfYear();

        $isExpired = $expiry !== null && $expiry->isPast();
        $isPrintableStatus = in_array($cardRequest->status, self::VALID_STATUSES, true);

        if (! $isPrintableStatus) {
            return $this->render($settings, 'invalid', null, null);
        }

        if ($isExpired) {
            return $this->render($settings, 'expired', null, null);
        }

        $face = StudentIdCardFace::fromStudent($cardRequest->student, settings: $settings);
        $student = $cardRequest->student;
        $canViewProfile = $request->user()?->can('view', $student) ?? false;

        return $this->render($settings, 'valid', [
            'photoUrl' => $this->photoDataUri($cardRequest),
            'studentName' => $face->studentName,
            'studentNumber' => $face->studentNumber,
            'department' => $face->department,
            'course' => $face->course,
            'expiryDate' => $face->expiryDate,
            'statusLabel' => $cardRequest->status?->label(),
        ], $canViewProfile && $student !== null ? route('students.show', $student) : null);
    }

    /**
     * @param  array{
     *     photoUrl: string|null,
     *     studentName: string,
     *     studentNumber: string,
     *     department: string,
     *     course: string,
     *     expiryDate: string,
     *     statusLabel: string|null,
     * }|null  $card
     */
    private function render(
        StudentIdCardSetting $settings,
        string $outcome,
        ?array $card,
        ?string $studentProfileUrl,
    ): Response {
        return Inertia::render('site/id-cards/Verify', [
            'outcome' => $outcome,
            'institution' => StudentIdCardSettingResource::make($settings)->resolve(),
            'card' => $card,
            'studentProfileUrl' => $studentProfileUrl,
        ]);
    }

    private function photoDataUri(StudentIdCardRequest $request): ?string
    {
        $media = $request->photo ?? $request->getFirstMedia(StudentIdCardRequest::MEDIA_COLLECTION);
        if (! $media instanceof Media) {
            return null;
        }

        $path = $media->getPath('card');
        if (! is_string($path) || ! is_file($path)) {
            $path = $media->getPath();
        }

        if (! is_string($path) || ! is_file($path)) {
            return $media->getFullUrl('card') ?: $media->getFullUrl();
        }

        $contents = file_get_contents($path);
        if ($contents === false) {
            return null;
        }

        $mime = mime_content_type($path) ?: 'image/jpeg';

        return 'data:'.$mime.';base64,'.base64_encode($contents);
    }
}
