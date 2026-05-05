<?php

namespace App\Exceptions;

use App\Enums\ArticleStage;
use App\Models\User;
use Exception;

class WorkflowException extends Exception
{
    public static function invalidStage(ArticleStage $current, ArticleStage $expected): self
    {
        return new self("Article is in '{$current->label()}', expected '{$expected->label()}'.");
    }

    public static function invalidStageOneOf(ArticleStage $current, array $expected): self
    {
        $list = implode(', ', array_map(fn (ArticleStage $s) => $s->label(), $expected));
        return new self("Article is in '{$current->label()}', expected one of: {$list}.");
    }

    public static function notAuthorized(?User $user, string $action): self
    {
        $name = $user?->name ?? 'guest';
        return new self("{$name} is not authorized to {$action}.");
    }

    public static function writerNotAssigned(): self
    {
        return new self('No tech writer is assigned to this article.');
    }

    public static function invalidWriter(): self
    {
        return new self('Selected user is not a tech writer.');
    }

    public static function noFile(): self
    {
        return new self('No file is attached to this article.');
    }
}
