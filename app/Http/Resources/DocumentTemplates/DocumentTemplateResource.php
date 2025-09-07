<?php

namespace App\Http\Resources\DocumentTemplates;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DocumentTemplateResource extends JsonResource
{

    public function toArray(Request $request): array
    {
        return [
            'type' => 'document-template',
            'id' => $this->id,
            'attributes' => [
                'name' => $this->name,
                'documentTypeId' => $this->document_type_id,
                'documentType' => $this->documentType?->name ?? null,
                'headerLine1' => $this->header_line_1,
                'headerLine2' => $this->header_line_2,
                'headerAddressLine1' => $this->header_address_line_1,
                'headerAddressLine2' => $this->header_address_line_2,
                'headerTelephone' => $this->header_telephone,
                'headerEmail' => $this->header_email,
                'headerWebsite' => $this->header_website,
                'headerLogoOne' => $this->header_logo_1,
                'headerLogoOneUrl' => $this->header_logo_one_url,
                'headerLogo2' => $this->header_logo_2,
                'headerLogo2Url' => $this->header_logo_two_url,
                'body' => $this->body,
                'logoOneUrl' => $this->logo_one_url,
                'createdAt' => $this->created_at,
                'updatedAt' => $this->updated_at,
                'deletedAt' => $this->deleted_at,
            ],
        ];
    }
}
