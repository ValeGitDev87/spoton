<?php

namespace App\Jobs\Push;

use App\Models\Post;
use App\Models\User;
use App\Services\Push\PushNotificationService;
use App\Support\Push\PushNotificationType;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable as FoundationQueueable;

class SendPostMentionNotifications implements ShouldQueue
{
    use FoundationQueueable;

    public int $tries = 3;

    public int $timeout = 90;

    /**
     * @param  array<int, string>  $recipientIds
     */
    public function __construct(
        private readonly string $postId,
        private readonly string $authorId,
        private readonly array $recipientIds = [],
        private readonly bool $everyone = false,
        private readonly bool $expandEveryone = false,
    ) {
        $this->onQueue('notifications');
        $this->afterCommit();
    }

    public function handle(PushNotificationService $push): void
    {
        $post = Post::query()->with('author')->find($this->postId);

        if (! $post || $post->status !== 'active') {
            return;
        }

        if ($this->everyone && $this->expandEveryone) {
            User::query()
                ->where('id', '!=', $this->authorId)
                ->where('is_admin', false)
                ->where('is_suspended', false)
                ->select('id')
                ->chunkById(200, function ($users): void {
                    self::dispatch(
                        $this->postId,
                        $this->authorId,
                        $users->pluck('id')->all(),
                        true,
                        false,
                    );
                });

            return;
        }

        $title = $this->everyone
            ? 'Nuovo annuncio per tutti su SpotOn'
            : 'Ti hanno taggato su SpotOn';
        $actorName = $post->is_anonymous ? 'Qualcuno' : $post->author->display_name;
        $body = $this->everyone
            ? $actorName.' ha pubblicato un annuncio menzionando tutti.'
            : $actorName.' ti ha menzionato in un annuncio.';

        User::query()
            ->whereIn('id', array_values(array_unique($this->recipientIds)))
            ->where('id', '!=', $this->authorId)
            ->where('is_admin', false)
            ->where('is_suspended', false)
            ->get()
            ->each(function (User $user) use ($push, $title, $body): void {
                $push->sendToUser($user, $title, $body, [
                    'type' => PushNotificationType::USER_MENTIONED,
                    'source' => 'post',
                    'post_id' => $this->postId,
                    'mention_scope' => $this->everyone ? 'everyone' : 'user',
                ]);
            });
    }
}
