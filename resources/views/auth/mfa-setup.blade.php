<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Set Up Two-Factor Authentication — {{ config('app.name') }}</title>
    @include('layouts.partials.head')
</head>
<body class="bg-gray-100 min-h-screen flex items-center justify-center py-12">
    <div class="max-w-md w-full bg-white rounded-xl shadow-lg p-8">
        <h2 class="text-2xl font-bold text-center text-hcrg-burgundy mb-2">Set up two-factor authentication</h2>
        <p class="text-sm text-gray-600 text-center mb-6">To protect your admin account, you need to set up an authenticator app.</p>

        @if($errors->any())
            <div class="mb-4 p-3 bg-red-50 border border-red-200 text-red-700 text-sm rounded-lg">
                {{ $errors->first() }}
            </div>
        @endif

        {{-- QR Code --}}
        <div class="flex justify-center mb-4">
            <div class="border border-gray-200 rounded-lg p-3 bg-white">
                {!! $qrCodeSvg !!}
            </div>
        </div>

        {{-- Manual entry key --}}
        <div class="mb-6">
            <p class="text-xs text-gray-500 text-center mb-1">Or enter this key manually:</p>
            <div class="bg-gray-50 border border-gray-200 rounded-lg p-3 text-center">
                <code class="text-sm font-mono font-bold text-gray-800 tracking-wider select-all">{{ $secret }}</code>
            </div>
        </div>

        {{-- Instructions --}}
        <ol class="text-sm text-gray-600 mb-6 space-y-1 list-decimal list-inside">
            <li>Open your authenticator app (Google Authenticator, Microsoft Authenticator, etc.)</li>
            <li>Scan the QR code or enter the key manually</li>
            <li>Enter the 6-digit code below to confirm</li>
        </ol>

        {{-- Confirm form --}}
        <form method="POST" action="{{ route('mfa.confirm') }}">
            @csrf

            <div class="mb-4">
                <label for="code" class="block text-sm font-medium text-gray-700 mb-1">6-digit code</label>
                <input type="text" name="code" id="code" maxlength="6" pattern="[0-9]{6}" inputmode="numeric" autocomplete="one-time-code" required autofocus
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-hcrg-burgundy focus:border-hcrg-burgundy text-center text-lg tracking-widest font-mono">
            </div>

            <button type="submit" class="w-full bg-hcrg-burgundy text-white py-2 px-4 rounded-full hover:bg-[#9A1B4B] font-medium">
                Confirm setup
            </button>
        </form>

        <div class="mt-4 text-center">
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="text-sm text-gray-500 hover:text-gray-700">Sign out</button>
            </form>
        </div>
    </div>
</body>
</html>
