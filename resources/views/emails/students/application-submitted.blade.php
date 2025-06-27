@component('mail::message')
    # Hello {{ $student->full_name }}

    Your application has been successfully submitted.

    **Tracking Number:** {{ $trackingNumber }}

    You can use this number to track your application status.

    @component('mail::button', ['url' => url('/track?ref=' . $trackingNumber)])
        Track Application
    @endcomponent

    Thanks,<br>
    {{ config('app.name') }}
@endcomponent
