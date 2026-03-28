<?php

use App\Http\Controllers\Admin\ChangeRequestController;
use App\Http\Controllers\Admin\CheckQuestionController;
use App\Http\Controllers\Admin\CptController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\SettingsController;
use App\Http\Controllers\Admin\SiteController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Api\SitemapController;
use App\Http\Controllers\Api\UploadController;
use App\Http\Controllers\Admin\EntraSettingsController;
use App\Http\Controllers\Admin\UpdateController;
use App\Http\Controllers\Auth\EntraController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\MfaController;
use App\Http\Controllers\Auth\PasswordResetController;
use App\Http\Controllers\PublicSite\ApprovalController;
use App\Http\Controllers\PublicSite\TrackingController;
use App\Http\Controllers\PublicSite\WizardController;
use App\Http\Controllers\PublicSite\SubmissionController;
use Illuminate\Support\Facades\Route;

// Public wizard
Route::get('/', [WizardController::class, 'index'])->name('wizard');
Route::post('/submit', [SubmissionController::class, 'store'])
    ->name('submit')
    ->middleware('throttle:10,60');
Route::get('/confirmation/{reference}', [SubmissionController::class, 'confirmation'])->name('confirmation');

// Public tracking
Route::get('/track', [TrackingController::class, 'index'])->name('tracking');
Route::get('/track/{reference}', [TrackingController::class, 'direct'])->name('tracking.direct');
Route::post('/track', [TrackingController::class, 'show'])->name('tracking.show')->middleware('throttle:10,1');

// Public approval links
Route::get('/approve/{token}', [ApprovalController::class, 'show'])->name('approval.show');
Route::post('/approve/{token}', [ApprovalController::class, 'respond'])->name('approval.respond')->middleware('throttle:5,1');

// AJAX API endpoints (public, rate-limited)
Route::prefix('api')->middleware('throttle:60,1')->group(function () {
    Route::post('/sitemap/refresh/{site}', [SitemapController::class, 'refresh'])->name('api.sitemap.refresh');
    Route::get('/sitemap/status/{site}', [SitemapController::class, 'status'])->name('api.sitemap.status');
    Route::get('/pages/{site}', [SitemapController::class, 'pages'])->name('api.pages');
    Route::post('/upload', [UploadController::class, 'store'])->name('api.upload');
    Route::delete('/upload/{filename}', [UploadController::class, 'destroy'])->name('api.upload.destroy');
});

// Auth routes
Route::get('/admin/login', [LoginController::class, 'showLoginForm'])->name('login');
Route::post('/admin/login', [LoginController::class, 'login'])->middleware('throttle:5,1');
Route::post('/admin/logout', [LoginController::class, 'logout'])->name('logout');

// Password reset
Route::get('/admin/forgot-password', [PasswordResetController::class, 'showForgotForm'])->name('password.request');
Route::post('/admin/forgot-password', [PasswordResetController::class, 'sendResetLink'])->name('password.email')->middleware('throttle:3,1');
Route::get('/admin/reset-password/{token}', [PasswordResetController::class, 'showResetForm'])->name('password.reset');
Route::post('/admin/reset-password', [PasswordResetController::class, 'reset'])->name('password.update')->middleware('throttle:3,1');

// Microsoft Entra SSO
Route::get('/auth/microsoft', [EntraController::class, 'redirect'])->name('auth.microsoft');
Route::get('/auth/microsoft/callback', [EntraController::class, 'callback'])->name('auth.microsoft.callback');

// MFA routes (require auth but NOT mfa — user is in the MFA flow)
Route::middleware('auth')->prefix('admin/mfa')->group(function () {
    Route::get('/setup', [MfaController::class, 'setup'])->name('mfa.setup');
    Route::post('/setup', [MfaController::class, 'confirmSetup'])->name('mfa.confirm');
    Route::get('/challenge', [MfaController::class, 'challenge'])->name('mfa.challenge');
    Route::post('/challenge', [MfaController::class, 'verify'])->name('mfa.verify')->middleware('throttle:5,1');
    Route::post('/disable', [MfaController::class, 'disable'])->name('mfa.disable');
});

// Admin routes (editor + super_admin)
Route::prefix('admin')->middleware(['auth', 'admin', 'mfa'])->group(function () {
    Route::get('/', [DashboardController::class, 'index'])->name('admin.dashboard');

    // Change requests
    Route::get('/requests/export', [ChangeRequestController::class, 'export'])->name('admin.requests.export');
    Route::get('/requests', [ChangeRequestController::class, 'index'])->name('admin.requests.index');
    Route::get('/requests/{changeRequest}', [ChangeRequestController::class, 'show'])->name('admin.requests.show');
    Route::patch('/requests/{changeRequest}/status', [ChangeRequestController::class, 'updateStatus'])->name('admin.requests.status');
    Route::post('/requests/{changeRequest}/notes', [ChangeRequestController::class, 'addNote'])->name('admin.requests.notes');
    Route::get('/requests/{changeRequest}/files/{file}', [ChangeRequestController::class, 'downloadFile'])->name('admin.requests.download');
    Route::post('/requests/{changeRequest}/approvers', [ChangeRequestController::class, 'addApprover'])->name('admin.requests.approvers.add');
    Route::patch('/requests/{changeRequest}/approvers/{approver}', [ChangeRequestController::class, 'updateApprover'])->name('admin.requests.approvers.update');
    Route::delete('/requests/{changeRequest}/approvers/{approver}', [ChangeRequestController::class, 'removeApprover'])->name('admin.requests.approvers.remove');
    Route::patch('/requests/{changeRequest}/items/{item}/status', [ChangeRequestController::class, 'updateItemStatus'])->name('admin.requests.items.status');
    Route::post('/requests/{changeRequest}/send-for-approval', [ChangeRequestController::class, 'sendForApproval'])->name('admin.requests.send-approval');

    // Password change (all admins)
    Route::get('/password', [UserController::class, 'editPassword'])->name('admin.password.edit');
    Route::put('/password', [UserController::class, 'updatePassword'])->name('admin.password.update');

    // Super admin only routes
    Route::middleware('super_admin')->group(function () {
        // Sites
        Route::resource('sites', SiteController::class)->names('admin.sites');
        Route::post('/sites/{site}/refresh', [SiteController::class, 'refreshSitemap'])->name('admin.sites.refresh');

        // CPT Types
        Route::resource('cpts', CptController::class)->names('admin.cpts');

        // Check Questions
        Route::resource('questions', CheckQuestionController::class)->names('admin.questions');

        // Settings
        Route::get('/settings/mail', [SettingsController::class, 'edit'])->name('admin.settings.mail');
        Route::put('/settings/mail', [SettingsController::class, 'update'])->name('admin.settings.mail.update');
        Route::post('/settings/mail/test', [SettingsController::class, 'test'])->name('admin.settings.mail.test');
        Route::get('/settings/mail/preview/{template}', [SettingsController::class, 'previewEmail'])->name('admin.settings.mail.preview');

        // Email Templates
        Route::get('/settings/email-templates', [SettingsController::class, 'emailTemplates'])->name('admin.settings.email-templates');
        Route::put('/settings/email-templates', [SettingsController::class, 'updateEmailTemplates'])->name('admin.settings.email-templates.update');
        Route::post('/settings/email-templates/reset', [SettingsController::class, 'resetEmailTemplate'])->name('admin.settings.email-templates.reset');

        // SSO Settings
        Route::get('/settings/entra', [EntraSettingsController::class, 'edit'])->name('admin.settings.entra');
        Route::put('/settings/entra', [EntraSettingsController::class, 'update'])->name('admin.settings.entra.update');

        // Updates
        Route::get('/settings/updates', [UpdateController::class, 'index'])->name('admin.settings.updates');
        Route::post('/settings/updates/check', [UpdateController::class, 'check'])->name('admin.settings.updates.check');
        Route::post('/settings/updates/install', [UpdateController::class, 'install'])->name('admin.settings.updates.install');

        // Users
        Route::resource('users', UserController::class)->except(['show'])->names('admin.users');
        Route::post('/users/{user}/reset-mfa', [UserController::class, 'resetMfa'])->name('admin.users.reset-mfa');
    });
});

// Deploy endpoint — POST only, token required, no output leaked
Route::post('/deploy/{token}', function (string $token) {
    $expected = config('app.deploy_token');

    if (!$expected || !hash_equals($expected, $token)) {
        abort(403);
    }

    $log = ['timestamp' => date('Y-m-d H:i:s'), 'ip' => request()->ip()];

    // Try git pull
    $gitResult = shell_exec('git pull origin main 2>&1');
    $log['git'] = $gitResult ?: 'git not available';

    // Try migrations
    try {
        \Illuminate\Support\Facades\Artisan::call('migrate', ['--force' => true]);
        $log['migrate'] = trim(\Illuminate\Support\Facades\Artisan::output()) ?: 'Nothing to migrate.';
    } catch (\Exception $e) {
        $log['migrate'] = 'Error: ' . $e->getMessage();
    }

    file_put_contents(storage_path('logs/deploy.log'), json_encode($log) . "\n", FILE_APPEND);

    return response()->json(['status' => 'ok']);
})->name('deploy')->middleware('throttle:3,1');
