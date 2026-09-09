<?php

namespace App\Services;

use App\Models\User;
use App\Models\UserBlock;
use Illuminate\Support\Collection;

class UserBlockService
{
    public function block(User $blocker, User $blocked, bool $identityHidden = false): UserBlock
    {
        abort_if($blocker->is($blocked), 422, 'Non puoi bloccare te stesso.');

        $block = UserBlock::query()->firstOrCreate(
            [
                'blocker_id' => $blocker->id,
                'blocked_id' => $blocked->id,
            ],
            ['identity_hidden' => $identityHidden],
        );

        if (! $identityHidden && $block->identity_hidden) {
            $block->update(['identity_hidden' => false]);
        }

        return $block->refresh();
    }

    public function isBlockedBetween(string $firstUserId, string $secondUserId): bool
    {
        if ($firstUserId === $secondUserId) {
            return false;
        }

        return UserBlock::query()
            ->where(function ($query) use ($firstUserId, $secondUserId): void {
                $query->where('blocker_id', $firstUserId)
                    ->where('blocked_id', $secondUserId);
            })
            ->orWhere(function ($query) use ($firstUserId, $secondUserId): void {
                $query->where('blocker_id', $secondUserId)
                    ->where('blocked_id', $firstUserId);
            })
            ->exists();
    }

    public function isBlockedBy(string $blockerId, string $blockedId): bool
    {
        return UserBlock::query()
            ->where('blocker_id', $blockerId)
            ->where('blocked_id', $blockedId)
            ->exists();
    }

    public function ensureInteractionAllowed(string $firstUserId, string $secondUserId): void
    {
        abort_if(
            $this->isBlockedBetween($firstUserId, $secondUserId),
            403,
            'Questa interazione non e disponibile perche uno dei profili ha bloccato l altro.',
        );
    }

    /** @return Collection<int, string> */
    public function blockedUserIds(string $userId): Collection
    {
        return UserBlock::query()
            ->where('blocker_id', $userId)
            ->pluck('blocked_id')
            ->merge(
                UserBlock::query()
                    ->where('blocked_id', $userId)
                    ->pluck('blocker_id'),
            )
            ->unique()
            ->values();
    }
}
