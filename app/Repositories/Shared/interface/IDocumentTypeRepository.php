<?php

namespace App\Repositories\Shared\interface;

use App\DTO\Shared\DocumentTypeDto;
use App\Http\Filters\Shared\SharedNameFilter;
use App\Models\Shared\DocumentType;
use App\Repositories\Base\Interface\IBaseRepository;

interface IDocumentTypeRepository extends IBaseRepository
{
    public function create(DocumentTypeDto $dto);

    public function update(DocumentType $documentType, DocumentTypeDto $dto);

    public function allFilter($columns = ['*'], SharedNameFilter $filters = null);
}
