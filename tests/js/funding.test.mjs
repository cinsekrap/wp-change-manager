/**
 * The funding page's selection rules. Every row is selectable; the buttons carry
 * their own reasons, because "not sized" blocks asking for money and says
 * nothing about recording a decision made elsewhere.
 */
import { JSDOM } from 'jsdom';
import { readFileSync } from 'node:fs';

const results = [];
const check = (name, ok) => results.push([name, !!ok]);
const tick = () => new Promise((r) => setTimeout(r, 0));

// The page's own script, with Blade's server-side bits resolved.
const blade = readFileSync('resources/views/admin/funding.blade.php', 'utf8');
const script = blade.slice(blade.lastIndexOf('<script>') + 8, blade.lastIndexOf('</script>'))
    .replace(/\{\{[^}]*\}\}/g, '/route/');

function row(ref, { sized = true, asked = false, hours = 8 } = {}) {
    return `<tr><td><input type="checkbox" class="fund-row" value="${ref}"
        data-hours="${hours}" data-sized="${sized ? 1 : 0}" data-asked="${asked ? 1 : 0}" data-ref="${ref}"></td></tr>`;
}

async function build(rowsHtml) {
    const dom = new JSDOM(`<!doctype html><html><body>
        <input type="checkbox" id="selAll">
        <span id="selCount"></span><span id="selHours"></span>
        <table><tbody>${rowsHtml}</tbody></table>
        <form id="askForm"><select id="fundingApprover"></select>
            <button id="askButton" disabled></button>
            <span id="askRemit" class="hidden"></span>
            <span id="askBlocked" class="hidden"></span>
        </form>
        <button id="markFunded" disabled></button>
        <script>${script}<\/script>
    </body></html>`, { runScripts: 'dangerously' });

    await tick();
    const { window } = dom;
    return {
        window,
        ask: window.document.getElementById('askButton'),
        fund: window.document.getElementById('markFunded'),
        blocked: window.document.getElementById('askBlocked'),
        hours: window.document.getElementById('selHours'),
        pick(ref) {
            const box = window.document.querySelector(`[data-ref="${ref}"]`);
            box.checked = true;
            box.dispatchEvent(new window.Event('change', { bubbles: true }));
        },
    };
}

const shown = (el) => !el.classList.contains('hidden');

await (async () => {
    const p = await build(row('A') + row('B'));
    check('nothing selected: both actions off', p.ask.disabled && p.fund.disabled);
    p.pick('A');
    check('a sized row enables the ask', !p.ask.disabled);
    check('a sized row enables recording', !p.fund.disabled);
    check('no blocking message when it can go', !shown(p.blocked));
    p.pick('B');
    check('hours total across the selection', p.hours.textContent === '16');
})();

await (async () => {
    // Every row unsized, which is what the demo data looked like.
    const p = await build(row('A', { sized: false, hours: 0 }) + row('B', { sized: false, hours: 0 }));
    check('unsized rows are still selectable',
        p.window.document.querySelectorAll('.fund-row:disabled').length === 0);
    p.pick('A');
    check('asking is refused when unsized', p.ask.disabled);
    check('and it says which ones', shown(p.blocked) && p.blocked.textContent.includes('A'));
    check('recording a decision is still allowed', !p.fund.disabled);
})();

await (async () => {
    const p = await build(row('A') + row('B', { asked: true }));
    p.pick('B');
    check('asking twice is refused', p.ask.disabled);
    check('and it says why', p.blocked.textContent.includes('Already waiting'));
})();

await (async () => {
    const p = await build(row('A') + row('B', { sized: false, hours: 0 }));
    p.pick('A');
    check('a good selection is askable', !p.ask.disabled);
    p.pick('B');
    check('adding an unsized row blocks it again', p.ask.disabled);
})();

let failed = 0;
for (const [name, ok] of results) {
    if (!ok) failed++;
    console.log(`  ${ok ? 'PASS' : 'FAIL'}  ${name}`);
}
console.log(`\n${results.length - failed}/${results.length} checks passed`);
process.exit(failed ? 1 : 0);
