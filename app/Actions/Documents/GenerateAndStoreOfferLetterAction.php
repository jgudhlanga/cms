<?php

declare(strict_types=1);

namespace App\Actions\Documents;

use App\Models\Students\StudentApplication;
use App\Services\Documents\OfferLetterAssembler;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class GenerateAndStoreOfferLetterAction
{
    public function __construct(
        private readonly OfferLetterAssembler $assembler,
    ) {}

    public function execute(
        StudentApplication $application,
        bool $requireVerifiedClassList = true,
    ): Media {
        return DB::transaction(function () use ($application, $requireVerifiedClassList): Media {
            /** @var StudentApplication $locked */
            $locked = StudentApplication::query()
                ->whereKey($application->id)
                ->lockForUpdate()
                ->firstOrFail();

            $existing = $this->readableStoredMedia($locked);
            if ($existing instanceof Media) {
                if ((int) $locked->offer_letter_id !== (int) $existing->id) {
                    $locked->update(['offer_letter_id' => $existing->id]);
                }

                return $existing;
            }

            $assembly = $this->assembler->assemble($locked, $requireVerifiedClassList);
            $assembly->documentTemplate->loadMissing(['institutionDepartments', 'levels']);
            $generatedAt = now()->format('d M Y');
            $fileName = $this->fileName($assembly->studentNumber, $assembly->studentName);
            $binary = Pdf::loadView('students.offer-letter', $assembly->viewData($generatedAt))->output();

            $media = $locked
                ->addMediaFromString($binary)
                ->usingFileName($fileName)
                ->usingName('Offer letter')
                ->withCustomProperties([
                    'offer_letter_template_id' => $assembly->documentTemplate->id,
                    'scope_summary' => $assembly->documentTemplate->scopeSummary(),
                    'tuition' => $assembly->tuition,
                    'generated_at' => $generatedAt,
                ])
                ->toMediaCollection('offer-letter');

            $locked->update(['offer_letter_id' => $media->id]);

            activity('OfferLetter')
                ->performedOn($locked)
                ->event('offer-letter-generated')
                ->withProperties([
                    'media_id' => $media->id,
                    'offer_letter_template_id' => $assembly->documentTemplate->id,
                    'scope_summary' => $assembly->documentTemplate->scopeSummary(),
                    'tuition' => $assembly->tuition,
                    'generated_at' => $generatedAt,
                ])
                ->log('Offer letter generated and stored.');

            return $media;
        });
    }

    private function readableStoredMedia(StudentApplication $application): ?Media
    {
        $application->loadMissing('offerLetter');

        $candidates = collect([
            $application->offerLetter,
            $application->getFirstMedia('offer-letter'),
        ])->filter(fn (mixed $media): bool => $media instanceof Media)
            ->unique(fn (Media $media): int => $media->id);

        foreach ($candidates as $media) {
            if (is_file($media->getPath())) {
                return $media;
            }

            $media->delete();
        }

        if ($application->offer_letter_id) {
            $application->update(['offer_letter_id' => null]);
        }

        return null;
    }

    private function fileName(string $studentNumber, string $studentName): string
    {
        $slug = $studentNumber !== ''
            ? Str::slug($studentNumber)
            : Str::slug($studentName);

        if ($slug === '') {
            $slug = 'student';
        }

        return $slug.'-offer-letter.pdf';
    }
}
