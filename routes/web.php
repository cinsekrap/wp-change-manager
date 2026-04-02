<?php

use App\Http\Controllers\Admin\ApproverController;
use App\Http\Controllers\Admin\AuditLogController;
use App\Http\Controllers\Admin\BulkActionController;
use App\Http\Controllers\Admin\ChangeRequestController;
use App\Http\Controllers\Admin\CheckQuestionController;
use App\Http\Controllers\Admin\CptController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\ConfigController;
use App\Http\Controllers\DeployController;
use App\Http\Controllers\Admin\EmailLogController;
use App\Http\Controllers\Admin\EmailTemplateController;
use App\Http\Controllers\Admin\MailSettingsController;
use App\Http\Controllers\Admin\NotificationSettingsController;
use App\Http\Controllers\Admin\SiteController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Api\SitemapController;
use App\Http\Controllers\Api\UploadController;
use App\Http\Controllers\Admin\EntraSettingsController;
use App\Http\Controllers\Admin\TagController;
use App\Http\Controllers\Admin\UpdateController;
use App\Http\Controllers\Auth\EntraController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\MfaController;
use App\Http\Controllers\Auth\PasswordResetController;
use App\Http\Controllers\InstallerController;
use App\Http\Controllers\PublicSite\ApprovalController;
use App\Http\Controllers\PublicSite\TrackingController;
use App\Http\Controllers\PublicSite\WizardController;
use App\Http\Controllers\PublicSite\SubmissionController;
use Illuminate\Support\Facades\Route;

// Installer routes (only accessible when app is not yet installed)
Route::middleware('installer')->prefix('install')->group(function () {
    Route::get('/', [InstallerController::class, 'index'])->name('install');
    Route::post('/check', [InstallerController::class, 'checkEnvironment'])->name('install.check');
    Route::post('/database', [InstallerController::class, 'setupDatabase'])->name('install.database');
    Route::post('/application', [InstallerController::class, 'setupApplication'])->name('install.application');
    Route::post('/migrate', [InstallerController::class, 'runMigrations'])->name('install.migrate');
    Route::post('/admin', [InstallerController::class, 'createAdmin'])->name('install.admin');
    Route::post('/complete', [InstallerController::class, 'complete'])->name('install.complete');
});

// Public wizard
Route::get('/', [WizardController::class, 'index'])->name('wizard');
Route::post('/submit', [SubmissionController::class, 'store'])
    ->name('submit')
    ->middleware('throttle:public-submit');
Route::get('/confirmation/{reference}', [SubmissionController::class, 'confirmation'])->name('confirmation')->middleware('signed');

// Public tracking
Route::get('/track', [TrackingController::class, 'index'])->name('tracking');
Route::get('/track/{reference}', [TrackingController::class, 'direct'])->name('tracking.direct');
Route::post('/track', [TrackingController::class, 'show'])->name('tracking.show')->middleware('throttle:public-tracking');

// Public approval links
Route::get('/approve/queue/{approver}', [ApprovalController::class, 'showFromQueue'])->name('approval.queue')->middleware(['signed', 'throttle:5,1']);
Route::get('/approve/{token}', [ApprovalController::class, 'show'])->name('approval.show');
Route::post('/approve/{token}', [ApprovalController::class, 'respond'])->name('approval.respond')->middleware('throttle:5,1');

// AJAX API endpoints (public, rate-limited)
Route::prefix('api')->middleware('throttle:public-api')->group(function () {
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
});

// Admin routes (editor + super_admin)
Route::prefix('admin')->middleware(['auth', 'admin', 'mfa'])->group(function () {
    Route::get('/', [DashboardController::class, 'index'])->name('admin.dashboard');

    // Change requests
    Route::get('/requests/export', [ChangeRequestController::class, 'export'])->name('admin.requests.export');
    Route::post('/requests/bulk/status', [BulkActionController::class, 'bulkUpdateStatus'])->name('admin.requests.bulk.status');
    Route::post('/requests/bulk/assign', [BulkActionController::class, 'bulkAssign'])->name('admin.requests.bulk.assign');
    Route::get('/requests', [ChangeRequestController::class, 'index'])->name('admin.requests.index');
    Route::get('/requests/{changeRequest}', [ChangeRequestController::class, 'show'])->name('admin.requests.show');
    Route::patch('/requests/{changeRequest}/status', [ChangeRequestController::class, 'updateStatus'])->name('admin.requests.status');
    Route::post('/requests/{changeRequest}/notes', [ChangeRequestController::class, 'addNote'])->name('admin.requests.notes');
    Route::get('/requests/{changeRequest}/files/{file}', [ChangeRequestController::class, 'downloadFile'])->name('admin.requests.download');
    Route::post('/requests/{changeRequest}/approvers', [ApproverController::class, 'addApprover'])->name('admin.requests.approvers.add');
    Route::patch('/requests/{changeRequest}/approvers/{approver}', [ApproverController::class, 'updateApprover'])->name('admin.requests.approvers.update');
    Route::delete('/requests/{changeRequest}/approvers/{approver}', [ApproverController::class, 'removeApprover'])->name('admin.requests.approvers.remove');
    Route::patch('/requests/{changeRequest}/items/{item}/status', [ChangeRequestController::class, 'updateItemStatus'])->name('admin.requests.items.status');
    Route::post('/requests/{changeRequest}/send-for-approval', [ApproverController::class, 'sendForApproval'])->name('admin.requests.send-approval');
    Route::post('/requests/{changeRequest}/override-approvals', [ApproverController::class, 'overrideApprovals'])->name('admin.requests.override-approvals')->middleware('super_admin');
    Route::patch('/requests/{changeRequest}/assign', [ChangeRequestController::class, 'updateAssignment'])->name('admin.requests.assign');
    Route::patch('/requests/{changeRequest}/priority', [ChangeRequestController::class, 'updatePriority'])->name('admin.requests.priority');

    // Tags on requests
    Route::post('/requests/{changeRequest}/tags', [ChangeRequestController::class, 'addTag'])->name('admin.requests.tags.add');
    Route::delete('/requests/{changeRequest}/tags/{tag}', [ChangeRequestController::class, 'removeTag'])->name('admin.requests.tags.remove');

    // MFA disable (requires full auth + admin + mfa)
    Route::post('/mfa/disable', [MfaController::class, 'disable'])->name('mfa.disable');

    // Password change (all admins)
    Route::get('/password', [UserController::class, 'editPassword'])->name('admin.password.edit');
    Route::put('/password', [UserController::class, 'updatePassword'])->name('admin.password.update');

    // All admins (editor + super_admin)
    Route::resource('sites', SiteController::class)->names('admin.sites');
    Route::post('/sites/{site}/refresh', [SiteController::class, 'refreshSitemap'])->name('admin.sites.refresh');
    Route::resource('questions', CheckQuestionController::class)->names('admin.questions');
    Route::get('/tags', [TagController::class, 'index'])->name('admin.tags.index');
    Route::put('/tags/{tag}', [TagController::class, 'update'])->name('admin.tags.update');
    Route::delete('/tags/{tag}', [TagController::class, 'destroy'])->name('admin.tags.destroy');

    // Super admin only
    Route::middleware('super_admin')->group(function () {
        // CPT Types
        Route::resource('cpts', CptController::class)->names('admin.cpts');

        // Mail Settings
        Route::get('/settings/mail', [MailSettingsController::class, 'edit'])->name('admin.settings.mail');
        Route::put('/settings/mail', [MailSettingsController::class, 'update'])->name('admin.settings.mail.update');
        Route::post('/settings/mail/test', [MailSettingsController::class, 'test'])->name('admin.settings.mail.test');

        // Notifications (SLA, chase, alerts)
        Route::get('/settings/notifications', [NotificationSettingsController::class, 'edit'])->name('admin.settings.notifications');
        Route::put('/settings/sla', [NotificationSettingsController::class, 'updateSla'])->name('admin.settings.sla.update');
        Route::put('/settings/chase', [NotificationSettingsController::class, 'updateChase'])->name('admin.settings.chase.update');
        Route::put('/settings/new-request-alert', [NotificationSettingsController::class, 'updateNewRequestAlert'])->name('admin.settings.new-request-alert.update');

        // Email Log
        Route::get('/settings/email-log', [EmailLogController::class, 'index'])->name('admin.settings.email-log');
        Route::get('/settings/email-log/{emailLog}', [EmailLogController::class, 'show'])->name('admin.settings.email-log.show');

        // Email Templates
        Route::get('/settings/email-templates', [EmailTemplateController::class, 'index'])->name('admin.settings.email-templates');
        Route::put('/settings/email-templates', [EmailTemplateController::class, 'update'])->name('admin.settings.email-templates.update');
        Route::post('/settings/email-templates/reset', [EmailTemplateController::class, 'reset'])->name('admin.settings.email-templates.reset');
        Route::get('/settings/mail/preview/{template}', [EmailTemplateController::class, 'preview'])->name('admin.settings.mail.preview');

        // Configuration Import/Export
        Route::get('/settings/config', [ConfigController::class, 'index'])->name('admin.settings.config');
        Route::post('/settings/config/export', [ConfigController::class, 'export'])->name('admin.settings.config.export');
        Route::post('/settings/config/import', [ConfigController::class, 'import'])->name('admin.settings.config.import');

        // SSO Settings
        Route::get('/settings/entra', [EntraSettingsController::class, 'edit'])->name('admin.settings.entra');
        Route::put('/settings/entra', [EntraSettingsController::class, 'update'])->name('admin.settings.entra.update');

        // Updates
        Route::get('/settings/updates', [UpdateController::class, 'index'])->name('admin.settings.updates');
        Route::post('/settings/updates/check', [UpdateController::class, 'check'])->name('admin.settings.updates.check');
        Route::post('/settings/updates/install', [UpdateController::class, 'install'])->name('admin.settings.updates.install');
        Route::post('/settings/updates/rollback', [UpdateController::class, 'rollback'])->name('admin.settings.updates.rollback');
        Route::delete('/settings/updates/backup', [UpdateController::class, 'deleteBackup'])->name('admin.settings.updates.delete-backup');
        Route::put('/settings/github-token', [UpdateController::class, 'updateGithubToken'])->name('admin.settings.github-token.update');

        // Users
        Route::resource('users', UserController::class)->except(['show'])->names('admin.users');
        Route::post('/users/{user}/reset-mfa', [UserController::class, 'resetMfa'])->name('admin.users.reset-mfa');

        // Audit Log
        Route::get('/audit-log', [AuditLogController::class, 'index'])->name('admin.audit-log');
    });
});

// Deploy endpoint — POST only, token required, no output leaked
Route::post('/deploy/{token}', DeployController::class)->name('deploy')->middleware('throttle:3,1');
