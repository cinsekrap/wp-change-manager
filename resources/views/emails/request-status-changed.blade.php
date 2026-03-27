@extends('emails.layout')

@section('subject', "Change Request {$reference} — {$newStatus}")

@section('content')
    <h2 style="margin:0 0 16px;color:#3C3C3B;font-size:20px;font-weight:700;">
        Your request has been updated
    </h2>

    <p style="margin:0 0 16px;">
        The status of your change request <strong>{{ $reference }}</strong> has changed:
    </p>

    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin:0 0 24px;border-collapse:collapse;">
        <tr>
            <td style="padding:8px 12px;border-bottom:1px solid #eeeeee;font-weight:600;width:140px;color:#3C3C3B;">Reference</td>
            <td style="padding:8px 12px;border-bottom:1px solid #eeeeee;color:#3C3C3B;">{{ $reference }}</td>
        </tr>
        <tr>
            <td style="padding:8px 12px;border-bottom:1px solid #eeeeee;font-weight:600;color:#3C3C3B;">Previous Status</td>
            <td style="padding:8px 12px;border-bottom:1px solid #eeeeee;color:#3C3C3B;">{{ $oldStatus }}</td>
        </tr>
        <tr>
            <td style="padding:8px 12px;border-bottom:1px solid #eeeeee;font-weight:600;color:#3C3C3B;">New Status</td>
            <td style="padding:8px 12px;border-bottom:1px solid #eeeeee;color:#B52159;font-weight:600;">{{ $newStatus }}</td>
        </tr>
    </table>

    @if($rejectionReason)
    <div style="margin:0 0 24px;padding:12px 16px;background-color:#FEF2F2;border:1px solid #FECACA;border-radius:8px;">
        <p style="margin:0 0 4px;font-size:13px;font-weight:600;color:#991B1B;">Reason provided:</p>
        <p style="margin:0;color:#3C3C3B;">{{ $rejectionReason }}</p>
    </div>
    @endif

    @if($newStatus === 'Approved')
    <p style="margin:0 0 16px;">
        Your request has been approved and is now in the queue to be actioned by the web team. Please note that approved requests are scheduled based on priority and team capacity — we'll update you when work is complete.
    </p>
    @elseif($newStatus === 'Scheduled')
    <p style="margin:0 0 16px;">
        Your request has been scheduled for implementation. You'll receive a further update once the changes have been made.
    </p>
    @elseif($newStatus === 'Done')
    <p style="margin:0 0 16px;">
        The changes you requested have been completed. Please check the page and let us know if anything doesn't look right.
    </p>
    @elseif($newStatus === 'Declined' || $newStatus === 'Cancelled')
    <p style="margin:0 0 16px;">
        If you have any questions about this decision, please contact the web team.
    </p>
    @elseif($newStatus === 'Referred' || $newStatus === 'Requires referral')
    <p style="margin:0 0 16px;">
        Your request is being referred for approval. This is a standard part of our process for some types of changes. You'll receive an update once a decision has been made.
    </p>
    @else
    <p style="margin:0 0 16px;">
        You'll receive further updates as your request progresses.
    </p>
    @endif

    <p style="margin:0 0 24px;">
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
