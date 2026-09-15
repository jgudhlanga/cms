<?php

namespace App\Support\Media;

use App\Models\Ledgers\Ledger;
use App\Models\Students\StudentApplication;
use Illuminate\Database\Eloquent\Relations\Relation;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

/**
 * Proof-of-payment uploads carry bank and personal details, so they live on a private disk and are served
 * only through the authorized documents.proof-of-payment route.
 */
final class ProofOfPaymentMedia
{
    public const string DISK = 'proofs-of-payment';

    /**
     * Media collections holding proofs of payment, per owning model.
     *
     * @var array<class-string, list<string>>
     */
    public const array COLLECTIONS = [
        StudentApplication::class => ['application-fee', 'tuition-fee'],
        Ledger::class => ['receipts'],
    ];

    public static function isProofOfPayment(Media $media): bool
    {
        $modelClass = Relation::getMorphedModel($media->model_type) ?? $media->model_type;

        return in_array($media->collection_name, self::COLLECTIONS[$modelClass] ?? [], true);
    }

    public static function url(int $mediaId): string
    {
        return route('documents.proof-of-payment', ['media' => $mediaId]);
    }
}
