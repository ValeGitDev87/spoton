<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Chat;
use App\Models\Post;
use App\Models\User;
use App\Models\UserBlock;
use App\Services\UserBlockService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UserBlockController extends Controller
{
    public function __construct(private readonly UserBlockService $blocks) {}

    public function index(Request $request): JsonResponse
    {
        $blocked = UserBlock::query()
            ->with('blockedUser')
            ->where('blocker_id', $request->user()->id)
            ->latest()
            ->get()
            ->map(fn (UserBlock $block) => $this->payload($block));

        return response()->json([
            'message' => 'OK',
            'data' => $blocked,
        ]);
    }

    public function store(Request $request, User $user): JsonResponse
    {
        abort_if($user->is_admin || $user->is_suspended, 404);

        $block = $this->blocks->block($request->user(), $user);

        return response()->json([
            'message' => 'Utente bloccato.',
            'data' => $this->payload($block->load('blockedUser')),
        ]);
    }

    public function storeForChat(Request $request, Chat $chat): JsonResponse
    {
        abort_unless($chat->hasParticipant($request->user()->id), 403);

        $blockedId = $chat->user_one_id === $request->user()->id
            ? $chat->user_two_id
            : $chat->user_one_id;
        $blocked = User::query()->findOrFail($blockedId);
        $identityHidden = $chat->shouldMaskIdentityOf($blockedId, $request->user());
        $block = $this->blocks->block($request->user(), $blocked, $identityHidden);

        return response()->json([
            'message' => 'Utente bloccato.',
            'data' => $this->payload($block->load('blockedUser')),
        ]);
    }

    public function storeForPost(Request $request, Post $post): JsonResponse
    {
        abort_if($post->author_id === $request->user()->id, 422, 'Non puoi bloccare te stesso.');

        $block = $this->blocks->block(
            $request->user(),
            $post->author,
            $post->is_anonymous,
        );

        return response()->json([
            'message' => 'Autore bloccato. I suoi contenuti non saranno piu mostrati.',
            'data' => $this->payload($block->load('blockedUser')),
        ]);
    }

    public function destroy(Request $request, UserBlock $userBlock): JsonResponse
    {
        abort_unless($userBlock->blocker_id === $request->user()->id, 404);
        $userBlock->delete();

        return response()->json([
            'message' => 'Utente sbloccato.',
            'data' => ['unblocked' => true],
        ]);
    }

    public function destroyForUser(Request $request, User $user): JsonResponse
    {
        UserBlock::query()
            ->where('blocker_id', $request->user()->id)
            ->where('blocked_id', $user->id)
            ->delete();

        return response()->json([
            'message' => 'Utente sbloccato.',
            'data' => ['unblocked' => true],
        ]);
    }

    private function payload(UserBlock $block): array
    {
        $hidden = $block->identity_hidden;

        return [
            'id' => $block->id,
            'identity_hidden' => $hidden,
            'user' => [
                'id' => $hidden ? null : $block->blockedUser->id,
                'display_name' => $hidden ? 'Utente Ghost' : $block->blockedUser->display_name,
                'avatar_color' => $hidden ? null : $block->blockedUser->avatar_color,
                'avatar_url' => $hidden ? null : $block->blockedUser->avatar_url,
            ],
            'created_at' => $block->created_at?->toISOString(),
        ];
    }
}
