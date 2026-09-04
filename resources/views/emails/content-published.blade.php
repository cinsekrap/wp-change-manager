@extends('emails.layout')

@section('subject', "Content suggestion {$reference} — now live")

@section('content')
    <div style="margin:0 0 24px;padding:16px 20px;background-color:#F0FDF4;border-left:4px solid #3CB764;border-radius:4px;">
        <h2 style="margin:0 0 4px;color:#166534;font-size:20px;font-weight:700;">Your content is live</h2>
        <p style="margin:0;color:#166534;font-size:14px;">Reference {{ $reference }}</p>
    </div>

    <p style="margin:0 0 16px;">
        {!! nl2br(e($customBody ?? $defaultBody)) !!}
    </p>

    <div style="margin:0 0 24px;padding:12px 16px;background-color:#F0FDF4;border:1px solid #BBF7D0;border-radius:8px;">
        <p style="margin:0 0 8px;font-size:13px;font-weight:600;color:#166534;">Published to:</p>
        @foreach($publishedSites as $siteTitle)
            <p style="margin:0 0 4px;color:#3C3C3B;">{{ $siteTitle }}</p>
        @endforeach
    </div>

    <p style="margin:24px 0 0;font-size:13px;color:#6E6E6D;">
        You can check progress at any time using <a href="{{ $trackingUrl }}" style="color:#B52159;">your tracking link</a>.
    </p>
@endsection
