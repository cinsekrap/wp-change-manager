@extends('emails.layout')

@section('subject', "Changes needed: {$reference}")

@section('content')
    <div style="background:#FEF3C7;border-left:4px solid #F39204;padding:16px 20px;margin:0 0 24px;">
        <h2 style="margin:0 0 4px;color:#92400E;font-size:20px;font-weight:700;">Changes needed before approval</h2>
        <p style="margin:0;color:#4B5563;font-size:14px;">{{ $changeRequest->subjectDescription() }} &middot; {{ $reference }}</p>
    </div>

    <p style="margin:0 0 20px;color:#374151;font-size:15px;line-height:1.6;">{{ $bodyText }}</p>

    @if($feedback)
    <div style="background:#F9FAFB;border:1px solid #E5E7EB;border-radius:8px;padding:16px 20px;margin:0 0 24px;">
        <p style="margin:0 0 6px;color:#6B7280;font-size:12px;text-transform:uppercase;letter-spacing:.05em;">From {{ $approverName }}</p>
        <p style="margin:0;color:#111827;font-size:15px;line-height:1.6;white-space:pre-wrap;">{{ $feedback }}</p>
    </div>
    @endif

    <table role="presentation" cellpadding="0" cellspacing="0" style="margin:0 auto 24px;">
        <tr>
            <td align="center" bgcolor="#B52159" style="border-radius:50px;">
                <a href="{{ $adminUrl }}" target="_blank" style="display:inline-block;padding:12px 32px;color:#ffffff;font-size:15px;font-weight:600;text-decoration:none;border-radius:50px;">
                    Open the draft
                </a>
            </td>
        </tr>
    </table>
@endsection
