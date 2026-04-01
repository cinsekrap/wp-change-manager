@extends('emails.layout')

@section('subject', "Approval No Longer Required: {$reference}")

@section('content')
    <h2 style="margin:0 0 16px;color:#3C3C3B;font-size:20px;font-weight:700;">
        Your approval is no longer required
    </h2>

    <p style="margin:0 0 16px;">
        Hi {{ $approverName }},
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
            <td style="padding:8px 12px;border-bottom:1px solid #eeeeee;font-weight:600;color:#3C3C3B;">Page</td>
            <td style="padding:8px 12px;border-bottom:1px solid #eeeeee;color:#3C3C3B;">{{ $pageTitle }}</td>
        </tr>
    </table>

    <p style="margin:0 0 8px;font-size:13px;color:#6E6E6D;">
        {{ $overriddenBy }} has decided to progress this request. If you have any concerns, please contact the web team directly.
    </p>
@endsection
