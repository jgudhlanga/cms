<?php

declare(strict_types=1);

namespace App\Support\Students;

final readonly class StudentIdCardPrintResult
{
    public function __construct(
        public string $serialNumber,
        public string $driver,
        public string $pdfBinary,
        public string $fileName,
    ) {}
}
