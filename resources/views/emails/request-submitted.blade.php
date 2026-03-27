@extends('emails.layout')

@section('subject', "Change Request {$reference} — Submitted")

@section('content')
    <h2 style="margin:0 0 16px;color:#3C3C3B;font-size:20px;font-weight:700;">
        Your request has been received
    </h2>

    <p style="margin:0 0 16px;">
        Thank you for submitting your website change request. Our web team will review it and be in touch if we need any further information.
    </p>

    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin:0 0 24px;border-collapse:collapse;">
        <tr>
            <td style="padding:8px 12px;border-bottom:1px solid #eeeeee;font-weight:600;width:140px;color:#3C3C3B;">Reference</td>
            <td style="padding:8px 12px;border-bottom:1px solid #eeeeee;color:#3C3C3B;">{{ $reference }}</td>
        </tr>
        <tr>
            <td style="padding:8px 12px;border-bottom:1px solid #eeeeee;font-weight:600;color:#3C3C3B;">Site</td>
            <td style="padding:8px 12px;border-bottom:1px solid #eeeeee;color:#3C3C3B;">{{ $siteName }}</td>
        </tr>
        <tr>
            <td style="padding:8px 12px;border-bottom:1px solid #eeeeee;font-weight:600;color:#3C3C3B;">Page</td>
            <td style="padding:8px 12px;border-bottom:1px solid #eeeeee;color:#3C3C3B;">{{ $pageTitle }}</td>
        </tr>
        <tr>
            <td style="padding:8px 12px;border-bottom:1px solid #eeeeee;font-weight:600;color:#3C3C3B;">Items</td>
            <td style="padding:8px 12px;border-bottom:1px solid #eeeeee;color:#3C3C3B;">{{ $itemCount }} {{ Str::plural('change', $itemCount) }}</td>
        </tr>
        @if($deadlineDate)
        <tr>
            <td style="padding:8px 12px;border-bottom:1px solid #eeeeee;font-weight:600;color:#3C3C3B;">Requested by</td>
            <td style="padding:8px 12px;border-bottom:1px solid #eeeeee;color:#3C3C3B;">{{ $deadlineDate->format('j M Y') }}</td>
        </tr>
        @endif
    </table>

    @if($deadlineDate)
    <p style="margin:0 0 16px;font-size:13px;color:#6E6E6D;">
        <strong>Please note:</strong> The date above is your requested completion date. We will do our best to accommodate this, but it is not guaranteed. Requests may require approval or review before work can begin. We'll let you know if there are any issues meeting this date.
    </p>
    @endif

    <p style="margin:0 0 8px;">
        <strong>What happens next?</strong>
    </p>
    <ul style="margin:0 0 24px;padding-left:20px;color:#3C3C3B;">
        <li style="margin-bottom:6px;">Your request will be reviewed by the web team.</li>
        <li style="margin-bottom:6px;">Some requests may need approval from a service lead before work can start.</li>
        <li style="margin-bottom:6px;">You'll receive an email when the status of your request changes.</li>
    </ul>

    <p style="margin:0 0 24px;">
        You can check the status of your request at any time:
    </p>

    {{-- CTA Button --}}
    <table role="presentation" cellpadding="0" cellspacing="0" style="margin:0 auto;">
        <tr>
            <td style="border-radius:50px;background-color:#B52159;">
                <a href="{{ $trackingUrl }}" target="_blank" style="display:inline-block;padding:12px 32px;color:#ffffff;font-size:15px;font-weight:600;text-decoration:none;border-radius:50px;">
                    Track Your Request
                </a>
            </td>
        </tr>
    </table>

    <p style="margin:24px 0 0;font-size:13px;color:#A0A09F;">
        Please keep your reference number <strong>{{ $reference }}</strong> for your records. If you have any questions, contact the web team.
    </p>
@endsection
