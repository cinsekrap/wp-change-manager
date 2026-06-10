@extends('emails.layout')

@section('subject', "Your access is ready: {$reference}")

@section('content')
    <div style="margin:0 0 24px;padding:16px 20px;background-color:#ECFDF5;border-left:4px solid #059669;border-radius:4px;">
        <h2 style="margin:0 0 4px;color:#065F46;font-size:20px;font-weight:700;">Your access is ready</h2>
        <p style="margin:0;color:#047857;font-size:14px;">Reference {{ $reference }}</p>
    </div>

    <p style="margin:0 0 16px;">
        Hi {{ $recipientName }},
    </p>

    <p style="margin:0 0 16px;">
        {!! nl2br(e($customBody ?? $defaultBody)) !!}
    </p>

    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin:0 0 16px;border-collapse:collapse;">
        <tr>
            <td style="padding:8px 12px;border-bottom:1px solid #eeeeee;font-weight:600;width:140px;color:#3C3C3B;">Site</td>
            <td style="padding:8px 12px;border-bottom:1px solid #eeeeee;color:#3C3C3B;">{{ $siteName }}</td>
        </tr>
        <tr>
            <td style="padding:8px 12px;border-bottom:1px solid #eeeeee;font-weight:600;color:#3C3C3B;">Access to</td>
            <td style="padding:8px 12px;border-bottom:1px solid #eeeeee;color:#3C3C3B;">{{ $cptName }}</td>
        </tr>
    </table>

    @if($loginUrl)
    {{-- CTA Button --}}
    <table role="presentation" cellpadding="0" cellspacing="0" style="margin:0 auto 24px;">
        <tr>
            <td style="border-radius:50px;background-color:#059669;">
                <a href="{{ $loginUrl }}" target="_blank" style="display:inline-block;padding:12px 32px;color:#ffffff;font-size:15px;font-weight:600;text-decoration:none;border-radius:50px;">
                    Log In
                </a>
            </td>
        </tr>
    </table>
    @endif

    <p style="margin:0 0 8px;font-size:13px;color:#6E6E6D;">
        If you have any trouble logging in or using the tool, please contact the marketing team.
    </p>
@endsection
