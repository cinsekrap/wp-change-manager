<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Website Change Request') — {{ config('app.name') }}</title>
    @include('layouts.partials.head')
</head>
<body class="bg-gray-50 min-h-screen">
    <header class="bg-white shadow-sm">
        <div class="max-w-3xl mx-auto px-4 py-6 flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-hcrg-burgundy">{{ config('app.name') }}</h1>
                <p class="mt-1 text-sm text-hcrg-charcoal">Website Change Request Tool</p>
            </div>
            <div class="flex items-center space-x-3">
                <a href="{{ route('wizard') }}" class="text-sm font-medium text-white bg-hcrg-burgundy px-4 py-2 rounded-full hover:bg-[#9A1B4B] transition-colors">Submit a request</a>
                <a href="{{ route('tracking') }}" class="text-sm font-medium text-hcrg-burgundy border border-hcrg-burgundy px-4 py-2 rounded-full hover:bg-hcrg-burgundy hover:text-white transition-colors">Track a request</a>
                <a href="{{ route(auth()->check() ? 'admin.dashboard' : 'login') }}" class="text-sm font-medium text-gray-400 border border-gray-300 px-4 py-2 rounded-full hover:border-hcrg-burgundy hover:text-hcrg-burgundy transition-colors">{{ auth()->check() ? 'Admin' : 'Sign in' }}</a>
            </div>
        </div>
    </header>

    <main class="max-w-3xl mx-auto py-8 px-4">
        @yield('content')
    </main>

    <footer class="bg-hcrg-burgundy text-white mt-12">
        <div class="max-w-3xl mx-auto px-4 py-8 text-center text-sm space-y-1">
            <p>&copy; {{ date('Y') }} {{ config('app.name') }}</p>
            <p class="text-white/50">Powered by <a href="https://github.com/cinsekrap/wp-change-manager" target="_blank" class="text-white/70 hover:text-white underline">WP Change Manager</a></p>
        </div>
    </footer>

    @yield('scripts')
</body>
</html>
