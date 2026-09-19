<?php

declare(strict_types=1);

namespace App\Http\Controllers\Institution\Config;

use App\DTO\Documents\OfferLetterTemplateDto;
use App\Http\Controllers\Controller;
use App\Http\Filters\Shared\SharedNameFilter;
use App\Http\Requests\Documents\OfferLetterTemplateRequest;
use App\Http\Resources\Documents\OfferLetterTemplateResource;
use App\Http\Resources\Institution\IntakePeriodResource;
use App\Models\Institution\IntakePeriod;
use App\Models\Institution\OfferLetterTemplate;
use App\Repositories\Institution\interface\IOfferLetterTemplateRepository;
use App\Services\Documents\OfferLetterAssembler;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\MediaLibrary\MediaCollections\Exceptions\FileDoesNotExist;
use Spatie\MediaLibrary\MediaCollections\Exceptions\FileIsTooBig;

class OfferLetterTemplateController extends Controller
{
    public function __construct(
        protected IOfferLetterTemplateRepository $repository,
        protected OfferLetterAssembler $offerLetterAssembler,
    ) {}

    public function index(SharedNameFilter $filters, IntakePeriod $intakePeriod): Response
    {
        $this->authorize('view', $intakePeriod);

        $templates = OfferLetterTemplateResource::collection(
            $this->repository->allForIntake($intakePeriod, ['*'], $filters),
        );

        return Inertia::render('institution/dropdowns/intakePeriods/offerLetters/Index', [
            'intakePeriod' => IntakePeriodResource::make($intakePeriod),
            'offerLetterTemplates' => $templates,
            'filters' => request()->only(['search', 'trashed']),
            'trashedCount' => OfferLetterTemplate::onlyTrashed()
                ->where('intake_period_id', $intakePeriod->id)
                ->count(),
        ]);
    }

    public function create(IntakePeriod $intakePeriod): Response
    {
        $this->authorize('update', $intakePeriod);

        return Inertia::render('institution/dropdowns/intakePeriods/offerLetters/Create', [
            'intakePeriod' => IntakePeriodResource::make($intakePeriod),
        ]);
    }

    public function store(OfferLetterTemplateRequest $request, IntakePeriod $intakePeriod): RedirectResponse
    {
        $this->authorize('update', $intakePeriod);

        DB::transaction(function () use ($request, $intakePeriod): void {
            $template = $this->repository->createForIntake(
                $intakePeriod,
                OfferLetterTemplateDto::fromRequest($request),
            );
            $this->uploadLogos($request, $template);
        });

        return to_route('intake-periods.offer-letter-templates.index', $intakePeriod);
    }

    public function edit(IntakePeriod $intakePeriod, OfferLetterTemplate $offerLetterTemplate): Response
    {
        $this->authorize('update', $intakePeriod);
        $this->assertBelongsToIntake($intakePeriod, $offerLetterTemplate);

        $offerLetterTemplate->loadMissing([
            'intakePeriod',
            'institutionDepartments.department',
            'levels',
            'course',
            'modeOfStudy',
            'headerLogoOne',
            'headerLogoTwo',
        ]);

        return Inertia::render('institution/dropdowns/intakePeriods/offerLetters/Edit', [
            'intakePeriod' => IntakePeriodResource::make($intakePeriod),
            'offerLetterTemplate' => OfferLetterTemplateResource::make($offerLetterTemplate),
        ]);
    }

    public function update(
        OfferLetterTemplateRequest $request,
        IntakePeriod $intakePeriod,
        OfferLetterTemplate $offerLetterTemplate,
    ): RedirectResponse {
        $this->authorize('update', $intakePeriod);
        $this->assertBelongsToIntake($intakePeriod, $offerLetterTemplate);

        DB::transaction(function () use ($request, $offerLetterTemplate): void {
            $template = $this->repository->updateTemplate(
                $offerLetterTemplate,
                OfferLetterTemplateDto::fromRequest($request),
            );
            $this->uploadLogos($request, $template);
        });

        return to_route('intake-periods.offer-letter-templates.index', $intakePeriod);
    }

    public function destroy(IntakePeriod $intakePeriod, OfferLetterTemplate $offerLetterTemplate): void
    {
        $this->authorize('update', $intakePeriod);
        $this->assertBelongsToIntake($intakePeriod, $offerLetterTemplate);
        $this->repository->delete($offerLetterTemplate);
    }

    public function restore(IntakePeriod $intakePeriod, string $offer_letter_template): void
    {
        $this->authorize('update', $intakePeriod);
        $template = $this->repository->findTrashed($offer_letter_template);
        abort_unless($template instanceof OfferLetterTemplate, 404);
        $this->assertBelongsToIntake($intakePeriod, $template);
        $this->repository->restore($template);
    }

    public function forceDelete(IntakePeriod $intakePeriod, OfferLetterTemplate $offerLetterTemplate): void
    {
        $this->authorize('update', $intakePeriod);
        $this->assertBelongsToIntake($intakePeriod, $offerLetterTemplate);
        $this->repository->delete($offerLetterTemplate, true);
    }

    public function preview(IntakePeriod $intakePeriod, OfferLetterTemplate $offerLetterTemplate)
    {
        $this->authorize('view', $intakePeriod);
        $this->assertBelongsToIntake($intakePeriod, $offerLetterTemplate);

        $generatedAt = now()->format('d M Y');
        $fileName = Str::slug((string) $offerLetterTemplate->name).'-preview.pdf';
        $pdf = Pdf::loadView(
            'students.offer-letter',
            $this->offerLetterAssembler->previewViewData($offerLetterTemplate, $generatedAt),
        );

        return $pdf->stream($fileName);
    }

    /**
     * @throws FileDoesNotExist
     * @throws FileIsTooBig
     */
    public function uploadLogos(OfferLetterTemplateRequest $request, OfferLetterTemplate $template): void
    {
        if ($request->hasFile('header_logo_1') && $request->file('header_logo_1')->isValid() && $request->file('header_logo_1')->getSize() > 0) {
            $media = $template->addMedia($request->file('header_logo_1'))
                ->toMediaCollection('logo-1');
            $template->update(['header_logo_1' => $media->id]);
        }

        if ($request->hasFile('header_logo_2') && $request->file('header_logo_2')->isValid() && $request->file('header_logo_2')->getSize() > 0) {
            $media = $template->addMedia($request->file('header_logo_2'))
                ->toMediaCollection('logo-2');
            $template->update(['header_logo_2' => $media->id]);
        }
    }

    private function assertBelongsToIntake(IntakePeriod $intakePeriod, OfferLetterTemplate $template): void
    {
        abort_unless((int) $template->intake_period_id === (int) $intakePeriod->id, 404);
    }
}
