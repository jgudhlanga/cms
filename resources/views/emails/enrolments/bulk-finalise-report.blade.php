@component('mail::message')
# Bulk enrolment finalisation report

Date window: {{ $startDate }} to {{ $endDate }}

Successfully finalised: {{ $successfulFinalised }}

Failed finalisations: {{ $failedFinalisations }}

@if(!empty($reportPath))
Failure report CSV path: {{ $reportPath }}
@endif

Thanks,\n{{ config('app.name') }}
@endcomponent

