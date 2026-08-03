# Changelog

All notable changes to ACME Change.

This file is the source for GitHub release notes: when `config/version.php` is bumped on
`main`, the release workflow publishes the topmost section below as the release body and
appends the zipball SHA-256 the in-app updater verifies. Add the new section in the same
pull request as the version bump.

## 1.11.0

### Improvements
- More reliable in-app updates — new releases are now packaged, checksummed and published automatically, so each update is verified end to end before it goes live.
- Various dependency and maintenance updates.

## 1.10.0

### Bug Fixes
- Email log: removed the Type column so the View action stays visible — the table no longer overflows off-screen.

### Improvements
- Various security and maintenance updates.

## 1.9.0

### Features

- **Put requests on hold** — a new "On Hold" status with a required reason. The requester is automatically emailed the reason, and the request's turnaround targets (SLA) and any requested deadline are paused while it's on hold. The clock picks up exactly where it left off when work resumes.
- **Request clarification from requesters** — ask the requester a question directly from any active request. They receive an email with a secure link where they can reply with a comment or update the wording of their original request — no login needed. While you wait, the request sits in a new "Awaiting User" status with the SLA paused; as soon as they respond, it returns to its previous status and the assignee (or alert address) is notified.

### Improvements

- Both new emails are fully customisable in the email template editor, with live previews.
- The public tracking page now explains the on-hold and awaiting-response states, shows the hold reason or question asked, and offers a "Respond to This Request" button when the team is waiting on you.
- Requests that are on hold or awaiting a requester response are excluded from automated chase reminders.

## 1.8.3


### Features
- **Reports (preview)** — a new Reports tab with management reporting: request volumes, average completion times, SLA performance, breakdowns by site and content type, approval response times and access-request training turnaround, all filterable by date range. It's badged "v2 preview" while we gather feedback — the existing dashboard is unchanged.

### Improvements
- **Confirmation prompts reworked** — all "Are you sure?" browser pop-ups have been replaced with confirmations shown in the page, and status messages now appear as notifications rather than pop-up alerts. Browsers quietly suppress the old-style pop-ups on background tabs, which could make buttons appear to do nothing.
- File uploads that are rejected by the server (for example, an unsupported file type) now show the reason next to the upload field instead of failing silently.
- Various security and maintenance updates.

## 1.8.2


### Bug Fixes
- **Admin dashboard error after 1.8.1** — fixed a crash that could take down the admin dashboard once scheduled tasks began running. The scheduler status check is now robust: if the heartbeat data is ever missing or unreadable, the dashboard shows the "not running" warning instead of an error page.

### Improvements
- Dependency updates are now locked to the PHP version available on the server, preventing scheduled tasks from silently breaking after an update.

## 1.8.1


### Features
- **Scheduler status on the dashboard** — the admin dashboard now shows whether scheduled tasks are running: a green "Scheduler running" indicator when all is well, and a clear warning with the time of the last successful run if they've stopped.

### Bug Fixes
- **Self-service content types no longer ask for a page** — selecting a self-service content type in the request wizard now goes straight to the access request form. Previously the page list stayed visible and the form often only appeared after picking a page, which wasn't relevant to an access request.

### Improvements
- Various security and maintenance updates.

## 1.8.0


### Features
- **Video attachments** — change requests now accept video files (MP4, MOV, WebM and AVI) alongside the existing image and document types.
- **Larger attachments** — the per-file upload limit has been raised from 10MB to 128MB, so screen recordings and video walkthroughs fit comfortably.
- **Automatic attachment cleanup** — attachment files are now removed 30 days after a request is completed, declined or cancelled, keeping server storage under control. The request still shows what was attached and when it was removed, and reopening a request within the 30 days protects its files.

### Improvements
- All recurring jobs (daily reminders, upload cleanup) now run through a single scheduler, so future scheduled tasks won't need new hosting panel entries.
- Various security and maintenance updates.

## 1.7.0


### Features
- **Access requests** — content types managed outside the change process (like Events) now have a proper access request workflow. Requesters say who needs access (themselves or a colleague) and why, and the request goes through the normal approval flow.
- **Training before access** — once an access request is approved, the person needing access automatically receives an email with the training video and a confirmation link. They confirm they've watched it and feel competent, the team is notified to set up access, and the person gets a "your access is ready" email once it's done. Requests show new "Awaiting Training" and "Training Confirmed" statuses throughout, including on the public tracking page.
- **Training video per content type** — each self-service content type has its own training video link, set in Content Type settings.
- **Three new email templates** — Training Requested, Training Confirmed and Access Granted, all editable and previewable in the email template editor.

### Bug Fixes
- Requesting access to a self-service content type no longer fails with a "cpt slug" error when no page is selected.
- Access requests now display as access requests — not as page changes against a placeholder page — on the approval page, in emails, in the admin back end and on the tracking page.
- Requests that have been completed, declined or cancelled no longer appear under "other outstanding approvals" on approver pages, and their unused approval links are deactivated.

### ⚙️ Action required after updating
Set a **training video URL** on each self-service content type (Settings → Content Types → edit → Training video URL). Without it, approved access requests wait at "Approved" and the training email isn't sent (a note on the request explains why).

## 1.6.1


### Features
- **Reading-age awareness** — when a requested change makes the wording more complex (raising the reading age), the requester is asked whether the change is really needed, and approvers see it clearly flagged on the approval page — the current → new reading age (with the UK average of 9–10 for context) and an inline highlight of exactly what changed. Approving such a change now asks for confirmation.

### Also included since 1.5.1 (from 1.6.0)

### Features
- **Scheduled changes** — set the date a request is scheduled for. Scheduling stops the SLA timer, and the assigned admin gets a reminder email on the day it's due.
- **Clearer change requests** — requesters now supply the current text and an inline highlight shows exactly what's being added and removed.
- **Governance checkpoint** — a short confirmation after choosing a page steers requesters to the correct page (or to contact the team) rather than repurposing a random one.

### Bug Fixes
- Line items can no longer be marked done before a request has been approved.
- Approval notification emails show the person's name correctly instead of a placeholder.
- Fixed an error that could occur when automated chase reminders were sent.

### ⚙️ Action required after updating
The "scheduled for action today" reminder needs a daily scheduled task. In your hosting control panel, add a **Scheduled Task** that runs each morning:

```
php <your-site-path>/artisan requests:notify-scheduled-today
```

## 1.6.0


### Features
- **Scheduled changes** — you can now set the date a request is scheduled for. Scheduling stops the SLA timer (the agreed date becomes the commitment), and the assigned admin gets a reminder email on the day it's due.
- **Clearer change requests** — when requesting a change to existing content, the requester now supplies the current text and an inline highlight shows exactly what's being added and removed, so it's obvious at a glance what should change.
- **Governance checkpoint** — a short confirmation after choosing a page reminds requesters to select the correct page (and contact the team if theirs isn't listed) rather than repurposing a random page or asking for a new page in the change box.

### Bug Fixes
- Line items can no longer be marked done before a request has been approved.
- Approval notification emails now show the person's name correctly instead of a placeholder.
- Fixed an error that could occur when automated chase reminders were sent.

### ⚙️ Action required after updating
The new "scheduled for action today" reminder needs a daily scheduled task. In your hosting control panel, add a **Scheduled Task** that runs once each morning:

```
php <your-site-path>/artisan requests:notify-scheduled-today
```

Everything else works without it — only that daily reminder email won't be sent until the task is in place.

## 1.5.1

### Fixes

- Fixed a stray horizontal scrollbar on the **Email log** screen. The table now fits within the page width; long recipient addresses are shortened with an ellipsis and shown in full on hover.

### Maintenance

- Various security and maintenance updates.

### Carried over from 1.5.0

- **Reading age indicator now appears on all existing forms.** Previously it only showed on content fields created or re-saved since v1.4.0; it now shows automatically on every text and rich-text field, with the submission readability warning applying consistently. Fields where it was deliberately switched off stay off.
- Updated underlying dependencies to their latest supported versions.

## 1.5.0

### Fixes

- **Reading age indicator now appears on all existing forms.** Previously the indicator only showed on content fields created or re-saved since v1.4.0; forms set up before then displayed nothing even though the feature was meant to be on by default. It now shows automatically on every text and rich-text field, with the submission readability warning applying consistently. Fields where it was deliberately switched off stay off.

### Improvements

- Updated underlying dependencies to their latest supported versions.

## 1.4.0 and earlier

See the [GitHub releases page](https://github.com/cinsekrap/wp-change-manager/releases).
