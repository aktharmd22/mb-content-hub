<?php

use App\Http\Controllers\Admin\SettingsController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\SearchController;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => redirect()->route('login'));

Route::middleware('auth')->group(function () {
    // Role-based dashboard redirect
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Profile (all roles)
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // Global search (all roles)
    Route::get('/search',                              [SearchController::class, 'search'])->name('search');

    // Notifications (all roles)
    Route::get('/notifications',                       [NotificationController::class, 'index'])->name('notifications.index');
    Route::get('/notifications/dropdown',              [NotificationController::class, 'dropdown'])->name('notifications.dropdown');
    Route::post('/notifications/{id}/read',            [NotificationController::class, 'markRead'])->name('notifications.read');
    Route::post('/notifications/read-all',             [NotificationController::class, 'markAllRead'])->name('notifications.read-all');
    Route::get('/notifications/preferences',           [NotificationController::class, 'preferences'])->name('notifications.preferences');
    Route::post('/notifications/preferences',          [NotificationController::class, 'savePreferences'])->name('notifications.preferences.save');

    // Admin-only routes
    Route::middleware('role:admin')->prefix('admin')->name('admin.')->group(function () {
        Route::get('/dashboard',                        [\App\Http\Controllers\Admin\DashboardController::class, 'index'])->name('dashboard');

        Route::resource('users', UserController::class)->except(['show']);

        // All articles + bulk actions
        Route::get('/articles',                         [\App\Http\Controllers\Admin\ArticleController::class, 'index'])->name('articles.index');
        Route::get('/articles/export',                  [\App\Http\Controllers\Admin\ArticleController::class, 'export'])->name('articles.export');
        Route::post('/articles/bulk',                   [\App\Http\Controllers\Admin\ArticleController::class, 'bulkAction'])->name('articles.bulk');
        Route::delete('/articles/{article}',            [\App\Http\Controllers\Admin\ArticleController::class, 'destroy'])->name('articles.destroy');

        // Assignment queue
        Route::get('/assignments',                      [\App\Http\Controllers\Admin\AssignmentController::class, 'index'])->name('assignments.index');
        Route::post('/assignments/{article}/assign',    [\App\Http\Controllers\Admin\AssignmentController::class, 'assign'])->name('assignments.assign');

        // Analytics
        Route::get('/analytics',                        [\App\Http\Controllers\Admin\AnalyticsController::class, 'index'])->name('analytics.index');

        // Activity log
        Route::get('/activity',                         [\App\Http\Controllers\Admin\ActivityController::class, 'index'])->name('activity.index');

        // Article types (CRUD)
        Route::resource('article-types', \App\Http\Controllers\Admin\ArticleTypeController::class)
            ->except(['show'])
            ->parameters(['article-types' => 'articleType']);

        // Settings
        Route::get('/settings',                         [SettingsController::class, 'index'])->name('settings.index');
        Route::get('/settings/general',                 [SettingsController::class, 'general'])->name('settings.general');
        Route::post('/settings/general',                [SettingsController::class, 'saveGeneral'])->name('settings.general.save');
        Route::post('/settings/general/logo',           [SettingsController::class, 'uploadLogo'])->name('settings.general.logo');
        Route::delete('/settings/general/logo',         [SettingsController::class, 'removeLogo'])->name('settings.general.logo.remove');
        Route::post('/settings/general/favicon',        [SettingsController::class, 'uploadFavicon'])->name('settings.general.favicon');
        Route::delete('/settings/general/favicon',      [SettingsController::class, 'removeFavicon'])->name('settings.general.favicon.remove');
        Route::get('/settings/drive',                   [SettingsController::class, 'drive'])->name('settings.drive');
        Route::post('/settings/drive/credentials',      [SettingsController::class, 'uploadCredentials'])->name('settings.drive.credentials');
        Route::delete('/settings/drive/credentials',    [SettingsController::class, 'clearCredentials'])->name('settings.drive.credentials.clear');
        Route::post('/settings/drive/test',             [SettingsController::class, 'testConnection'])->name('settings.drive.test');
        Route::post('/settings/drive/setup',            [SettingsController::class, 'setupFolders'])->name('settings.drive.setup');
        Route::post('/settings/drive/folders',          [SettingsController::class, 'saveFolderIds'])->name('settings.drive.folders');

        // OAuth user-impersonation
        Route::post('/settings/drive/oauth/credentials', [SettingsController::class, 'saveOAuthCredentials'])->name('settings.drive.oauth.credentials');
        Route::get('/settings/drive/oauth/start',        [SettingsController::class, 'startOAuth'])->name('settings.drive.oauth.start');
        Route::get('/settings/drive/oauth/callback',     [SettingsController::class, 'oauthCallback'])->name('settings.drive.oauth.callback');
        Route::post('/settings/drive/oauth/disconnect',  [SettingsController::class, 'disconnectOAuth'])->name('settings.drive.oauth.disconnect');
    });

    // Sales
    Route::middleware('role:sales,admin')->prefix('sales')->name('sales.')->group(function () {
        Route::get('/dashboard', [\App\Http\Controllers\Sales\DashboardController::class, 'index'])->name('dashboard');

        Route::get('/articles',                       [\App\Http\Controllers\Sales\ArticleController::class, 'index'])->name('articles.index');
        Route::get('/articles/create',                [\App\Http\Controllers\Sales\ArticleController::class, 'create'])->name('articles.create');
        Route::post('/articles',                      [\App\Http\Controllers\Sales\ArticleController::class, 'store'])->name('articles.store');
        Route::get('/articles/{article}',             [\App\Http\Controllers\Sales\ArticleController::class, 'show'])->name('articles.show');
        Route::delete('/articles/{article}',          [\App\Http\Controllers\Sales\ArticleController::class, 'destroy'])->name('articles.destroy');
        Route::get('/articles/{article}/download',    [\App\Http\Controllers\Sales\ArticleController::class, 'download'])->name('articles.download');
        Route::get('/articles/{article}/assets/{asset}', [\App\Http\Controllers\Sales\ArticleController::class, 'downloadAsset'])->name('articles.assets.download');
        Route::post('/articles/{article}/comment',    [\App\Http\Controllers\Sales\ArticleController::class, 'comment'])->name('articles.comment');
        Route::post('/articles/{article}/client-approved', [\App\Http\Controllers\Sales\ArticleController::class, 'clientApproved'])->name('articles.client-approved');
        Route::post('/articles/{article}/request-revision', [\App\Http\Controllers\Sales\ArticleController::class, 'requestRevision'])->name('articles.request-revision');
        Route::post('/articles/{article}/publish',    [\App\Http\Controllers\Sales\ArticleController::class, 'publish'])->name('articles.publish');

        Route::post('/clients/quick-create',          [\App\Http\Controllers\Sales\ClientController::class, 'quickCreate'])->name('clients.quick-create');
        Route::resource('clients', \App\Http\Controllers\Sales\ClientController::class)->except(['show']);
    });

    // Tech team — writer-side actions (assignments, write & submit)
    Route::middleware('role:tech_team,admin')->prefix('writer')->name('writer.')->group(function () {
        Route::get('/dashboard',                              [\App\Http\Controllers\Writer\DashboardController::class, 'index'])->name('dashboard');
        Route::get('/articles',                               [\App\Http\Controllers\Writer\ArticleController::class, 'index'])->name('articles.index');
        Route::get('/articles/{article}',                     [\App\Http\Controllers\Writer\ArticleController::class, 'show'])->name('articles.show');
        Route::post('/articles/{article}/pick-up',            [\App\Http\Controllers\Writer\ArticleController::class, 'pickUp'])->name('articles.pick-up');
        Route::delete('/articles/{article}',                  [\App\Http\Controllers\Writer\ArticleController::class, 'destroy'])->name('articles.destroy');
        Route::post('/articles/{article}/start',              [\App\Http\Controllers\Writer\ArticleController::class, 'start'])->name('articles.start');
        Route::post('/articles/{article}/submit-review',      [\App\Http\Controllers\Writer\ArticleController::class, 'submitForReview'])->name('articles.submit-review');
        Route::post('/articles/{article}/publish',            [\App\Http\Controllers\Writer\ArticleController::class, 'publish'])->name('articles.publish');
        Route::post('/articles/{article}/comment',            [\App\Http\Controllers\Writer\ArticleController::class, 'comment'])->name('articles.comment');
        Route::get('/articles/{article}/download',            [\App\Http\Controllers\Writer\ArticleController::class, 'downloadCurrent'])->name('articles.download');
        Route::get('/articles/{article}/download-source',     [\App\Http\Controllers\Writer\ArticleController::class, 'downloadSource'])->name('articles.download-source');
        Route::get('/articles/{article}/assets/{asset}',      [\App\Http\Controllers\Writer\ArticleController::class, 'downloadAsset'])->name('articles.assets.download');
    });

    // Tech team — lead-side actions (review, approve, team performance)
    Route::middleware('role:tech_team,admin')->prefix('lead')->name('lead.')->group(function () {
        Route::get('/dashboard',                              [\App\Http\Controllers\Lead\DashboardController::class, 'index'])->name('dashboard');
        Route::get('/articles',                               [\App\Http\Controllers\Lead\ArticleController::class, 'index'])->name('articles.index');
        Route::get('/articles/{article}',                     [\App\Http\Controllers\Lead\ArticleController::class, 'show'])->name('articles.show');
        Route::post('/articles/{article}/approve',            [\App\Http\Controllers\Lead\ArticleController::class, 'approve'])->name('articles.approve');
        Route::post('/articles/{article}/request-revision',   [\App\Http\Controllers\Lead\ArticleController::class, 'requestRevision'])->name('articles.request-revision');
        Route::post('/articles/{article}/reassign',           [\App\Http\Controllers\Lead\ArticleController::class, 'reassign'])->name('articles.reassign');
        Route::post('/articles/{article}/comment',            [\App\Http\Controllers\Lead\ArticleController::class, 'comment'])->name('articles.comment');
        Route::get('/articles/{article}/download',            [\App\Http\Controllers\Lead\ArticleController::class, 'downloadCurrent'])->name('articles.download');
        Route::get('/articles/{article}/download-source',     [\App\Http\Controllers\Lead\ArticleController::class, 'downloadSource'])->name('articles.download-source');
        Route::get('/articles/{article}/assets/{asset}',      [\App\Http\Controllers\Lead\ArticleController::class, 'downloadAsset'])->name('articles.assets.download');

        Route::get('/team',                                   [\App\Http\Controllers\Lead\TeamController::class, 'index'])->name('team.index');
    });
});

require __DIR__.'/auth.php';
