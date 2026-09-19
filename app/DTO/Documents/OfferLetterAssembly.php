<?php

declare(strict_types=1);

namespace App\DTO\Documents;

use App\Models\Institution\OfferLetterTemplate;

final readonly class OfferLetterAssembly
{
    public function __construct(
        public OfferLetterTemplate $documentTemplate,
        public string $studentName,
        public string $studentIdNumber,
        public string $studentNumber,
        public string $intakePeriod,
        public string $department,
        public string $level,
        public string $course,
        public string $modeOfStudy,
        public string $tuition,
        public ?string $offerLetterDate,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function viewData(string $generatedAt): array
    {
        return [
            'documentTemplate' => $this->documentTemplate,
            'studentName' => $this->studentName,
            'studentIdNumber' => $this->studentIdNumber,
            'studentNumber' => $this->studentNumber,
            'intakePeriod' => $this->intakePeriod,
            'department' => $this->department,
            'level' => $this->level,
            'course' => $this->course,
            'modeOfStudy' => $this->modeOfStudy,
            'tuition' => $this->tuition,
            'generatedAt' => $generatedAt,
        ];
    }

    /**
     * @return list<mixed>
     */
    public function toLegacyList(): array
    {
        return [
            $this->documentTemplate,
            $this->studentName,
            $this->studentIdNumber,
            $this->studentNumber,
            $this->intakePeriod,
            $this->department,
            $this->level,
            $this->course,
            $this->modeOfStudy,
            $this->tuition,
            $this->offerLetterDate,
        ];
    }
}
