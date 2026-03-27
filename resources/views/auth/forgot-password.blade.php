<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password — {{ config('app.name') }}</title>
    @include('layouts.partials.head')
</head>
<body class="bg-gray-100 min-h-screen flex items-center justify-center">
    <div class="max-w-md w-full bg-white rounded-xl shadow-lg p-8">
        <h2 class="text-2xl font-bold text-center text-hcrg-burgundy mb-2">{{ config('app.name') }}</h2>
        <p class="text-center text-sm text-gray-500 mb-6">Enter your email address and we'll send you a link to reset your password.</p>

        @if(session('status'))
            <div class="mb-4 p-3 bg-green-50 border border-green-200 text-green-700 text-sm rounded-lg">
                {{ session('status') }}
            </div>
        @endif

        @if($errors->any())
            <div class="mb-4 p-3 bg-red-50 border border-red-200 text-red-700 text-sm rounded-lg">
                {{ $errors->first() }}
            </div>
        @endif

        <form method="POST" action="{{ route('password.email') }}">
            @csrf

            <div class="mb-6">
                <label for="email" class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                <input type="email" name="email" id="email" value="{{ old('email') }}" required autofocus
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-hcrg-burgundy focus:border-hcrg-burgundy">
            </div>

            <button type="submit" class="w-full bg-hcrg-burgundy text-white py-2 px-4 rounded-full hover:bg-[#9A1B4B] font-medium">
                Send Reset Link
            </button>
        </form>

        <p class="mt-4 text-center text-sm text-gray-500">
            <a href="{{ route('login') }}" class="text-hcrg-burgundy hover:underline">Back to sign in</a>
        </p>
    </div>
</body>
</html>
