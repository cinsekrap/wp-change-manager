/**
 * Reading-age gate on the admin copy fields.
 *
 * The badge and the save gate are pure client behaviour: PHPUnit can only prove
 * the markup is on the page, not that typing complex copy stops a save. The two
 * fields live behind admin auth, so rather than fetching a page this renders the
 * real partial — its script verbatim — into jsdom.
 *
 *   npm run test:js
 */
import { JSDOM, VirtualConsole } from 'jsdom';
import { readFileSync } from 'node:fs';

const results = [];
const check = (name, ok) => results.push([name, !!ok]);
const tick = () => new Promise((r) => setTimeout(r, 0));
const shown = (el) => !el.classList.contains('hidden');

const SIMPLE = 'If you are coming to see us, you do not need to bring much. '
    + 'Bring a list of any medicines you take. Bring your glasses if you wear them. '
    + 'A friend or family member can come in with you if you would like that.';

const COMPLEX = 'Prior to attendance at the aforementioned appointment, service users are '
    + 'requested to ensure the provision of a comprehensive pharmacological inventory. '
    + 'Additionally, the utilisation of corrective optical apparatus should be facilitated '
    + 'where such apparatus is habitually employed by the individual concerned.';

/** The partial as the browser would receive it, with Blade's directives resolved. */
function renderPartial(field) {
    return readFileSync('resources/views/partials/reading-age.blade.php', 'utf8')
        .replace(/\{\{--[\s\S]*?--\}\}/g, '')
        .replace(/@once|@endonce/g, '')
        .replaceAll('{{ $field }}', field);
}

/** A form holding one copy field, with `baseline` as its server-rendered value. */
async function build(baseline = '') {
    const virtualConsole = new VirtualConsole();
    const errors = [];
    virtualConsole.on('jsdomError', (e) => {
        const message = e.detail?.message || e.message;
        if (message.startsWith('Not implemented:')) return;
        errors.push(message);
    });

    const dom = new JSDOM(`<!doctype html><html><body>
        <form id="f" method="POST" action="/save">
            <textarea name="draft_content" id="copy">${baseline}</textarea>
            ${renderPartial('copy')}
            <button type="submit">Save</button>
        </form>
    </body></html>`, { runScripts: 'dangerously', virtualConsole });

    const { window } = dom;
    const form = window.document.getElementById('f');
    const submits = [];

    // jsdom does not submit forms. Record the attempts instead, and make
    // requestSubmit re-fire submit the way a browser does.
    const fire = () => {
        const ev = new window.Event('submit', { bubbles: true, cancelable: true });
        form.dispatchEvent(ev);
        submits.push(!ev.defaultPrevented);
        return !ev.defaultPrevented;
    };
    form.requestSubmit = fire;

    // The partial wires itself up on DOMContentLoaded, a tick after the document
    // is built.
    await tick();

    return {
        window,
        errors,
        submits,
        input: window.document.getElementById('copy'),
        badge: window.document.querySelector('[data-reading-age-badge-for="copy"]'),
        warning: window.document.querySelector('[data-reading-age-warning-for="copy"]'),
        type(text) {
            this.input.value = text;
            this.input.dispatchEvent(new window.Event('input', { bubbles: true }));
        },
        submit: fire,
        reason(which) {
            return shown(this.warning.querySelector(`[data-reading-age-${which}]`));
        },
    };
}

// --- The badge -------------------------------------------------------------

await (async () => {
    const p = await build();
    check('empty field says there is not enough text', /not enough text/.test(p.badge.textContent));

    p.type(SIMPLE);
    check('simple copy scores an age', /Reading age: \d+/.test(p.badge.textContent));
    check('simple copy is green', p.badge.innerHTML.includes('text-green-600'));

    p.type(COMPLEX);
    check('complex copy is red', p.badge.innerHTML.includes('text-red-600'));

    p.type('Only a handful of words here.');
    check('too little text scores nothing', /not enough text/.test(p.badge.textContent));

    check('no script errors', p.errors.length === 0);
})();

// --- The save gate ---------------------------------------------------------

await (async () => {
    const p = await build();
    p.type(SIMPLE);
    check('simple copy saves without a warning', p.submit() === true);
    check('no warning panel for simple copy', !shown(p.warning));
})();

await (async () => {
    const p = await build();
    p.type(COMPLEX);
    check('complex copy is stopped', p.submit() === false);
    check('the warning is shown', shown(p.warning));
    check('the high-reading-age reason is given', p.reason('high'));
    check('the age is named in the warning',
        /\d+/.test(p.warning.querySelector('[data-reading-age-value]').textContent));
    check('the NHS readability tool is offered',
        p.warning.innerHTML.includes('readability.ncldata.dev'));

    // Guidance, not a block: the designer can overrule it.
    p.warning.querySelector('[data-reading-age-continue]').click();
    check('save anyway goes through', p.submits[p.submits.length - 1] === true);
    check('the warning closes on save anyway', !shown(p.warning));
})();

// --- Making existing copy harder ------------------------------------------

await (async () => {
    // Simple copy already saved, rewritten into something harder.
    const p = await build(SIMPLE);
    p.type(COMPLEX);
    check('a raised reading age is flagged', p.submit() === false);
    check('the increase reason is given', p.reason('increase'));

    const from = Number(p.warning.querySelector('[data-reading-age-from]').textContent);
    const to = Number(p.warning.querySelector('[data-reading-age-to]').textContent);
    check('the warning names both readings', to > from);
})();

await (async () => {
    // The other direction is the whole point of the tool working.
    const p = await build(COMPLEX);
    p.type(SIMPLE);
    check('simplifying existing copy is never blocked', p.submit() === true);
})();

await (async () => {
    // A new piece has no baseline, so there is nothing it can have made worse.
    const p = await build();
    p.type(SIMPLE);
    p.submit();
    check('a new field is not compared against an empty baseline', !p.reason('increase'));
})();

// --- Report ----------------------------------------------------------------

let failed = 0;
for (const [name, ok] of results) {
    if (!ok) failed++;
    console.log(`  ${ok ? 'PASS' : 'FAIL'}  ${name}`);
}
console.log(`\n${results.length - failed}/${results.length} checks passed`);
process.exit(failed ? 1 : 0);
