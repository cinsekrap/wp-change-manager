/**
 * Choosing between a note and a question.
 *
 * The seam: the question button asks for confirmation, and the layout's
 * interceptor re-submits with form.requestSubmit() — which carries no submitter.
 * If the choice lived on the button rather than in the form, confirming a
 * question would silently send a note instead, and nothing would say so.
 *
 *   npm run test:js
 */
import { JSDOM } from 'jsdom';
import { readFileSync } from 'node:fs';

const results = [];
const check = (name, ok) => results.push([name, !!ok]);
const tick = () => new Promise((r) => setTimeout(r, 0));

// The real confirm interceptor from the admin layout.
const layout = readFileSync('resources/views/layouts/admin.blade.php', 'utf8');
const start = layout.indexOf("document.addEventListener('submit'");
// The same script block carries the what's-new code, which is wrapped in Blade.
// The interceptor sits above it.
const blade = layout.indexOf('@if($whatsNewNotes', start);
const interceptor = layout.slice(start, blade === -1 ? layout.indexOf('</script>', start) : blade);

// The real form, with Blade resolved the way the server would.
const partial = readFileSync('resources/views/admin/requests/partials/_add-note.blade.php', 'utf8');
const form = partial
    .slice(partial.indexOf('<form'), partial.indexOf('</form>') + 7)
    .replace(/\{\{--[\s\S]*?--\}\}/g, '')
    .replace(/@csrf/g, '')
    .replace(/@if\([^)]*\)|@endif|@error\([^)]*\)|@enderror/g, '')
    .replace(/\{\{ addslashes\(\$changeRequest->requester_name\) \}\}/g, 'Priya Sharma')
    .replace(/\{\{[^}]*\}\}/g, 'Priya Sharma');

async function build() {
    const dom = new JSDOM(`<!doctype html><html><body>${form}<script>${interceptor}<\/script></body></html>`,
        { runScripts: 'dangerously', url: 'https://acme-change.test/admin/requests/1' });
    await tick();

    const { document } = dom.window;
    const el = document.querySelector('form');
    const submitted = [];

    // Recorded on the document, and registered after the interceptor, so this
    // runs once the interceptor has had its chance to hold the submit back.
    //
    // Read out of a serialised FormData rather than off the element, so a field
    // that loses its name attribute is caught rather than passed over.
    document.addEventListener('submit', (e) => {
        if (e.defaultPrevented) return;
        e.preventDefault();
        submitted.push(new dom.window.FormData(el).get('kind'));
    });

    return {
        document,
        submitted,
        buttons: Array.from(el.querySelectorAll('button[type="submit"]')),
        confirmVisible: () => !!el.querySelector('.inline-confirm'),
        confirm: () => Array.from(el.querySelectorAll('.inline-confirm button'))
            .find((b) => b.textContent === 'Confirm').click(),
        cancel: () => Array.from(el.querySelectorAll('.inline-confirm button'))
            .find((b) => b.textContent === 'Cancel').click(),
    };
}

await (async () => {
    const p = await build();
    check('two buttons, not a radio group', p.buttons.length === 2);
    check('no radio inputs remain', p.document.querySelectorAll('input[type="radio"]').length === 0);
    check('the buttons name both actions',
        p.buttons[0].textContent.trim() === 'Add note' &&
        p.buttons[1].textContent.includes('Send as a question to Priya Sharma'));
})();

await (async () => {
    const p = await build();
    p.buttons[0].click();
    check('a note goes straight through', p.submitted.join() === 'note');
    check('and asks for no confirmation', !p.confirmVisible());
})();

await (async () => {
    const p = await build();
    p.buttons[1].click();
    check('a question is held for confirmation', p.confirmVisible());
    check('and nothing is sent yet', p.submitted.length === 0);

    p.confirm();
    // The whole point: the re-submit carries no submitter, so the choice has to
    // be in the form itself.
    check('confirming still sends a question, not a note', p.submitted.join() === 'question');
})();

await (async () => {
    const p = await build();
    p.buttons[1].click();
    p.cancel();
    check('cancelling sends nothing', p.submitted.length === 0);
})();

await (async () => {
    const p = await build();
    p.buttons[1].click();
    p.cancel();
    p.buttons[0].click();
    check('a note after a cancelled question is still a note', p.submitted.join() === 'note');
    check('and is not held for confirmation', !p.confirmVisible());
})();

let failed = 0;
for (const [name, ok] of results) {
    if (!ok) failed++;
    console.log(`  ${ok ? 'PASS' : 'FAIL'}  ${name}`);
}
console.log(`\n${results.length - failed}/${results.length} checks passed`);
process.exit(failed ? 1 : 0);
