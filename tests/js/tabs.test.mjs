/**
 * The request page's tabs.
 *
 * PHPUnit can prove the panels are on the page and which one starts open; it
 * cannot click. The case worth guarding is the deep link: "Unlock for editing"
 * sends you to ?unlock_draft=1#draft, and the draft form lives inside a panel —
 * so a hash naming an element has to open the panel that holds it.
 *
 *   npm run test:js
 */
import { JSDOM } from 'jsdom';
import { readFileSync } from 'node:fs';

const results = [];
const check = (name, ok) => results.push([name, !!ok]);
const tick = () => new Promise((r) => setTimeout(r, 0));

const blade = readFileSync('resources/views/components/admin/tabs.blade.php', 'utf8');
const script = blade.slice(blade.lastIndexOf('<script>') + 8, blade.lastIndexOf('</script>'));

async function build(hash = '') {
    const dom = new JSDOM(`<!doctype html><html><body>
        <div data-tabs>
          <nav>
            <button data-tab="work" class="border-hcrg-burgundy text-hcrg-burgundy">The work</button>
            <button data-tab="brief" class="border-transparent text-hcrg-grey-400">Brief</button>
            <button data-tab="history" class="border-transparent text-hcrg-grey-400">History</button>
          </nav>
        </div>
        <div data-tab-panel="work"><form id="draft">draft copy</form></div>
        <div data-tab-panel="brief" hidden>the brief</div>
        <div data-tab-panel="history" hidden><div id="activity">activity</div></div>
        <script>${script}<\/script>
    </body></html>`, { runScripts: 'dangerously', url: 'https://acme-change.test/admin/requests/1' + hash });

    await tick();
    const { document } = dom.window;
    return {
        window: dom.window,
        open: () => Array.from(document.querySelectorAll('[data-tab-panel]'))
            .filter((p) => !p.hidden).map((p) => p.dataset.tabPanel),
        active: () => Array.from(document.querySelectorAll('[data-tab]'))
            .filter((b) => b.classList.contains('text-hcrg-burgundy')).map((b) => b.dataset.tab),
        click: (key) => document.querySelector(`[data-tab="${key}"]`).click(),
    };
}

await (async () => {
    const p = await build();
    check('opens on the first tab', p.open().join() === 'work');
    check('and marks it active', p.active().join() === 'work');
})();

await (async () => {
    const p = await build();
    p.click('brief');
    check('clicking shows that panel only', p.open().join() === 'brief');
    check('and moves the active mark', p.active().join() === 'brief');
    p.click('work');
    check('and back again', p.open().join() === 'work');
})();

await (async () => {
    const p = await build('#history');
    check('a hash naming a tab opens it', p.open().join() === 'history');
})();

await (async () => {
    // The case that matters: "Unlock for editing" links to #draft, which is a
    // form inside the work panel rather than a tab.
    const p = await build('#draft');
    check('a hash naming an element opens its panel', p.open().join() === 'work');
})();

await (async () => {
    const p = await build('#activity');
    check('an element deep in another panel opens that one', p.open().join() === 'history');
})();

await (async () => {
    const p = await build('#nonsense');
    check('an unknown hash falls back to the first tab', p.open().join() === 'work');
})();

await (async () => {
    const p = await build();
    p.click('history');
    check('the open tab is recorded in the address', p.window.location.hash === '#history');
})();

let failed = 0;
for (const [name, ok] of results) {
    if (!ok) failed++;
    console.log(`  ${ok ? 'PASS' : 'FAIL'}  ${name}`);
}
console.log(`\n${results.length - failed}/${results.length} checks passed`);
process.exit(failed ? 1 : 0);
