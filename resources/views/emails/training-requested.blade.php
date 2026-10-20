@extends('emails.layout')

@section('subject', "Training Required: {$reference}")

@section('content')
    <h2 style="margin:0 0 16px;color:#44403c;font-size:20px;font-weight:700;">
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
            <td style="padding:8px 12px;border-bottom:1px solid #e7e5e4;font-weight:600;width:140px;color:#44403c;">Reference</td>
            <td style="padding:8px 12px;border-bottom:1px solid #e7e5e4;color:#44403c;">{{ $reference }}</td>
        </tr>
        <tr>
            <td style="padding:8px 12px;border-bottom:1px solid #e7e5e4;font-weight:600;color:#44403c;">Site</td>
            <td style="padding:8px 12px;border-bottom:1px solid #e7e5e4;color:#44403c;">{{ $siteName }}</td>
        </tr>
        <tr>
            <td style="padding:8px 12px;border-bottom:1px solid #e7e5e4;font-weight:600;color:#44403c;">Access to</td>
            <td style="padding:8px 12px;border-bottom:1px solid #e7e5e4;color:#44403c;">{{ $cptName }}</td>
        </tr>
        <tr>
            <td style="padding:8px 12px;border-bottom:1px solid #e7e5e4;font-weight:600;color:#44403c;">Requested by</td>
            <td style="padding:8px 12px;border-bottom:1px solid #e7e5e4;color:#44403c;">{{ $requesterName }}</td>
        </tr>
    </table>

    <p style="margin:0 0 8px;">
        Please watch the training video, then confirm you've completed it:
    </p>

    {{-- CTA Buttons --}}
    <table role="presentation" cellpadding="0" cellspacing="0" style="margin:0 auto 12px;">
        <tr>
            <td style="border-radius:8px;background-color:#1c1917;">
                <a href="{{ $trainingUrl }}" target="_blank" style="display:inline-block;padding:12px 32px;color:#ffffff;font-size:15px;font-weight:600;text-decoration:none;border-radius:8px;">
                    Watch the Training Video
                </a>
            </td>
        </tr>
    </table>
    <table role="presentation" cellpadding="0" cellspacing="0" style="margin:0 auto 24px;">
        <tr>
            <td style="border-radius:8px;background-color:#44403c;">
                <a href="{{ $confirmUrl }}" target="_blank" style="display:inline-block;padding:12px 32px;color:#ffffff;font-size:15px;font-weight:600;text-decoration:none;border-radius:8px;">
                    Confirm Training Complete
                </a>
            </td>
        </tr>
    </table>

    <p style="margin:0 0 8px;font-size:13px;color:#78716c;">
        <strong>What happens next?</strong> Once you confirm you've watched the training and feel competent, the team will set up your access and let you know when it's ready.
    </p>

    <p style="margin:16px 0 0;font-size:13px;color:#a8a29e;">
        This link is unique to you. Please do not forward this email.
    </p>
@endsection
