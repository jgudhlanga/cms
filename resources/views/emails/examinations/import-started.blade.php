@component('mail::message')
# {{ __('examinations.import_started_heading') }}

{{ __('examinations.import_started_body', ['filename' => $filename, 'source' => $source]) }}

Thanks,<br>
{{ config('app.name') }}
@endcomponent
