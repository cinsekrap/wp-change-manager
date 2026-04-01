<?php

return [
    'request_submitted' => [
        'name' => 'Request Submitted',
        'description' => 'Sent to the requester when they submit a change request.',
        'subject' => 'Change Request {reference} — Submitted',
        'body' => 'Thank you for submitting your website change request. Our web team will review it and be in touch if we need any further information.',
        'placeholders' => ['reference', 'site_name', 'page_title', 'item_count', 'deadline_date'],
    ],

    'status_changed' => [
        'name' => 'Status Changed',
        'description' => 'Sent to the requester when the status of their request changes.',
        'subject' => 'Change Request {reference} — {new_status}',
        'body' => 'The status of your change request has changed. Here\'s a reminder of what you asked for:',
        'placeholders' => ['reference', 'site_name', 'page_title', 'old_status', 'new_status', 'rejection_reason'],
    ],

    'new_request_alert' => [
        'name' => 'New Request Alert',
        'description' => 'Sent to admins when a new change request is submitted.',
        'subject' => 'New Change Request: {reference}',
        'body' => 'A new change request has been submitted and requires your attention.',
        'placeholders' => ['reference', 'site_name', 'page_title', 'requester_name', 'requester_email', 'item_count'],
    ],

    'approval_requested' => [
        'name' => 'Approval Requested',
        'description' => 'Sent to approvers when their approval is needed for a change request.',
        'subject' => 'Approval Requested: {reference}',
        'body' => 'A website change request has been submitted that requires your approval before our web team can begin work on it. Please review the details below and let us know whether you\'re happy for this to go ahead.',
        'placeholders' => ['reference', 'site_name', 'page_title', 'approver_name', 'requester_name', 'item_count', 'deadline_date'],
    ],

    'request_assigned' => [
        'name' => 'Request Assigned',
        'description' => 'Sent to an admin when a change request is assigned to them.',
        'subject' => 'Change Request {reference} — Assigned to you',
        'body' => 'A change request has been assigned to you. Please review the details below and take the appropriate action.',
        'placeholders' => ['reference', 'site_name', 'page_title', 'requester_name', 'assignee_name'],
    ],

    'request_chase' => [
        'name' => 'Chase Reminder',
        'description' => 'Sent when a change request has not moved status within the configured chase period.',
        'subject' => 'Reminder: Change Request {reference} needs attention',
        'body' => 'This change request has been inactive for {stale_hours} hours and needs attention. Please review it and take the appropriate action.',
        'placeholders' => ['reference', 'site_name', 'page_title', 'status', 'stale_hours', 'requester_name', 'requester_email'],
    ],

    'approval_overridden' => [
        'name' => 'Approval Overridden',
        'description' => 'Sent to pending approvers when a super-admin overrides the approval gate.',
        'subject' => 'Approval No Longer Required: {reference}',
        'body' => '{overridden_by} has progressed this change request, so your approval is no longer required. No action is needed from you.',
        'placeholders' => ['reference', 'site_name', 'page_title', 'approver_name', 'overridden_by'],
    ],
];
