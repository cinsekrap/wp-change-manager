{{-- What the requester sent with the suggestion. It is read before the copy is
     written, so it sits with the brief rather than the draft. --}}
@if($changeRequest->files->isNotEmpty())
<div class="card card-body mb-6">
    <h2 class="card-title mb-1">Attached to the brief</h2>
    <p class="text-sm text-gray-500 mb-4">What the requester sent with the suggestion.</p>
    <ul class="space-y-2">
        @foreach($changeRequest->files as $file)
            <li class="flex items-center justify-between p-3 border border-gray-200 rounded-lg">
                <div>
                    <p class="text-sm font-medium text-gray-700">{{ $file->original_filename }}</p>
                    <p class="text-xs text-gray-500">{{ number_format($file->file_size / 1024, 0) }} KB</p>
                </div>
                @if($file->purged_at)
                    <span class="text-xs text-gray-500">removed {{ $file->purged_at->diffForHumans() }}</span>
                @else
                    <a href="{{ route('admin.requests.download', [$changeRequest, $file]) }}"
                       class="text-sm text-primary underline">Download</a>
                @endif
            </li>
        @endforeach
    </ul>
</div>
@endif
