@extends('layouts.admin')
@section('title', $changeRequest->reference)

@section('content')
<div class="flex items-center justify-between mb-6">
    <div>
        <a href="{{ route('admin.requests.index') }}" class="text-sm text-gray-500 hover:text-gray-700">&larr; Back to requests</a>
        <h1 class="text-2xl font-bold text-gray-900 mt-1">{{ $changeRequest->reference }}</h1>
    </div>
    <div class="flex items-center space-x-2">
        @include('admin.partials.priority-badge', ['priority' => $changeRequest->priority ?? 'normal'])
        @include('admin.partials.status-badge', ['status' => $changeRequest->status])
    </div>
</div>

@if($changeRequest->status === 'requested')
    @php
        $allPassed = collect($changeRequest->check_answers ?? [])->every(fn($a) => !empty($a['pass']));
        $siteRequiresApproval = $changeRequest->site->requires_approval ?? false;
    @endphp
    @if(!$allPassed)
        <div class="mb-6 p-4 bg-amber-50 border-2 border-amber-200 rounded-xl flex items-start space-x-3">
            <svg class="w-6 h-6 text-amber-500 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M10.29 3.86l-8.58 14.86A1 1 0 002.56 20h18.88a1 1 0 00.85-1.28L13.71 3.86a1 1 0 00-1.42 0z"/>
            </svg>
            <div>
                <p class="text-sm font-semibold text-amber-800">Pre-submission checks did not all pass</p>
                <p class="text-sm text-amber-700 mt-1">This request has not been automatically sent for approval. Review the pre-submission checks below and decide whether to send for approval or decline.</p>
            </div>
        </div>
    @elseif($siteRequiresApproval)
        <div class="mb-6 p-4 bg-blue-50 border-2 border-blue-200 rounded-xl flex items-start space-x-3">
            <svg class="w-6 h-6 text-blue-500 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            <div>
                <p class="text-sm font-semibold text-blue-800">This site requires manual approval for all requests</p>
                <p class="text-sm text-blue-700 mt-1">Review the request and send for approval when ready, or decline if not appropriate.</p>
            </div>
        </div>
    @endif
@endif

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    {{-- Main content --}}
    <div class="lg:col-span-2 space-y-6">
        {{-- Combined request + requester info --}}
        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex flex-wrap items-start gap-x-8 gap-y-2 text-sm">
                <div>
                    <span class="text-gray-500">Site:</span>
                    <span class="font-medium text-gray-900 ml-1">{{ $changeRequest->site->name ?? '—' }}</span>
                </div>
                <div>
                    <span class="text-gray-500">Type:</span>
                    <span class="font-medium text-gray-900 ml-1">{{ $changeRequest->cpt_slug }}</span>
                </div>
                <div>
                    <span class="text-gray-500">Submitted:</span>
                    <span class="font-medium text-gray-900 ml-1">{{ $changeRequest->created_at->format('d M Y H:i') }}</span>
                    <span class="text-gray-400 ml-1">({{ $changeRequest->created_at->diffForHumans() }})</span>
                </div>
                @if($changeRequest->deadline_date)
                <div>
                    @php $overdue = $changeRequest->deadline_date->isPast(); @endphp
                    <span class="text-gray-500">Deadline:</span>
                    <span class="font-medium ml-1 {{ $overdue ? 'text-red-600' : 'text-gray-900' }}">{{ $changeRequest->deadline_date->format('d M Y') }}</span>
                    <span class="{{ $overdue ? 'text-red-400' : 'text-gray-400' }} ml-1">({{ $changeRequest->deadline_date->diffForHumans() }})</span>
                </div>
                @endif
            </div>
            @if($changeRequest->deadline_reason)
            <div class="mt-1 text-sm">
                <span class="text-gray-500">Deadline reason:</span>
                <span class="text-gray-700 ml-1">{{ $changeRequest->deadline_reason }}</span>
            </div>
            @endif

            @if($changeRequest->isActive())
            @php
                $slaStatus = $changeRequest->slaStatus();
                $slaHoursRemaining = $changeRequest->slaRemainingHours();
                $slaColors = [
                    'on_track' => 'text-emerald-600',
                    'at_risk' => 'text-amber-600',
                    'overdue' => 'text-red-600',
                ];
            @endphp
            <div class="mt-2 text-sm flex items-center gap-x-2">
                <span class="text-gray-500">SLA:</span>
                @if($slaStatus === 'overdue')
                    <span class="font-medium {{ $slaColors[$slaStatus] }}">Overdue by {{ abs($slaHoursRemaining) }} hours</span>
                @elseif($slaStatus === 'at_risk')
                    <span class="font-medium {{ $slaColors[$slaStatus] }}">Due in {{ $slaHoursRemaining }} hours</span>
                @else
                    <span class="font-medium {{ $slaColors[$slaStatus] }}">Due in {{ $slaHoursRemaining }} hours</span>
                @endif
            </div>
            @endif

            <div class="mt-2 text-sm">
                <span class="text-gray-500">Page:</span>
                @if($changeRequest->is_new_page)
                    <span class="text-orange-600 font-medium ml-1">New page:</span>
                    <span class="text-gray-900 ml-1">{{ $changeRequest->page_title }}</span>
                @else
                    <a href="{{ $changeRequest->page_url }}" target="_blank" class="text-hcrg-burgundy hover:underline ml-1">{{ $changeRequest->page_title ?: $changeRequest->page_url }}</a>
                @endif
            </div>

            <div class="mt-3 pt-3 border-t border-gray-100 flex flex-wrap items-center gap-x-6 gap-y-1 text-sm">
                <span class="font-medium text-gray-900">{{ $changeRequest->requester_name }}</span>
                <a href="mailto:{{ $changeRequest->requester_email }}" class="text-hcrg-burgundy hover:underline">{{ $changeRequest->requester_email }}</a>
                @if($changeRequest->requester_phone)
                    <span class="text-gray-600">{{ $changeRequest->requester_phone }}</span>
                @endif
                @if($changeRequest->requester_role)
                    <span class="text-gray-400">{{ $changeRequest->requester_role }}</span>
                @endif
            </div>

        </div>

        {{-- Line items --}}
        <div class="bg-white rounded-lg shadow p-6">
            @php
                $totalItems = $changeRequest->items->count();
                $doneItems = $changeRequest->items->where('status', 'done')->count();
                $allDone = $totalItems > 0 && $doneItems === $totalItems;
                $itemStatusColors = [
                    'pending' => 'bg-gray-400',
                    'in_progress' => 'bg-hcrg-burgundy',
                    'done' => 'bg-emerald-500',
                    'not_done' => 'bg-red-500',
                    'deferred' => 'bg-amber-500',
                ];
                $itemStatusRingColors = [
                    'pending' => 'ring-gray-400',
                    'in_progress' => 'ring-hcrg-burgundy',
                    'done' => 'ring-emerald-500',
                    'not_done' => 'ring-red-500',
                    'deferred' => 'ring-amber-500',
                ];
                $itemStatusLabels = [
                    'pending' => 'Pending',
                    'in_progress' => 'In Progress',
                    'done' => 'Done',
                    'not_done' => 'Not Done',
                    'deferred' => 'Deferred',
                ];
            @endphp

            <div class="flex items-center justify-between mb-4">
                <h2 class="text-lg font-semibold text-gray-900">Change Items ({{ $totalItems }})</h2>
                @if($totalItems > 0)
                <div class="flex items-center space-x-3">
                    <span class="text-sm text-gray-500">{{ $doneItems }} of {{ $totalItems }} items completed</span>
                    <div class="w-24 h-2 bg-gray-200 rounded-full overflow-hidden">
                        <div class="h-full rounded-full {{ $allDone ? 'bg-emerald-500' : 'bg-hcrg-burgundy' }} transition-all" style="width: {{ round(($doneItems / $totalItems) * 100) }}%"></div>
                    </div>
                </div>
                @endif
            </div>

            {{-- All complete banner --}}
            @if($allDone && $changeRequest->status !== 'done')
            <div class="mb-4 p-3 bg-emerald-50 border border-emerald-200 rounded-lg flex items-center justify-between">
                <div class="flex items-center space-x-2">
                    <svg class="w-5 h-5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    <span class="text-sm font-medium text-emerald-800">All items are complete. Would you like to mark this request as done?</span>
                </div>
                <form method="POST" action="{{ route('admin.requests.status', $changeRequest) }}">
                    @csrf @method('PATCH')
                    <input type="hidden" name="status" value="done">
                    <button type="submit" class="inline-flex items-center px-3 py-1.5 bg-emerald-600 text-white text-xs font-medium rounded-full hover:bg-emerald-700 transition-colors">
                        Mark as Done
                    </button>
                </form>
            </div>
            @endif

            <div class="space-y-4">
                @foreach($changeRequest->items as $index => $item)
                @php
                    $borderColor = match($item->action_type) {
                        'add' => 'border-green-200',
                        'delete' => 'border-red-200',
                        default => 'border-hcrg-burgundy/20',
                    };
                @endphp
                <div class="border {{ $borderColor }} rounded-lg p-4">
                    <div class="flex items-center justify-between mb-3">
                        <div class="flex items-center space-x-3">
                            <span class="flex items-center space-x-1.5">
                                <span class="w-2 h-2 rounded-full {{ $itemStatusColors[$item->status] ?? 'bg-gray-400' }}"></span>
                                <span class="text-sm font-medium text-gray-500">#{{ $index + 1 }}</span>
                            </span>
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                {{ $item->action_type === 'add' ? 'bg-green-100 text-green-800' : ($item->action_type === 'delete' ? 'bg-red-100 text-red-800' : 'bg-hcrg-burgundy/10 text-hcrg-burgundy') }}">
                                {{ ucfirst($item->action_type) }}
                            </span>
                            @if($item->content_area)
                                <span class="text-sm text-gray-500">{{ $item->content_area }}</span>
                            @endif
                        </div>
                        <form method="POST" action="{{ route('admin.requests.items.status', [$changeRequest, $item]) }}" class="flex-shrink-0">
                            @csrf @method('PATCH')
                            <select name="status" onchange="this.form.submit()"
                                class="text-xs pl-2 pr-7 py-1 border border-gray-300 rounded-full focus:ring-2 focus:ring-hcrg-burgundy focus:border-hcrg-burgundy {{ $itemStatusRingColors[$item->status] ?? '' }} ring-1 appearance-none bg-white cursor-pointer"
                                style="background-image: url('data:image/svg+xml;charset=UTF-8,%3Csvg xmlns=%22http://www.w3.org/2000/svg%22 viewBox=%220 0 20 20%22 fill=%22%236b7280%22%3E%3Cpath fill-rule=%22evenodd%22 d=%22M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z%22 clip-rule=%22evenodd%22/%3E%3C/svg%3E'); background-position: right 0.35rem center; background-repeat: no-repeat; background-size: 0.9em 0.9em;">
                                @foreach(\App\Models\ChangeRequestItem::STATUSES as $s)
                                    <option value="{{ $s }}" {{ $item->status === $s ? 'selected' : '' }}>{{ $itemStatusLabels[$s] }}</option>
                                @endforeach
                            </select>
                        </form>
                    </div>

                    @if($item->action_type === 'change' && $item->current_content)
                        {{-- Change: show before -> after --}}
                        <div class="space-y-2">
                            <div class="p-3 bg-red-50 border border-red-200 rounded-lg">
                                <p class="text-xs font-medium text-red-700 mb-1">Current content</p>
                                <p class="text-sm text-gray-700 whitespace-pre-wrap">{{ $item->current_content }}</p>
                            </div>
                            <div class="flex justify-center">
                                <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 14l-7 7m0 0l-7-7m7 7V3"/></svg>
                            </div>
                            <div class="p-3 bg-green-50 border border-green-200 rounded-lg">
                                <p class="text-xs font-medium text-green-700 mb-1">Replace with</p>
                                <p class="text-sm text-gray-700 whitespace-pre-wrap">{{ $item->description }}</p>
                            </div>
                        </div>
                    @elseif($item->action_type === 'delete')
                        {{-- Delete: show what's being removed --}}
                        <div class="p-3 bg-red-50 border border-red-200 rounded-lg">
                            <p class="text-xs font-medium text-red-700 mb-1">Content to remove</p>
                            <p class="text-sm text-gray-700 whitespace-pre-wrap">{{ $item->description }}</p>
                        </div>
                        @if($item->current_content)
                            <p class="mt-2 text-sm text-gray-500"><span class="font-medium">Reason:</span> {{ $item->current_content }}</p>
                        @endif
                    @elseif($item->action_type === 'add')
                        {{-- Add: show what's being added --}}
                        <div class="p-3 bg-green-50 border border-green-200 rounded-lg">
                            <p class="text-xs font-medium text-green-700 mb-1">New content</p>
                            <p class="text-sm text-gray-700 whitespace-pre-wrap">{{ $item->description }}</p>
                        </div>
                    @else
                        {{-- Fallback: plain text --}}
                        <p class="text-sm text-gray-700 whitespace-pre-wrap">{{ $item->description }}</p>
                    @endif

                    @if($item->files->isNotEmpty())
                    <div class="mt-3 pt-3 border-t border-gray-100">
                        <p class="text-xs font-medium text-gray-500 mb-2">Attachments:</p>
                        <div class="space-y-1">
                            @foreach($item->files as $file)
                            <div class="mb-2">
                                <a href="{{ route('admin.requests.download', [$changeRequest, $file]) }}"
                                   class="flex items-center text-sm text-hcrg-burgundy hover:text-[#9A1B4B]">
                                    <svg class="w-4 h-4 mr-1 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                    </svg>
                                    {{ $file->original_filename }}
                                    <span class="ml-1 text-gray-400 text-xs">({{ number_format($file->file_size / 1024, 0) }}KB)</span>
                                </a>
                                @if($file->title)
                                    <p class="text-sm font-medium text-gray-700 ml-5">{{ $file->title }}</p>
                                @endif
                                @if($file->description)
                                    <p class="text-xs text-gray-500 ml-5">{{ $file->description }}</p>
                                @endif
                            </div>
                            @endforeach
                        </div>
                    </div>
                    @endif
                </div>
                @endforeach
            </div>
        </div>

        {{-- Check answers — accordion --}}
        @if($changeRequest->check_answers)
        @php
            $allPass = collect($changeRequest->check_answers)->every(fn($a) => !empty($a['pass']));
            $failCount = collect($changeRequest->check_answers)->filter(fn($a) => isset($a['pass']) && !$a['pass'])->count();
        @endphp
        <div class="bg-white rounded-lg shadow">
            <button type="button" onclick="this.nextElementSibling.classList.toggle('hidden'); this.querySelector('.chevron').classList.toggle('rotate-180')"
                class="w-full flex items-center justify-between p-4 text-left hover:bg-gray-50 transition-colors">
                <div class="flex items-center space-x-2">
                    <span class="text-sm font-semibold text-gray-700">Pre-submission Checks</span>
                    @if($allPass)
                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-700">All passed</span>
                    @else
                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-700">{{ $failCount }} failed</span>
                    @endif
                </div>
                <svg class="chevron w-4 h-4 text-gray-400 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                </svg>
            </button>
            <div class="hidden border-t border-gray-100 p-4">
                <dl class="space-y-2 text-sm">
                    @foreach($changeRequest->check_answers as $answer)
                    @php $passed = !empty($answer['pass']); @endphp
                    <div class="flex items-start justify-between gap-4">
                        <dt class="text-gray-500 flex items-center gap-1.5">
                            @if(isset($answer['pass']))
                                <span class="flex-shrink-0 w-4 h-4 rounded-full {{ $passed ? 'bg-green-100 text-green-600' : 'bg-red-100 text-red-600' }} flex items-center justify-center">
                                    @if($passed)
                                        <svg class="w-2.5 h-2.5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                                    @else
                                        <svg class="w-2.5 h-2.5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"/></svg>
                                    @endif
                                </span>
                            @endif
                            {{ $answer['question_text'] ?? 'Question' }}
                        </dt>
                        <dd class="font-medium flex-shrink-0 {{ isset($answer['pass']) ? ($passed ? 'text-green-700' : 'text-red-700') : 'text-gray-900' }}">{{ $answer['answer'] ?? '—' }}</dd>
                    </div>
                    @endforeach
                </dl>
            </div>
        </div>
        @endif

        {{-- Activity timeline: notes + status changes merged --}}
        <div class="bg-white rounded-lg shadow p-6">
            <h2 class="text-lg font-semibold text-gray-900 mb-4">Activity</h2>

            <div class="relative">
                <div class="absolute left-3.5 top-2 bottom-2 w-px bg-gray-200"></div>

                <div class="space-y-4">
                    @foreach($activities as $activity)
                    <div class="relative flex items-start space-x-3 pl-1">
                        @if($activity->type === 'created')
                            <div class="flex-shrink-0 w-6 h-6 rounded-full bg-hcrg-burgundy/20 flex items-center justify-center ring-4 ring-white z-10">
                                <div class="w-2 h-2 rounded-full bg-hcrg-burgundy"></div>
                            </div>
                            <div class="flex-1 min-w-0">
                                <p class="text-sm text-gray-700"><span class="font-medium">{{ $activity->user }}</span> submitted this request</p>
                                <p class="text-xs text-gray-400 mt-0.5">{{ $activity->date->format('d M Y H:i') }} &middot; {{ $activity->date->diffForHumans() }}</p>
                            </div>
                        @elseif($activity->type === 'status')
                            <div class="flex-shrink-0 w-6 h-6 rounded-full bg-amber-100 flex items-center justify-center ring-4 ring-white z-10">
                                <div class="w-2 h-2 rounded-full bg-amber-500"></div>
                            </div>
                            <div class="flex-1 min-w-0">
                                <p class="text-sm text-gray-700">
                                    <span class="font-medium">{{ $activity->user }}</span> changed status
                                    @include('admin.partials.status-badge', ['status' => $activity->old_status])
                                    <span class="text-gray-400 mx-0.5">&rarr;</span>
                                    @include('admin.partials.status-badge', ['status' => $activity->new_status])
                                </p>
                                <p class="text-xs text-gray-400 mt-0.5">{{ $activity->date->format('d M Y H:i') }} &middot; {{ $activity->date->diffForHumans() }}</p>
                            </div>
                        @elseif($activity->type === 'approval')
                            <div class="flex-shrink-0 w-6 h-6 rounded-full {{ $activity->approval_status === 'approved' ? 'bg-green-100' : 'bg-red-100' }} flex items-center justify-center ring-4 ring-white z-10">
                                <div class="w-2 h-2 rounded-full {{ $activity->approval_status === 'approved' ? 'bg-green-500' : 'bg-red-500' }}"></div>
                            </div>
                            <div class="flex-1 min-w-0">
                                <p class="text-sm text-gray-700">
                                    <span class="font-medium">{{ $activity->user }}</span>
                                    {{ $activity->approval_status === 'approved' ? 'approved' : 'rejected' }} this request
                                </p>
                                @if($activity->notes)
                                    <p class="text-xs text-gray-500 mt-0.5">{{ $activity->notes }}</p>
                                @endif
                                <p class="text-xs text-gray-400 mt-0.5">{{ $activity->date->format('d M Y H:i') }} &middot; {{ $activity->date->diffForHumans() }}</p>
                            </div>
                        @elseif($activity->type === 'override')
                            <div class="flex-shrink-0 w-6 h-6 rounded-full bg-amber-100 flex items-center justify-center ring-4 ring-white z-10">
                                <div class="w-2 h-2 rounded-full bg-amber-500"></div>
                            </div>
                            <div class="flex-1 min-w-0">
                                <p class="text-sm text-gray-700"><span class="font-medium">{{ $activity->user }}</span> overrode the approval gate</p>
                                <p class="text-xs text-gray-400 mt-0.5">{{ $activity->date->format('d M Y H:i') }} &middot; {{ $activity->date->diffForHumans() }}</p>
                            </div>
                        @elseif($activity->type === 'note')
                            <div class="flex-shrink-0 w-6 h-6 rounded-full bg-gray-100 flex items-center justify-center ring-4 ring-white z-10">
                                <div class="w-2 h-2 rounded-full bg-gray-400"></div>
                            </div>
                            <div class="flex-1 min-w-0">
                                <p class="text-sm text-gray-700">{{ $activity->note }}</p>
                                <p class="text-xs text-gray-400 mt-0.5"><span class="font-medium">{{ $activity->user }}</span> &middot; {{ $activity->date->format('d M Y H:i') }} &middot; {{ $activity->date->diffForHumans() }}</p>
                            </div>
                        @endif
                    </div>
                    @endforeach
                </div>
            </div>

            {{-- Add note form --}}
            <form method="POST" action="{{ route('admin.requests.notes', $changeRequest) }}" class="mt-6 pt-4 border-t border-gray-200">
                @csrf
                <div class="flex items-start space-x-3">
                    <div class="flex-1">
                        <textarea name="note" rows="2" required placeholder="Add a note..."
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-hcrg-burgundy focus:border-hcrg-burgundy"></textarea>
                    </div>
                    <button type="submit" class="flex-shrink-0 bg-hcrg-burgundy text-white px-4 py-2 rounded-full hover:bg-[#9A1B4B] text-sm font-medium">Add</button>
                </div>
            </form>
        </div>

        {{-- Audit trail (collapsed by default, super_admin only) --}}
        @if(auth()->user()->isSuperAdmin())
        @php $auditEntries = \App\Models\AuditLog::forModel($changeRequest)->with('user')->latest()->get(); @endphp
        @if($auditEntries->isNotEmpty())
        <div class="bg-white rounded-lg shadow">
            <button type="button" onclick="this.nextElementSibling.classList.toggle('hidden'); this.querySelector('.chevron').classList.toggle('rotate-180')"
                class="w-full flex items-center justify-between p-4 text-left hover:bg-gray-50 transition-colors">
                <div class="flex items-center space-x-2">
                    <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/></svg>
                    <span class="text-sm font-semibold text-gray-700">Audit Trail</span>
                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-600">{{ $auditEntries->count() }}</span>
                </div>
                <svg class="chevron w-4 h-4 text-gray-400 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                </svg>
            </button>
            <div class="hidden border-t border-gray-100 p-4">
                <div class="space-y-3">
                    @foreach($auditEntries as $entry)
                    <div class="flex items-start justify-between gap-4 text-sm">
                        <div class="flex-1 min-w-0">
                            <p class="text-gray-700">{{ $entry->description }}</p>
                            <p class="text-xs text-gray-400 mt-0.5">
                                {{ $entry->user->name ?? 'System' }} &middot;
                                {{ $entry->created_at->format('d M Y H:i') }}
                                @if($entry->ip_address)
                                    &middot; {{ $entry->ip_address }}
                                @endif
                            </p>
                        </div>
                        @php
                            $auditActionColors = [
                                'status_changed' => 'bg-amber-100 text-amber-700',
                                'assigned' => 'bg-cyan-100 text-cyan-700',
                                'note_added' => 'bg-gray-100 text-gray-700',
                                'approver_added' => 'bg-green-100 text-green-700',
                                'approver_removed' => 'bg-red-100 text-red-700',
                                'approver_updated' => 'bg-hcrg-burgundy/10 text-hcrg-burgundy',
                                'sent_for_approval' => 'bg-purple-100 text-purple-700',
                                'item_status_changed' => 'bg-hcrg-burgundy/10 text-hcrg-burgundy',
                                'priority_changed' => 'bg-orange-100 text-orange-700',
                                'approval_overridden' => 'bg-amber-100 text-amber-700',
                            ];
                        @endphp
                        <span class="flex-shrink-0 inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-medium {{ $auditActionColors[$entry->action] ?? 'bg-gray-100 text-gray-600' }}">
                            {{ str_replace('_', ' ', ucfirst($entry->action)) }}
                        </span>
                    </div>
                    @endforeach
                </div>
            </div>
        </div>
        @endif
        @endif
    </div>

    {{-- Sidebar --}}
    <div class="space-y-4">
        {{-- Status + Priority + Assignment combined card --}}
        <div class="bg-white rounded-lg shadow p-4">
            <h2 class="text-sm font-semibold text-gray-900 mb-3">Status & Priority</h2>
            @php $canMovePast = $changeRequest->canMovePastReferred(); @endphp
            <form method="POST" action="{{ route('admin.requests.status', $changeRequest) }}" id="statusForm">
                @csrf @method('PATCH')
                <select name="status" id="statusSelect" onchange="toggleReasonField()" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm mb-2 focus:ring-2 focus:ring-hcrg-burgundy focus:border-hcrg-burgundy">
                    @foreach(\App\Models\ChangeRequest::STATUSES as $status)
                        @php $blocked = !$canMovePast && in_array($status, ['approved', 'scheduled', 'done']); @endphp
                        <option value="{{ $status }}" {{ $changeRequest->status === $status ? 'selected' : '' }} {{ $blocked ? 'disabled' : '' }}>
                            {{ $status === 'requires_referral' ? 'Requires Referral' : ucfirst($status) }}{{ $blocked ? ' (approvals required)' : '' }}
                        </option>
                    @endforeach
                </select>
                <div id="reasonField" class="hidden mb-2">
                    <textarea name="rejection_reason" rows="2" placeholder="Reason (required)..."
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-hcrg-burgundy focus:border-hcrg-burgundy">{{ old('rejection_reason') }}</textarea>
                    @error('rejection_reason') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>
                <button type="submit" class="w-full bg-hcrg-burgundy text-white px-4 py-2 rounded-full hover:bg-[#9A1B4B] text-sm font-medium">Update Status</button>
            </form>
            <script>
            function toggleReasonField() {
                var val = document.getElementById('statusSelect').value;
                var show = val === 'declined' || val === 'cancelled';
                document.getElementById('reasonField').classList.toggle('hidden', !show);
            }
            toggleReasonField();
            </script>

            @if($changeRequest->rejection_reason && in_array($changeRequest->status, ['declined', 'cancelled']))
            <div class="mt-2 p-2.5 bg-red-50 border border-red-200 rounded-lg">
                <p class="text-xs font-medium text-red-700 mb-0.5">Reason</p>
                <p class="text-sm text-red-800">{{ $changeRequest->rejection_reason }}</p>
            </div>
            @endif

            {{-- Send for approval button --}}
            @if($changeRequest->status === 'requested')
                @php
                    $siteHasApprovers = !empty($changeRequest->site->default_approvers);
                    $hasManualApprovers = $changeRequest->approvers->isNotEmpty();
                    $allChecksPassed = collect($changeRequest->check_answers ?? [])->every(fn($a) => !empty($a['pass']));
                @endphp
                @if($siteHasApprovers || $hasManualApprovers)
                    <div class="mt-3 pt-3 border-t border-gray-100">
                        <form method="POST" action="{{ route('admin.requests.send-approval', $changeRequest) }}" onsubmit="return confirm('This will{{ $siteHasApprovers && !$hasManualApprovers ? ' add the site\'s default approvers and' : '' }} send approval emails. Continue?')">
                            @csrf
                            <button type="submit" class="w-full bg-amber-500 text-white px-4 py-2 rounded-full hover:bg-amber-600 text-sm font-medium transition-colors">
                                Send for Approval
                            </button>
                        </form>
                    </div>
                @endif
            @endif

            {{-- Priority --}}
            <div class="mt-3 pt-3 border-t border-gray-100">
                <div class="flex items-center justify-between mb-2">
                    <span class="text-xs font-medium text-gray-500">Priority</span>
                    @include('admin.partials.priority-badge', ['priority' => $changeRequest->priority ?? 'normal'])
                </div>
                <form method="POST" action="{{ route('admin.requests.priority', $changeRequest) }}">
                    @csrf @method('PATCH')
                    <select name="priority" onchange="this.form.submit()"
                        class="w-full px-3 py-1.5 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-hcrg-burgundy focus:border-hcrg-burgundy">
                        @foreach(\App\Models\ChangeRequest::PRIORITIES as $p)
                            <option value="{{ $p }}" {{ ($changeRequest->priority ?? 'normal') === $p ? 'selected' : '' }}>{{ ucfirst($p) }}</option>
                        @endforeach
                    </select>
                </form>
            </div>

            {{-- Assignment inline --}}
            <div class="mt-3 pt-3 border-t border-gray-100">
                <div class="flex items-center justify-between mb-2">
                    <span class="text-xs font-medium text-gray-500">Assigned to</span>
                    @if($changeRequest->assignee)
                        <div class="flex items-center space-x-1.5">
                            <span class="inline-flex items-center justify-center w-5 h-5 rounded-full bg-hcrg-burgundy text-white text-[10px] font-semibold flex-shrink-0">{{ strtoupper(substr($changeRequest->assignee->name, 0, 1)) }}</span>
                            <span class="text-xs font-medium text-gray-700">{{ $changeRequest->assignee->name }}</span>
                        </div>
                    @else
                        <span class="text-xs text-gray-400">Unassigned</span>
                    @endif
                </div>
                <form method="POST" action="{{ route('admin.requests.assign', $changeRequest) }}">
                    @csrf @method('PATCH')
                    <select name="assigned_to" onchange="this.form.submit()"
                        class="w-full px-3 py-1.5 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-hcrg-burgundy focus:border-hcrg-burgundy">
                        <option value="">Unassigned</option>
                        @foreach($adminUsers as $admin)
                            <option value="{{ $admin->id }}" {{ $changeRequest->assigned_to == $admin->id ? 'selected' : '' }}>{{ $admin->name }}</option>
                        @endforeach
                    </select>
                </form>
            </div>
        </div>

        {{-- Approvals --}}
        <div class="bg-white rounded-lg shadow p-4">
            <div class="flex items-center justify-between mb-3">
                <h2 class="text-sm font-semibold text-gray-900">Approvals</h2>
                @if($changeRequest->approvers->isNotEmpty())
                    @if($changeRequest->approvalsAllApproved())
                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-700">All approved</span>
                    @elseif($changeRequest->approvalsComplete())
                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-700">Has rejections</span>
                    @else
                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-700">Pending</span>
                    @endif
                @endif
            </div>

            @if($changeRequest->approval_overridden)
            <div class="mb-3 p-2.5 bg-amber-50 border border-amber-200 rounded-lg">
                <p class="text-xs font-medium text-amber-800">
                    Approval gate overridden by {{ $changeRequest->approvalOverriddenByUser->name ?? 'Unknown' }}
                </p>
                <p class="text-xs text-amber-600 mt-0.5">
                    {{ $changeRequest->approval_overridden_at->format('d M Y H:i') }}
                </p>
            </div>
            @endif

            {{-- Existing approvers --}}
            @if($changeRequest->approvers->isNotEmpty())
            <div class="space-y-3 mb-4">
                @foreach($changeRequest->approvers as $approver)
                <div class="border border-gray-200 rounded-lg p-3">
                    <div class="flex items-center justify-between mb-1">
                        <div>
                            <span class="text-sm font-medium text-gray-900">{{ $approver->name }}</span>
                            @if($approver->email)
                                <span class="text-xs text-gray-400 ml-1">{{ $approver->email }}</span>
                            @endif
                        </div>
                        @if($approver->isPending())
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-700">Pending</span>
                        @elseif($approver->status === 'approved')
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-700">Approved</span>
                        @else
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-700">Rejected</span>
                        @endif
                    </div>

                    @if($approver->responded_at)
                        <p class="text-xs text-gray-400">{{ $approver->responded_at->format('d M Y H:i') }} ({{ $approver->responded_at->diffForHumans() }})</p>
                    @endif

                    @if($approver->notes)
                        <p class="text-xs text-gray-500 mt-1">{{ $approver->notes }}</p>
                    @endif

                    @if($approver->isPending())
                        {{-- Record response form --}}
                        <div class="mt-2 pt-2 border-t border-gray-100">
                            <form method="POST" action="{{ route('admin.requests.approvers.update', [$changeRequest, $approver]) }}" class="space-y-2">
                                @csrf @method('PATCH')
                                <div class="flex gap-2">
                                    <select name="status" required class="flex-1 px-2 py-1 border border-gray-300 rounded text-xs focus:ring-2 focus:ring-hcrg-burgundy focus:border-hcrg-burgundy">
                                        <option value="">Decision...</option>
                                        <option value="approved">Approved</option>
                                        <option value="rejected">Rejected</option>
                                    </select>
                                    <input type="datetime-local" name="responded_at" value="{{ now()->format('Y-m-d\TH:i') }}" required
                                        class="flex-1 px-2 py-1 border border-gray-300 rounded text-xs focus:ring-2 focus:ring-hcrg-burgundy focus:border-hcrg-burgundy">
                                </div>
                                <input type="text" name="notes" placeholder="Notes (optional)" class="w-full px-2 py-1 border border-gray-300 rounded text-xs focus:ring-2 focus:ring-hcrg-burgundy focus:border-hcrg-burgundy">
                                <button type="submit" class="w-full bg-hcrg-burgundy text-white px-2 py-1 rounded-full text-xs font-medium hover:bg-[#9A1B4B]">Record</button>
                            </form>
                            <form method="POST" action="{{ route('admin.requests.approvers.remove', [$changeRequest, $approver]) }}" class="mt-1" onsubmit="return confirm('Remove this approver?')">
                                @csrf @method('DELETE')
                                <button type="submit" class="text-red-500 hover:text-red-700 text-xs">Remove</button>
                            </form>
                        </div>
                    @else
                        <div class="mt-1">
                            @if($approver->recordedByUser)
                                <p class="text-xs text-gray-400">Recorded by {{ $approver->recordedByUser->name }}</p>
                            @endif
                        </div>
                    @endif
                </div>
                @endforeach
            </div>
            @else
                <p class="text-sm text-gray-400 mb-4">No approvers added. Status can progress freely.</p>
            @endif

            @php $pendingCount = $changeRequest->approvers->where('status', 'pending')->count(); @endphp
            @if(auth()->user()->isSuperAdmin() && !$changeRequest->approval_overridden && $pendingCount > 0)
            <div class="border-t border-gray-100 pt-3 mb-3">
                <form method="POST" action="{{ route('admin.requests.override-approvals', $changeRequest) }}"
                      onsubmit="return confirm('This will override the approval gate and notify {{ $pendingCount }} pending approver(s). Continue?')">
                    @csrf
                    <button type="submit" class="override-btn w-full relative overflow-hidden text-white px-4 py-2 rounded-full text-sm font-medium bg-amber-500 transition-all duration-300">
                        <span class="relative">Override Approvals</span>
                    </button>
                    <style>.override-btn:hover{background:repeating-linear-gradient(-45deg,#f59e0b,#f59e0b 10px,#1a1a1a 10px,#1a1a1a 20px);text-shadow:0 1px 2px rgba(0,0,0,.5)}</style>
                </form>
            </div>
            @endif

            {{-- Add approver form --}}
            <form method="POST" action="{{ route('admin.requests.approvers.add', $changeRequest) }}" class="border-t border-gray-100 pt-3">
                @csrf
                <p class="text-xs font-medium text-gray-500 mb-2">Add approver</p>
                <div class="space-y-2">
                    <input type="text" name="name" required placeholder="Name" class="w-full px-2 py-1.5 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-hcrg-burgundy focus:border-hcrg-burgundy">
                    <input type="email" name="email" placeholder="Email (optional)" class="w-full px-2 py-1.5 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-hcrg-burgundy focus:border-hcrg-burgundy">
                    <button type="submit" class="w-full bg-hcrg-burgundy text-white px-3 py-1.5 rounded-full text-sm font-medium hover:bg-[#9A1B4B]">Add</button>
                </div>
            </form>
        </div>

        {{-- Tags --}}
        <div class="bg-white rounded-lg shadow p-4" id="tagsSection">
            <h2 class="text-sm font-semibold text-gray-900 mb-2">Tags</h2>
            <div id="tagsList" class="flex flex-wrap gap-1.5 mb-3">
                @foreach($changeRequest->tags as $tag)
                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium text-white tag-pill" style="background-color: {{ $tag->colour }}" data-tag-id="{{ $tag->id }}">
                    {{ $tag->name }}
                    <button type="button" onclick="removeTag({{ $changeRequest->id }}, {{ $tag->id }}, this)" class="ml-1 hover:text-gray-200 focus:outline-none">&times;</button>
                </span>
                @endforeach
            </div>
            <div class="relative">
                <input type="text" id="tagInput" placeholder="Add a tag..." autocomplete="off"
                    class="w-full px-3 py-1.5 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-hcrg-burgundy focus:border-hcrg-burgundy">
                <div id="tagSuggestions" class="hidden absolute z-20 mt-1 w-full bg-white border border-gray-200 rounded-lg shadow-lg py-1 max-h-40 overflow-y-auto"></div>
            </div>
        </div>
        <script>
        (function() {
            function escHtml(str) {
                var div = document.createElement('div');
                div.appendChild(document.createTextNode(str));
                return div.innerHTML;
            }
            var crId = {{ $changeRequest->id }};
            var csrfToken = document.querySelector('meta[name="csrf-token"]').content;
            var tagInput = document.getElementById('tagInput');
            var tagSuggestions = document.getElementById('tagSuggestions');
            var tagsList = document.getElementById('tagsList');
            var allTags = @json(\App\Models\Tag::orderBy('name')->get(['id','name','colour']));

            function getCurrentTagIds() {
                var pills = tagsList.querySelectorAll('.tag-pill');
                var ids = [];
                pills.forEach(function(p) { ids.push(parseInt(p.dataset.tagId)); });
                return ids;
            }

            tagInput.addEventListener('input', function() {
                var val = this.value.trim().toLowerCase();
                if (!val) { tagSuggestions.classList.add('hidden'); return; }
                var currentIds = getCurrentTagIds();
                var matches = allTags.filter(function(t) {
                    return t.name.toLowerCase().indexOf(val) !== -1 && currentIds.indexOf(t.id) === -1;
                });
                var html = '';
                matches.forEach(function(t) {
                    html += '<button type="button" class="flex items-center w-full px-3 py-1.5 text-sm text-left hover:bg-gray-50" onclick="selectTag(\'' + escHtml(t.name).replace(/'/g, "&#39;") + '\')">' +
                        '<span class="w-3 h-3 rounded-full mr-2 flex-shrink-0" style="background-color:' + escHtml(t.colour) + '"></span>' + escHtml(t.name) + '</button>';
                });
                if (!matches.length && val.length > 0) {
                    html = '<button type="button" class="flex items-center w-full px-3 py-1.5 text-sm text-left hover:bg-gray-50 text-gray-500" onclick="selectTag(\'' + escHtml(val).replace(/'/g, "&#39;") + '\')">Create &ldquo;' + escHtml(val) + '&rdquo;</button>';
                }
                tagSuggestions.innerHTML = html;
                tagSuggestions.classList.remove('hidden');
            });

            tagInput.addEventListener('keydown', function(e) {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    var val = this.value.trim();
                    if (val) selectTag(val);
                }
            });

            document.addEventListener('click', function(e) {
                if (!document.getElementById('tagsSection').contains(e.target)) {
                    tagSuggestions.classList.add('hidden');
                }
            });

            window.selectTag = function(name) {
                tagInput.value = '';
                tagSuggestions.classList.add('hidden');
                fetch('/admin/requests/' + crId + '/tags', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' },
                    body: JSON.stringify({ tag_name: name })
                }).then(function(r) { return r.json(); }).then(function(data) {
                    if (data.success) {
                        var tag = data.tag;
                        // Add to allTags if new
                        if (!allTags.find(function(t) { return t.id === tag.id; })) {
                            allTags.push({ id: tag.id, name: tag.name, colour: tag.colour });
                        }
                        // Add pill if not already there
                        if (!tagsList.querySelector('[data-tag-id="' + tag.id + '"]')) {
                            var span = document.createElement('span');
                            span.className = 'inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium text-white tag-pill';
                            span.style.backgroundColor = tag.colour;
                            span.dataset.tagId = tag.id;
                            span.innerHTML = escHtml(tag.name) + ' <button type="button" onclick="removeTag(' + crId + ',' + tag.id + ',this)" class="ml-1 hover:text-gray-200 focus:outline-none">&times;</button>';
                            tagsList.appendChild(span);
                        }
                    }
                });
            };

            window.removeTag = function(crId, tagId, btn) {
                fetch('/admin/requests/' + crId + '/tags/' + tagId, {
                    method: 'DELETE',
                    headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' }
                }).then(function(r) { return r.json(); }).then(function(data) {
                    if (data.success) {
                        btn.closest('.tag-pill').remove();
                    }
                });
            };
        })();
        </script>

        {{-- Quick links --}}
        <div class="bg-white rounded-lg shadow p-4">
            <h2 class="text-sm font-semibold text-gray-900 mb-2">Quick Links</h2>
            <div class="space-y-1">
                @if(!$changeRequest->is_new_page)
                    <a href="{{ $changeRequest->page_url }}" target="_blank" class="flex items-center w-full px-2.5 py-1.5 bg-gray-50 text-gray-700 rounded-lg text-xs hover:bg-gray-100 transition-colors">
                        <svg class="w-3.5 h-3.5 mr-1.5 text-gray-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                        View page
                    </a>
                @endif
                @if($changeRequest->site)
                    <a href="https://{{ $changeRequest->site->domain }}/wp-admin" target="_blank" class="flex items-center w-full px-2.5 py-1.5 bg-gray-50 text-gray-700 rounded-lg text-xs hover:bg-gray-100 transition-colors">
                        <svg class="w-3.5 h-3.5 mr-1.5 text-gray-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.066 2.573c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.573 1.066c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.066-2.573c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                        wp-admin
                    </a>
                    <a href="https://{{ $changeRequest->site->domain }}" target="_blank" class="flex items-center w-full px-2.5 py-1.5 bg-gray-50 text-gray-700 rounded-lg text-xs hover:bg-gray-100 transition-colors">
                        <svg class="w-3.5 h-3.5 mr-1.5 text-gray-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9"/></svg>
                        View site
                    </a>
                @endif
            </div>
        </div>
        {{-- Page history --}}
        @if($pageHistory->isNotEmpty())
        <div class="bg-white rounded-lg shadow p-4">
            <h2 class="text-sm font-semibold text-gray-900 mb-2">Page History</h2>
            <div class="space-y-2">
                @foreach($pageHistory as $prev)
                <a href="{{ route('admin.requests.show', $prev) }}" class="block px-3 py-2 bg-gray-50 rounded-lg hover:bg-gray-100">
                    <div class="flex items-center justify-between">
                        <span class="text-sm font-medium text-hcrg-burgundy">{{ $prev->reference }}</span>
                        @include('admin.partials.status-badge', ['status' => $prev->status])
                    </div>
                    <p class="text-xs text-gray-500 mt-0.5">{{ $prev->requester_name }} &middot; {{ $prev->created_at->format('d M Y') }} ({{ $prev->created_at->diffForHumans() }})</p>
                </a>
                @endforeach
            </div>
        </div>
        @endif
    </div>
</div>
@endsection
