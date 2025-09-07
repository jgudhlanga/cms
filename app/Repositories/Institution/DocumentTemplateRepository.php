<?php

namespace App\Repositories\Institution;

use App\DTO\DocumentTemplates\DocumentTemplateDto;
use App\Http\Filters\Shared\SharedNameFilter;
use App\Models\Institution\DocumentTemplate;
use App\Repositories\Base\BaseRepository;
use App\Repositories\Institution\interface\IDocumentTemplateRepository;
use Illuminate\Database\Eloquent\Model;

class DocumentTemplateRepository extends BaseRepository implements IDocumentTemplateRepository
{
    public function __construct(protected DocumentTemplate $documentTemplate)
    {
        parent::__construct($this->documentTemplate);
    }

    public function create(DocumentTemplateDto $dto): Model
    {
        return $this->documentTemplate->create($this->getFields($dto))->refresh();
    }

    public function update(DocumentTemplate $documentTemplate, DocumentTemplateDto $dto): DocumentTemplate
    {
        return tap($documentTemplate)->update($this->getFields($dto))->refresh();
    }

    public function allFilter($columns = ['*'], SharedNameFilter $filters = null)
    {
        return $this->documentTemplate
            ->select($columns)
            ->filter($filters)
            ->orderBy('name')
            ->orderBy('deleted_at')
            ->paginate()
            ->withQueryString();
    }

    private function getFields(DocumentTemplateDto $dto): array
    {
        return [
            'document_type_id' => $dto->document_type_id,
            'name' => $dto->name,
            'header_line_1' =>  $dto->header_line_1,
            'header_line_2' =>  $dto->header_line_2,
            'header_address_line_1' =>  $dto->header_address_line_1,
            'header_address_line_2' =>  $dto->header_address_line_2,
            'header_telephone' =>  $dto->header_telephone,
            'header_email' =>  $dto->header_email,
            'header_website' =>  $dto->header_website,
            'body' =>  $dto->body,
        ];
    }
}
