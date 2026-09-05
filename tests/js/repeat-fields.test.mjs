/**
 * Repeating field groups on the content draft — the Q&A case.
 *
 * The bug worth guarding: numbering a new row by the row count collides after a
 * middle row is removed, so two rows share an index and PHP keeps only one. It
 * saves without complaint and an answer disappears.
 *
 *   npm run test:js
 */
import { JSDOM } from 'jsdom';
import { readFileSync } from 'node:fs';

const results = [];
const check = (name, ok) => results.push([name, !!ok]);
const tick = () => new Promise((r) => setTimeout(r, 0));

const partial = readFileSync('resources/views/admin/requests/partials/_draft-fields.blade.php', 'utf8');
const script = partial.slice(partial.lastIndexOf('<script>') + 8, partial.lastIndexOf('</script>'));

function row(i, q = '', a = '') {
    return `<div data-repeat-row>
        <input name="fields[Questions and Answers][${i}][Question]" value="${q}">
        <textarea name="fields[Questions and Answers][${i}][Answer]">${a}</textarea>
        <button type="button" data-repeat-remove>Remove</button>
    </div>`;
}

async function build(rows = 1) {
    const html = Array.from({ length: rows }, (_, i) => row(i, 'Q' + i, 'A' + i)).join('');
    const dom = new JSDOM(`<!doctype html><html><body>
        <form>
          <div data-repeat-list="Questions and Answers">${html}</div>
          <button type="button" data-repeat-add="Questions and Answers">Add another</button>
        </form>
        <script>${script}<\/script>
    </body></html>`, { runScripts: 'dangerously', url: 'https://acme-change.test/' });

    await tick();
    const { document } = dom.window;
    return {
        document,
        add: () => document.querySelector('[data-repeat-add]').click(),
        removeAt: (i) => document.querySelectorAll('[data-repeat-remove]')[i].click(),
        rows: () => document.querySelectorAll('[data-repeat-row]').length,
        // What the browser would actually send.
        sent: () => {
            const data = new dom.window.FormData(document.querySelector('form'));
            return Array.from(data.entries()).map(([k, v]) => `${k}=${v}`);
        },
        indexes: () => Array.from(document.querySelectorAll('input[name]'))
            .map((i) => i.name.match(/\[(\d+)\]/)[1]),
    };
}

await (async () => {
    const p = await build(1);
    check('starts with one row', p.rows() === 1);
    p.add();
    check('adding gives a second', p.rows() === 2);
    check('and it is empty', p.sent().includes('fields[Questions and Answers][1][Question]='));
    check('without disturbing the first', p.sent().includes('fields[Questions and Answers][0][Question]=Q0'));
})();

await (async () => {
    const p = await build(3);
    p.removeAt(1);
    check('removing takes that row out', p.rows() === 2);
    check('and closes the gap in the numbering', p.indexes().join() === '0,1');
    check('keeping the rows either side', p.sent().join(' ').includes('Q0') && p.sent().join(' ').includes('Q2'));
})();

await (async () => {
    // The collision: remove a middle row, then add one.
    const p = await build(3);
    p.removeAt(1);
    p.add();
    check('adding after a removal does not reuse an index',
        new Set(p.indexes()).size === p.indexes().length);
    check('and every row is still sent', p.rows() === 3 && p.indexes().join() === '0,1,2');
})();

await (async () => {
    const p = await build(1);
    p.removeAt(0);
    check('removing the only row leaves it in place', p.rows() === 1);
    check('but empties it', p.sent().includes('fields[Questions and Answers][0][Question]='));
})();

let failed = 0;
for (const [name, ok] of results) {
    if (!ok) failed++;
    console.log(`  ${ok ? 'PASS' : 'FAIL'}  ${name}`);
}
console.log(`\n${results.length - failed}/${results.length} checks passed`);
process.exit(failed ? 1 : 0);
