@extends('layouts.admin')
@section('title', 'Updates')

@section('content')
<div class="max-w-2xl">
    <h1 class="text-2xl font-bold text-gray-900 mb-6">Updates</h1>

    {{-- Current version --}}
    <div class="bg-white rounded-lg shadow p-6 mb-6">
        <div class="text-2xl font-bold text-gray-900">{{ config('app.name') }} <span class="text-hcrg-burgundy">v{{ $currentVersion }}</span></div>
        <p class="text-sm text-gray-500 mt-1">Installed version</p>
    </div>

    {{-- Update status --}}
    <div class="mb-6">
        @if($update['available'])
            <div class="bg-amber-50 border border-amber-200 rounded-lg p-6">
                <div class="flex items-start justify-between">
                    <div>
                        <h2 class="text-lg font-semibold text-amber-900">Update available: v{{ $update['latest_version'] }}</h2>
                        @if($update['published_at'])
                            <p class="text-sm text-amber-700 mt-1">Published {{ \Carbon\Carbon::parse($update['published_at'])->format('d M Y') }}</p>
                        @endif
                    </div>
                    @if($update['html_url'])
                        <a href="{{ $update['html_url'] }}" target="_blank" rel="noopener noreferrer" class="text-sm text-hcrg-burgundy hover:underline whitespace-nowrap ml-4">
                            View on GitHub &rarr;
                        </a>
                    @endif
                </div>

                @if($update['release_notes'])
                    <div class="mt-4 pt-4 border-t border-amber-200">
                        <h3 class="text-sm font-medium text-amber-900 mb-2">Release Notes</h3>
                        <div class="text-sm text-amber-800 whitespace-pre-line">{{ $update['release_notes'] }}</div>
                    </div>
                @endif

                <div class="mt-4 pt-4 border-t border-amber-200 flex items-center space-x-3">
                    <form method="POST" action="{{ route('admin.settings.updates.install') }}" onsubmit="return confirm('This will pull the latest code and run migrations. Continue?')">
                        @csrf
                        <button type="submit" class="bg-hcrg-burgundy text-white px-5 py-2 rounded-full hover:bg-[#9A1B4B] text-sm font-medium">
                            Install Update
                        </button>
                    </form>
                    <form method="POST" action="{{ route('admin.settings.updates.check') }}">
                        @csrf
                        <button type="submit" class="border border-gray-300 text-gray-700 px-4 py-2 rounded-full hover:bg-gray-50 text-sm font-medium">
                            Check Again
                        </button>
                    </form>
                </div>
            </div>
        @else
            <div class="bg-green-50 border border-green-200 rounded-lg p-6">
                <div class="flex items-center space-x-3">
                    <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    <div>
                        <h2 class="text-lg font-semibold text-green-900">You're up to date</h2>
                        <p class="text-sm text-green-700 mt-0.5">{{ config('app.name') }} v{{ $currentVersion }} is the latest version.</p>
                    </div>
                </div>
                @if($update['checked_at'] ?? null)
                    <p class="text-xs text-green-600 mt-3">Last checked: {{ \Carbon\Carbon::parse($update['checked_at'])->format('d M Y H:i') }}</p>
                @endif
                <div class="mt-4">
                    <form method="POST" action="{{ route('admin.settings.updates.check') }}">
                        @csrf
                        <button type="submit" class="border border-gray-300 text-gray-700 px-4 py-2 rounded-full hover:bg-gray-50 text-sm font-medium">
                            Check for Updates
                        </button>
                    </form>
                </div>
            </div>
        @endif
    </div>

    {{-- Update history --}}
    @if($lastDeploy)
        <div class="bg-white rounded-lg shadow p-6">
            <h2 class="text-lg font-semibold text-gray-900 mb-3">Last Update</h2>
            <div class="text-sm text-gray-600 space-y-1">
                <p><span class="font-medium text-gray-700">Time:</span> {{ $lastDeploy['timestamp'] ?? 'Unknown' }}</p>
                <p>
                    <span class="font-medium text-gray-700">Status:</span>
                    @if($lastDeploy['success'] ?? false)
                        <span class="text-green-600">Success</span>
                    @else
                        <span class="text-red-600">Failed</span>
                    @endif
                </p>
                @if(isset($lastDeploy['steps']) && is_array($lastDeploy['steps']))
                    <div class="mt-3 pt-3 border-t border-gray-100">
                        <p class="font-medium text-gray-700 mb-1">Details:</p>
                        @foreach($lastDeploy['steps'] as $step => $output)
                            <div class="mb-1">
                                <span class="text-xs font-mono text-gray-500">{{ $step }}:</span>
                                <span class="text-xs text-gray-600">{{ is_string($output) ? $output : json_encode($output) }}</span>
                            </div>
                        @endforeach
                    </div>
                @endif
                {{-- Handle deploy log entries from the webhook (different format) --}}
                @if(isset($lastDeploy['git']) && !isset($lastDeploy['steps']))
                    <div class="mt-3 pt-3 border-t border-gray-100">
                        <p class="font-medium text-gray-700 mb-1">Details:</p>
                        <div class="mb-1">
                            <span class="text-xs font-mono text-gray-500">git:</span>
                            <span class="text-xs text-gray-600">{{ $lastDeploy['git'] }}</span>
                        </div>
                        @if(isset($lastDeploy['migrate']))
                            <div class="mb-1">
                                <span class="text-xs font-mono text-gray-500">migrate:</span>
                                <span class="text-xs text-gray-600">{{ $lastDeploy['migrate'] }}</span>
                            </div>
                        @endif
                    </div>
                @endif
            </div>
        </div>
    @endif

    {{-- GitHub Token --}}
    <div class="bg-white rounded-lg shadow p-6">
        <h2 class="text-lg font-semibold text-gray-900 mb-3">GitHub API Token</h2>
        <p class="text-sm text-gray-500 mb-4">Optional. Increases the API rate limit from 60 to 5,000 requests per hour. Generate a fine-grained personal access token at <a href="https://github.com/settings/tokens?type=beta" target="_blank" class="text-hcrg-burgundy hover:underline">github.com/settings/tokens</a> with no special permissions.</p>
        <form method="POST" action="{{ route('admin.settings.github-token.update') }}">
            @csrf
            @method('PUT')
            <div class="flex items-end space-x-3">
                <div class="flex-1">
                    <input type="password" name="github_token" value="" placeholder="{{ \App\Models\Setting::get('github_token') ? '••••••••' : 'Paste token here' }}"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-hcrg-burgundy focus:border-hcrg-burgundy">
                    @if(\App\Models\Setting::get('github_token'))
                        <p class="mt-1 text-xs text-gray-500">Token is set. Leave blank to keep current.</p>
                    @endif
                </div>
                <button type="submit" class="bg-hcrg-burgundy text-white px-4 py-2 rounded-full hover:bg-[#9A1B4B] text-sm font-medium whitespace-nowrap">Save</button>
            </div>
        </form>
    </div>

    {{-- Rollback --}}
    @if(!empty($backups))
    <div class="bg-white rounded-lg shadow p-6">
        <h2 class="text-lg font-semibold text-gray-900 mb-3">Rollback</h2>
        <p class="text-sm text-gray-500 mb-4">A backup is created automatically before each update. You can restore to a previous version if something goes wrong.</p>

        <div class="space-y-2">
            @foreach($backups as $backup)
            <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                <div>
                    <p class="text-sm font-medium text-gray-900">{{ $backup['filename'] }}</p>
                    <p class="text-xs text-gray-500">{{ $backup['date'] }} &middot; {{ $backup['size'] }}</p>
                </div>
                <div class="flex items-center space-x-2">
                    <form method="POST" action="{{ route('admin.settings.updates.rollback') }}" onsubmit="return confirm('This will restore the app to this backup. The current version will be overwritten. Continue?')">
                        @csrf
                        <input type="hidden" name="backup" value="{{ $backup['filename'] }}">
                        <button type="submit" class="text-sm text-red-600 hover:text-red-800 font-medium px-3 py-1 rounded-full border border-red-300 hover:bg-red-50 transition-colors">
                            Restore
                        </button>
                    </form>
                    <form method="POST" action="{{ route('admin.settings.updates.delete-backup') }}" onsubmit="return confirm('Delete this backup permanently?')">
                        @csrf
                        @method('DELETE')
                        <input type="hidden" name="backup" value="{{ $backup['filename'] }}">
                        <button type="submit" class="text-gray-400 hover:text-red-600 transition-colors p-1" title="Delete backup">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                        </button>
                    </form>
                </div>
            </div>
            @endforeach
        </div>
    </div>
    @endif
</div>
@endsection
