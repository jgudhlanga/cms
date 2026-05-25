@component('mail::message')
# Bulk enrolment finalisation report

@if($isDryRun ?? false)
> DRY RUN: no changes were persisted. This report shows what would have happened.
@endif

Date window: {{ $startDate }} to {{ $endDate }}

Successfully finalised: {{ $successfulFinalised }}

Failed finalisations: {{ $failedFinalisations }}

@if(!empty($reportPath))
Failure report CSV path: {{ $reportPath }}
@endif

Thanks,\n{{ config('app.name') }}
@endcomponent

