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
                        <label class="flex items-center space-x-1.5 cursor-pointer">
                            <input type="checkbox" name="share_details" value="1" class="h-3 w-3 text-hcrg-burgundy border-gray-300 rounded">
                            <span class="text-[10px] text-gray-500">Share approver name with requester</span>
                        </label>
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
