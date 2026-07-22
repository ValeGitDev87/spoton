<?php

namespace App\Services;

use App\Models\Post;
use App\Models\Report;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class AccountDeletionService
{
    public function __construct(private readonly PostAudioService $postAudioService) {}

    public function delete(User $user): void
    {
        $posts = $user->posts()
            ->get(['id', 'audio_disk', 'audio_path']);
        $postIds = $posts->pluck('id');
        $localPhotos = $this->localPublicPaths([
            $user->avatar_url,
            ...($user->photos ?? []),
        ]);

        DB::transaction(function () use ($user, $postIds): void {
            $user->tokens()->delete();

            Report::query()
                ->where(function ($query) use ($user, $postIds): void {
                    $query->where(function ($query) use ($user): void {
                        $query->where('reportable_type', 'user')
                            ->where('reportable_id', $user->id);
                    });

                    if ($postIds->isNotEmpty()) {
                        $query->orWhere(function ($query) use ($postIds): void {
                            $query->where('reportable_type', 'post')
                                ->whereIn('reportable_id', $postIds);
                        });
                    }
                })
                ->delete();

            DB::table('password_reset_tokens')->where('email', $user->email)->delete();
            $user->delete();
        });

        $posts->each(fn (Post $post) => $this->postAudioService->deleteForPost($post));

        foreach ($localPhotos as $path) {
            Storage::disk('public')->delete($path);
        }
    }

    /**
     * @param  array<int, mixed>  $urls
     * @return array<int, string>
     */
    private function localPublicPaths(array $urls): array
    {
        return collect($urls)
            ->filter(fn ($url): bool => is_string($url) && str_starts_with($url, '/storage/'))
            ->map(fn (string $url): string => substr($url, strlen('/storage/')))
            ->unique()
            ->values()
            ->all();
    }
}
