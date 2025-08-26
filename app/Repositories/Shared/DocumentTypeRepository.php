<?php

namespace App\Repositories\Shared;


use App\DTO\Shared\DocumentTypeDto;
use App\Http\Filters\Shared\SharedNameFilter;
use App\Models\Shared\DocumentType;
use App\Repositories\Base\BaseRepository;
use App\Repositories\Shared\interface\IDocumentTypeRepository;
use Illuminate\Database\Eloquent\Model;

class DocumentTypeRepository extends BaseRepository implements IDocumentTypeRepository
{
    public function __construct(protected DocumentType $documentType)
    {
        parent::__construct($this->documentType);
    }

    public function create(DocumentTypeDto $dto): Model
    {
        return $this->documentType->create($this->getFields($dto))->refresh();
    }

    public function update(DocumentType $documentType, DocumentTypeDto $dto): DocumentType
    {
        return tap($documentType)->update($this->getFields($dto));
    }

    public function allFilter($columns = ['*'], SharedNameFilter $filters = null)
    {
        return $this->documentType
            ->select($columns)
            ->filter($filters)
            ->orderBy('name')
            ->orderBy('deleted_at')
            ->paginate()
            ->withQueryString();
    }

    /**
     * @param DocumentTypeDto $dto
     * @return array
     */
    public function getFields(DocumentTypeDto $dto): array
    {
        return [
            'name' => $dto->name,
            'description' => $dto->description,
        ];
    }
}
