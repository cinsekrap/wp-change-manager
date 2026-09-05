@extends('layouts.admin')
@section('title', 'Check questions')

@section('content')
<x-admin.page-header title="Check questions" lede="Asked before a request is submitted, so obvious problems are caught early.">
    <a href="{{ route('admin.questions.create') }}" class="btn btn-primary">Add question</a>
</x-admin.page-header>

<div class="card overflow-hidden">
    <table class="table">
        <thead class="bg-gray-50">
            <tr>
                <th>Order</th>
                <th>Question</th>
                <th>Options</th>
                <th>Required</th>
                <th>Status</th>
                <th class="text-right">Actions</th>
            </tr>
        </thead>
        <tbody>
            @forelse($questions as $question)
            <tr class="hover:bg-gray-50">
                <td class="text-gray-600">{{ $question->sort_order }}</td>
                <td class="text-gray-900 max-w-md truncate">{{ $question->question_text }}</td>
                <td class="text-gray-600">{{ count($question->options ?? []) }} options</td>
                <td>{{ $question->is_required ? 'Yes' : 'No' }}</td>
                <td>
                    @include('admin.partials.active-badge', ['active' => $question->is_active])
                </td>
                <td class="text-right space-x-2">
                    <a href="{{ route('admin.questions.edit', $question) }}" class="text-sm text-hcrg-burgundy hover:text-[#9A1B4B]">Edit</a>
                    <form method="POST" action="{{ route('admin.questions.destroy', $question) }}" class="inline" data-confirm="Delete this question?">
                        @csrf @method('DELETE')
                        <button type="submit" class="text-sm text-red-600 hover:text-red-800">Delete</button>
                    </form>
                </td>
            </tr>
            @empty
            <tr><td colspan="6" class="p-0">
                <x-admin.empty-state message="No check questions yet. These are asked before a request is submitted.">
                    <a href="{{ route('admin.questions.create') }}" class="btn btn-primary btn-sm">Add question</a>
                </x-admin.empty-state>
            </td></tr>
            @endforelse
        </tbody>
    </table>
</div>

<div class="mt-4">{{ $questions->links() }}</div>
@endsection
