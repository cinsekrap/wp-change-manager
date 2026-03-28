<?php

namespace App\Http\Controllers\PublicSite;

use App\Http\Controllers\Controller;
use App\Models\CheckQuestion;
use App\Models\CptType;
use App\Models\Site;

class WizardController extends Controller
{
    public function index()
    {
        $sites = Site::active()->orderBy('name')->get(['id', 'name', 'domain']);
        $cptTypes = CptType::active()->ordered()->get(['id', 'slug', 'name', 'description', 'form_config', 'is_blocked', 'blocked_message']);
        $questions = CheckQuestion::active()->ordered()->get();

        return view('public.wizard', compact('sites', 'cptTypes', 'questions'));
    }
}
