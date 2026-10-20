@extends('emails.layout')

@section('subject', "Request Declined: {$reference}")

@section('content')
    <h2 style="margin:0 0 16px;color:#44403c;font-size:20px;font-weight:700;">
        Request has been declined
    </h2>

    <p style="margin:0 0 16px;">
        Hi {{ $approverName }},
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
            <td style="padding:8px 12px;border-bottom:1px solid #e7e5e4;font-weight:600;color:#44403c;">Page</td>
            <td style="padding:8px 12px;border-bottom:1px solid #e7e5e4;color:#44403c;">{{ $pageTitle }}</td>
        </tr>
    </table>

    <p style="margin:0 0 8px;font-size:13px;color:#78716c;">
        This request has been declined following a review by another approver. Your approval is no longer needed and no action is required from you.
    </p>
@endsection
