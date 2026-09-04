<?php

namespace App\Http\Controllers\PublicSite;

use App\Http\Controllers\Controller;
use App\Models\ChangeRequest;
use App\Models\CheckQuestion;
use App\Models\CptType;
use App\Models\Site;
use Illuminate\Support\Facades\Cache;

class WizardController extends Controller
{
    public function index()
    {
        $sites = Site::active()->orderBy('name')->get(['id', 'name', 'domain']);
        $cptTypes = CptType::active()->ordered()->get(['id', 'slug', 'name', 'description', 'form_config', 'request_mode', 'mode_message']);
        $questions = CheckQuestion::active()->ordered()->get();

        $contentTypes = config('content-types');
        $contentWaitDays = self::averageContentWaitDays();

        return view('public.wizard', compact('sites', 'cptTypes', 'questions', 'contentTypes', 'contentWaitDays'));
    }

    /**
     * Typical days from suggestion to published, over completed content requests.
     *
     * Shown on step 2 so nobody starts a content request expecting a change
     * request's turnaround. A hand-maintained number here would go stale and be
     * worse than none, so it is read from the requests themselves.
     *
     * Cached as a scalar — never an object; the framework cache stores restrict
     * unserialising classes and a cached Carbon has taken the dashboard down before.
     */
    public static function averageContentWaitDays(): ?int
    {
        $cached = Cache::remember('content_wait_days', now()->addHours(6), function () {
            $days = ChangeRequest::query()
                ->where('request_type', 'content')
                ->where('status', 'done')
                ->get(['created_at', 'updated_at'])
                ->map(fn ($cr) => $cr->created_at->diffInDays($cr->updated_at))
                ->filter(fn ($d) => $d >= 0);

            // 0 means "not enough history yet" — a scalar the cache can hold safely.
            return $days->count() < 3 ? 0 : (int) round($days->avg());
        });

        return is_numeric($cached) && $cached > 0 ? (int) $cached : null;
    }
}
