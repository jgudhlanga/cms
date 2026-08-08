<?php

declare(strict_types=1);

namespace App\Http\Controllers\Institution\Config;

use App\Http\Controllers\Controller;
use App\Http\Requests\Institution\UpdateInstitutionFeaturesRequest;
use App\Models\Institution\InstitutionFeature;
use App\Services\Institution\InstitutionFeatureService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class InstitutionFeatureController extends Controller
{
    public function __construct(
        private readonly InstitutionFeatureService $featureService,
    ) {}

    public function index(Request $request): Response
    {
        $this->authorize('manage:institution-features');

        $tenantId = (int) $request->user()->tenant_id;
        $features = $this->featureService->forTenant($tenantId);

        return Inertia::render('institution/config/Features', [
            'features' => [
                InstitutionFeature::ALLOW_ONLINE_CLEARANCE => $features->allowsOnlineClearance(),
            ],
        ]);
    }

    public function update(UpdateInstitutionFeaturesRequest $request): RedirectResponse
    {
        $tenantId = (int) $request->user()->tenant_id;

        $this->featureService->update($tenantId, [
            InstitutionFeature::ALLOW_ONLINE_CLEARANCE => $request->boolean(InstitutionFeature::ALLOW_ONLINE_CLEARANCE),
        ]);

        return back()->with('success', __('trans.institution_features_saved'));
    }
}
