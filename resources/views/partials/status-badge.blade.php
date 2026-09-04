{{-- Single source for status pills. Labels and colours live on the model so the
     six views that show a status cannot drift apart. --}}
<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium whitespace-nowrap {{ \App\Models\ChangeRequest::statusColor($status) }}">
    {{ \App\Models\ChangeRequest::statusLabel($status) }}
</span>
