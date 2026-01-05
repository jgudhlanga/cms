@extends('errors::minimal')

@section('title', __('System Under Maintenance'))
@section('code', '503')
@section('message', __('The system is temporarily unavailable while updates are being applied. Please try again after 24 hours.'))
