@extends('emails.layout')

@section('subject', "Change Request {$reference} — {$newStatus}")

@section('content')
    {{-- Status-specific heading with colour accent --}}
    @if($rawStatus === 'done')
    <div style="margin:0 0 24px;padding:16px 20px;background-color:#ECFDF5;border-left:4px solid #059669;border-radius:4px;">
        <h2 style="margin:0 0 4px;color:#065F46;font-size:20px;font-weight:700;">Your changes are complete</h2>
        <p style="margin:0;color:#047857;font-size:14px;">Reference {{ $reference }} — all done.</p>
    </div>
    @elseif($rawStatus === 'declined' || $rawStatus === 'cancelled')
    <div style="margin:0 0 24px;padding:16px 20px;background-color:#FEF2F2;border-left:4px solid #DC2626;border-radius:4px;">
        <h2 style="margin:0 0 4px;color:#991B1B;font-size:20px;font-weight:700;">Your request has been {{ strtolower($newStatus) }}</h2>
        <p style="margin:0;color:#B91C1C;font-size:14px;">Reference {{ $reference }}</p>
    </div>
    @elseif($rawStatus === 'approved')
    <div style="margin:0 0 24px;padding:16px 20px;background-color:#EFF6FF;border-left:4px solid #2563EB;border-radius:4px;">
        <h2 style="margin:0 0 4px;color:#1E40AF;font-size:20px;font-weight:700;">Your request has been approved</h2>
        <p style="margin:0;color:#1D4ED8;font-size:14px;">Reference {{ $reference }} — now in the queue.</p>
    </div>
    @elseif($rawStatus === 'referred' || $rawStatus === 'requires_referral')
    <div style="margin:0 0 24px;padding:16px 20px;background-color:#FFF7ED;border-left:4px solid #EA580C;border-radius:4px;">
        <h2 style="margin:0 0 4px;color:#9A3412;font-size:20px;font-weight:700;">Your request is awaiting approval</h2>
        <p style="margin:0;color:#C2410C;font-size:14px;">Reference {{ $reference }} — referred for review.</p>
    </div>
    @elseif($rawStatus === 'scheduled')
    <div style="margin:0 0 24px;padding:16px 20px;background-color:#F5F3FF;border-left:4px solid #7C3AED;border-radius:4px;">
        <h2 style="margin:0 0 4px;color:#5B21B6;font-size:20px;font-weight:700;">Your request has been scheduled</h2>
        <p style="margin:0;color:#6D28D9;font-size:14px;">Reference {{ $reference }} — work is underway.</p>
    </div>
    @else
    <div style="margin:0 0 24px;padding:16px 20px;background-color:#F0F0EF;border-left:4px solid #B52159;border-radius:4px;">
        <h2 style="margin:0 0 4px;color:#3C3C3B;font-size:20px;font-weight:700;">Your request has been updated</h2>
        <p style="margin:0;color:#6E6E6D;font-size:14px;">Reference {{ $reference }} — status changed to {{ $newStatus }}.</p>
    </div>
    @endif

    {{-- What this means — contextual message up front --}}
    @if($rawStatus === 'done')
    <p style="margin:0 0 16px;">
        The changes you requested have been completed. Please check the page and let us know if anything doesn't look right.
    </p>
    @if($pageUrl)
    <table role="presentation" cellpadding="0" cellspacing="0" style="margin:0 0 24px;">
        <tr>
            <td style="border-radius:50px;background-color:#059669;">
                <a href="{{ $pageUrl }}" target="_blank" style="display:inline-block;padding:12px 32px;color:#ffffff;font-size:15px;font-weight:600;text-decoration:none;border-radius:50px;">
                    View Your Page
                </a>
            </td>
        </tr>
    </table>
    @endif
    @elseif($rawStatus === 'declined' || $rawStatus === 'cancelled')
        @if($rejectionReason)
        <div style="margin:0 0 20px;padding:12px 16px;background-color:#FEF2F2;border:1px solid #FECACA;border-radius:8px;">
            <p style="margin:0 0 4px;font-size:13px;font-weight:600;color:#991B1B;">Reason provided:</p>
            <p style="margin:0;color:#3C3C3B;">{{ $rejectionReason }}</p>
        </div>
        @endif
    <p style="margin:0 0 16px;">
        If you have any questions about this decision, please contact the marketing team.
    </p>
    @elseif($rawStatus === 'approved')
    <p style="margin:0 0 16px;">
        Your request has been approved and is now in the queue to be actioned by the marketing team. Please note that approved requests are scheduled based on priority and team capacity — we'll update you when work is complete.
    </p>
    @elseif($rawStatus === 'scheduled')
    <p style="margin:0 0 16px;">
        Your request has been scheduled for implementation. You'll receive a further update once the changes have been made.
    </p>
    @elseif($rawStatus === 'referred' || $rawStatus === 'requires_referral')
    <p style="margin:0 0 16px;">
        Your request is being referred for approval. This is a standard part of our process for some types of changes. You'll receive an update once a decision has been made.
    </p>
    @else
    <p style="margin:0 0 16px;">
        {!! nl2br(e($customBody ?? $defaultBody)) !!}
    </p>
    @endif

    {{-- Request context --}}
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin:0 0 16px;border-collapse:collapse;">
        <tr>
            <td style="padding:8px 12px;border-bottom:1px solid #eeeeee;font-weight:600;width:140px;color:#3C3C3B;">Site</td>
            <td style="padding:8px 12px;border-bottom:1px solid #eeeeee;color:#3C3C3B;">{{ $siteName }}</td>
        </tr>
        <tr>
            <td style="padding:8px 12px;border-bottom:1px solid #eeeeee;font-weight:600;color:#3C3C3B;">Page</td>
            <td style="padding:8px 12px;border-bottom:1px solid #eeeeee;color:#3C3C3B;">{{ $isNewPage ? 'New page: ' : '' }}{{ $pageTitle }}</td>
        </tr>
        <tr>
            <td style="padding:8px 12px;border-bottom:1px solid #eeeeee;font-weight:600;color:#3C3C3B;">Status</td>
            <td style="padding:8px 12px;border-bottom:1px solid #eeeeee;color:#3C3C3B;">{{ $oldStatus }} &rarr; <strong>{{ $newStatus }}</strong></td>
        </tr>
    </table>

    {{-- What was requested --}}
    @if($items->isNotEmpty())
    <div style="margin:0 0 24px;padding:12px 16px;background-color:#F0F0EF;border-radius:8px;">
        <p style="margin:0 0 8px;font-size:13px;font-weight:600;color:#3C3C3B;">Changes requested ({{ $itemCount }}):</p>
        @foreach($items as $item)
        <p style="margin:0 0 4px;font-size:13px;color:#6E6E6D;">
            @if($item->action_type === 'add')
                <span style="color:#059669;font-weight:600;">Add</span>
            @elseif($item->action_type === 'change')
                <span style="color:#2563EB;font-weight:600;">Change</span>
            @elseif($item->action_type === 'delete')
                <span style="color:#DC2626;font-weight:600;">Remove</span>
            @endif
            {{ $item->content_area ? '— ' . $item->content_area : '' }}
        </p>
        @endforeach
        @if($itemCount > 5)
        <p style="margin:4px 0 0;font-size:12px;color:#A0A09F;">+ {{ $itemCount - 5 }} more {{ Str::plural('item', $itemCount - 5) }}</p>
        @endif
    </div>
    @endif

    {{-- Progress indicator --}}
    @php
        $steps = ['requested', 'referred', 'approved', 'scheduled', 'done'];
        $stepLabels = ['Submitted', 'Referred', 'Approved', 'Scheduled', 'Complete'];
        $isTerminal = in_array($rawStatus, ['declined', 'cancelled']);
        $currentIndex = array_search($rawStatus, $steps);
        if ($currentIndex === false && !$isTerminal) {
            $currentIndex = in_array($rawStatus, ['requires_referral']) ? 0 : -1;
        }
    @endphp
    @if(!$isTerminal)
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin:0 0 24px;border-collapse:collapse;">
        <tr>
            @foreach($steps as $i => $step)
                @php
                    $isComplete = $i <= $currentIndex;
                    $isCurrent = $i === $currentIndex;
                    $dotColor = $isComplete ? '#B52159' : '#D2D2D1';
                    $dotSize = $isCurrent ? '14' : '10';
                    $lineColor = ($i > 0 && $i <= $currentIndex) ? '#B52159' : '#D2D2D1';
                    $labelWeight = $isCurrent ? '700' : '400';
                    $labelColor = $isCurrent ? '#B52159' : ($isComplete ? '#3C3C3B' : '#A0A09F');
                @endphp
                <td style="text-align:center;vertical-align:top;width:{{ 100 / count($steps) }}%;padding:0;">
                    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="border-collapse:collapse;">
                        <tr>
                            <td style="width:50%;height:2px;background-color:{{ $i === 0 ? 'transparent' : $lineColor }};"></td>
                            <td style="width:{{ $dotSize }}px;padding:0;">
                                <div style="width:{{ $dotSize }}px;height:{{ $dotSize }}px;border-radius:50%;background-color:{{ $dotColor }};margin:0 auto;{{ $isCurrent ? 'box-shadow:0 0 0 3px rgba(181,33,89,0.2);' : '' }}"></div>
                            </td>
                            <td style="width:50%;height:2px;background-color:{{ $i === count($steps) - 1 ? 'transparent' : ($i < $currentIndex ? '#B52159' : '#D2D2D1') }};"></td>
                        </tr>
                    </table>
                    <p style="margin:6px 0 0;font-size:10px;font-weight:{{ $labelWeight }};color:{{ $labelColor }};line-height:1.2;">{{ $stepLabels[$i] }}</p>
                </td>
            @endforeach
        </tr>
    </table>
    @endif

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
