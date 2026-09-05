@extends('layouts.admin')
@section('title', 'Funding')

@section('content')
<div class="funding-page">
    <div class="flex flex-wrap justify-between items-start gap-3 mb-2">
        <h1 class="text-2xl font-bold text-gray-900">Content awaiting a funding decision</h1>
        <button type="button" onclick="window.print()" class="no-print border border-hcrg-burgundy text-hcrg-burgundy px-4 py-2 rounded-full text-sm font-medium hover:bg-hcrg-burgundy hover:text-white transition-colors">
            Print this list
        </button>
    </div>
    <p class="text-sm text-gray-500 mb-6 max-w-3xl">
        Everything suggested, sized up or waiting on money &mdash; oldest first, because the wait is part of the
        argument. Funding is decided outside this tool; tick what gets a yes and mark it funded to move it on.
    </p>

    {{-- The numbers you get asked for in the room. --}}
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 mb-6">
        <div class="bg-white rounded-lg shadow p-5">
            <div class="text-xs font-medium text-gray-500 uppercase tracking-wide">Pieces waiting</div>
            <div class="text-3xl font-bold text-hcrg-charcoal mt-1">{{ $requests->count() }}</div>
        </div>
        <div class="bg-white rounded-lg shadow p-5">
            <div class="text-xs font-medium text-gray-500 uppercase tracking-wide">Hours estimated</div>
            <div class="text-3xl font-bold text-hcrg-burgundy mt-1">{{ rtrim(rtrim(number_format($totalHours, 1), '0'), '.') ?: '0' }}</div>
        </div>
        <div class="bg-white rounded-lg shadow p-5">
            <div class="text-xs font-medium text-gray-500 uppercase tracking-wide">Still to size up</div>
            <div class="text-3xl font-bold {{ $unsized ? 'text-amber-600' : 'text-hcrg-charcoal' }} mt-1">{{ $unsized }}</div>
        </div>
        <div class="bg-white rounded-lg shadow p-5 no-print">
            <div class="text-xs font-medium text-gray-500 uppercase tracking-wide">Selected</div>
            <div class="text-3xl font-bold text-hcrg-charcoal mt-1"><span id="selCount">0</span></div>
            <div class="text-xs text-hcrg-grey-400 mt-1"><span id="selHours">0</span> hours</div>
        </div>
    </div>

    <div class="bg-white rounded-lg shadow overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-3 py-3 w-8 no-print"><input type="checkbox" id="selAll" class="h-3.5 w-3.5 text-hcrg-burgundy border-gray-300 rounded"></th>
                        <th class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase">Reference</th>
                        <th class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase">What it is</th>
                        <th class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase">What it's for</th>
                        <th class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase">Sites</th>
                        <th class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                        <th class="px-3 py-3 text-right text-xs font-medium text-gray-500 uppercase whitespace-nowrap">Waiting</th>
                        <th class="px-3 py-3 text-right text-xs font-medium text-gray-500 uppercase">Hours</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @forelse($requests as $req)
                    <tr class="hover:bg-gray-50 even:bg-gray-50/50">
                        @php $waitingOn = $pendingByRequest[$req->id] ?? null; @endphp
                        <td class="px-3 py-3 no-print">
                            <input type="checkbox" class="fund-row h-3.5 w-3.5 text-hcrg-burgundy border-gray-300 rounded"
                                   value="{{ $req->id }}" data-hours="{{ (float) $req->estimated_hours }}"
                                   @disabled($waitingOn || $req->estimated_hours === null)>
                        </td>
                        <td class="px-3 py-3 whitespace-nowrap">
                            <a href="{{ route('admin.requests.show', $req) }}" class="text-hcrg-burgundy hover:underline font-medium">{{ $req->reference }}</a>
                        </td>
                        <td class="px-3 py-3 text-gray-900 max-w-xs">{{ $req->subjectDescription() }}</td>
                        <td class="px-3 py-3 text-gray-600 max-w-sm">{{ \Illuminate\Support\Str::limit($req->content_brief['achieve'] ?? '', 120) ?: '—' }}</td>
                        <td class="px-3 py-3 text-gray-600">{{ $req->allSites()->pluck('name')->join(', ') ?: 'Not yet decided' }}</td>
                        <td class="px-3 py-3">
                            @include('partials.status-badge', ['status' => $req->status])
                            @if($waitingOn)
                                <span class="block text-xs text-hcrg-grey-400 mt-1">asked {{ $waitingOn->created_at->diffForHumans() }}</span>
                            @endif
                        </td>
                        <td class="px-3 py-3 text-right text-gray-600 whitespace-nowrap">{{ (int) $req->created_at->diffInDays(now()) }} days</td>
                        <td class="px-3 py-3 text-right font-semibold {{ $req->estimated_hours === null ? 'text-amber-600' : 'text-gray-900' }} whitespace-nowrap">
                            {{ $req->estimated_hours !== null ? rtrim(rtrim(number_format((float) $req->estimated_hours, 1), '0'), '.') : 'not sized' }}
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="8" class="px-6 py-8 text-center text-gray-500">Nothing is waiting on a funding decision.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($requests->isNotEmpty())
        <div class="no-print border-t border-gray-200 p-4 space-y-4">
            @if($fundingApprovers->isEmpty())
                <p class="text-sm text-amber-700 bg-amber-50 border border-amber-200 rounded-lg p-3">
                    No funding approvers set up yet.
                    <a href="{{ route('admin.funding-approvers.index') }}" class="underline">Add one</a> to ask for money from here.
                </p>
            @else
                <form method="POST" action="{{ route('admin.funding.rounds.store') }}" id="askForm" class="flex flex-wrap items-end gap-3">
                    @csrf
                    <div>
                        <label for="fundingApprover" class="block text-xs font-medium text-gray-500 mb-1">Ask for funding from</label>
                        <select name="funding_approver_id" id="fundingApprover"
                            class="px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-hcrg-burgundy focus:border-hcrg-burgundy">
                            <option value="">Choose someone...</option>
                            @foreach($fundingApprovers as $fa)
                                <option value="{{ $fa->id }}" data-remit="{{ $fa->remit }}">{{ $fa->label() }}</option>
                            @endforeach
                        </select>
                    </div>
                    <button type="submit" id="askButton" disabled
                        class="bg-hcrg-burgundy text-white px-5 py-2 rounded-full text-sm font-medium hover:bg-[#9A1B4B] disabled:opacity-40 disabled:cursor-not-allowed">
                        Request funding for selected
                    </button>
                    <span id="askRemit" class="hidden text-xs text-hcrg-grey-400 basis-full"></span>
                </form>
                @error('funding_approver_id') <p class="text-xs text-red-600">{{ $message }}</p> @enderror
            @endif

            <div class="flex flex-wrap items-center gap-3 border-t border-gray-100 pt-3">
                <button type="button" id="markFunded" disabled
                    class="border border-hcrg-burgundy text-hcrg-burgundy px-5 py-2 rounded-full text-sm font-medium hover:bg-hcrg-burgundy hover:text-white transition-colors disabled:opacity-40 disabled:cursor-not-allowed">
                    Mark selected as funded
                </button>
                <span class="text-xs text-gray-500">For a decision already made elsewhere. Moves them straight to Being Written without asking anyone.</span>
            </div>
        </div>
        @endif
    </div>
</div>

<style>
@media print {
    .no-print { display: none !important; }
    nav, footer { display: none !important; }
    .funding-page { font-size: 11pt; }
    .shadow { box-shadow: none !important; }
    a { text-decoration: none; color: #000 !important; }
}
</style>

<script>
(function () {
    var rows = Array.prototype.slice.call(document.querySelectorAll('.fund-row'));
    var all = document.getElementById('selAll');
    var count = document.getElementById('selCount');
    var hours = document.getElementById('selHours');
    var button = document.getElementById('markFunded');
    if (!rows.length) return;

    function selected() { return rows.filter(function (r) { return r.checked; }); }

    function paint() {
        var picked = selected();
        var total = picked.reduce(function (sum, r) { return sum + (parseFloat(r.dataset.hours) || 0); }, 0);
        count.textContent = picked.length;
        // Whole numbers read better than 8.0 in a conversation about money.
        hours.textContent = total % 1 === 0 ? total : total.toFixed(1);
        if (button) button.disabled = picked.length === 0;
        var ask = document.getElementById('askButton');
        if (ask) ask.disabled = picked.length === 0;
    }

    rows.forEach(function (r) { r.addEventListener('change', paint); });
    if (all) all.addEventListener('change', function () {
        rows.forEach(function (r) { r.checked = all.checked; });
        paint();
    });

    // The ask posts the same selection as a normal form.
    var askForm = document.getElementById('askForm');
    if (askForm) {
        askForm.addEventListener('submit', function () {
            selected().forEach(function (r) {
                var hidden = document.createElement('input');
                hidden.type = 'hidden';
                hidden.name = 'ids[]';
                hidden.value = r.value;
                askForm.appendChild(hidden);
            });
        });

        var picker = document.getElementById('fundingApprover');
        var remit = document.getElementById('askRemit');
        if (picker && remit) picker.addEventListener('change', function () {
            var text = this.selectedOptions[0] ? this.selectedOptions[0].dataset.remit : '';
            remit.textContent = text ? 'Remit: ' + text : '';
            remit.classList.toggle('hidden', !text);
        });
    }

    if (button) button.addEventListener('click', function () {
        var ids = selected().map(function (r) { return parseInt(r.value, 10); });
        if (!ids.length) return;
        button.disabled = true;
        button.textContent = 'Saving...';

        fetch('{{ route("admin.requests.bulk.status") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            },
            body: JSON.stringify({ ids: ids, status: 'in_progress' })
        }).then(function (r) { return r.json(); })
          .then(function () { window.location.reload(); })
          .catch(function () {
              button.disabled = false;
              button.textContent = 'Mark selected as funded';
          });
    });

    paint();
})();
</script>
@endsection
