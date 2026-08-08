@extends('errors::minimal')

@section('title', __('trans.error_401_title'))
@section('code', '401')
@section('message', __('trans.error_401_message'))
@section('secondary_url', route('login'))
@section('secondary_label', __('trans.error_sign_in'))
