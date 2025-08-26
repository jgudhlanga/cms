<?php

namespace App\Repositories\Institution\interface;

use App\DTO\DocumentTemplates\DocumentTemplateDto;
use App\Http\Filters\Shared\SharedNameFilter;
use App\Models\Institution\DocumentTemplate;
use App\Repositories\Base\Interface\IBaseRepository;

interface IDocumentTemplateRepository extends IBaseRepository
{
    public function create(DocumentTemplateDto $dto);

    public function update(DocumentTemplate $documentTemplate, DocumentTemplateDto $dto);

    public function allFilter($columns = ['*'], SharedNameFilter $filters = null);
}
