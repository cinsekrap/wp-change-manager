<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Website Change Request') — {{ config('app.name') }}</title>
    @include('layouts.partials.head')
</head>
<body class="bg-gray-50 min-h-screen flex flex-col">
    <header class="bg-white border-b border-gray-200">
        <div class="max-w-3xl mx-auto px-4 py-5 flex flex-wrap items-center justify-between gap-4">
            <a href="{{ route('wizard') }}" class="block">
                <span class="block text-xl font-semibold tracking-tight text-gray-900">{{ config('app.name') }}</span>
                <span class="block mt-0.5 text-sm text-gray-500">Website Change Request Tool</span>
            </a>
            <div class="flex flex-wrap items-center gap-2">
                <a href="{{ route('wizard') }}" class="btn btn-primary">Submit a request</a>
                <a href="{{ route('tracking') }}" class="btn btn-secondary">Track a request</a>
                <a href="{{ route(auth()->check() ? 'admin.dashboard' : 'login') }}" class="btn btn-quiet px-2">{{ auth()->check() ? 'Admin' : 'Sign in' }}</a>
            </div>
        </div>
    </header>

    <main class="flex-1 w-full max-w-3xl mx-auto py-8 px-4">
        @yield('content')
    </main>

    <footer class="border-t border-gray-200 bg-white">
        <div class="max-w-3xl mx-auto px-4 py-6 flex flex-wrap items-center justify-between gap-x-6 gap-y-2 text-sm text-gray-500">
            <p>&copy; {{ date('Y') }} {{ config('app.name') }}</p>
            <p class="flex flex-wrap gap-x-5 gap-y-1">
                <a href="{{ route('suggestions') }}" class="hover:text-gray-900 underline underline-offset-2 decoration-gray-300 hover:decoration-gray-900">Content suggestions</a>
                <span>Powered by <a href="https://github.com/cinsekrap/wp-change-manager" target="_blank" class="hover:text-gray-900 underline underline-offset-2 decoration-gray-300 hover:decoration-gray-900">WP Change Manager</a></span>
            </p>
        </div>
    </footer>

    @yield('scripts')
</body>
</html>
