<?php

return [
    'request_submitted' => [
        'name' => 'Request Submitted',
        'description' => 'Sent to the requester when they submit a change request.',
        'subject' => 'Change Request {reference} — Submitted',
        'body' => 'Thank you for submitting your website change request. Our marketing team will review it and be in touch if we need any further information.',
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
        'body' => 'A website change request has been submitted that requires your approval before our marketing team can begin work on it. Please review the details below and let us know whether you\'re happy for this to go ahead.',
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

    'scheduled_today' => [
        'name' => 'Scheduled for Action Today',
        'description' => 'Sent to the assignee on the day a request is scheduled to be actioned.',
        'subject' => 'Scheduled for today: Change Request {reference}',
        'body' => 'This change request is scheduled to be actioned today. Please make the changes and mark the items done once complete.',
        'placeholders' => ['reference', 'site_name', 'page_title', 'scheduled_date', 'assignee_name'],
    ],

    'approval_overridden' => [
        'name' => 'Approval Overridden',
        'description' => 'Sent to pending approvers when a super-admin overrides the approval gate.',
        'subject' => 'Approval No Longer Required: {reference}',
        'body' => '{overridden_by} has progressed this change request, so your approval is no longer required. No action is needed from you.',
        'placeholders' => ['reference', 'site_name', 'page_title', 'approver_name', 'overridden_by'],
    ],

    'group_approval_satisfied' => [
        'name' => 'Group Approval Satisfied',
        'description' => 'Sent to remaining group members when another member in their group has approved.',
        'subject' => 'Approval No Longer Required: {reference}',
        'body' => '{satisfied_by} has approved this request on behalf of your group, so your approval is no longer required. No action is needed from you.',
        'placeholders' => ['reference', 'site_name', 'page_title', 'approver_name', 'satisfied_by', 'group_name'],
    ],

    'approval_declined' => [
        'name' => 'Request Declined (to other approvers)',
        'description' => 'Sent to other pending approvers when a request is declined following a rejection.',
        'subject' => 'Request Declined: {reference}',
        'body' => 'The change request below has been declined following a review by another approver. Your approval is no longer needed and no action is required from you.',
        'placeholders' => ['reference', 'site_name', 'page_title', 'approver_name'],
    ],

    'training_requested' => [
        'name' => 'Training Requested',
        'description' => 'Sent to the access recipient when an approved access request needs training to be completed.',
        'subject' => 'Training Required: {reference}',
        'body' => 'Your request for access has been approved. Before we can set up your access, please watch the short training video below. Once you\'ve watched it and feel confident, use the confirmation link to let us know.',
        'placeholders' => ['reference', 'site_name', 'cpt_name', 'recipient_name', 'requester_name', 'training_url', 'confirm_url'],
    ],

    'training_confirmed' => [
        'name' => 'Training Confirmed',
        'description' => 'Sent to the assignee (or admin alert address) when an access recipient confirms they have completed training.',
        'subject' => 'Training Confirmed: {reference}',
        'body' => '{recipient_name} has confirmed they\'ve watched the training video and feel competent. The request is ready for access to be granted.',
        'placeholders' => ['reference', 'site_name', 'cpt_name', 'recipient_name', 'recipient_email', 'confirmed_at'],
    ],

    'access_granted' => [
        'name' => 'Access Granted',
        'description' => 'Sent to the access recipient when their access request is completed.',
        'subject' => 'Your access is ready: {reference}',
        'body' => 'Good news — your access has been set up. You can now log in to {site_name} and manage {cpt_name} yourself. Thank you for completing the training.',
        'placeholders' => ['reference', 'site_name', 'cpt_name', 'recipient_name'],
    ],
];
