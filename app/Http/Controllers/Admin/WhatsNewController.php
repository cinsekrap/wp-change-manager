<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;

/**
 * The "what's new in this version" modal in the admin layout. It has nothing to
 * do with what any one page shows, which is why it no longer lives on the
 * controller behind the landing page.
 */
class WhatsNewController extends Controller
{
    public function dismiss()
    {
        Setting::set('whats_new_seen_'.auth()->id(), config('version.current'));

        return response()->json(['ok' => true]);
    }
}
