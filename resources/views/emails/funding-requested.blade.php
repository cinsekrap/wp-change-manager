@extends('emails.layout')

@section('subject', "Funding decision needed: {$reference} — {$totalHours} hours")

@section('content')
    <div style="background:#f5f5f4;border-left:4px solid #1c1917;padding:16px 20px;margin:0 0 24px;">
        <h2 style="margin:0 0 4px;color:#1c1917;font-size:20px;font-weight:700;">Approve content design hours</h2>
        <p style="margin:0;color:#57534e;font-size:14px;">{{ $itemCount }} {{ \Illuminate\Support\Str::plural('piece', $itemCount) }} of content · {{ $totalHours }} hours in total</p>
    </div>

    <p style="margin:0 0 20px;color:#44403c;font-size:15px;line-height:1.6;">{{ $bodyText }}</p>

    <table role="presentation" cellpadding="0" cellspacing="0" width="100%" style="border-collapse:collapse;margin:0 0 24px;">
        <thead>
            <tr>
                <th align="left" style="padding:8px 12px;border-bottom:2px solid #e7e5e4;font-size:12px;color:#78716c;text-transform:uppercase;">Content</th>
                <th align="right" style="padding:8px 12px;border-bottom:2px solid #e7e5e4;font-size:12px;color:#78716c;text-transform:uppercase;">Hours</th>
            </tr>
        </thead>
        <tbody>
            @foreach($round->items as $item)
            <tr>
                <td style="padding:10px 12px;border-bottom:1px solid #f5f5f4;font-size:14px;color:#1c1917;">
                    {{ $item->changeRequest?->subjectDescription() ?? 'Removed' }}
                    <span style="display:block;color:#a8a29e;font-size:12px;">{{ $item->changeRequest?->reference }}</span>
                </td>
                <td align="right" style="padding:10px 12px;border-bottom:1px solid #f5f5f4;font-size:14px;color:#1c1917;font-weight:600;white-space:nowrap;">
                    {{ rtrim(rtrim(number_format((float) $item->estimated_hours, 1), '0'), '.') }}
                </td>
            </tr>
            @endforeach
            <tr>
                <td style="padding:12px;font-size:15px;font-weight:700;color:#1c1917;">Total</td>
                <td align="right" style="padding:12px;font-size:15px;font-weight:700;color:#1c1917;">{{ $totalHours }}</td>
            </tr>
        </tbody>
    </table>

    @if($approvalUrl)
    <table role="presentation" cellpadding="0" cellspacing="0" style="margin:0 auto 24px;">
        <tr>
            <td align="center" bgcolor="#1c1917" style="border-radius:8px;">
                <a href="{{ $approvalUrl }}" target="_blank" style="display:inline-block;padding:12px 32px;color:#ffffff;font-size:15px;font-weight:600;text-decoration:none;border-radius:8px;">
                    Review and approve
                </a>
            </td>
        </tr>
    </table>
    @endif

@endsection
