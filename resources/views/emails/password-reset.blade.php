@extends('emails.layout')

@section('subject', 'Reset Your Password')

@section('content')
    <h2 style="margin:0 0 16px;color:#3C3C3B;font-size:20px;font-weight:700;">
        Reset your password
    </h2>

    <p style="margin:0 0 16px;">
        You're receiving this email because a password reset was requested for your account. If you didn't make this request, you can safely ignore this email.
    </p>

    <p style="margin:0 0 24px;">
        Click the button below to choose a new password:
    </p>

    {{-- CTA Button --}}
    <table role="presentation" cellpadding="0" cellspacing="0" style="margin:0 auto 24px;">
        <tr>
            <td style="border-radius:50px;background-color:#B52159;">
                <a href="{{ $resetUrl }}" target="_blank" style="display:inline-block;padding:12px 32px;color:#ffffff;font-size:15px;font-weight:600;text-decoration:none;border-radius:50px;">
                    Reset Password
                </a>
            </td>
        </tr>
    </table>

    <p style="margin:0 0 8px;font-size:13px;color:#6E6E6D;">
        This link will expire in 60 minutes. If you need a new link, visit the <a href="{{ config('app.url') }}/admin/forgot-password" style="color:#B52159;">forgot password page</a>.
    </p>

    <p style="margin:16px 0 0;font-size:13px;color:#A0A09F;">
        If you're having trouble clicking the button, copy and paste this URL into your browser:<br>
        <span style="word-break:break-all;">{{ $resetUrl }}</span>
    </p>
@endsection
