@extends('emails.layout')

@section('subject', "New Change Request: {$reference}")

@section('content')
    <h2 style="margin:0 0 16px;color:#44403c;font-size:20px;font-weight:700;">
        New Change Request
    </h2>

    <p style="margin:0 0 16px;">
        {!! nl2br(e($customBody ?? $defaultBody)) !!}
    </p>

    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin:0 0 24px;border-collapse:collapse;">
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
        <tr>
            <td style="padding:8px 12px;border-bottom:1px solid #e7e5e4;font-weight:600;color:#44403c;">Requester</td>
            <td style="padding:8px 12px;border-bottom:1px solid #e7e5e4;color:#44403c;">{{ $requesterName }} ({{ $requesterEmail }})</td>
        </tr>
        <tr>
            <td style="padding:8px 12px;border-bottom:1px solid #e7e5e4;font-weight:600;color:#44403c;">Items</td>
            <td style="padding:8px 12px;border-bottom:1px solid #e7e5e4;color:#44403c;">{{ $itemCount }} {{ Str::plural('change', $itemCount) }}</td>
        </tr>
    </table>

    {{-- CTA Button --}}
    <table role="presentation" cellpadding="0" cellspacing="0" style="margin:0 auto;">
        <tr>
            <td style="border-radius:8px;background-color:#1c1917;">
                <a href="{{ $adminUrl }}" target="_blank" style="display:inline-block;padding:12px 32px;color:#ffffff;font-size:15px;font-weight:600;text-decoration:none;border-radius:8px;">
                    View Request
                </a>
            </td>
        </tr>
    </table>
@endsection
