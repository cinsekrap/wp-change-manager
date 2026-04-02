<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\EmailLog;
use Illuminate\Http\Request;

class EmailLogController extends Controller
{
    public function index(Request $request)
    {
        $logs = EmailLog::with('changeRequest')
            ->when($request->search, function ($q, $search) {
                $q->where('recipient_email', 'like', "%{$search}%")
                  ->orWhere('subject', 'like', "%{$search}%");
            })
            ->latest()
            ->paginate(25)
            ->withQueryString();

        return view('admin.settings.email-log', compact('logs'));
    }

    public function show(EmailLog $emailLog)
    {
        return response($emailLog->body_html)->header('Content-Security-Policy', 'sandbox');
    }
}
