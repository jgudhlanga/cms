@component('mail::message')
# Dear {{ $name }}

{{ __('hms.hostel_room_allocation_confirmed_intro') }}

@component('mail::panel')
**{{ __('hms.hostel_room_allocation_email_hostel') }}:** {{ $hostelName }}

**{{ __('hms.hostel_room_allocation_email_room') }}:** {{ $roomName }}

@if(filled($sectionName))
**{{ __('hms.hostel_room_allocation_email_section') }}:** {{ $sectionName }}
@endif

@if(filled($floorNumber))
**{{ __('hms.hostel_room_allocation_email_floor') }}:** {{ $floorNumber }}
@endif

@if(filled($roomType))
**{{ __('hms.hostel_room_allocation_email_room_type') }}:** {{ $roomType }}
@endif

**{{ __('hms.hostel_room_allocation_email_check_in') }}:** {{ $checkIn }}
@endcomponent

{{ __('trans.thanks') }},<br>
{{ config('app.name') }}
@endcomponent
