@props(['tabs'])

{{-- The request page carries eleven cards. They all apply, but not at once: what
     you are working on, what you are working from, and what has happened are
     three different questions. --}}
<div class="border-b border-hcrg-grey-200 mb-6" data-tabs>
    <nav class="flex gap-1 -mb-px">
        @foreach($tabs as $key => $label)
            <button type="button" data-tab="{{ $key }}"
                class="px-4 py-2.5 text-sm font-semibold border-b-2 transition-colors
                       {{ $loop->first
                            ? 'border-hcrg-burgundy text-hcrg-burgundy'
                            : 'border-transparent text-hcrg-grey-400 hover:text-hcrg-charcoal' }}">
                {{ $label }}
            </button>
        @endforeach
    </nav>
</div>

{{ $slot }}

<script>
(function () {
    var root = document.querySelector('[data-tabs]');
    if (!root) return;
    var buttons = Array.prototype.slice.call(root.querySelectorAll('[data-tab]'));
    var panels = Array.prototype.slice.call(document.querySelectorAll('[data-tab-panel]'));

    function show(key) {
        buttons.forEach(function (b) {
            var on = b.dataset.tab === key;
            b.classList.toggle('border-hcrg-burgundy', on);
            b.classList.toggle('text-hcrg-burgundy', on);
            b.classList.toggle('border-transparent', !on);
            b.classList.toggle('text-hcrg-grey-400', !on);
        });
        panels.forEach(function (p) { p.hidden = p.dataset.tabPanel !== key; });
        // Survives a reload, so an admin who refreshes stays where they were.
        history.replaceState(null, '', '#' + key);
    }

    buttons.forEach(function (b) {
        b.addEventListener('click', function () { show(b.dataset.tab); });
    });

    // A link into the page can name a tab, or an element inside one.
    var wanted = (location.hash || '').replace('#', '');
    var owner = wanted && document.getElementById(wanted);
    if (owner) {
        var panel = owner.closest('[data-tab-panel]');
        if (panel) wanted = panel.dataset.tabPanel;
    }
    show(buttons.some(function (b) { return b.dataset.tab === wanted; }) ? wanted : buttons[0].dataset.tab);
})();
</script>
