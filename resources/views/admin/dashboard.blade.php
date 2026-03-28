@extends('layouts.admin')
@section('title', 'Dashboard')

@section('content')
@php $update = app(App\Services\UpdateService::class)->checkForUpdates(); @endphp
@if($update['available'])
<div class="mb-4 p-3 bg-amber-50 border border-amber-200 rounded-lg flex items-center justify-between">
    <span class="text-sm text-amber-800">A new version ({{ $update['latest_version'] }}) is available.</span>
    <a href="{{ route('admin.settings.updates') }}" class="text-sm font-medium text-hcrg-burgundy hover:underline">View update</a>
</div>
@endif
<h1 class="text-2xl font-bold text-gray-900 mb-6">Dashboard</h1>

<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
    <div class="bg-white rounded-lg shadow p-6">
        <div class="text-sm font-medium text-gray-500">New Requests</div>
        <div class="text-3xl font-bold text-amber-600 mt-1">{{ $stats['requested'] }}</div>
    </div>
    <div class="bg-white rounded-lg shadow p-6">
        <div class="text-sm font-medium text-gray-500">In Progress</div>
        <div class="text-3xl font-bold text-hcrg-burgundy mt-1">{{ $stats['in_progress'] }}</div>
    </div>
    <div class="bg-white rounded-lg shadow p-6">
        <div class="text-sm font-medium text-gray-500">Completed</div>
        <div class="text-3xl font-bold text-emerald-600 mt-1">{{ $stats['done'] }}</div>
    </div>
    <div class="bg-white rounded-lg shadow p-6">
        <div class="text-sm font-medium text-gray-500">Active Sites</div>
        <div class="text-3xl font-bold text-hcrg-charcoal mt-1">{{ $stats['sites'] }}</div>
    </div>
</div>

<div class="bg-white rounded-lg shadow">
    <div class="px-6 py-4 border-b border-gray-200">
        <h2 class="text-lg font-semibold text-gray-900">Recent Requests</h2>
    </div>
    @if($recent->isEmpty())
        <div class="px-6 py-8 text-center text-gray-500">No requests yet.</div>
    @else
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Reference</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Site</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Requester</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Date</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
                @foreach($recent as $req)
                <tr class="hover:bg-gray-50 even:bg-gray-50/50">
                    <td class="px-6 py-4">
                        <a href="{{ route('admin.requests.show', $req) }}" class="text-hcrg-burgundy hover:underline font-medium">{{ $req->reference }}</a>
                    </td>
                    <td class="px-6 py-4 text-sm text-gray-600">{{ $req->site->name ?? '—' }}</td>
                    <td class="px-6 py-4 text-sm text-gray-600">{{ $req->requester_name }}</td>
                    <td class="px-6 py-4">
                        @include('admin.partials.status-badge', ['status' => $req->status])
                    </td>
                    <td class="px-6 py-4 text-sm text-gray-500">{{ $req->created_at->format('d M Y H:i') }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    @endif
</div>
@endsection
