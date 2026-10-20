@extends('emails.layout')

@section('subject', "Approval Requested: {$reference}")

@section('content')
    <h2 style="margin:0 0 16px;color:#44403c;font-size:20px;font-weight:700;">
        Your approval is needed
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
        @if($isAccessRequest)
        <tr>
            <td style="padding:8px 12px;border-bottom:1px solid #e7e5e4;font-weight:600;color:#44403c;">Access to</td>
            <td style="padding:8px 12px;border-bottom:1px solid #e7e5e4;color:#44403c;">{{ $cptName }}</td>
        </tr>
        <tr>
            <td style="padding:8px 12px;border-bottom:1px solid #e7e5e4;font-weight:600;color:#44403c;">Access for</td>
            <td style="padding:8px 12px;border-bottom:1px solid #e7e5e4;color:#44403c;">{{ $recipientName }} ({{ $recipientEmail }})</td>
        </tr>
        <tr>
            <td style="padding:8px 12px;border-bottom:1px solid #e7e5e4;font-weight:600;color:#44403c;">Requested by</td>
            <td style="padding:8px 12px;border-bottom:1px solid #e7e5e4;color:#44403c;">{{ $requesterName }}</td>
        </tr>
        @else
        <tr>
            <td style="padding:8px 12px;border-bottom:1px solid #e7e5e4;font-weight:600;color:#44403c;">{{ ($isContentRequest ?? false) ? 'Content' : 'Page' }}</td>
            <td style="padding:8px 12px;border-bottom:1px solid #e7e5e4;color:#44403c;">{{ (!($isContentRequest ?? false) && $isNewPage) ? 'New page: ' : '' }}{{ $pageTitle }}</td>
        </tr>
        <tr>
            <td style="padding:8px 12px;border-bottom:1px solid #e7e5e4;font-weight:600;color:#44403c;">Requested by</td>
            <td style="padding:8px 12px;border-bottom:1px solid #e7e5e4;color:#44403c;">{{ $requesterName }}</td>
        </tr>
        <tr>
            <td style="padding:8px 12px;border-bottom:1px solid #e7e5e4;font-weight:600;color:#44403c;">Changes</td>
            <td style="padding:8px 12px;border-bottom:1px solid #e7e5e4;color:#44403c;">{{ $itemCount }} {{ Str::plural('item', $itemCount) }}</td>
        </tr>
        @endif
    </table>

    @if($deadlineDate)
    <div style="margin:0 0 24px;padding:12px 16px;background-color:#FEF3C7;border:1px solid #FDE68A;border-radius:8px;">
        <p style="margin:0;font-size:13px;color:#92400E;">
            <strong>Note:</strong> The requester has asked for this to be completed by <strong>{{ $deadlineDate->format('j M Y') }}</strong>. Your timely response will help us meet this request. This is not a deadline for your approval — but the sooner we hear from you, the sooner we can get started.
        </p>
    </div>
    @endif

    <p style="margin:0 0 8px;">
        You can review the full details and respond using the button below:
    </p>

    {{-- CTA Button --}}
    <table role="presentation" cellpadding="0" cellspacing="0" style="margin:0 auto 24px;">
        <tr>
            <td style="border-radius:8px;background-color:#0f766e;">
                <a href="{{ $approvalUrl }}" target="_blank" style="display:inline-block;padding:12px 32px;color:#ffffff;font-size:15px;font-weight:600;text-decoration:none;border-radius:8px;">
                    Review &amp; Respond
                </a>
            </td>
        </tr>
    </table>

    <p style="margin:0 0 8px;font-size:13px;color:#78716c;">
        @if($isAccessRequest)
            <strong>What does approval mean?</strong> By approving, you're confirming that you're happy for {{ $recipientName }} to be given access to manage this content directly. They'll need to complete training and confirm they're competent before access is set up. If you have concerns, you can reject the request with a note explaining why.
        @else
            <strong>What does approval mean?</strong> By approving, you're confirming that you're happy for the marketing team to make the requested changes to the website. If you have concerns or need changes to the request, you can reject it with a note explaining why.
        @endif
    </p>

    <p style="margin:16px 0 0;font-size:13px;color:#a8a29e;">
        This link is unique to you and will remain active until you respond. Please do not forward this email.
    </p>
@endsection
