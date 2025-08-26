<?php

namespace App\DTO\DocumentTemplates;

use App\Http\Requests\DocumentTemplates\DocumentTemplateRequest;

readonly class DocumentTemplateDto
{
    public function __construct(
        public string   $name,
		public ? string $header_line_1,
		public ? string $header_line_2,
		public ? string $header_address_line_1,
        public ? string $header_address_line_2,
        public ? string $header_telephone,
        public ? string $header_email,
        public ? string $header_website,
        public ? string $header_logo_1,
        public ? string $header_logo_2,
        public ? string $body,
    )
    {
    }

    public static function fromDocumentTemplateRequest(DocumentTemplateRequest $request): DocumentTemplateDto
    {
        return new self(
            name: $request->name,
			header_line_1: $request->header_line_1,
            header_line_2: $request->header_line_2,
            header_address_line_1: $request->header_address_line_1,
            header_address_line_2: $request->header_address_line_2,
            header_telephone: $request->header_telephone,
            header_email: $request->header_email,
            header_website: $request->header_website,
            header_logo_1: $request->header_logo_1,
            header_logo_2: $request->header_logo_2,
            body: $request->body,
        );
    }
}
