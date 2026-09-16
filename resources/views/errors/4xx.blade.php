@extends('errors::minimal')

@php
    // Catch-all for client errors without a dedicated page (400, 405, 410, ...).
    $statusCode = ($exception ?? null) instanceof \Symfony\Component\HttpKernel\Exception\HttpExceptionInterface
        ? $exception->getStatusCode()
        : 400;
@endphp

@section('title', __('trans.error_4xx_title'))
@section('code', $statusCode)
@section('message', __('trans.error_4xx_message'))
