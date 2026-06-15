@component('mail::message')
# Dear {{ $name }}

{{ __('hms.hostel_application_declined_intro') }}

> {{ $declineReason }}

{{ __('trans.thanks') }},<br>
{{ config('app.name') }}
@endcomponent
