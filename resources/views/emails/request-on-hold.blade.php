@extends('emails.layout')

@section('subject', "Change Request {$reference} — On Hold")

@section('content')
    <div style="margin:0 0 24px;padding:16px 20px;background-color:#FFFBEB;border-left:4px solid #D97706;border-radius:4px;">
        <h2 style="margin:0 0 4px;color:#92400E;font-size:20px;font-weight:700;">Your request is on hold</h2>
        <p style="margin:0;color:#B45309;font-size:14px;">Reference {{ $reference }} — paused for now.</p>
    </div>

    @if($holdReason)
    <div style="margin:0 0 20px;padding:12px 16px;background-color:#FFFBEB;border:1px solid #FDE68A;border-radius:8px;">
        <p style="margin:0 0 4px;font-size:13px;font-weight:600;color:#92400E;">Reason:</p>
        <p style="margin:0;color:#3C3C3B;">{{ $holdReason }}</p>
    </div>
    @endif

    <p style="margin:0 0 16px;">
        {!! nl2br(e($customBody ?? $defaultBody)) !!}
    </p>

    <div style="margin:0 0 24px;padding:12px 16px;background-color:#F0F0EF;border-radius:8px;">
        <p style="margin:0;font-size:13px;color:#3C3C3B;">
            <strong>What this means:</strong> while your request is on hold, our usual turnaround targets (SLA) and any deadline you requested are paused. The clock picks up where it left off as soon as work resumes — you'll receive another email when that happens.
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
        <tr>
            <td style="padding:8px 12px;border-bottom:1px solid #eeeeee;font-weight:600;color:#3C3C3B;">Status</td>
            <td style="padding:8px 12px;border-bottom:1px solid #eeeeee;color:#3C3C3B;"><strong>On Hold</strong></td>
        </tr>
    </table>

    <p style="margin:0 0 24px;font-size:14px;color:#6E6E6D;">
        You can view the full details of your request at any time:
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
@endsection
