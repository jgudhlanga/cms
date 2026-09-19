<?php

declare(strict_types=1);

namespace App\Support\Documents;

use App\Models\Institution\DocumentTemplate;
use App\Models\Institution\OfferLetterTemplate;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

final class CopyOfferLetterTemplateLogos
{
    public function fromOfferLetterTemplate(OfferLetterTemplate $source, OfferLetterTemplate $copy): void
    {
        $this->copyCollection($source->getFirstMedia('logo-1'), $copy, 'logo-1', 'header_logo_1');
        $this->copyCollection($source->getFirstMedia('logo-2'), $copy, 'logo-2', 'header_logo_2');
    }

    public function fromDocumentTemplate(DocumentTemplate $source, OfferLetterTemplate $copy): void
    {
        $this->copyCollection($source->getFirstMedia('logo-1'), $copy, 'logo-1', 'header_logo_1');
        $this->copyCollection($source->getFirstMedia('logo-2'), $copy, 'logo-2', 'header_logo_2');
    }

    private function copyCollection(?Media $media, OfferLetterTemplate $copy, string $collection, string $column): void
    {
        if ($media === null) {
            return;
        }

        try {
            $cloned = $media->copy($copy, $collection);
            $copy->update([$column => $cloned->id]);
        } catch (\Throwable) {
            // Logos are optional; a missing file must not block copying templates.
        }
    }
}
