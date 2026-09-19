<?php

declare(strict_types=1);

namespace App\Repositories\Institution\interface;

use App\DTO\Documents\OfferLetterTemplateDto;
use App\Http\Filters\Shared\SharedNameFilter;
use App\Models\Institution\IntakePeriod;
use App\Models\Institution\OfferLetterTemplate;
use App\Repositories\Base\Interface\IBaseRepository;

interface IOfferLetterTemplateRepository extends IBaseRepository
{
    public function createForIntake(IntakePeriod $intakePeriod, OfferLetterTemplateDto $dto): OfferLetterTemplate;

    public function updateTemplate(OfferLetterTemplate $template, OfferLetterTemplateDto $dto): OfferLetterTemplate;

    public function allForIntake(IntakePeriod $intakePeriod, $columns = ['*'], ?SharedNameFilter $filters = null);
}
