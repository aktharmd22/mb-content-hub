<?php

namespace App\Exceptions;

use Exception;

class DriveException extends Exception
{
    public static function notConfigured(): self
    {
        return new self('Google Drive is not configured. Upload service-account credentials in admin settings.');
    }

    public static function invalidCredentials(string $detail = ''): self
    {
        return new self('Invalid Google Drive credentials.' . ($detail ? " {$detail}" : ''));
    }

    public static function quotaExceeded(string $detail = ''): self
    {
        return new self('Google Drive API quota exceeded. Try again later.' . ($detail ? " ({$detail})" : ''));
    }

    public static function serviceAccountStorageLimit(): self
    {
        return new self(
            'Google service accounts have no storage of their own. ' .
            'To upload files you must either (1) use a Shared Drive (requires Google Workspace) and share it with the service account, ' .
            'or (2) switch to OAuth user-impersonation. ' .
            'See https://developers.google.com/drive/api/guides/about-shareddrives'
        );
    }

    public static function networkError(string $detail = ''): self
    {
        return new self('Could not reach Google Drive.' . ($detail ? " {$detail}" : ''));
    }

    public static function operationFailed(string $detail): self
    {
        return new self("Google Drive operation failed: {$detail}");
    }
}
