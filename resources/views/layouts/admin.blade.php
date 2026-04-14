<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Admin') — {{ config('app.name') }}</title>
    @include('layouts.partials.head')
</head>
<body class="bg-gray-100 min-h-screen">
    <nav class="bg-hcrg-burgundy">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between h-16">
                @php $isConfig = request()->routeIs('admin.sites.*', 'admin.cpts.*', 'admin.questions.*', 'admin.settings.*', 'admin.settings.updates*', 'admin.settings.config*', 'admin.settings.notifications*', 'admin.users.*', 'admin.audit-log'); @endphp
                <div class="flex items-center space-x-6">
                    <a href="{{ route('admin.dashboard') }}" class="text-xl font-bold text-white">{{ config('app.name') }}</a>
                    <span class="text-xs text-white/50">v{{ config('version.current') }}</span>
                    <div class="hidden md:flex items-center space-x-1">
                        <a href="{{ route('admin.dashboard') }}" class="flex items-center px-3 py-2 text-sm font-medium rounded-lg {{ request()->routeIs('admin.dashboard') ? 'bg-white/20 text-white' : 'text-white/80 hover:text-white hover:bg-white/10' }}">
                            <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
                            Dashboard
                        </a>
                        <a href="{{ route('admin.requests.index') }}" class="flex items-center px-3 py-2 text-sm font-medium rounded-lg {{ request()->routeIs('admin.requests.*') ? 'bg-white/20 text-white' : 'text-white/80 hover:text-white hover:bg-white/10' }}">
                            <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"/></svg>
                            Requests
                        </a>

                        {{-- Configuration dropdown --}}
                        <div class="relative" id="configDropdown">
                            <button type="button" onclick="document.getElementById('configMenu').classList.toggle('hidden')"
                                class="flex items-center px-3 py-2 text-sm font-medium rounded-lg {{ $isConfig ? 'bg-white/20 text-white' : 'text-white/80 hover:text-white hover:bg-white/10' }}">
                                <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.066 2.573c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.573 1.066c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.066-2.573c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                                Configuration
                                <svg class="w-3 h-3 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                            </button>
                            <div id="configMenu" class="hidden absolute left-0 mt-1 w-48 bg-white rounded-lg shadow-lg border border-gray-200 py-1 z-50">
                                {{-- All admins --}}
                                <a href="{{ route('admin.sites.index') }}" class="flex items-center px-4 py-2 text-sm {{ request()->routeIs('admin.sites.*') ? 'bg-hcrg-burgundy/10 text-hcrg-burgundy' : 'text-gray-700 hover:bg-gray-50' }}">
                                    <svg class="w-4 h-4 mr-2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9"/></svg>
                                    Sites
                                </a>
                                <a href="{{ route('admin.questions.index') }}" class="flex items-center px-4 py-2 text-sm {{ request()->routeIs('admin.questions.*') ? 'bg-hcrg-burgundy/10 text-hcrg-burgundy' : 'text-gray-700 hover:bg-gray-50' }}">
                                    <svg class="w-4 h-4 mr-2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                    Check Questions
                                </a>
                                @if(auth()->user()->isSuperAdmin())
                                {{-- Super admin only --}}
                                <div class="border-t border-gray-200 my-1"></div>
                                <a href="{{ route('admin.cpts.index') }}" class="flex items-center px-4 py-2 text-sm {{ request()->routeIs('admin.cpts.*') ? 'bg-hcrg-burgundy/10 text-hcrg-burgundy' : 'text-gray-700 hover:bg-gray-50' }}">
                                    <svg class="w-4 h-4 mr-2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A2 2 0 013 12V7a4 4 0 014-4z"/></svg>
                                    Content Types
                                </a>
                                <a href="{{ route('admin.settings.mail') }}" class="flex items-center px-4 py-2 text-sm {{ request()->routeIs('admin.settings.mail*') ? 'bg-hcrg-burgundy/10 text-hcrg-burgundy' : 'text-gray-700 hover:bg-gray-50' }}">
                                    <svg class="w-4 h-4 mr-2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                                    Mail Settings
                                </a>
                                <a href="{{ route('admin.settings.notifications') }}" class="flex items-center px-4 py-2 text-sm {{ request()->routeIs('admin.settings.notifications*') ? 'bg-hcrg-burgundy/10 text-hcrg-burgundy' : 'text-gray-700 hover:bg-gray-50' }}">
                                    <svg class="w-4 h-4 mr-2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/></svg>
                                    Notifications
                                </a>
                                <a href="{{ route('admin.settings.entra') }}" class="flex items-center px-4 py-2 text-sm {{ request()->routeIs('admin.settings.entra*') ? 'bg-hcrg-burgundy/10 text-hcrg-burgundy' : 'text-gray-700 hover:bg-gray-50' }}">
                                    <svg class="w-4 h-4 mr-2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"/></svg>
                                    SSO Settings
                                </a>
                                <a href="{{ route('admin.users.index') }}" class="flex items-center px-4 py-2 text-sm {{ request()->routeIs('admin.users.*') ? 'bg-hcrg-burgundy/10 text-hcrg-burgundy' : 'text-gray-700 hover:bg-gray-50' }}">
                                    <svg class="w-4 h-4 mr-2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
                                    Admin Users
                                </a>
                                <a href="{{ route('admin.settings.updates') }}" class="flex items-center px-4 py-2 text-sm {{ request()->routeIs('admin.settings.updates*') ? 'bg-hcrg-burgundy/10 text-hcrg-burgundy' : 'text-gray-700 hover:bg-gray-50' }}">
                                    <svg class="w-4 h-4 mr-2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 11l5-5m0 0l5 5m-5-5v12"/><circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="2" fill="none"/></svg>
                                    Updates
                                </a>
                                <a href="{{ route('admin.audit-log') }}" class="flex items-center px-4 py-2 text-sm {{ request()->routeIs('admin.audit-log') ? 'bg-hcrg-burgundy/10 text-hcrg-burgundy' : 'text-gray-700 hover:bg-gray-50' }}">
                                    <svg class="w-4 h-4 mr-2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/></svg>
                                    Audit Log
                                </a>
                                <a href="{{ route('admin.settings.config') }}" class="flex items-center px-4 py-2 text-sm {{ request()->routeIs('admin.settings.config*') ? 'bg-hcrg-burgundy/10 text-hcrg-burgundy' : 'text-gray-700 hover:bg-gray-50' }}">
                                    <svg class="w-4 h-4 mr-2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/></svg>
                                    Import / Export
                                </a>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
                <div class="relative" id="userDropdown">
                    <button type="button" onclick="document.getElementById('userMenu').classList.toggle('hidden')"
                        class="flex items-center text-sm font-medium text-white hover:text-white/80">
                        {{ auth()->user()->name }}
                        <svg class="w-3 h-3 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                    </button>
                    <div id="userMenu" class="hidden absolute right-0 mt-2 w-40 bg-white rounded-lg shadow-lg border border-gray-200 py-1 z-50">
                        <a href="{{ route('admin.password.edit') }}" class="flex items-center px-4 py-2 text-sm text-gray-700 hover:bg-gray-50">
                            <svg class="w-4 h-4 mr-2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"/></svg>
                            Change password
                        </a>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" class="flex items-center w-full px-4 py-2 text-sm text-gray-700 hover:bg-gray-50">
                                <svg class="w-4 h-4 mr-2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
                                Log out
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </nav>

    <main class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
        @if(session('success'))
            <div class="mb-4 p-4 bg-green-50 border border-green-200 text-green-700 rounded-lg">
                {{ session('success') }}
            </div>
        @endif

        @if(session('error'))
            <div class="mb-4 p-4 bg-red-50 border border-red-200 text-red-700 rounded-lg">
                {{ session('error') }}
            </div>
        @endif

        @yield('content')
    </main>

    @php
        $whatsNewVersion = config('version.current');
        $whatsNewSeen = \App\Models\Setting::get('whats_new_seen_' . auth()->id());
        $whatsNewNotes = ($whatsNewSeen !== $whatsNewVersion) ? \App\Models\Setting::get('release_notes') : null;
    @endphp

    @if($whatsNewNotes)
    <div id="whatsNewModal" class="fixed inset-0 z-50 flex items-center justify-center">
        <div class="absolute inset-0 bg-black/50" onclick="dismissWhatsNew()"></div>
        <div class="relative w-[90vw] max-w-lg max-h-[60vh] bg-white rounded-lg shadow-xl flex flex-col">
            <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200">
                <div>
                    <h2 class="text-lg font-bold text-gray-900">What's new in v{{ $whatsNewVersion }}</h2>
                    <p class="text-xs text-gray-500 mt-0.5">This update has been installed</p>
                </div>
                <button onclick="dismissWhatsNew()" class="text-gray-400 hover:text-gray-600 p-1">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>
            <div class="flex-1 overflow-y-auto px-6 py-4 prose prose-sm max-w-none">
                {!! \Illuminate\Support\Str::markdown($whatsNewNotes) !!}
            </div>
            <div class="px-6 py-4 border-t border-gray-200 text-right">
                <button onclick="dismissWhatsNew()" class="px-5 py-2 bg-hcrg-burgundy text-white rounded-full text-sm font-medium hover:bg-[#9A1B4B] transition-colors">Got it</button>
            </div>
        </div>
    </div>
    @endif

<script>
document.addEventListener('click', function(e) {
    [['configDropdown','configMenu'], ['userDropdown','userMenu']].forEach(function(pair) {
        var dd = document.getElementById(pair[0]);
        var menu = document.getElementById(pair[1]);
        if (dd && menu && !dd.contains(e.target)) menu.classList.add('hidden');
    });
});

@if($whatsNewNotes ?? false)
function dismissWhatsNew() {
    document.getElementById('whatsNewModal').remove();
    fetch('{{ route("admin.whats-new.dismiss") }}', {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            'Accept': 'application/json',
        },
    });
}
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape' && document.getElementById('whatsNewModal')) dismissWhatsNew();
});
@endif
</script>
</body>
</html>
