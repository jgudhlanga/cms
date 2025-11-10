@component('mail::message')
    # Dear {{ $name }}

    Please see the provided url and download the offer letter and adhere to instructions stated within. If there are any issues or alterations to be done on the offer letter do not hesitate to contact us.

    Kind regards,

    Admissions Team
    Harare Polytechnic

    NB: This is a system-generated email. Please do not reply directly to this message.

    @component('mail::button', ['url' => url('/documents/offer-letter/' . $applicationId)])
        Download Offer Letter
    @endcomponent
@endcomponent
