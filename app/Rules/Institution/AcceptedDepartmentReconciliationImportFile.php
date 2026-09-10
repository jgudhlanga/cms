<?php

declare(strict_types=1);

namespace App\Rules\Institution;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Http\UploadedFile;

class AcceptedDepartmentReconciliationImportFile implements ValidationRule
{
    /**
     * Must stay in step with what the reader can actually open. openspout (behind SimpleExcelReader)
     * supports csv, xlsx and ods only — listing legacy xls here meant a valid-looking upload threw
     * UnsupportedTypeException and surfaced as a 500 instead of a validation message.
     *
     * @var list<string>
     */
    public const EXTENSIONS = ['xlsx', 'csv', 'ods'];

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! $value instanceof UploadedFile) {
            $fail(__('trans.department_reconciliation_import_invalid_file_type'));

            return;
        }

        $extension = strtolower($value->getClientOriginalExtension());

        if (! in_array($extension, self::EXTENSIONS, true)) {
            $fail(__('trans.department_reconciliation_import_invalid_file_type'));
        }
    }
}
