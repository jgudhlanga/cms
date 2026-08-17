<?php

declare(strict_types=1);

namespace App\Services\Students;

use App\Contracts\Students\StudentIdCardPrinter;
use App\Models\Students\StudentIdCardRequest;
use App\Support\Students\StudentIdCardPrintResult;
use RuntimeException;

/**
 * Adapter for a future physical PVC card printer (Evolis, HID Fargo, Magicard, CUPS).
 *
 * Implement vendor SDK / CUPS job submission in print() using the same
 * StudentIdCardPrintResult contract. Bind via config('id_cards.printer.driver').
 */
class PhysicalCardPrinter implements StudentIdCardPrinter
{
    public function print(StudentIdCardRequest $request): StudentIdCardPrintResult
    {
        throw new RuntimeException(
            'Physical ID card printer is not configured. Set ID_CARD_PRINTER_DRIVER=pdf until the hardware adapter is implemented.'
        );
    }

    public function printMany(iterable $requests): StudentIdCardPrintResult
    {
        throw new RuntimeException(
            'Physical ID card printer is not configured. Set ID_CARD_PRINTER_DRIVER=pdf until the hardware adapter is implemented.'
        );
    }
}
