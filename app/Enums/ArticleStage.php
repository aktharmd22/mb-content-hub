<?php

namespace App\Enums;

enum ArticleStage: string
{
    case INBOX            = 'inbox';
    case ASSIGNED         = 'assigned';
    case IN_PROGRESS      = 'in_progress';
    case INTERNAL_REVIEW  = 'internal_review';
    case CLIENT_APPROVAL  = 'client_approval';
    case REVISIONS        = 'revisions';
    case APPROVED         = 'approved';
    case PUBLISHED        = 'published';

    public function label(): string
    {
        return match ($this) {
            self::INBOX           => 'New submission',
            self::ASSIGNED        => 'Assigned',
            self::IN_PROGRESS     => 'Writing',
            self::INTERNAL_REVIEW => 'Internal review',
            self::CLIENT_APPROVAL => 'Sales review',
            self::REVISIONS       => 'Correction',
            self::APPROVED        => 'Verified',
            self::PUBLISHED       => 'Published',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::INBOX           => 'gray',
            self::ASSIGNED        => 'blue',
            self::IN_PROGRESS     => 'indigo',
            self::INTERNAL_REVIEW => 'amber',
            self::CLIENT_APPROVAL => 'pink',
            self::REVISIONS       => 'orange',
            self::APPROVED        => 'emerald',
            self::PUBLISHED       => 'green',
        };
    }

    public function order(): int
    {
        return match ($this) {
            self::INBOX           => 1,
            self::ASSIGNED        => 2,
            self::IN_PROGRESS     => 3,
            self::INTERNAL_REVIEW => 4,
            self::CLIENT_APPROVAL => 5,
            self::REVISIONS       => 6,
            self::APPROVED        => 7,
            self::PUBLISHED       => 8,
        };
    }

    public function isTerminal(): bool
    {
        return $this === self::PUBLISHED;
    }

    /** @return self[] */
    public static function all(): array
    {
        return self::cases();
    }
}
