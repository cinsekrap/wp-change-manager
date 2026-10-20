@extends('emails.layout')

@section('subject', "Confirm updates about {$reference}")

@section('content')
    <div style="margin:0 0 24px;padding:16px 20px;background-color:#f5f5f4;border-left:4px solid #0f766e;border-radius:4px;">
        <h2 style="margin:0 0 4px;color:#0f766e;font-size:20px;font-weight:700;">Confirm you want these updates</h2>
        <p style="margin:0;color:#0f766e;font-size:14px;">{{ $publicTitle }}</p>
    </div>

    <p style="margin:0 0 16px;">
        {!! nl2br(e($customBody ?? $defaultBody)) !!}
    </p>

    <p style="margin:0 0 24px;">
        <a href="{{ $confirmUrl }}" style="display:inline-block;padding:12px 24px;background-color:#0f766e;color:#ffffff;text-decoration:none;border-radius:9999px;font-weight:600;">Yes, keep me posted</a>
    </p>

    <p style="margin:0;font-size:13px;color:#78716c;">
        If you did not ask for this, ignore this email — nothing will be sent unless you confirm.
    </p>
@endsection
