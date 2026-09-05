<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password — {{ config('app.name') }}</title>
    @include('layouts.partials.head')
</head>
<body class="bg-gray-100 min-h-screen flex items-center justify-center">
    <div class="max-w-md w-full bg-white rounded-xl shadow-lg p-8">
        <h2 class="text-2xl font-bold text-center text-hcrg-burgundy mb-2">{{ config('app.name') }}</h2>
        <p class="text-center text-sm text-gray-500 mb-6">Choose a new password for your account.</p>

        @if($errors->any())
            <div class="mb-4 p-3 bg-red-50 border border-red-200 text-red-700 text-sm rounded-lg">
                {{ $errors->first() }}
            </div>
        @endif

        <form method="POST" action="{{ route('password.update') }}">
            @csrf

            <input type="hidden" name="token" value="{{ $token }}">

            <div class="mb-4">
                <label for="email" class="field-label">Email</label>
                <input type="email" name="email" id="email" value="{{ old('email', $email) }}" required
                    class="field-input">
            </div>

            <div class="mb-4">
                <label for="password" class="field-label">New Password</label>
                <input type="password" name="password" id="password" required
                    class="field-input">
                <p class="mt-1 text-xs text-gray-500">Minimum 10 characters, must include uppercase, lowercase, and a number.</p>
            </div>

            <div class="mb-6">
                <label for="password_confirmation" class="field-label">Confirm Password</label>
                <input type="password" name="password_confirmation" id="password_confirmation" required
                    class="field-input">
            </div>

            <button type="submit" class="btn btn-primary w-full">
                Reset Password
            </button>
        </form>

        <p class="mt-4 text-center text-sm text-gray-500">
            <a href="{{ route('login') }}" class="text-hcrg-burgundy hover:underline">Back to sign in</a>
        </p>
    </div>
</body>
</html>
