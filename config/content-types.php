<?php

/**
 * The kinds of job a piece of content does, and the page type each one lands on.
 *
 * These are deliberately phrased as the job the content does for the reader, not as
 * CMS templates — asking "what type of content is this?" is what the content lane
 * exists to get away from. Several jobs legitimately share one page type: our users
 * already ask for "a need page" without always meaning the same thing, and the point
 * of this list is to bridge that gap.
 *
 * `tag` is the page type, and is what reporting groups by. Keep the keys stable —
 * they are stored on change_requests.content_type.
 */

return [
    'situation_support' => [
        'label' => 'Explains a situation and what support exists',
        'help' => "Someone is dealing with something and needs to know what help is out there — including support we don't provide ourselves.",
        'tag' => 'Need',
    ],
    'appointment_prep' => [
        'label' => 'Helps someone prepare for an appointment',
        'help' => 'What to expect, what to bring, what happens on the day.',
        'tag' => 'Need',
    ],
    'referral_self' => [
        'label' => 'Helps someone get ready to make a referral',
        'help' => 'For members of the public referring themselves or someone they care for.',
        'tag' => 'Self help',
    ],
    'task_walkthrough' => [
        'label' => 'Walks someone through a task',
        'help' => 'Booking something, filling something in, finding the right team.',
        'tag' => 'Self help',
    ],
    'professional_prep' => [
        'label' => 'Helps professionals get ready',
        'help' => 'Referral criteria, forms and process for GPs and other referrers.',
        'tag' => 'Professional',
    ],
    'service_explainer' => [
        'label' => 'Explains a service and how it works',
        'help' => "What it does, who it's for, and how someone gets it.",
        'tag' => 'Service',
    ],
    'announcement' => [
        'label' => 'Tells people about something happening',
        'help' => 'A campaign, an event, a service opening or closing.',
        'tag' => 'Latest',
    ],
    'governance' => [
        'label' => "Something we're required to publish",
        'help' => 'Legal, clinical or compliance information.',
        'tag' => 'Governance',
        // "This is a Governance" doesn't read; these names predate the sentence frame.
        'tag_article' => '',
    ],
];
