<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Like;
use App\Models\Post;
use App\Models\PostCommunityVote;
use App\Models\PostIWasThere;
use App\Models\PresenceSession;
use App\Support\PostCategory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class PostEngagementController extends Controller
{
    public function toggleLike(Request $request, Post $post): JsonResponse
    {
        $liked = DB::transaction(function () use ($request, $post): bool {
            $lockedPost = Post::query()->lockForUpdate()->findOrFail($post->id);

            $like = Like::query()
                ->where('post_id', $lockedPost->id)
                ->where('user_id', $request->user()->id)
                ->first();

            if ($like) {
                $like->delete();
                $lockedPost->update(['like_count' => max(0, $lockedPost->likes()->count())]);

                return false;
            }

            Like::query()->create([
                'post_id' => $lockedPost->id,
                'user_id' => $request->user()->id,
            ]);

            $lockedPost->update(['like_count' => $lockedPost->likes()->count()]);

            return true;
        });

        $post->refresh();

        return response()->json([
            'message' => 'OK',
            'data' => [
                'liked' => $liked,
                'like_count' => $post->like_count,
            ],
        ]);
    }

    public function toggleIoCero(Request $request, Post $post): JsonResponse
    {
        abort_unless($post->category === PostCategory::SPOTTED_LOVE, 422, 'Io c\'ero e disponibile solo per Spotted / Amore.');
        abort_if($post->author_id === $request->user()->id, 403, 'Il proprietario non puo usare Io c\'ero sul proprio post.');

        $ioCero = DB::transaction(function () use ($request, $post): bool {
            $lockedPost = Post::query()->lockForUpdate()->findOrFail($post->id);

            $record = PostIWasThere::query()
                ->where('post_id', $lockedPost->id)
                ->where('user_id', $request->user()->id)
                ->first();

            if ($record) {
                $record->delete();
                $count = max(0, $lockedPost->iWasThere()->count());
                $lockedPost->update([
                    'io_cero_count' => $count,
                    'spot_on_count' => $count,
                ]);

                return false;
            }

            PostIWasThere::query()->create([
                'post_id' => $lockedPost->id,
                'user_id' => $request->user()->id,
            ]);

            $count = $lockedPost->iWasThere()->count();
            $lockedPost->update([
                'io_cero_count' => $count,
                'spot_on_count' => $count,
            ]);

            return true;
        });

        $post->refresh();

        return response()->json([
            'message' => 'OK',
            'data' => [
                'io_cero' => $ioCero,
                'io_cero_count' => $post->io_cero_count,
                'spot_on_count' => $post->spot_on_count,
            ],
        ]);
    }

    public function communityVote(Request $request, Post $post): JsonResponse
    {
        $data = $request->validate([
            'vote' => ['required', 'string', Rule::in([
                PostCommunityVote::VOTE_CONFIRM,
                PostCommunityVote::VOTE_FALSE,
            ])],
        ]);

        abort_unless($post->category === PostCategory::WEATHER_TRANSPORT, 422, 'La verifica Community e disponibile solo per Meteo e trasporti.');
        abort_if($post->author_id === $request->user()->id, 403, 'Non puoi verificare una tua segnalazione.');
        abort_unless($post->isActive(), 422, 'Questa segnalazione non e piu attiva.');

        $isPresent = PresenceSession::query()
            ->where('user_id', $request->user()->id)
            ->where('location_id', $post->location_id)
            ->whereNull('ended_at')
            ->where('last_ping_at', '>=', now()->subMinutes(5))
            ->exists();

        abort_unless($isPresent, 403, 'Devi essere presente nel luogo della segnalazione per votare.');

        $result = DB::transaction(function () use ($data, $request, $post): array {
            $lockedPost = Post::query()->lockForUpdate()->findOrFail($post->id);

            abort_unless($lockedPost->isActive(), 422, 'Questa segnalazione non e piu attiva.');

            PostCommunityVote::query()->updateOrCreate(
                [
                    'post_id' => $lockedPost->id,
                    'user_id' => $request->user()->id,
                ],
                ['vote' => $data['vote']],
            );

            $confirmCount = $lockedPost->communityVotes()
                ->where('vote', PostCommunityVote::VOTE_CONFIRM)
                ->count();
            $falseCount = $lockedPost->communityVotes()
                ->where('vote', PostCommunityVote::VOTE_FALSE)
                ->count();

            $communityStatus = $falseCount >= 2
                ? 'rejected'
                : ($confirmCount >= 4 ? 'verified' : 'pending');
            $status = $communityStatus === 'rejected' ? 'removed' : $lockedPost->status;

            $lockedPost->update([
                'community_confirm_count' => $confirmCount,
                'community_false_count' => $falseCount,
                'community_status' => $communityStatus,
                'status' => $status,
            ]);

            return [
                'vote' => $data['vote'],
                'confirm_count' => $confirmCount,
                'false_count' => $falseCount,
                'community_status' => $communityStatus,
                'status' => $status,
                'is_active' => $lockedPost->refresh()->isActive(),
            ];
        });

        return response()->json([
            'message' => $result['community_status'] === 'rejected'
                ? 'Segnalazione nascosta dalla Community.'
                : 'Voto registrato.',
            'data' => [
                'community_vote_by_me' => $result['vote'],
                'community_confirm_count' => $result['confirm_count'],
                'community_false_count' => $result['false_count'],
                'community_status' => $result['community_status'],
                'status' => $result['status'],
                'is_active' => $result['is_active'],
            ],
        ]);
    }

    public function ioCeroUsers(Request $request, Post $post): JsonResponse
    {
        abort_unless($post->author_id === $request->user()->id || $request->user()->is_admin, 403);

        $users = $post->iWasThere()
            ->with('user')
            ->latest()
            ->paginate((int) $request->query('per_page', 25));

        return response()->json([
            'message' => 'OK',
            'data' => collect($users->items())->map(fn (PostIWasThere $record) => [
                'id' => $record->user->id,
                'display_name' => $record->user->display_name,
                'avatar_color' => $record->user->avatar_color,
                'created_at' => $record->created_at?->toISOString(),
            ])->values(),
            'meta' => [
                'current_page' => $users->currentPage(),
                'last_page' => $users->lastPage(),
                'per_page' => $users->perPage(),
                'total' => $users->total(),
            ],
        ]);
    }
}
