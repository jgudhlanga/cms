<?php

namespace Database\Factories\Finance;

use App\Models\Finance\Invoice;
use App\Models\Finance\InvoiceReceiptAllocation;
use App\Models\Finance\Receipt;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<InvoiceReceiptAllocation>
 */
class InvoiceReceiptAllocationFactory extends Factory
{
    protected $model = InvoiceReceiptAllocation::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $invoice = Invoice::factory()->create();
        $receipt = Receipt::factory()->create(['tenant_id' => $invoice->tenant_id]);

        return [
            'invoice_id' => $invoice->id,
            'receipt_id' => $receipt->id,
            'amount' => min((float) $invoice->amount, (float) $receipt->amount),
        ];
    }
}
