/**
 * Wizard behaviour tests.
 *
 * The wizard is the app's largest piece of JavaScript and PHPUnit cannot reach it:
 * the feature tests post JSON straight to /submit, so they prove the server is
 * right and say nothing about the client. A content request that could not be
 * submitted at all still passed the whole PHP suite.
 *
 * Needs the app running. Point at it with APP_URL, e.g.
 *   APP_URL=http://acme-change.test npm run test:js
 */
import { JSDOM, VirtualConsole } from 'jsdom';

const BASE = (process.env.APP_URL || 'http://acme-change.test').replace(/\/$/, '');

const results = [];
const check = (name, ok) => results.push([name, !!ok]);
const wait = (ms) => new Promise((r) => setTimeout(r, ms));

async function loadWizard() {
    let html;
    try {
        const res = await fetch(BASE + '/');
        if (!res.ok) throw new Error(`HTTP ${res.status}`);
        html = await res.text();
    } catch (e) {
        console.error(`\nCould not load ${BASE}/ — is the app running?\n  ${e.message}\n`);
        process.exit(2);
    }

    const virtualConsole = new VirtualConsole();
    const errors = [];
    virtualConsole.on('jsdomError', (e) => {
        const message = e.detail?.message || e.message;
        // jsdom has no layout engine, so scrollTo is unimplemented. That is the
        // test environment, not the app.
        if (message.startsWith('Not implemented:')) return;
        errors.push(message);
    });

    const dom = new JSDOM(html, { runScripts: 'dangerously', url: BASE + '/', virtualConsole });
    const { window } = dom;

    // Stub the endpoints the wizard calls, honouring their real shapes:
    // cpts is an array of slug strings, and loadSitePages holds a 3s minimum delay.
    let submitted = null;
    window.fetch = async (url, opts) => {
        const u = String(url);
        if (u.includes('/api/sitemap/status/')) return { ok: true, json: async () => ({ has_data: true, needs_refresh: false }) };
        if (u.includes('/api/sitemap/refresh/')) return { ok: true, json: async () => ({ success: true }) };
        if (u.includes('/api/pages/')) {
            return { ok: true, json: async () => ({
                pages: [{ id: 1, url: '/contact-us', title: 'Contact us', cpt_slug: 'pages' }],
                cpts: ['pages'],
            }) };
        }
        if (u.includes('/api/suggestions/search')) return { ok: true, json: async () => ({ results: [] }) };
        if (u.includes('/submit')) {
            submitted = JSON.parse(opts.body);
            return { ok: true, json: async () => ({ reference: 'CR-TEST', redirect: '/' }) };
        }
        return { ok: true, json: async () => ({}) };
    };

    return { window, doc: window.document, errors, getSubmitted: () => submitted };
}

const click = (window, el) => el.dispatchEvent(new window.MouseEvent('click', { bubbles: true }));
const fire = (window, el, type) => el.dispatchEvent(new window.Event(type, { bubbles: true }));
const visible = (doc, panel) => {
    const el = doc.querySelector(`[data-panel="${panel}"]`);
    return !!el && !el.classList.contains('hidden');
};

async function chooseSiteAndReachStepTwo({ window, doc }) {
    const $ = (id) => doc.getElementById(id);
    await wait(60);
    const opt = doc.querySelector('.site-option');
    $('siteSelect').value = opt.dataset.value;
    $('siteSearch').value = opt.dataset.label;
    click(window, opt);
    await wait(3400); // loadSitePages enforces a 3s minimum
    click(window, $('nextBtn'));
    await wait(60);
}

async function passGovernance({ window, doc }, group) {
    const $ = (id) => doc.getElementById(id);
    click(window, $('nextBtn'));
    await wait(40);
    doc.querySelectorAll(`#${group} .gov-check`).forEach((c) => { c.checked = true; fire(window, c, 'change'); });
    await wait(30);
    click(window, $('govContinue'));
    await wait(60);
}

async function contentLane() {
    const ctx = await loadWizard();
    const { window, doc } = ctx;
    const $ = (id) => doc.getElementById(id);

    await chooseSiteAndReachStepTwo(ctx);
    check('step 2 shows the lane choice', visible(doc, 'page'));
    check('the removed "new page" checkbox is gone', $('isNewPage') === null);

    click(window, doc.querySelector('.lane-option[data-lane="content"]'));
    await wait(40);
    check('content lane hides the page picker', $('lanePickerWrap').classList.contains('hidden'));

    await passGovernance(ctx, 'govNewChecks');
    check('content step 3 is the brief', visible(doc, 'brief'));

    $('briefAchieve').value = 'Stop people ringing about the first appointment.';
    fire(window, $('briefAchieve'), 'input');
    $('briefKnowOrDo').value = 'Know what to bring.';
    fire(window, $('briefKnowOrDo'), 'input');
    const aud = doc.querySelector('.brief-audience');
    aud.checked = true; fire(window, aud, 'change');
    const exists = doc.querySelector('input[name="brief_exists"][value="yes"]');
    exists.checked = true; fire(window, exists, 'change');
    await wait(30);
    check('saying "yes" reveals the where-is-it field', !$('briefExistsDetailWrap').classList.contains('hidden'));
    check('saying "yes" repoints the upload prompt', /upload it here/i.test($('briefUploadHelp').textContent));
    $('briefExistsDetail').value = 'An out-of-date PDF on the intranet.';
    fire(window, $('briefExistsDetail'), 'input');
    await wait(30);

    click(window, $('nextBtn'));
    await wait(60);
    check('content step 4 is what-it-is-and-where-it-lives', visible(doc, 'where'));
    check('next is blocked until a content type is chosen', $('nextBtn').disabled);

    const ct = doc.querySelector('input[name="content_type"]');
    ct.checked = true; fire(window, ct, 'change');
    await wait(30);
    click(window, $('nextBtn'));
    await wait(60);
    check('content step 5 shows details and the check questions together',
        visible(doc, 'details') && visible(doc, 'questions'));

    $('requesterName').value = 'Jane Doe'; fire(window, $('requesterName'), 'input');
    $('requesterEmail').value = 'jane@example.com'; fire(window, $('requesterEmail'), 'input');
    const noDeadline = doc.querySelector('input[name="has_deadline"][value="no"]');
    noDeadline.checked = true; fire(window, noDeadline, 'change');
    doc.querySelectorAll('.question-group[data-required="1"]').forEach((g) => {
        const r = g.querySelector('input[type="radio"]');
        if (r) { r.checked = true; fire(window, r, 'change'); }
    });
    await wait(40);
    click(window, $('nextBtn'));
    await wait(80);

    const review = $('reviewContent').textContent || '';
    check('reached review', visible(doc, 'review'));
    // Regression: a blank line item from init rendered an empty "Changes (1)" card.
    check('review has no empty Changes card', !/Changes \(/.test(review));
    check('review summarises the brief', review.includes('The brief'));

    click(window, $('submitBtn'));
    await wait(200);
    const payload = ctx.getSubmitted();
    // Regression: collecting that blank line item threw and killed submit.
    if (ctx.errors.length) console.log('  errors:', JSON.stringify(ctx.errors.slice(0, 3)));
    check('submit fires without throwing', ctx.errors.length === 0);
    check('submit sends a payload', payload !== null);
    check('payload has no line items', Array.isArray(payload?.items) && payload.items.length === 0);
    check('payload is a content request', payload?.request_type === 'content');
    check('payload carries the brief', !!payload?.content_brief?.achieve);
}

async function changeLane() {
    const ctx = await loadWizard();
    const { window, doc } = ctx;
    const $ = (id) => doc.getElementById(id);

    await chooseSiteAndReachStepTwo(ctx);
    click(window, doc.querySelector('.lane-option[data-lane="change"]'));
    await wait(40);
    check('change lane reveals the picker', !$('lanePickerWrap').classList.contains('hidden'));
    check('change lane hides the content preview', $('laneContentPreview').classList.contains('hidden'));
    check('cpt tabs render', doc.querySelectorAll('.cpt-tab').length > 0);

    const page = doc.querySelector('.page-option');
    check('pages render from site data', !!page);
    click(window, page);
    await wait(40);
    check('next enables once a page is chosen', !$('nextBtn').disabled);

    await passGovernance(ctx, 'govExistingChecks');
    check('change step 3 is the changes panel', visible(doc, 'changes'));
    check('change step 3 is not the brief', !visible(doc, 'brief'));
    check('a line item is ready to fill in', doc.querySelectorAll('.line-item').length > 0);
    check('change lane raised no errors', ctx.errors.length === 0);
}

console.log(`\nWizard tests against ${BASE}\n`);
await contentLane();
await changeLane();

let passed = 0;
for (const [name, ok] of results) {
    console.log(`  ${ok ? 'PASS' : 'FAIL'}  ${name}`);
    if (ok) passed++;
}
console.log(`\n${passed}/${results.length} checks passed\n`);
process.exit(passed === results.length ? 0 : 1);
