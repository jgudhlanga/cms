<?php

namespace Database\Factories\Institution;

use App\Enums\Shared\DocumentTypeEnum;
use App\Models\Institution\DocumentTemplate;
use App\Models\Shared\DocumentType;
use App\Models\Tenants\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DocumentTemplate>
 */
class DocumentTemplateFactory extends Factory
{
    public function definition(): array
    {
        $documentType = DocumentType::query()->firstOrCreate(
            ['name' => DocumentTypeEnum::ADMISSION_LETTER->name()],
            ['description' => DocumentTypeEnum::ADMISSION_LETTER->description()],
        );

        return [
            'tenant_id' => Tenant::query()->value('id') ?? Tenant::factory(),
            'document_type_id' => $documentType->id,
            'name' => fake()->unique()->words(3, true).' '.fake()->numerify('####'),
            'body' => '<p>{studentName}</p>',
            'header_line_1' => 'Republic of Zimbabwe',
            'header_line_2' => 'Harare Polytechnic',
        ];
    }
}
