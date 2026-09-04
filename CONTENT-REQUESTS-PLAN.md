# Content requests — build plan

Splits the public wizard into two lanes: **change requests** (what exists today, unchanged) and
**content requests** (new content, briefed by outcome, written by a content designer, clinically
approved, published across one or more sites).

Design: https://claude.ai/code/artifact/3c8ff3ad-85f7-4f3d-9f19-09897b126e64

Work through the phases in order. Each is independently shippable and leaves `main` releasable.

## Progress

- [x] Phase 1 — Schema and model foundations
- [x] Phase 2 — The wizard fork
- [x] Phase 3 — Lifecycle and clinical approval
- [x] Phase 4 — Public queue and watchers
- [ ] Phase 5 — Reporting

Tick a phase in the same PR that completes it. This list is the record of what is done — do not infer
progress from the state of the code.

---

## Decisions already made — do not reopen

| Question | Decision |
|---|---|
| Where the lanes fork | Step 2 of the wizard ("What are you working on?"). Site is still chosen at step 1. |
| One record or two | **One.** `request_type = 'content'`, one reference from suggestion to published URL. |
| Content type question | Asks the **job the content does**, not the CMS type. 8 options → 6 page-type tags. |
| Approval of shared content | **Approved once.** One clinical sign-off covers every site, using the main home site's approvers. |
| Per-site wording variants | A variant is different copy, so it needs its own approval. Falls out of version-binding. |
| Public queue | Public, no sign-in. Shows **site titles, never URLs**. |
| Turnaround reporting | Content is slow by design and that is reported truthfully. Do **not** SLA-pause or exclude it — split `avg_days` by `request_type` instead. |
| The funding debate | Happens outside this tool. The tool records the status only. |

Out of scope: the funding process itself; the "fork first" and "outcome first" wizard alternatives
(parked on the design canvas, page 4).

---

## House rules — read before touching anything

- **Never push to `main`.** Branch protection is enforced; every change lands via PR.
- **`vendor/` and `public/build` are committed.** If you change CSS or JS, rebuild assets and commit
  the result. Before using a Tailwind class, grep the built CSS — if it isn't in the bundle it won't
  render, and an unnecessary rebuild causes manifest conflicts.
- **New mailables need three things**, or they are incomplete: a preview, a template-editor entry in
  `config/email-templates.php`, and synchronous send via `EmailLog::dispatch()`.
- **Never cache objects** — scalars only. Cache-related tests must run on the database cache store;
  the array store never serializes and hides the bug.
- **Tests** run on sqlite with the array cache, which differs from production. Keep SQL DB-agnostic
  (no MySQL-only date functions). Use `UploadedFile::fake()`.
- **Releases** are automated: bump `config/version.php` and add the matching `CHANGELOG.md` section
  in the same PR. Merging that PR tags and publishes.

---

## Phase 1 — Schema and model foundations

No user-visible change. Ships on its own.

**Migrations** (follow the `YYYY_MM_DD_NNNNNN_*` naming already in `database/migrations/`):

1. `add_content_fields_to_change_requests_table`
   - `content_type` — `string(40)` nullable, **indexed** (the page-type tag; needed for reporting)
   - `content_brief` — `json` nullable (achieve / audience / know-or-do / measure / already-exists)
   - `public_title` — `string(255)` nullable (written by the content designer, safe for the public queue)
2. `create_change_request_sites_table` — the multi-site pivot
   - `change_request_id`, `site_id`, `published_url` nullable, `published_title` nullable, timestamps
   - unique on (`change_request_id`, `site_id`)
   - `change_requests.site_id` stays as the **main home** so every existing query keeps working
3. `add_approval_binding_to_change_request_approvers_table`
   - `approved_content_hash` — `string(64)` nullable
   - `approved_content_snapshot` — `text` nullable

**Model changes** (`app/Models/ChangeRequest.php`):

- Add the new statuses to `STATUSES`:
  `suggested`, `scoped`, `awaiting_funding`, `in_progress`, `awaiting_approval`
- Add `CONTENT_ONLY_STATUSES` alongside the existing `ACCESS_ONLY_STATUSES` / `CHANGE_ONLY_STATUSES`
- Add `isContentRequest()` mirroring `isAccessRequest()` (line ~110)
- Add the `sites()` belongsToMany relationship; add new columns to `$fillable` and `$casts`
- **Do not** add the new statuses to `SLA_PAUSED_STATUSES` — see the decisions table

**Acceptance:** migrations run clean up and down; existing tests pass untouched; a request with
`request_type = 'content'` can be created and read back with its brief and sites.

---

## Phase 2 — The wizard fork

`resources/views/public/partials/wizard/`

- **`step-2-page.blade.php`** — replace the CPT-tabs-plus-checkbox layout with two equal panels
  ("A page that already exists" / "Something new"). Choosing the first reveals today's picker
  **unchanged**; choosing the second shows the route preview. **Remove the `isNewPage` checkbox** and
  its handlers — it is the thing being replaced.
- **New `step-3-brief.blade.php`** — the content brief. Free-text achieve, audience chips,
  know-or-do, optional measure, and the required "does something like this already exist?".
- **New `step-4-where-it-lives.blade.php`** — the content-type question (8 options, single-select,
  each tagged with its page type), then main home site (prefilled from step 1) and additional sites.
- **`wizard-scripts.blade.php`** — branch the step sequence on the step-2 choice. Both lanes stay
  **six steps**, so the progress bar needs no special-casing. Update the `stepTitles` array per lane.
- **Blocked and self-service CPTs** keep working exactly as now — those messages and the access form
  live inside the "existing page" branch and must not move.

`app/Http/Controllers/PublicSite/SubmissionController.php` — extend validation to accept
`request_type=content` with the brief fields and site list. Keep `change` and `access` behaviour identical.

**Tests:** new `ContentRequestSubmissionTest`; extend `PublicWizardTest` to cover both branches and
assert the change lane is unaffected.

**Acceptance:** a content request can be submitted end to end and lands as `suggested` with its brief,
content type and sites. Submitting a change request produces a byte-identical record to before.

---

## Phase 3 — Lifecycle and clinical approval

- Wire the new statuses into the admin status dropdown and `ChangeRequestStatusLog`, restricted to
  content requests via `CONTENT_ONLY_STATUSES`.
- **Gate 2 — approval binds to a version of the copy.** On approval, hash and snapshot the approved
  text onto `change_request_approvers`. If the copy changes afterwards, **void the approval** and
  return the request to `awaiting_approval`. An approval that does not name the text it approved is
  not worth recording.
- One sign-off covers every site, taken from the main home site's `default_approvers`.
- **Publish step:** whoever implements enters one URL and page title per site into the pivot.
- **New mailables** (each needs preview + template entry + `EmailLog::dispatch`):
  - `ContentSuggestionReceived` — to the suggester on submit
  - `ContentAwaitingFunding` — to suggester and watchers
  - `ContentApprovalRequested` — to the clinical approver
  - `ContentPublished` — to suggester and watchers, listing **every** published URL

**Acceptance:** a request can walk the full lifecycle; editing approved copy demonstrably voids the
approval; the published email lists one URL per site.

---

## Phase 4 — Public queue and watchers

There is no sign-in, so **the queue is a published page**. The field allowlist is not a detail:

- **Shown:** reference, `public_title`, content type, status, dates, and the **site titles** it went live on.
- **Never shown:** suggester identity, the full brief, hours estimate, draft copy, decline reasons,
  the clinical approver's name, and the URLs themselves.

Follow the existing public tracking page for the pattern: read-only, reference-led, no personal data.
`public_title` is set by the content designer at `scoped` — a requester's own words were not written
for publication.

**Wire the queue into the brief.** The "does something like this already exist?" field should search
the queue live. This is the queue's business case: it catches a duplicate at the moment someone is
about to ask for one.

**Watchers** (build after the queue — the button has nowhere to live until it exists):
`change_request_watchers` (`change_request_id`, `email`, `token`, `confirmed_at`). Public sign-up
means an email address is all it takes, so **require a confirmation click before sending anything**.
Every notification carries an unsubscribe link.

**Acceptance:** the queue renders for a signed-out visitor and leaks none of the "never shown" fields
— assert this in a test, it is the one that matters.

---

## Phase 5 — Reporting

- Split `avg_days` in `app/Http/Controllers/Admin/ReportsController.php` (~line 46) by `request_type`.
  A blended figure across a fast change lane and a slow content lane describes neither.
- Feed the content-only average into the waiting-time line on wizard step 2, replacing the
  placeholder. A stale hand-maintained number there is worse than no number.

---

## Suggested release grouping

| Release | Contents |
|---|---|
| Minor | Phase 1 |
| Minor | Phases 2–3 — the lane is usable end to end |
| Minor | Phase 4 |
| Patch | Phase 5 |

Phases 2 and 3 should land together: a wizard that submits content requests into a lifecycle that
cannot process them is worse than not shipping either.
