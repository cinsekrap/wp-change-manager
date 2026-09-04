@extends('emails.layout')

@section('subject', "Content suggestion {$reference} — agreed, awaiting funding")

@section('content')
    <div style="margin:0 0 24px;padding:16px 20px;background-color:#FFFBEB;border-left:4px solid #F39204;border-radius:4px;">
        <h2 style="margin:0 0 4px;color:#92400E;font-size:20px;font-weight:700;">Agreed — waiting on funding</h2>
        <p style="margin:0;color:#92400E;font-size:14px;">Reference {{ $reference }}</p>
    </div>

    <p style="margin:0 0 16px;">
        {!! nl2br(e($customBody ?? $defaultBody)) !!}
    </p>

    <div style="margin:0 0 24px;padding:12px 16px;background-color:#FFFBEB;border:1px solid #FDE68A;border-radius:8px;">
        <p style="margin:0;font-size:13px;color:#92400E;">
            <strong>Why this can take time:</strong> new content goes through a funding decision before anyone writes it. We would rather tell you honestly that it is queued than give you a date we cannot keep.
        </p>
    </div>

    <p style="margin:24px 0 0;font-size:13px;color:#6E6E6D;">
        You can check progress at any time using <a href="{{ $trackingUrl }}" style="color:#B52159;">your tracking link</a>.
    </p>

    @if($unsubscribeUrl ?? null)
    <p style="margin:12px 0 0;font-size:12px;color:#A0A09F;">
        You are getting this because you asked to follow this suggestion.
        <a href="{{ $unsubscribeUrl }}" style="color:#6E6E6D;">Stop these updates</a>.
    </p>
    @endif
@endsection
