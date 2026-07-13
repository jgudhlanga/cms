@component('mail::message')
# Dear {{ $name }}

{{ __('hms.hostel_application_awaiting_payment_intro') }}

**{{ __('hms.payment_due_date') }}:** {{ $paymentDueDate }}

@component('mail::button', ['url' => $portalUrl])
Student portal
@endcomponent

{{ __('trans.thanks') }},<br>
{{ config('app.name') }}
@endcomponent
