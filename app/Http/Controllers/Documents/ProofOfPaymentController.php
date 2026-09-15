<?php

namespace App\Http\Controllers\Documents;

use App\Http\Controllers\Controller;
use App\Models\Ledgers\Ledger;
use App\Models\Students\StudentApplication;
use App\Models\Users\User;
use App\Support\Media\ProofOfPaymentMedia;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ProofOfPaymentController extends Controller
{
    public function show(Request $request, Media $media): StreamedResponse
    {
        // Only proofs of payment are served here; other media keep their own routes.
        abort_unless(ProofOfPaymentMedia::isProofOfPayment($media), 404);

        // Tenant scopes apply to the owning model, so another tenant's record resolves to null.
        $owner = $media->model;
        abort_if($owner === null, 404);

        abort_unless($this->canView($request->user(), $owner), 403);

        return $media->toInlineResponse($request);
    }

    private function canView(User $user, Model $owner): bool
    {
        if ($owner instanceof StudentApplication) {
            return (int) $user->studentProfile?->id === (int) $owner->student_id
                || $user->can('view', $owner);
        }

        if ($owner instanceof Ledger) {
            $isOwnLedger = $owner->ledgerable_type === $user->getMorphClass()
                && (int) $owner->ledgerable_id === (int) $user->id;

            return $isOwnLedger || $user->can('viewFinances');
        }

        return false;
    }
}
