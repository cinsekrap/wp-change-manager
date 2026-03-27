@extends('layouts.admin')
@section('title', 'Check Questions')

@section('content')
<div class="flex justify-between items-center mb-6">
    <h1 class="text-2xl font-bold text-gray-900">Check Questions</h1>
    <a href="{{ route('admin.questions.create') }}" class="bg-hcrg-burgundy text-white px-4 py-2 rounded-full hover:bg-[#9A1B4B] text-sm font-medium">Add Question</a>
</div>

<div class="bg-white rounded-lg shadow overflow-hidden">
    <table class="min-w-full divide-y divide-gray-200">
        <thead class="bg-gray-50">
            <tr>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Order</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Question</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Options</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Required</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Actions</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-200">
            @forelse($questions as $question)
            <tr class="hover:bg-gray-50 even:bg-gray-50/50">
                <td class="px-6 py-4 text-sm text-gray-600">{{ $question->sort_order }}</td>
                <td class="px-6 py-4 text-sm text-gray-900 max-w-md truncate">{{ $question->question_text }}</td>
                <td class="px-6 py-4 text-sm text-gray-600">{{ count($question->options ?? []) }} options</td>
                <td class="px-6 py-4 text-sm">{{ $question->is_required ? 'Yes' : 'No' }}</td>
                <td class="px-6 py-4">
                    @include('admin.partials.active-badge', ['active' => $question->is_active])
                </td>
                <td class="px-6 py-4 text-right space-x-2">
                    <a href="{{ route('admin.questions.edit', $question) }}" class="text-sm text-hcrg-burgundy hover:text-[#9A1B4B]">Edit</a>
                    <form method="POST" action="{{ route('admin.questions.destroy', $question) }}" class="inline" onsubmit="return confirm('Delete this question?')">
                        @csrf @method('DELETE')
                        <button type="submit" class="text-sm text-red-600 hover:text-red-800">Delete</button>
                    </form>
                </td>
            </tr>
            @empty
            <tr><td colspan="6" class="px-6 py-8 text-center text-gray-500">No questions yet.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>

<div class="mt-4">{{ $questions->links() }}</div>
@endsection
