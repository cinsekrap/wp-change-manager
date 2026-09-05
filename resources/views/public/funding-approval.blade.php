@extends('layouts.public')
@section('title', 'Funding decision')

@section('content')
<div class="bg-white rounded-lg shadow p-6 mb-6">
    <h1 class="text-xl font-bold text-gray-900 mb-1">Funding decision needed</h1>
    <p class="text-sm text-gray-500">
        {{ $round->items->count() }} {{ \Illuminate\Support\Str::plural('piece', $round->items->count()) }} of content,
        {{ rtrim(rtrim(number_format((float) $round->total_hours, 1), '0'), '.') }} hours in total.
        Nothing is written until this is agreed.
    </p>
</div>

<div class="bg-white rounded-lg shadow overflow-hidden mb-6">
    <table class="min-w-full divide-y divide-gray-200 text-sm">
        <thead class="bg-gray-50">
            <tr>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Content</th>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">What it's for</th>
                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Hours</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-200">
            @foreach($round->items as $item)
            <tr>
                <td class="px-4 py-3 text-gray-900">
                    {{ $item->changeRequest?->subjectDescription() ?? 'No longer in the system' }}
                    <span class="block text-xs text-gray-400">{{ $item->changeRequest?->reference }}</span>
                </td>
                <td class="px-4 py-3 text-gray-600">{{ \Illuminate\Support\Str::limit($item->changeRequest?->content_brief['achieve'] ?? '', 140) ?: '—' }}</td>
                <td class="px-4 py-3 text-right font-semibold text-gray-900 whitespace-nowrap">{{ rtrim(rtrim(number_format((float) $item->estimated_hours, 1), '0'), '.') }}</td>
            </tr>
            @endforeach
            <tr class="bg-gray-50">
                <td colspan="2" class="px-4 py-3 font-bold text-gray-900">Total</td>
                <td class="px-4 py-3 text-right font-bold text-hcrg-burgundy">{{ rtrim(rtrim(number_format((float) $round->total_hours, 1), '0'), '.') }}</td>
            </tr>
        </tbody>
    </table>
</div>

<div class="bg-white rounded-lg shadow p-6">
    <form method="POST" action="{{ route('funding.respond', request()->route('token')) }}" id="fundingForm">
        @csrf
        <input type="hidden" name="decision" id="decisionField" value="approved">

        <div id="declineReason" class="hidden mb-4">
            <label for="notes" class="block text-sm font-medium text-gray-700 mb-1">Why not? <span class="text-red-500">*</span></label>
            <p class="text-xs text-gray-500 mb-2">The content team picks this up, so a sentence on what would change your mind is more use than a no.</p>
            <textarea name="notes" id="notes" rows="3"
                class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-hcrg-burgundy focus:border-hcrg-burgundy">{{ old('notes') }}</textarea>
            @error('notes') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
        </div>

        <div class="flex flex-col sm:flex-row gap-3">
            <button type="submit" id="approveBtn"
                class="flex-1 bg-status-success hover:bg-green-700 text-white font-bold py-3 px-6 rounded-lg transition-colors">
                Approve these hours
            </button>
            <button type="button" id="declineBtn"
                class="flex-1 border border-gray-300 text-gray-700 font-medium py-3 px-6 rounded-lg hover:bg-gray-50 transition-colors">
                Decline
            </button>
        </div>
    </form>
</div>

<script>
(function () {
    var decline = document.getElementById('declineBtn');
    var reason = document.getElementById('declineReason');
    var field = document.getElementById('decisionField');
    var approve = document.getElementById('approveBtn');
    var notes = document.getElementById('notes');

    decline.addEventListener('click', function () {
        // Two steps for a decline: the reason is what the content team acts on.
        if (reason.classList.contains('hidden')) {
            reason.classList.remove('hidden');
            field.value = 'declined';
            notes.required = true;
            approve.classList.add('hidden');
            decline.textContent = 'Confirm decline';
            decline.type = 'submit';
            notes.focus();
        }
    });
})();
</script>
@endsection
