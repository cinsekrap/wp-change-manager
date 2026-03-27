@extends('emails.layout')

@section('subject', "New Change Request: {$reference}")

@section('content')
    <h2 style="margin:0 0 16px;color:#3C3C3B;font-size:20px;font-weight:700;">
        New Change Request
    </h2>

    <p style="margin:0 0 16px;">
        A new change request has been submitted and requires your attention.
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
            <td style="padding:8px 12px;border-bottom:1px solid #eeeeee;font-weight:600;color:#3C3C3B;">Requester</td>
            <td style="padding:8px 12px;border-bottom:1px solid #eeeeee;color:#3C3C3B;">{{ $requesterName }} ({{ $requesterEmail }})</td>
        </tr>
        <tr>
            <td style="padding:8px 12px;border-bottom:1px solid #eeeeee;font-weight:600;color:#3C3C3B;">Items</td>
            <td style="padding:8px 12px;border-bottom:1px solid #eeeeee;color:#3C3C3B;">{{ $itemCount }} {{ Str::plural('change', $itemCount) }}</td>
        </tr>
    </table>

    {{-- CTA Button --}}
    <table role="presentation" cellpadding="0" cellspacing="0" style="margin:0 auto;">
        <tr>
            <td style="border-radius:50px;background-color:#B52159;">
                <a href="{{ $adminUrl }}" target="_blank" style="display:inline-block;padding:12px 32px;color:#ffffff;font-size:15px;font-weight:600;text-decoration:none;border-radius:50px;">
                    View Request
                </a>
            </td>
        </tr>
    </table>
@endsection
