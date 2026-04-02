<div class="flex items-center justify-between mb-6">
    <div>
        <a href="{{ route('admin.requests.index') }}" class="text-sm text-gray-500 hover:text-gray-700">&larr; Back to requests</a>
        <h1 class="text-2xl font-bold text-gray-900 mt-1">{{ $changeRequest->reference }}</h1>
    </div>
    <div class="flex items-center space-x-2">
        @include('admin.partials.priority-badge', ['priority' => $changeRequest->priority ?? 'normal'])
        @include('partials.status-badge', ['status' => $changeRequest->status])
    </div>
</div>
