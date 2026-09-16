@extends('errors::minimal')

@php
    // Catch-all for server errors without a dedicated page (501, 502, 504, ...).
    $statusCode = ($exception ?? null) instanceof \Symfony\Component\HttpKernel\Exception\HttpExceptionInterface
        ? $exception->getStatusCode()
        : 500;
@endphp

@section('title', __('trans.error_5xx_title'))
@section('code', $statusCode)
@section('message', __('trans.error_5xx_message'))
