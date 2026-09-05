<?php

/**
 * Who a piece of content has to serve.
 *
 * One list, read by the public wizard, the admin brief and the admin create form.
 * It was written out by hand in all three, which is how the labels drift apart —
 * keep the keys stable, they are stored inside change_requests.content_brief.
 */

return [
    'patients' => 'Patients & service users',
    'families' => 'Families & carers',
    'referrers' => 'Referrers & GPs',
    'staff' => 'Our staff',
    'commissioners' => 'Commissioners',
    'public' => 'General public',
];
