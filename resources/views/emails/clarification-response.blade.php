@extends('emails.layout')

@section('subject', "Response received: {$reference}")

@section('content')
    <div style="margin:0 0 24px;padding:16px 20px;background-color:#ECFDF5;border-left:4px solid #059669;border-radius:4px;">
        <h2 style="margin:0 0 4px;color:#065F46;font-size:20px;font-weight:700;">Clarification response received</h2>
        <p style="margin:0;color:#047857;font-size:14px;">Reference {{ $reference }} — back to {{ $newStatus }}.</p>
    </div>

    <p style="margin:0 0 16px;">
        {!! nl2br(e($customBody ?? $defaultBody)) !!}
    </p>

    @if($comment !== '')
    <div style="margin:0 0 20px;padding:12px 16px;background-color:#F0F0EF;border:1px solid #D2D2D1;border-radius:8px;">
        <p style="margin:0 0 4px;font-size:13px;font-weight:600;color:#3C3C3B;">{{ $requesterName }} said:</p>
        <p style="margin:0;color:#3C3C3B;">{!! nl2br(e($comment)) !!}</p>
    </div>
    @endif

    @if($itemsUpdated > 0)
    <p style="margin:0 0 16px;font-size:14px;color:#3C3C3B;">
        They also updated <strong>{{ $itemsUpdated }}</strong> change {{ $itemsUpdated === 1 ? 'item' : 'items' }} on the original request.
    </p>
    @endif

    {{-- Request context --}}
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin:0 0 24px;border-collapse:collapse;">
        <tr>
            <td style="padding:8px 12px;border-bottom:1px solid #eeeeee;font-weight:600;width:140px;color:#3C3C3B;">Site</td>
            <td style="padding:8px 12px;border-bottom:1px solid #eeeeee;color:#3C3C3B;">{{ $siteName }}</td>
        </tr>
        <tr>
            <td style="padding:8px 12px;border-bottom:1px solid #eeeeee;font-weight:600;color:#3C3C3B;">Requester</td>
            <td style="padding:8px 12px;border-bottom:1px solid #eeeeee;color:#3C3C3B;">{{ $requesterName }}</td>
        </tr>
        <tr>
            <td style="padding:8px 12px;border-bottom:1px solid #eeeeee;font-weight:600;color:#3C3C3B;">Status</td>
            <td style="padding:8px 12px;border-bottom:1px solid #eeeeee;color:#3C3C3B;">Awaiting user &rarr; <strong>{{ $newStatus }}</strong></td>
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
