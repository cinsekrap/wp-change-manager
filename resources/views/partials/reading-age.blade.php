{{-- Live reading-age feedback on a copy field, and a gate on saving text that reads
     too old.

     The public wizard applies this to every change someone asks for; copy written
     inside the tool is held to the same standard, because content nobody can read
     is not content. The maths is a port of App\Support\ReadingAge — it has to run
     as you type, so it cannot call the PHP.

     Usage: @include('partials.reading-age', ['field' => 'draft_content'])
     where $field is the id of a textarea inside a form. The baseline for the
     "raises the reading age" nudge is the textarea's server-rendered value, so a
     new field only gets the absolute check. --}}

@once
<script>
(function () {
    const HIGH = 13;              // above this, the copy is flagged
    const MIN_WORDS = 30;         // Flesch-Kincaid is noise below this
    const MIN_WORDS_COMPARISON = 10;  // a softer floor, as in the wizard

    function countSyllables(word) {
        word = word.toLowerCase().replace(/[^a-z]/g, '');
        if (!word) return 1;
        if (word.length <= 3) return 1;
        word = word.replace(/(?:[^laeiouy]es|ed|[^laeiouy]e)$/, '');
        word = word.replace(/^y/, '');
        const matches = word.match(/[aeiouy]{1,2}/g);
        return matches ? Math.max(matches.length, 1) : 1;
    }

    function calculateReadingAge(text, minWords) {
        minWords = minWords || MIN_WORDS;
        if (!text || !text.trim()) return null;
        const words = text.trim().split(/\s+/).filter(w => w.length > 0);
        if (words.length < minWords) return null;
        const sentences = text.split(/[.!?]+/).filter(s => s.trim().length > 0);
        const sentenceCount = Math.max(sentences.length, 1);
        let totalSyllables = 0;
        words.forEach(w => { totalSyllables += countSyllables(w); });
        const gradeLevel = 0.39 * (words.length / sentenceCount) + 11.8 * (totalSyllables / words.length) - 15.59;
        return Math.max(Math.round(gradeLevel + 5), 5);
    }

    function badgeHtml(age) {
        if (age === null) return '<span class="text-gray-400">Reading age: not enough text</span>';
        let colour, dot;
        if (age <= 11) { colour = 'text-green-600'; dot = 'bg-green-500'; }
        else if (age <= HIGH) { colour = 'text-amber-600'; dot = 'bg-amber-500'; }
        else { colour = 'text-red-600'; dot = 'bg-red-500'; }
        return '<span class="' + colour + ' font-medium inline-flex items-center gap-1.5">'
            + '<span class="w-2 h-2 rounded-full ' + dot + ' inline-block"></span>Reading age: ' + age + '</span>';
    }

    function wire(input) {
        const badge = document.querySelector('[data-reading-age-badge-for="' + input.id + '"]');
        const warning = document.querySelector('[data-reading-age-warning-for="' + input.id + '"]');
        const form = input.form;
        if (!badge || !warning || !form) return;

        // What the copy read like when the page loaded. An empty baseline (a new
        // piece) means there is nothing to have made worse.
        const baseline = input.defaultValue;
        let bypassed = false;

        const paint = () => { badge.innerHTML = badgeHtml(calculateReadingAge(input.value)); };
        input.addEventListener('input', paint);
        paint();

        form.addEventListener('submit', function (e) {
            if (bypassed) return;

            const age = calculateReadingAge(input.value);
            const from = calculateReadingAge(baseline, MIN_WORDS_COMPARISON);
            const to = calculateReadingAge(input.value, MIN_WORDS_COMPARISON);
            const tooHigh = age !== null && age > HIGH;
            const raised = from !== null && to !== null && to > from;

            if (!tooHigh && !raised) return;

            e.preventDefault();
            // Stop the layout's data-confirm handler seeing a submit we blocked.
            e.stopPropagation();

            const high = warning.querySelector('[data-reading-age-high]');
            high.classList.toggle('hidden', !tooHigh);
            if (tooHigh) high.querySelector('[data-reading-age-value]').textContent = age;

            const increase = warning.querySelector('[data-reading-age-increase]');
            increase.classList.toggle('hidden', !raised);
            if (raised) {
                increase.querySelector('[data-reading-age-from]').textContent = from;
                increase.querySelector('[data-reading-age-to]').textContent = to;
            }

            warning.classList.remove('hidden');
            warning.scrollIntoView({ block: 'nearest' });
        });

        warning.querySelector('[data-reading-age-continue]').addEventListener('click', function () {
            bypassed = true;
            warning.classList.add('hidden');
            form.requestSubmit ? form.requestSubmit() : form.submit();
        });
    }

    document.addEventListener('DOMContentLoaded', function () {
        document.querySelectorAll('[data-reading-age-badge-for]').forEach(function (badge) {
            const input = document.getElementById(badge.dataset.readingAgeBadgeFor);
            if (input) wire(input);
        });
    });
})();
</script>
@endonce

<div class="mt-1 text-xs text-right" data-reading-age-badge-for="{{ $field }}">
    <span class="text-gray-400">Reading age: not enough text</span>
</div>

<div class="hidden mt-3 p-4 bg-amber-50 border border-amber-200 rounded-lg" data-reading-age-warning-for="{{ $field }}">
    <div class="flex gap-3">
        <svg class="w-5 h-5 text-amber-500 shrink-0 mt-0.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
        <div class="flex-1">
            <div class="hidden" data-reading-age-high>
                <p class="text-sm font-medium text-amber-800">
                    This copy has a reading age of <span data-reading-age-value></span>.
                </p>
                <p class="mt-1 text-xs text-amber-600">
                    The average reading age in the UK is 9&ndash;10. Consider simplifying the language so it can be
                    understood by a wider audience &mdash; the
                    <a href="https://readability.ncldata.dev/" target="_blank" rel="noopener noreferrer" class="underline font-medium text-amber-700 hover:text-amber-900">NHS Medical Document Readability Tool</a>
                    can help.
                </p>
            </div>
            <div class="hidden" data-reading-age-increase>
                <p class="text-sm font-medium text-amber-800">
                    Your edit raises the reading age from <span data-reading-age-from></span> to <span data-reading-age-to></span>.
                </p>
                <p class="mt-1 text-xs text-amber-600">
                    Rewording to sound more formal often raises the reading age without changing the meaning &mdash;
                    which makes the content harder for people to understand.
                </p>
            </div>
            <div class="mt-3 text-right">
                <button type="button" data-reading-age-continue
                    class="px-4 py-1.5 text-xs font-medium rounded-full border border-amber-400 text-amber-700 hover:bg-amber-100 transition-colors">
                    Save anyway &rarr;
                </button>
            </div>
        </div>
    </div>
</div>
