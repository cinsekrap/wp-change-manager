@extends('emails.layout')

@section('subject', "Approval Requested: {$reference}")

@section('content')
    <h2 style="margin:0 0 16px;color:#3C3C3B;font-size:20px;font-weight:700;">
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
            <td style="padding:8px 12px;border-bottom:1px solid #eeeeee;font-weight:600;width:140px;color:#3C3C3B;">Reference</td>
            <td style="padding:8px 12px;border-bottom:1px solid #eeeeee;color:#3C3C3B;">{{ $reference }}</td>
        </tr>
        <tr>
            <td style="padding:8px 12px;border-bottom:1px solid #eeeeee;font-weight:600;color:#3C3C3B;">Site</td>
            <td style="padding:8px 12px;border-bottom:1px solid #eeeeee;color:#3C3C3B;">{{ $siteName }}</td>
        </tr>
        <tr>
            <td style="padding:8px 12px;border-bottom:1px solid #eeeeee;font-weight:600;color:#3C3C3B;">Page</td>
            <td style="padding:8px 12px;border-bottom:1px solid #eeeeee;color:#3C3C3B;">{{ $isNewPage ? 'New page: ' : '' }}{{ $pageTitle }}</td>
        </tr>
        <tr>
            <td style="padding:8px 12px;border-bottom:1px solid #eeeeee;font-weight:600;color:#3C3C3B;">Requested by</td>
            <td style="padding:8px 12px;border-bottom:1px solid #eeeeee;color:#3C3C3B;">{{ $requesterName }}</td>
        </tr>
        <tr>
            <td style="padding:8px 12px;border-bottom:1px solid #eeeeee;font-weight:600;color:#3C3C3B;">Changes</td>
            <td style="padding:8px 12px;border-bottom:1px solid #eeeeee;color:#3C3C3B;">{{ $itemCount }} {{ Str::plural('item', $itemCount) }}</td>
        </tr>
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
            <td style="border-radius:50px;background-color:#B52159;">
                <a href="{{ $approvalUrl }}" target="_blank" style="display:inline-block;padding:12px 32px;color:#ffffff;font-size:15px;font-weight:600;text-decoration:none;border-radius:50px;">
                    Review &amp; Respond
                </a>
            </td>
        </tr>
    </table>

    <p style="margin:0 0 8px;font-size:13px;color:#6E6E6D;">
        <strong>What does approval mean?</strong> By approving, you're confirming that you're happy for the web team to make the requested changes to the website. If you have concerns or need changes to the request, you can reject it with a note explaining why.
    </p>

    <p style="margin:16px 0 0;font-size:13px;color:#A0A09F;">
        This link is unique to you and will remain active until you respond. Please do not forward this email.
    </p>
@endsection
