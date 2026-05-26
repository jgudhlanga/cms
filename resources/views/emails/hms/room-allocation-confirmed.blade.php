@component('mail::message')
# Dear {{ $name }}

{{ __('hms.hostel_room_allocation_confirmed_intro') }}

@component('mail::panel')
**{{ __('hms.hostel_room_allocation_email_hostel') }}:** {{ $hostelName }}

**{{ __('hms.hostel_room_allocation_email_room') }}:** {{ $roomName }}

@if(filled($floorNumber))
**{{ __('hms.hostel_room_allocation_email_floor') }}:** {{ $floorNumber }}
@endif

@if(filled($roomType))
**{{ __('hms.hostel_room_allocation_email_room_type') }}:** {{ $roomType }}
@endif

**{{ __('hms.hostel_room_allocation_email_check_in') }}:** {{ $checkIn }}

**{{ __('hms.hostel_room_allocation_email_check_out') }}:** {{ $checkOut }}
@endcomponent

{{ __('trans.thanks') }},<br>
{{ config('app.name') }}
@endcomponent
