@extends('emails.layout')

@section('subject', "Training Required: {$reference}")

@section('content')
    <h2 style="margin:0 0 16px;color:#3C3C3B;font-size:20px;font-weight:700;">
        Your access request has been approved
    </h2>

    <p style="margin:0 0 16px;">
        Hi {{ $recipientName }},
    </p>

    <p style="margin:0 0 16px;">
        {!! nl2br(e($customBody ?? $defaultBody)) !!}
    </p>

    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin:0 0 16px;border-collapse:collapse;">
        <tr>
            <td style="padding:8px 12px;border-bottom:1px solid #eeeeee;font-weight:600;width:140px;color:#3C3C3B;">Reference</td>
            <td style="padding:8px 12px;border-bottom:1px solid #eeeeee;color:#3C3C3B;">{{ $reference }}</td>
        </tr>
        <tr>
            <td style="padding:8px 12px;border-bottom:1px solid #eeeeee;font-weight:600;color:#3C3C3B;">Site</td>
            <td style="padding:8px 12px;border-bottom:1px solid #eeeeee;color:#3C3C3B;">{{ $siteName }}</td>
        </tr>
        <tr>
            <td style="padding:8px 12px;border-bottom:1px solid #eeeeee;font-weight:600;color:#3C3C3B;">Access to</td>
            <td style="padding:8px 12px;border-bottom:1px solid #eeeeee;color:#3C3C3B;">{{ $cptName }}</td>
        </tr>
        <tr>
            <td style="padding:8px 12px;border-bottom:1px solid #eeeeee;font-weight:600;color:#3C3C3B;">Requested by</td>
            <td style="padding:8px 12px;border-bottom:1px solid #eeeeee;color:#3C3C3B;">{{ $requesterName }}</td>
        </tr>
    </table>

    <p style="margin:0 0 8px;">
        Please watch the training video, then confirm you've completed it:
    </p>

    {{-- CTA Buttons --}}
    <table role="presentation" cellpadding="0" cellspacing="0" style="margin:0 auto 12px;">
        <tr>
            <td style="border-radius:50px;background-color:#B52159;">
                <a href="{{ $trainingUrl }}" target="_blank" style="display:inline-block;padding:12px 32px;color:#ffffff;font-size:15px;font-weight:600;text-decoration:none;border-radius:50px;">
                    Watch the Training Video
                </a>
            </td>
        </tr>
    </table>
    <table role="presentation" cellpadding="0" cellspacing="0" style="margin:0 auto 24px;">
        <tr>
            <td style="border-radius:50px;background-color:#3C3C3B;">
                <a href="{{ $confirmUrl }}" target="_blank" style="display:inline-block;padding:12px 32px;color:#ffffff;font-size:15px;font-weight:600;text-decoration:none;border-radius:50px;">
                    Confirm Training Complete
                </a>
            </td>
        </tr>
    </table>

    <p style="margin:0 0 8px;font-size:13px;color:#6E6E6D;">
        <strong>What happens next?</strong> Once you confirm you've watched the training and feel competent, the team will set up your access and let you know when it's ready.
    </p>

    <p style="margin:16px 0 0;font-size:13px;color:#A0A09F;">
        This link is unique to you. Please do not forward this email.
    </p>
@endsection
