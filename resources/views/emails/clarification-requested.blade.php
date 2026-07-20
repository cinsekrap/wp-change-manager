@extends('emails.layout')

@section('subject', "Change Request {$reference} — We need some more information")

@section('content')
    <div style="margin:0 0 24px;padding:16px 20px;background-color:#EFF6FF;border-left:4px solid #2563EB;border-radius:4px;">
        <h2 style="margin:0 0 4px;color:#1E40AF;font-size:20px;font-weight:700;">We need some more information</h2>
        <p style="margin:0;color:#1D4ED8;font-size:14px;">Reference {{ $reference }} — awaiting your response.</p>
    </div>

    <p style="margin:0 0 16px;">
        {!! nl2br(e($customBody ?? $defaultBody)) !!}
    </p>

    @if($clarificationMessage)
    <div style="margin:0 0 20px;padding:12px 16px;background-color:#EFF6FF;border:1px solid #BFDBFE;border-radius:8px;">
        <p style="margin:0 0 4px;font-size:13px;font-weight:600;color:#1E40AF;">Our question:</p>
        <p style="margin:0;color:#3C3C3B;">{!! nl2br(e($clarificationMessage)) !!}</p>
    </div>
    @endif

    <div style="margin:0 0 24px;padding:12px 16px;background-color:#F0F0EF;border-radius:8px;">
        <p style="margin:0;font-size:13px;color:#3C3C3B;">
            <strong>What this means:</strong> your request is paused while we wait to hear from you, so our usual turnaround targets (SLA) and any deadline you requested are on hold. As soon as you respond, the request goes straight back into the queue where it left off.
        </p>
    </div>

    {{-- Request context --}}
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin:0 0 24px;border-collapse:collapse;">
        <tr>
            <td style="padding:8px 12px;border-bottom:1px solid #eeeeee;font-weight:600;width:140px;color:#3C3C3B;">Site</td>
            <td style="padding:8px 12px;border-bottom:1px solid #eeeeee;color:#3C3C3B;">{{ $siteName }}</td>
        </tr>
        @if($isAccessRequest)
        <tr>
            <td style="padding:8px 12px;border-bottom:1px solid #eeeeee;font-weight:600;color:#3C3C3B;">Access to</td>
            <td style="padding:8px 12px;border-bottom:1px solid #eeeeee;color:#3C3C3B;">{{ $cptName }}</td>
        </tr>
        <tr>
            <td style="padding:8px 12px;border-bottom:1px solid #eeeeee;font-weight:600;color:#3C3C3B;">Access for</td>
            <td style="padding:8px 12px;border-bottom:1px solid #eeeeee;color:#3C3C3B;">{{ $recipientName }}</td>
        </tr>
        @else
        <tr>
            <td style="padding:8px 12px;border-bottom:1px solid #eeeeee;font-weight:600;color:#3C3C3B;">Page</td>
            <td style="padding:8px 12px;border-bottom:1px solid #eeeeee;color:#3C3C3B;">{{ $isNewPage ? 'New page: ' : '' }}{{ $pageTitle }}</td>
        </tr>
        @endif
    </table>

    <p style="margin:0 0 24px;font-size:14px;color:#6E6E6D;">
        Use the button below to reply with a comment — you can also update your original request if anything needs correcting:
    </p>

    {{-- CTA Button --}}
    <table role="presentation" cellpadding="0" cellspacing="0" style="margin:0 auto;">
        <tr>
            <td style="border-radius:50px;background-color:#B52159;">
                <a href="{{ $respondUrl }}" target="_blank" style="display:inline-block;padding:12px 32px;color:#ffffff;font-size:15px;font-weight:600;text-decoration:none;border-radius:50px;">
                    Respond to This Request
                </a>
            </td>
        </tr>
    </table>
@endsection
