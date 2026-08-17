<?php

declare(strict_types=1);

namespace App\Contracts\Students;

use App\Models\Students\StudentIdCardRequest;
use App\Support\Students\StudentIdCardPrintResult;

interface StudentIdCardPrinter
{
    public function print(StudentIdCardRequest $request): StudentIdCardPrintResult;

    /**
     * @param  iterable<int, StudentIdCardRequest>  $requests
     */
    public function printMany(iterable $requests): StudentIdCardPrintResult;
}
