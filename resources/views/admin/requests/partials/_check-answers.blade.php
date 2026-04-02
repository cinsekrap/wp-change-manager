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
