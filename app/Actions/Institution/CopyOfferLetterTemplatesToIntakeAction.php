<?php

declare(strict_types=1);

namespace App\Actions\Institution;

use App\Models\Institution\IntakePeriod;
use App\Models\Institution\OfferLetterTemplate;
use App\Support\Documents\CopyOfferLetterTemplateLogos;
use App\Support\Documents\SeedDefaultOfferLetterTemplates;
use Illuminate\Support\Facades\DB;

class CopyOfferLetterTemplatesToIntakeAction
{
    public function __construct(
        private readonly CopyOfferLetterTemplateLogos $copyLogos = new CopyOfferLetterTemplateLogos,
        private readonly SeedDefaultOfferLetterTemplates $seedDefaults = new SeedDefaultOfferLetterTemplates,
    ) {}

    public function execute(IntakePeriod $target): int
    {
        return (int) DB::transaction(function () use ($target): int {
            $alreadyHas = OfferLetterTemplate::query()
                ->where('tenant_id', $target->tenant_id)
                ->where('intake_period_id', $target->id)
                ->exists();

            if ($alreadyHas) {
                return 0;
            }

            $sourceIntake = $this->sourceIntake($target);

            if (! $sourceIntake instanceof IntakePeriod) {
                return $this->seedDefaults->execute($target);
            }

            $created = 0;

            $templates = OfferLetterTemplate::query()
                ->where('tenant_id', $target->tenant_id)
                ->where('intake_period_id', $sourceIntake->id)
                ->with(['institutionDepartments', 'levels'])
                ->orderBy('id')
                ->get();

            foreach ($templates as $source) {
                $created += $this->copyOne($source, $target);
            }

            return $created;
        });
    }

    private function sourceIntake(IntakePeriod $target): ?IntakePeriod
    {
        return IntakePeriod::query()
            ->where('tenant_id', $target->tenant_id)
            ->whereKeyNot($target->id)
            ->whereHas('offerLetterTemplates')
            ->orderByDesc('start_date')
            ->orderByDesc('id')
            ->first();
    }

    private function copyOne(OfferLetterTemplate $source, IntakePeriod $target): int
    {
        $name = $this->uniqueName($source, $target);

        $copy = $source->replicate(['intake_period_id', 'header_logo_1', 'header_logo_2']);
        $copy->tenant_id = $target->tenant_id;
        $copy->intake_period_id = $target->id;
        $copy->name = $name;
        $copy->header_logo_1 = null;
        $copy->header_logo_2 = null;
        $copy->body = $this->bodyWithoutLetterDate((string) $source->body);
        $copy->save();

        $copy->institutionDepartments()->sync($source->institutionDepartments->pluck('id')->all());
        $copy->levels()->sync($source->levels->pluck('id')->all());
        $this->copyLogos->fromOfferLetterTemplate($source, $copy);

        return 1;
    }

    private function uniqueName(OfferLetterTemplate $source, IntakePeriod $target): string
    {
        $candidate = (string) $source->name;
        if (! $this->nameTaken($target, $candidate)) {
            return $candidate;
        }

        $index = 2;
        while ($this->nameTaken($target, $candidate.' ('.$index.')')) {
            $index++;
        }

        return $candidate.' ('.$index.')';
    }

    private function nameTaken(IntakePeriod $target, string $name): bool
    {
        return OfferLetterTemplate::withTrashed()
            ->where('tenant_id', $target->tenant_id)
            ->where('intake_period_id', $target->id)
            ->where('name', $name)
            ->exists();
    }

    private function bodyWithoutLetterDate(string $body): string
    {
        $withoutDate = preg_replace('/\{date\}/', '', $body) ?? $body;

        return str_replace('  ', ' ', $withoutDate);
    }
}
