<?php

declare(strict_types=1);

namespace App\Services\Students;

use App\Enums\Students\IdCardRequestStatusEnum;
use App\Exceptions\Students\InvalidIdCardRequestTransitionException;
use App\Models\Students\Student;
use App\Models\Students\StudentIdCardRequest;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class StudentIdCardPhotoService
{
    public const DISK = 'id-card-photos';

    /**
     * @var list<string>
     */
    private const EXTENSIONS = ['jpg', 'jpeg', 'png'];

    public function uploadIdPhoto(Student $student, UploadedFile $photo): Media
    {
        $stem = $this->identityStem($student);
        if ($stem === null) {
            throw InvalidIdCardRequestTransitionException::because('students.id_card_photo_identity_required');
        }

        $extension = $this->extensionFromUpload($photo);
        $fileName = sprintf('id-photo-%s-%s.%s', $student->id, now()->format('YmdHis'), $extension);

        $media = $student
            ->addMedia($photo)
            ->usingFileName($fileName)
            ->toMediaCollection(Student::ID_PHOTO_COLLECTION);

        $this->writeNamedCopy($student, $media);
        $this->attachToOpenRequest($student, $media);

        return $media;
    }

    public function uploadGalleryPhoto(Student $student, UploadedFile $photo): Media
    {
        $extension = $this->extensionFromUpload($photo);

        return $student
            ->addMedia($photo)
            ->usingFileName(sprintf('gallery-%s-%s.%s', $student->id, now()->format('YmdHis'), $extension))
            ->toMediaCollection(Student::GALLERY_COLLECTION);
    }

    public function deleteGalleryPhoto(Student $student, Media $media): void
    {
        if ($media->model_type !== $student->getMorphClass()
            || (int) $media->model_id !== (int) $student->id
            || $media->collection_name !== Student::GALLERY_COLLECTION) {
            throw InvalidIdCardRequestTransitionException::because('students.id_card_gallery_photo_invalid');
        }

        $media->delete();
    }

    public function replaceNamedCopy(Student $student, array $previousValues): void
    {
        foreach ($previousValues as $value) {
            $stem = $this->sanitizeStem(trim((string) $value));
            if ($stem === null) {
                continue;
            }

            foreach (self::EXTENSIONS as $extension) {
                $relative = $stem.'.'.$extension;
                if (Storage::disk(self::DISK)->exists($relative)) {
                    Storage::disk(self::DISK)->delete($relative);
                }
            }
        }

        $this->syncNamedCopy($student);
    }

    public function syncNamedCopy(Student $student): void
    {
        $media = $student->latestIdPhoto();
        if (! $media instanceof Media) {
            return;
        }

        $this->writeNamedCopy($student, $media);
    }

    public function hasPrintPhoto(Student $student): bool
    {
        return $this->printFolderAbsolutePath($student) !== null
            || $student->latestIdPhoto() instanceof Media;
    }

    public function printPhotoThumbUrl(Student $student): ?string
    {
        $media = $student->latestIdPhoto();
        if ($media instanceof Media) {
            return $media->getFullUrl('thumb') ?: $media->getFullUrl();
        }

        return null;
    }

    public function ensureMediaFromPrintFolder(Student $student): ?Media
    {
        $existing = $student->latestIdPhoto();
        if ($existing instanceof Media) {
            $this->writeNamedCopy($student, $existing);

            return $existing;
        }

        $path = $this->printFolderAbsolutePath($student);
        if ($path === null) {
            return null;
        }

        $media = $student
            ->addMedia($path)
            ->preservingOriginal()
            ->usingFileName(basename($path))
            ->toMediaCollection(Student::ID_PHOTO_COLLECTION);

        $this->writeNamedCopy($student, $media);

        return $media;
    }

    /**
     * @return list<array{id: int, url: string, thumbUrl: string, createdAt: string|null}>
     */
    public function galleryPayload(Student $student): array
    {
        return $student->getMedia(Student::GALLERY_COLLECTION)
            ->map(static function (Media $media): array {
                return [
                    'id' => (int) $media->id,
                    'url' => $media->getFullUrl('card') ?: $media->getFullUrl(),
                    'thumbUrl' => $media->getFullUrl('thumb') ?: $media->getFullUrl(),
                    'createdAt' => $media->created_at?->toIso8601String(),
                ];
            })
            ->values()
            ->all();
    }

    public function identityStem(Student $student): ?string
    {
        $value = $student->isZimbabwean()
            ? trim((string) $student->id_number)
            : trim((string) $student->passport_number);

        if ($value === '') {
            $value = $student->isZimbabwean()
                ? trim((string) $student->passport_number)
                : trim((string) $student->id_number);
        }

        return $this->sanitizeStem($value);
    }

    public function printFolderAbsolutePath(Student $student): ?string
    {
        foreach ($this->candidateStems($student) as $stem) {
            $path = $this->absolutePathForStem($stem);
            if ($path !== null) {
                return $path;
            }
        }

        return null;
    }

    /**
     * @return list<string>
     */
    private function candidateStems(Student $student): array
    {
        $stems = [];

        foreach ([$student->id_number, $student->passport_number] as $value) {
            $stem = $this->sanitizeStem(trim((string) $value));
            if ($stem !== null) {
                $stems[] = $stem;
            }
        }

        $preferred = $this->identityStem($student);
        if ($preferred !== null) {
            array_unshift($stems, $preferred);
        }

        return array_values(array_unique($stems));
    }

    private function writeNamedCopy(Student $student, Media $media): void
    {
        $stem = $this->identityStem($student);
        if ($stem === null) {
            return;
        }

        $source = $this->mediaSourcePath($media);
        if ($source === null) {
            return;
        }

        $extension = strtolower(pathinfo($source, PATHINFO_EXTENSION) ?: 'jpg');
        if (! in_array($extension, self::EXTENSIONS, true)) {
            $extension = 'jpg';
        }

        $this->deleteNamedCopies($student);

        $contents = file_get_contents($source);
        if ($contents === false) {
            return;
        }

        Storage::disk(self::DISK)->put($stem.'.'.$extension, $contents);
    }

    private function deleteNamedCopies(Student $student): void
    {
        foreach ($this->candidateStems($student) as $stem) {
            foreach (self::EXTENSIONS as $extension) {
                $relative = $stem.'.'.$extension;
                if (Storage::disk(self::DISK)->exists($relative)) {
                    Storage::disk(self::DISK)->delete($relative);
                }
            }
        }
    }

    private function absolutePathForStem(string $stem): ?string
    {
        foreach (self::EXTENSIONS as $extension) {
            $relative = $stem.'.'.$extension;
            if (Storage::disk(self::DISK)->exists($relative)) {
                return Storage::disk(self::DISK)->path($relative);
            }
        }

        return null;
    }

    private function mediaSourcePath(Media $media): ?string
    {
        $path = $media->getPath('card');
        if (is_string($path) && is_file($path)) {
            return $path;
        }

        $path = $media->getPath();

        return is_string($path) && is_file($path) ? $path : null;
    }

    private function attachToOpenRequest(Student $student, Media $photo): void
    {
        $open = $student->idCardRequests()
            ->whereIn('status', [
                IdCardRequestStatusEnum::PENDING->value,
                IdCardRequestStatusEnum::AWAITING_PAYMENT->value,
            ])
            ->latest()
            ->first();

        if (! $open instanceof StudentIdCardRequest) {
            return;
        }

        $copy = $photo->copy($open, StudentIdCardRequest::MEDIA_COLLECTION);
        $open->update([
            'photo_media_id' => $copy->id,
        ]);
    }

    private function sanitizeStem(string $value): ?string
    {
        $normalized = strtoupper(trim($value));
        $normalized = preg_replace('/[^A-Z0-9._-]/', '', $normalized) ?? '';

        return $normalized === '' ? null : $normalized;
    }

    private function extensionFromUpload(UploadedFile $photo): string
    {
        $extension = strtolower($photo->getClientOriginalExtension() ?: 'jpg');

        return in_array($extension, self::EXTENSIONS, true) ? $extension : 'jpg';
    }
}
