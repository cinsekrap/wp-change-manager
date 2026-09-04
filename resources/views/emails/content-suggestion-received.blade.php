@extends('emails.layout')

@section('subject', "Content suggestion {$reference} — received")

@section('content')
    <div style="margin:0 0 24px;padding:16px 20px;background-color:#FDF2F6;border-left:4px solid #B52159;border-radius:4px;">
        <h2 style="margin:0 0 4px;color:#961C3E;font-size:20px;font-weight:700;">We have your suggestion</h2>
        <p style="margin:0;color:#961C3E;font-size:14px;">Reference {{ $reference }}</p>
    </div>

    <p style="margin:0 0 16px;">
        {!! nl2br(e($customBody ?? $defaultBody)) !!}
    </p>

    <div style="margin:0 0 24px;padding:12px 16px;background-color:#F0F0EF;border-radius:8px;">
        <p style="margin:0;font-size:13px;color:#3C3C3B;">
            <strong>What you suggested:</strong> {{ $contentTypeLabel }} for {{ $siteName }}.
        </p>
    </div>

    <p style="margin:24px 0 0;font-size:13px;color:#6E6E6D;">
        You can check progress at any time using <a href="{{ $trackingUrl }}" style="color:#B52159;">your tracking link</a>.
    </p>
@endsection
