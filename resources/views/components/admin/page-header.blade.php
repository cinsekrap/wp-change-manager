@props(['title', 'lede' => null])

{{-- Every admin page opens the same way: what this is, one line on what it holds,
     and the actions that belong to the whole page. --}}
<div class="flex flex-wrap justify-between items-start gap-3 mb-6">
    <div>
        <h1 class="page-title">{{ $title }}</h1>
        @if($lede)
            <p class="page-lede">{{ $lede }}</p>
        @endif
    </div>
    @if(trim($slot) !== '')
        <div class="flex flex-wrap items-center gap-2">{{ $slot }}</div>
    @endif
</div>
