<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login — {{ config('app.name') }}</title>
    @include('layouts.partials.head')
</head>
<body class="bg-gray-100 min-h-screen flex items-center justify-center">
    <div class="max-w-md w-full bg-white rounded-xl shadow-lg p-8">
        <h2 class="text-2xl font-bold text-center text-hcrg-burgundy mb-6">{{ config('app.name') }}</h2>

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

        <form method="POST" action="{{ route('login') }}">
            @csrf

            <div class="mb-4">
                <label for="email" class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                <input type="email" name="email" id="email" value="{{ old('email') }}" required autofocus
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-hcrg-burgundy focus:border-hcrg-burgundy">
            </div>

            <div class="mb-4">
                <label for="password" class="block text-sm font-medium text-gray-700 mb-1">Password</label>
                <input type="password" name="password" id="password" required
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-hcrg-burgundy focus:border-hcrg-burgundy">
            </div>

            <div class="mb-6 flex items-center justify-between">
                <div class="flex items-center">
                    <input type="checkbox" name="remember" id="remember" class="h-4 w-4 text-hcrg-burgundy border-gray-300 rounded accent-hcrg-burgundy">
                    <label for="remember" class="ml-2 text-sm text-gray-600">Remember me</label>
                </div>
                <a href="{{ route('password.request') }}" class="text-sm text-hcrg-burgundy hover:underline">Forgot password?</a>
            </div>

            <button type="submit" class="w-full bg-hcrg-burgundy text-white py-2 px-4 rounded-full hover:bg-[#9A1B4B] font-medium">
                Sign in
            </button>
        </form>

        @if($entraEnabled ?? false)
            <div class="relative my-6">
                <div class="absolute inset-0 flex items-center">
                    <div class="w-full border-t border-gray-300"></div>
                </div>
                <div class="relative flex justify-center text-sm">
                    <span class="px-2 bg-white text-gray-500">or</span>
                </div>
            </div>

            <a href="{{ route('auth.microsoft') }}"
               class="w-full inline-flex items-center justify-center py-2 px-4 border border-gray-300 rounded-full bg-white text-gray-700 font-medium hover:bg-gray-50 transition-colors">
                {{-- Microsoft 4-square logo --}}
                <svg class="w-5 h-5 mr-2" viewBox="0 0 21 21" xmlns="http://www.w3.org/2000/svg">
                    <rect x="1" y="1" width="9" height="9" fill="#F25022"/>
                    <rect x="11" y="1" width="9" height="9" fill="#7FBA00"/>
                    <rect x="1" y="11" width="9" height="9" fill="#00A4EF"/>
                    <rect x="11" y="11" width="9" height="9" fill="#FFB900"/>
                </svg>
                Sign in with Microsoft
            </a>
        @endif
    </div>
</body>
</html>
