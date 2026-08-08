@extends('errors::minimal')

@section('title', __('trans.error_419_title'))
@section('code', '419')
@section('message', __('trans.error_419_message'))
@section('secondary_url', route('login'))
@section('secondary_label', __('trans.error_sign_in_again'))
