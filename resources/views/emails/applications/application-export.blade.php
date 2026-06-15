@component('mail::message')
# Application export completed

The application CSV export has finished successfully.

@if(! empty($intakeYear))
Intake year filter: {{ $intakeYear }}
@endif

File: {{ $reportPath }}

Thanks,<br>
{{ config('app.name') }}
@endcomponent
