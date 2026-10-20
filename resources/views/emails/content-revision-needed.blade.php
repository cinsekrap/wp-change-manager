@extends('emails.layout')

@section('subject', "Changes needed: {$reference}")

@section('content')
    <div style="background:#FEF3C7;border-left:4px solid #F39204;padding:16px 20px;margin:0 0 24px;">
        <h2 style="margin:0 0 4px;color:#92400E;font-size:20px;font-weight:700;">Changes needed before approval</h2>
        <p style="margin:0;color:#57534e;font-size:14px;">{{ $changeRequest->subjectDescription() }} &middot; {{ $reference }}</p>
    </div>

    <p style="margin:0 0 20px;color:#44403c;font-size:15px;line-height:1.6;">{{ $bodyText }}</p>

    @if($feedback)
    <div style="background:#fafaf9;border:1px solid #e7e5e4;border-radius:8px;padding:16px 20px;margin:0 0 24px;">
        <p style="margin:0 0 6px;color:#78716c;font-size:12px;text-transform:uppercase;letter-spacing:.05em;">From {{ $approverName }}</p>
        <p style="margin:0;color:#1c1917;font-size:15px;line-height:1.6;white-space:pre-wrap;">{{ $feedback }}</p>
    </div>
    @endif

    <table role="presentation" cellpadding="0" cellspacing="0" style="margin:0 auto 24px;">
        <tr>
            <td align="center" bgcolor="#0f766e" style="border-radius:8px;">
                <a href="{{ $adminUrl }}" target="_blank" style="display:inline-block;padding:12px 32px;color:#ffffff;font-size:15px;font-weight:600;text-decoration:none;border-radius:8px;">
                    Open the draft
                </a>
            </td>
        </tr>
    </table>
@endsection
