<?php

namespace App\Services;

use App\Exceptions\DriveException;
use App\Models\Setting;
use Google\Client;
use Google\Service\Drive;
use Google\Service\Drive\DriveFile as GoogleDriveFile;
use Google\Service\Exception as GoogleServiceException;
use Throwable;

class GoogleDriveService
{
    public const STAGE_FOLDERS = [
        'inbox'            => 'drive_folder_inbox',
        'assigned'         => 'drive_folder_assigned',
        'in_progress'      => 'drive_folder_in_progress',
        'internal_review'  => 'drive_folder_review',
        'client_approval'  => 'drive_folder_client_approval',
        'revisions'        => 'drive_folder_revisions',
        'approved'         => 'drive_folder_approved',
        'published'        => 'drive_folder_published',
    ];

    public const STAGE_NAMES = [
        'inbox'           => '01_Inbox',
        'assigned'        => '02_Assigned',
        'in_progress'     => '03_InProgress',
        'internal_review' => '04_InternalReview',
        'client_approval' => '05_ClientApproval',
        'revisions'       => '06_Revisions',
        'approved'        => '07_Approved',
        'published'       => '08_Published',
    ];

    private ?Drive $drive = null;
    private ?array $credentials = null;

    public function isConfigured(): bool
    {
        return $this->isOAuthConnected() || ! empty($this->getCredentialsArray());
    }

    public function isServiceAccountConfigured(): bool
    {
        return ! empty($this->getCredentialsArray());
    }

    public function getActiveAuthMode(): string
    {
        if ($this->isOAuthConnected()) return 'oauth';
        if ($this->isServiceAccountConfigured()) return 'service_account';
        return 'none';
    }

    public function getServiceAccountEmail(): ?string
    {
        $creds = $this->getCredentialsArray();
        return $creds['client_email'] ?? null;
    }

    public function getRootFolderId(): ?string
    {
        return Setting::get('drive_folder_root');
    }

    public function getStageFolderId(string $stage): ?string
    {
        $key = self::STAGE_FOLDERS[$stage] ?? null;
        return $key ? Setting::get($key) : null;
    }

    /** @return array<string, ?string> */
    public function getAllFolderIds(): array
    {
        $out = ['root' => Setting::get('drive_folder_root')];
        foreach (self::STAGE_FOLDERS as $stage => $settingKey) {
            $out[$stage] = Setting::get($settingKey);
        }
        return $out;
    }

    public function setCredentialsFromJson(string $json): void
    {
        $decoded = json_decode($json, true);

        if (! is_array($decoded)) {
            throw DriveException::invalidCredentials('File is not valid JSON.');
        }

        if (($decoded['type'] ?? null) !== 'service_account') {
            throw DriveException::invalidCredentials('Expected a service-account credentials file.');
        }

        foreach (['client_email', 'private_key', 'project_id'] as $required) {
            if (empty($decoded[$required])) {
                throw DriveException::invalidCredentials("Missing required field: {$required}");
            }
        }

        Setting::put('drive_credentials', $json, encrypted: true);
        Setting::put('drive_service_account_email', $decoded['client_email']);

        $this->resetClient();
    }

    public function clearCredentials(): void
    {
        Setting::forget('drive_credentials');
        Setting::forget('drive_service_account_email');
        $this->resetClient();
    }

    public function testConnection(): array
    {
        try {
            $about = $this->drive()->about->get(['fields' => 'user,storageQuota']);

            $quota = $about->getStorageQuota();
            $usage = $quota?->getUsage();
            $limit = $quota?->getLimit();

            return [
                'ok'              => true,
                'account_email'   => $about->getUser()?->getEmailAddress(),
                'storage_used'    => $usage !== null ? (int) $usage : null,
                'storage_limit'   => $limit !== null ? (int) $limit : null,
            ];
        } catch (Throwable $e) {
            return ['ok' => false, 'error' => $this->humanizeError($e)];
        }
    }

    public function uploadFile(string $localPath, string $driveFolderId, string $fileName): string
    {
        if (! file_exists($localPath)) {
            throw DriveException::operationFailed("Local file not found: {$localPath}");
        }

        try {
            $metadata = new GoogleDriveFile([
                'name'    => $fileName,
                'parents' => [$driveFolderId],
            ]);

            $file = $this->drive()->files->create($metadata, [
                'data'       => file_get_contents($localPath),
                'mimeType'   => mime_content_type($localPath) ?: 'application/octet-stream',
                'uploadType' => 'multipart',
                'fields'     => 'id,name,size,mimeType',
            ]);

            return $file->getId();
        } catch (Throwable $e) {
            throw $this->wrapException($e);
        }
    }

    public function downloadFile(string $driveFileId, string $localPath): void
    {
        try {
            $response = $this->drive()->files->get($driveFileId, ['alt' => 'media']);
            file_put_contents($localPath, $response->getBody()->getContents());
        } catch (Throwable $e) {
            throw $this->wrapException($e);
        }
    }

    public function moveFile(string $driveFileId, string $newFolderId): void
    {
        try {
            $file = $this->drive()->files->get($driveFileId, ['fields' => 'parents']);
            $previousParents = implode(',', $file->getParents() ?? []);

            $this->drive()->files->update(
                $driveFileId,
                new GoogleDriveFile,
                [
                    'addParents'    => $newFolderId,
                    'removeParents' => $previousParents,
                    'fields'        => 'id,parents',
                ]
            );
        } catch (Throwable $e) {
            throw $this->wrapException($e);
        }
    }

    public function deleteFile(string $driveFileId): void
    {
        try {
            $this->drive()->files->delete($driveFileId);
        } catch (Throwable $e) {
            throw $this->wrapException($e);
        }
    }

    public function createFolder(string $name, ?string $parentId = null): string
    {
        try {
            $metadata = new GoogleDriveFile([
                'name'     => $name,
                'mimeType' => 'application/vnd.google-apps.folder',
                'parents'  => $parentId ? [$parentId] : [],
            ]);

            $folder = $this->drive()->files->create($metadata, ['fields' => 'id,name']);
            return $folder->getId();
        } catch (Throwable $e) {
            throw $this->wrapException($e);
        }
    }

    /**
     * List every folder the service account can see.
     *
     * @return array<int, array{id:string,name:string,parents:array<string>}>
     */
    public function listFolders(int $limit = 200): array
    {
        try {
            $folders   = [];
            $pageToken = null;

            do {
                $params = [
                    'q'        => "mimeType = 'application/vnd.google-apps.folder' and trashed = false",
                    'fields'   => 'nextPageToken, files(id,name,parents)',
                    'pageSize' => 100,
                    'orderBy'  => 'name',
                ];
                if ($pageToken) {
                    $params['pageToken'] = $pageToken;
                }

                $result = $this->drive()->files->listFiles($params);

                foreach ($result->getFiles() as $f) {
                    $folders[] = [
                        'id'      => $f->getId(),
                        'name'    => $f->getName(),
                        'parents' => $f->getParents() ?? [],
                    ];
                    if (count($folders) >= $limit) {
                        return $folders;
                    }
                }

                $pageToken = $result->getNextPageToken();
            } while ($pageToken);

            return $folders;
        } catch (Throwable $e) {
            throw $this->wrapException($e);
        }
    }

    public function findFolderByName(string $name, ?string $parentId = null): ?string
    {
        try {
            $q = "name = '" . addslashes($name) . "' and mimeType = 'application/vnd.google-apps.folder' and trashed = false";
            if ($parentId) {
                $q .= " and '{$parentId}' in parents";
            }

            $result = $this->drive()->files->listFiles([
                'q'        => $q,
                'fields'   => 'files(id,name)',
                'pageSize' => 1,
            ]);

            $files = $result->getFiles();
            return $files ? $files[0]->getId() : null;
        } catch (Throwable $e) {
            throw $this->wrapException($e);
        }
    }

    public function getFileMetadata(string $driveFileId): array
    {
        try {
            $file = $this->drive()->files->get($driveFileId, [
                'fields' => 'id,name,size,mimeType,createdTime,modifiedTime,parents,webViewLink',
            ]);

            return [
                'id'            => $file->getId(),
                'name'          => $file->getName(),
                'size'          => (int) $file->getSize(),
                'mime_type'     => $file->getMimeType(),
                'created_time'  => $file->getCreatedTime(),
                'modified_time' => $file->getModifiedTime(),
                'parents'       => $file->getParents() ?? [],
                'web_view_link' => $file->getWebViewLink(),
            ];
        } catch (Throwable $e) {
            throw $this->wrapException($e);
        }
    }

    public function getDownloadUrl(string $driveFileId): string
    {
        return "https://drive.google.com/uc?export=download&id={$driveFileId}";
    }

    public function getWebViewLink(string $driveFileId): string
    {
        return "https://drive.google.com/file/d/{$driveFileId}/view";
    }

    /**
     * Idempotently create the root + 8 stage folders, persisting their IDs to settings.
     *
     * @return array<string,string> stage => folder id
     */
    public function setupFolderStructure(string $rootName = 'Malayznbeat Platform Storage'): array
    {
        $created = [];

        $rootId = Setting::get('drive_folder_root');
        if (! $rootId) {
            $existing = $this->findFolderByName($rootName);
            $rootId = $existing ?: $this->createFolder($rootName);
            Setting::put('drive_folder_root', $rootId);
        }
        $created['root'] = $rootId;

        foreach (self::STAGE_FOLDERS as $stage => $settingKey) {
            $folderId = Setting::get($settingKey);

            if (! $folderId) {
                $folderName = self::STAGE_NAMES[$stage];
                $existing   = $this->findFolderByName($folderName, $rootId);
                $folderId   = $existing ?: $this->createFolder($folderName, $rootId);
                Setting::put($settingKey, $folderId);
            }

            $created[$stage] = $folderId;
        }

        return $created;
    }

    // -------------------------------------------------------------------
    // OAuth (user-impersonation) support
    // -------------------------------------------------------------------

    public function isOAuthConfigured(): bool
    {
        return ! empty(Setting::get('google_oauth_client_id'))
            && ! empty(Setting::get('google_oauth_client_secret'));
    }

    public function isOAuthConnected(): bool
    {
        return $this->isOAuthConfigured()
            && ! empty(Setting::get('google_oauth_refresh_token'));
    }

    public function getOAuthUserEmail(): ?string
    {
        return Setting::get('google_oauth_user_email');
    }

    public function setOAuthClientCredentials(string $clientId, string $clientSecret): void
    {
        Setting::put('google_oauth_client_id', $clientId);
        Setting::put('google_oauth_client_secret', $clientSecret, encrypted: true);
        $this->resetClient();
    }

    public function getAuthorizationUrl(string $redirectUri): string
    {
        if (! $this->isOAuthConfigured()) {
            throw DriveException::operationFailed('OAuth client ID and secret are not configured.');
        }

        $client = new Client;
        $client->setApplicationName('Malayznbeat Content Hub');
        $client->setClientId(Setting::get('google_oauth_client_id'));
        $client->setClientSecret(Setting::get('google_oauth_client_secret'));
        $client->setRedirectUri($redirectUri);
        $client->addScope(Drive::DRIVE);
        $client->addScope('https://www.googleapis.com/auth/userinfo.email');
        $client->setAccessType('offline');     // get refresh token
        $client->setPrompt('consent');         // force re-consent so we always get a refresh token
        $client->setIncludeGrantedScopes(true);

        return $client->createAuthUrl();
    }

    public function exchangeAuthorizationCode(string $code, string $redirectUri): void
    {
        if (! $this->isOAuthConfigured()) {
            throw DriveException::operationFailed('OAuth client ID and secret are not configured.');
        }

        try {
            $client = new Client;
            $client->setClientId(Setting::get('google_oauth_client_id'));
            $client->setClientSecret(Setting::get('google_oauth_client_secret'));
            $client->setRedirectUri($redirectUri);
            $client->addScope(Drive::DRIVE);

            $token = $client->fetchAccessTokenWithAuthCode($code);

            if (isset($token['error'])) {
                throw DriveException::operationFailed("OAuth exchange failed: " . ($token['error_description'] ?? $token['error']));
            }

            $this->persistOAuthToken($token);

            // Fetch user email so we can show it in settings
            $oauth2 = new \Google\Service\Oauth2($client);
            $userInfo = $oauth2->userinfo->get();
            Setting::put('google_oauth_user_email', $userInfo->getEmail());

            $this->resetClient();
        } catch (DriveException $e) {
            throw $e;
        } catch (Throwable $e) {
            throw DriveException::operationFailed('OAuth exchange failed: ' . $e->getMessage());
        }
    }

    public function disconnectOAuth(): void
    {
        Setting::forget('google_oauth_access_token');
        Setting::forget('google_oauth_refresh_token');
        Setting::forget('google_oauth_token_expires_at');
        Setting::forget('google_oauth_user_email');
        $this->resetClient();
    }

    private function persistOAuthToken(array $token): void
    {
        if (! empty($token['access_token'])) {
            Setting::put('google_oauth_access_token', $token['access_token'], encrypted: true);
        }
        if (! empty($token['refresh_token'])) {
            Setting::put('google_oauth_refresh_token', $token['refresh_token'], encrypted: true);
        }
        if (! empty($token['expires_in'])) {
            Setting::put('google_oauth_token_expires_at', (string) (time() + (int) $token['expires_in']));
        }
    }

    // -------------------------------------------------------------------
    // Auth selection (OAuth wins over service account)
    // -------------------------------------------------------------------

    private function drive(): Drive
    {
        if ($this->drive !== null) {
            return $this->drive;
        }

        try {
            if ($this->isOAuthConnected()) {
                return $this->drive = new Drive($this->buildOAuthClient());
            }

            $credentials = $this->getCredentialsArray();
            if (empty($credentials)) {
                throw DriveException::notConfigured();
            }

            $client = new Client;
            $client->setApplicationName('Malayznbeat Content Hub');
            $client->setAuthConfig($credentials);
            $client->addScope(Drive::DRIVE);

            return $this->drive = new Drive($client);
        } catch (DriveException $e) {
            throw $e;
        } catch (Throwable $e) {
            throw DriveException::invalidCredentials($e->getMessage());
        }
    }

    private function buildOAuthClient(): Client
    {
        $client = new Client;
        $client->setApplicationName('Malayznbeat Content Hub');
        $client->setClientId(Setting::get('google_oauth_client_id'));
        $client->setClientSecret(Setting::get('google_oauth_client_secret'));
        $client->addScope(Drive::DRIVE);

        $accessToken  = Setting::get('google_oauth_access_token');
        $refreshToken = Setting::get('google_oauth_refresh_token');
        $expiresAt    = (int) Setting::get('google_oauth_token_expires_at', '0');

        $client->setAccessToken([
            'access_token'  => $accessToken ?: '',
            'refresh_token' => $refreshToken,
            'expires_in'    => max(0, $expiresAt - time()),
            'created'       => time(),
        ]);

        if ($client->isAccessTokenExpired() && $refreshToken) {
            $newToken = $client->fetchAccessTokenWithRefreshToken($refreshToken);
            if (! empty($newToken['error'])) {
                throw DriveException::invalidCredentials('OAuth refresh failed: ' . ($newToken['error_description'] ?? $newToken['error']));
            }
            $this->persistOAuthToken($newToken);
        }

        return $client;
    }

    private function getCredentialsArray(): array
    {
        if ($this->credentials !== null) {
            return $this->credentials;
        }

        $json = Setting::get('drive_credentials');
        if (! $json) {
            return $this->credentials = [];
        }

        $decoded = json_decode($json, true);
        return $this->credentials = is_array($decoded) ? $decoded : [];
    }

    private function resetClient(): void
    {
        $this->drive = null;
        $this->credentials = null;
    }

    private function wrapException(Throwable $e): DriveException
    {
        if ($e instanceof DriveException) {
            return $e;
        }

        // Log the raw Google error so admin can diagnose precisely
        \Illuminate\Support\Facades\Log::warning('Drive API error', [
            'class'   => get_class($e),
            'code'    => method_exists($e, 'getCode') ? $e->getCode() : null,
            'message' => $e->getMessage(),
        ]);

        if ($e instanceof GoogleServiceException) {
            $code    = $e->getCode();
            $message = strtolower($e->getMessage());

            // 404 "File not found" usually means the configured destination folder ID
            // doesn't exist in the current authenticated user's Drive (common after
            // switching from service-account auth to OAuth). Give an actionable hint.
            if ($code === 404 && str_contains($message, 'file not found')) {
                return DriveException::operationFailed(
                    'A configured folder is unreachable. Open admin → settings → Drive and re-pick each stage folder from the dropdown.'
                );
            }

            // Service accounts have 0 bytes of storage. Files uploaded to a folder
            // shared with a service account are *owned* by it and immediately fail.
            // Fix: use a Shared Drive (Google Workspace) or switch to OAuth.
            if ($code === 403 && (
                str_contains($message, 'service accounts do not have storage')
                || str_contains($message, 'storagequotaexceeded')
                || str_contains($message, 'storage quota')
            )) {
                return DriveException::serviceAccountStorageLimit();
            }

            if ($code === 403 && (
                str_contains($message, 'rate limit')
                || str_contains($message, 'ratelimit')
                || str_contains($message, 'usage limits')
                || str_contains($message, 'quota')
            )) {
                return DriveException::quotaExceeded($this->humanizeError($e));
            }

            if ($code === 401) {
                return DriveException::invalidCredentials($e->getMessage());
            }
        }

        return DriveException::operationFailed($this->humanizeError($e));
    }

    private function humanizeError(Throwable $e): string
    {
        $msg = $e->getMessage();

        // The Google client wraps cURL errors in JSON-ish strings — pull out the gist.
        if (preg_match('/"message":\s*"([^"]+)"/', $msg, $m)) {
            return $m[1];
        }

        return $msg;
    }
}
