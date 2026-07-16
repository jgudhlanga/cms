@component('mail::message')
# {{ __('examinations.import_completed_heading') }}

**{{ __('examinations.import_status') }}:** {{ $status }}

**{{ __('examinations.import_file') }}:** {{ $filename }}

**{{ __('examinations.rows_total') }}:** {{ $rowsTotal }}

**{{ __('examinations.rows_processed') }}:** {{ $rowsProcessed }}

**{{ __('examinations.rows_upserted') }}:** {{ $rowsUpserted }}

**{{ __('examinations.rows_failed') }}:** {{ $rowsFailed }}

@if(!empty($errorMessage))
**{{ __('examinations.import_error') }}:** {{ $errorMessage }}
@endif

Thanks,<br>
{{ config('app.name') }}
@endcomponent
