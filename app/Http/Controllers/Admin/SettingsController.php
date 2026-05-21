<?php

namespace App\Http\Controllers\Admin;

use App\Exceptions\DriveException;
use App\Http\Controllers\Controller;
use App\Models\DriveFile;
use App\Models\Setting;
use App\Services\GoogleDriveService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SettingsController extends Controller
{
    public function __construct(private readonly GoogleDriveService $drive) {}

    public function index(): RedirectResponse
    {
        return redirect()->route('admin.settings.general');
    }

    public function general(): \Illuminate\View\View
    {
        return view('admin.settings.general', [
            'defaultDeadlineDays' => (int) (\App\Models\Setting::get('default_deadline_days', 7)),
            'stuckThresholdDays'  => (int) (\App\Models\Setting::get('stuck_threshold_days', 3)),
            'appName'             => (string) \App\Models\Setting::get('app_brand_name', config('app.name')),
            'logoPath'            => \App\Models\Setting::get('app_logo_path'),
            'logoUrl'             => \App\Support\Branding::logoUrl(),
            'faviconUrl'          => \App\Support\Branding::faviconUrl(),
            'viralPackagesFolder' => (string) \App\Models\Setting::get('drive_folder_viral_packages', ''),
        ]);
    }

    public function saveGeneral(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'default_deadline_days'         => ['required', 'integer', 'min:1', 'max:90'],
            'stuck_threshold_days'          => ['required', 'integer', 'min:1', 'max:30'],
            'app_brand_name'                => ['required', 'string', 'max:50'],
            'drive_folder_viral_packages'   => ['nullable', 'string', 'max:128'],
        ]);

        foreach ($data as $key => $value) {
            if ($value === null || $value === '') {
                \App\Models\Setting::forget($key);
            } else {
                \App\Models\Setting::put($key, (string) $value);
            }
        }

        return back()->with('success', 'Settings saved.');
    }

    public function uploadLogo(Request $request): RedirectResponse
    {
        $request->validate([
            'logo' => ['required', 'image', 'mimes:png,jpg,jpeg,svg,webp', 'max:2048'],
        ]);

        // Remove the previous logo if there was one
        $oldPath = \App\Models\Setting::get('app_logo_path');
        if ($oldPath && \Illuminate\Support\Facades\Storage::disk('public')->exists($oldPath)) {
            \Illuminate\Support\Facades\Storage::disk('public')->delete($oldPath);
        }

        $ext  = $request->file('logo')->getClientOriginalExtension();
        $path = $request->file('logo')->storeAs('branding', 'logo-' . time() . '.' . $ext, 'public');

        \App\Models\Setting::put('app_logo_path', $path);

        return back()->with('success', 'Logo uploaded.');
    }

    public function removeLogo(): RedirectResponse
    {
        $path = \App\Models\Setting::get('app_logo_path');
        if ($path && \Illuminate\Support\Facades\Storage::disk('public')->exists($path)) {
            \Illuminate\Support\Facades\Storage::disk('public')->delete($path);
        }
        \App\Models\Setting::forget('app_logo_path');

        return back()->with('success', 'Logo removed. Default logo restored.');
    }

    public function uploadFavicon(Request $request): RedirectResponse
    {
        $request->validate([
            'favicon' => ['required', 'image', 'mimes:png,jpg,jpeg,ico,svg,webp', 'max:512'],
        ]);

        $oldPath = \App\Models\Setting::get('app_favicon_path');
        if ($oldPath && \Illuminate\Support\Facades\Storage::disk('public')->exists($oldPath)) {
            \Illuminate\Support\Facades\Storage::disk('public')->delete($oldPath);
        }

        $ext  = $request->file('favicon')->getClientOriginalExtension();
        $path = $request->file('favicon')->storeAs('branding', 'favicon-' . time() . '.' . $ext, 'public');

        \App\Models\Setting::put('app_favicon_path', $path);

        return back()->with('success', 'Favicon uploaded.');
    }

    public function removeFavicon(): RedirectResponse
    {
        $path = \App\Models\Setting::get('app_favicon_path');
        if ($path && \Illuminate\Support\Facades\Storage::disk('public')->exists($path)) {
            \Illuminate\Support\Facades\Storage::disk('public')->delete($path);
        }
        \App\Models\Setting::forget('app_favicon_path');

        return back()->with('success', 'Favicon removed.');
    }

    public function drive(): View
    {
        $configured = $this->drive->isConfigured();
        $folders    = $this->drive->getAllFolderIds();

        $connection      = ['tested' => false];
        $availableFolders = [];
        if ($configured) {
            $connection = $this->drive->testConnection() + ['tested' => true];

            if ($connection['ok'] ?? false) {
                try {
                    $availableFolders = $this->drive->listFolders();
                } catch (\Throwable) {
                    $availableFolders = [];
                }
            }
        }

        $recentFiles = DriveFile::with('uploader')
            ->orderByDesc('uploaded_at')
            ->limit(10)
            ->get();

        return view('admin.settings.drive', [
            'configured'          => $configured,
            'authMode'            => $this->drive->getActiveAuthMode(),
            'oauthConfigured'     => $this->drive->isOAuthConfigured(),
            'oauthConnected'      => $this->drive->isOAuthConnected(),
            'oauthUserEmail'      => $this->drive->getOAuthUserEmail(),
            'oauthClientId'       => \App\Models\Setting::get('google_oauth_client_id'),
            'serviceAccountEmail' => $this->drive->getServiceAccountEmail(),
            'folders'             => $folders,
            'availableFolders'    => $availableFolders,
            'connection'          => $connection,
            'recentFiles'         => $recentFiles,
        ]);
    }

    public function uploadCredentials(Request $request): RedirectResponse
    {
        $request->validate([
            'credentials' => ['required', 'file', 'mimetypes:application/json,text/plain', 'max:50'],
        ], [
            'credentials.mimetypes' => 'Please upload a valid JSON file.',
        ]);

        $contents = file_get_contents($request->file('credentials')->getRealPath());

        try {
            $this->drive->setCredentialsFromJson($contents);
        } catch (DriveException $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()
            ->route('admin.settings.drive')
            ->with('success', 'Credentials saved. Connection ready to test.');
    }

    public function clearCredentials(): RedirectResponse
    {
        $this->drive->clearCredentials();

        return redirect()
            ->route('admin.settings.drive')
            ->with('success', 'Drive credentials removed.');
    }

    public function saveOAuthCredentials(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'oauth_client_id'     => ['required', 'string', 'max:255'],
            'oauth_client_secret' => ['required', 'string', 'max:255'],
        ]);

        $this->drive->setOAuthClientCredentials($data['oauth_client_id'], $data['oauth_client_secret']);

        return redirect()
            ->route('admin.settings.drive')
            ->with('success', 'OAuth client saved. Click "Connect Google account" to authorize.');
    }

    public function startOAuth(Request $request): \Illuminate\Http\RedirectResponse
    {
        try {
            $url = $this->drive->getAuthorizationUrl(route('admin.settings.drive.oauth.callback'));
        } catch (DriveException $e) {
            return back()->with('error', $e->getMessage());
        }
        return redirect()->away($url);
    }

    public function oauthCallback(Request $request): RedirectResponse
    {
        if ($request->filled('error')) {
            return redirect()
                ->route('admin.settings.drive')
                ->with('error', 'Google rejected the authorization: ' . $request->get('error'));
        }

        if (! $request->filled('code')) {
            return redirect()
                ->route('admin.settings.drive')
                ->with('error', 'No authorization code returned from Google.');
        }

        try {
            $this->drive->exchangeAuthorizationCode(
                $request->get('code'),
                route('admin.settings.drive.oauth.callback'),
            );
        } catch (DriveException $e) {
            return redirect()
                ->route('admin.settings.drive')
                ->with('error', $e->getMessage());
        }

        return redirect()
            ->route('admin.settings.drive')
            ->with('success', 'Connected as ' . ($this->drive->getOAuthUserEmail() ?? 'Google account') . '. Uploads will now use your storage.');
    }

    public function disconnectOAuth(): RedirectResponse
    {
        $this->drive->disconnectOAuth();
        return redirect()
            ->route('admin.settings.drive')
            ->with('success', 'Google account disconnected.');
    }

    public function testConnection(): RedirectResponse
    {
        $result = $this->drive->testConnection();

        if ($result['ok']) {
            return redirect()
                ->route('admin.settings.drive')
                ->with('success', 'Connected as ' . ($result['account_email'] ?? 'service account') . '.');
        }

        return redirect()
            ->route('admin.settings.drive')
            ->with('error', 'Connection failed: ' . ($result['error'] ?? 'unknown error'));
    }

    public function setupFolders(Request $request): RedirectResponse
    {
        $name = $request->validate([
            'root_name' => ['nullable', 'string', 'max:255'],
        ])['root_name'] ?? 'Malayznbeat Platform Storage';

        try {
            $this->drive->setupFolderStructure($name);
        } catch (DriveException $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()
            ->route('admin.settings.drive')
            ->with('success', 'Folder structure created. Files will now be organized by stage.');
    }

    public function saveFolderIds(Request $request): RedirectResponse
    {
        $rules = ['drive_folder_root' => ['nullable', 'string', 'max:128']];
        foreach (GoogleDriveService::STAGE_FOLDERS as $settingKey) {
            $rules[$settingKey] = ['nullable', 'string', 'max:128'];
        }

        $data = $request->validate($rules);

        foreach ($data as $key => $value) {
            if ($value === null || $value === '') {
                Setting::forget($key);
            } else {
                Setting::put($key, $value);
            }
        }

        return redirect()
            ->route('admin.settings.drive')
            ->with('success', 'Folder IDs updated.');
    }
}
