<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Challenge;
use App\Models\Chat;
use App\Models\Comment;
use App\Models\Post;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class ChallengeController extends Controller
{
    public function pending(Request $request): JsonResponse
    {
        $challenges = Challenge::query()
            ->with(['post.location', 'challenger', 'targetUser', 'sourceComment.author', 'counterProposer'])
            ->where(function (Builder $query) use ($request): void {
                $query
                    ->where(fn (Builder $inner) => $inner
                        ->where('target_user_id', $request->user()->id)
                        ->whereIn('status', [Challenge::STATUS_PENDING, Challenge::STATUS_REJECTED]))
                    ->orWhere(fn (Builder $inner) => $inner
                        ->where('status', Challenge::STATUS_COUNTER_PENDING)
                        ->where(function (Builder $reviewer) use ($request): void {
                            $reviewer
                                ->where(fn (Builder $classic) => $classic
                                    ->where('origin', Challenge::ORIGIN_CLASSIC)
                                    ->where('target_user_id', $request->user()->id))
                                ->orWhere(fn (Builder $inverted) => $inverted
                                    ->where('origin', Challenge::ORIGIN_INVERTED)
                                    ->where('challenger_id', $request->user()->id));
                        }));
            })
            ->latest()
            ->paginate((int) $request->query('per_page', 25));

        return response()->json([
            'message' => 'OK',
            'data' => collect($challenges->items())->map(fn (Challenge $challenge) => $this->challengePayload($challenge))->values(),
            'meta' => [
                'current_page' => $challenges->currentPage(),
                'last_page' => $challenges->lastPage(),
                'per_page' => $challenges->perPage(),
                'total' => $challenges->total(),
            ],
        ]);
    }

    public function verifyClassic(Request $request, Post $post): JsonResponse
    {
        abort_if($post->author_id === $request->user()->id, 422, 'Non puoi verificare un tuo post.');
        abort_if(! $post->secret_answer_hash, 422, 'Questo post non ha una domanda di verifica.');

        $data = $request->validate([
            'answer' => ['required', 'string', 'max:255'],
        ]);

        if (! Hash::check($this->normalizeAnswer($data['answer']), $post->secret_answer_hash)) {
            throw ValidationException::withMessages([
                'answer' => ['Risposta non corretta.'],
            ]);
        }

        $result = DB::transaction(function () use ($request, $post): array {
            $lockedPost = Post::query()->lockForUpdate()->findOrFail($post->id);
            $nextCount = $lockedPost->spot_on_count + 1;

            $lockedPost->update([
                'is_anonymous' => false,
                'spot_on_count' => $nextCount,
                'io_cero_count' => $nextCount,
            ]);

            $request->user()->increment('karma');

            $chat = $this->createOrReuseChat($lockedPost->author_id, $request->user()->id, [
                'origin_post_id' => $lockedPost->id,
            ]);

            return [
                'post' => $lockedPost->refresh(),
                'chat' => $chat,
                'karma' => $request->user()->fresh()->karma,
            ];
        });

        return response()->json([
            'message' => 'OK',
            'data' => [
                'verified' => true,
                'post_id' => $result['post']->id,
                'spot_on_count' => $result['post']->spot_on_count,
                'chat_id' => $result['chat']->id,
                'karma' => $result['karma'],
            ],
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'post_id' => ['required', 'uuid', 'exists:posts,id'],
            'target_type' => ['required', 'in:post_author,comment_author'],
            'source_comment_id' => ['required_if:target_type,comment_author', 'nullable', 'uuid', 'exists:comments,id'],
            'mode' => ['nullable', 'in:direct,question'],
            'question' => ['required_if:mode,question', 'nullable', 'string', 'max:500'],
            'answer' => ['required_if:mode,question', 'nullable', 'string', 'max:255'],
        ]);

        $mode = $data['mode'] ?? 'question';
        $post = Post::query()->findOrFail($data['post_id']);
        $targetUserId = $this->resolveTargetUserId($post, $data);

        abort_if($targetUserId === $request->user()->id, 422, 'Non puoi sfidare te stesso.');
        abort_if(
            $data['target_type'] === Challenge::TARGET_POST_AUTHOR
            && $post->secret_answer_hash,
            422,
            'Questo post usa gia la verifica classica: rispondi alla domanda del post.',
        );

        if ($mode === 'direct') {
            $chat = DB::transaction(function () use ($request, $post, $targetUserId, $data): Chat {
                if ($data['target_type'] === Challenge::TARGET_POST_AUTHOR) {
                    $post->increment('spot_on_count');
                    $post->increment('io_cero_count');
                }

                return $this->createOrReuseChat($targetUserId, $request->user()->id, [
                    'origin_post_id' => $post->id,
                ]);
            });

            return response()->json([
                'message' => 'OK',
                'data' => [
                    'direct' => true,
                    'chat_id' => $chat->id,
                ],
            ], 201);
        }

        $challenge = Challenge::query()->create([
            'post_id' => $post->id,
            'origin' => Challenge::ORIGIN_INVERTED,
            'challenger_id' => $request->user()->id,
            'target_type' => $data['target_type'],
            'target_user_id' => $targetUserId,
            'source_comment_id' => $data['source_comment_id'] ?? null,
            'question' => $data['question'],
            'answer_hash' => Hash::make($this->normalizeAnswer($data['answer'])),
            'status' => Challenge::STATUS_PENDING,
        ])->load(['post.location', 'challenger', 'targetUser', 'sourceComment.author']);

        return response()->json([
            'message' => 'OK',
            'data' => $this->challengePayload($challenge),
        ], 201);
    }

    public function answer(Request $request, Challenge $challenge): JsonResponse
    {
        abort_unless($challenge->target_user_id === $request->user()->id, 403);
        abort_unless(in_array($challenge->status, [Challenge::STATUS_PENDING, Challenge::STATUS_REJECTED], true), 422);

        $data = $request->validate([
            'answer' => ['required', 'string', 'max:255'],
        ]);

        if (! Hash::check($this->normalizeAnswer($data['answer']), $challenge->answer_hash)) {
            $challenge->update(['status' => Challenge::STATUS_REJECTED]);

            throw ValidationException::withMessages([
                'answer' => ['Risposta non corretta.'],
            ]);
        }

        $result = DB::transaction(function () use ($challenge): array {
            $lockedChallenge = Challenge::query()->lockForUpdate()->findOrFail($challenge->id);
            $post = Post::query()->lockForUpdate()->findOrFail($lockedChallenge->post_id);

            if ($lockedChallenge->target_type === Challenge::TARGET_POST_AUTHOR) {
                $nextCount = $post->spot_on_count + 1;
                $post->update([
                    'is_anonymous' => false,
                    'spot_on_count' => $nextCount,
                    'io_cero_count' => $nextCount,
                ]);
            }

            User::query()->whereKey($lockedChallenge->challenger_id)->increment('karma');

            $lockedChallenge->update([
                'status' => Challenge::STATUS_UNLOCKED,
                'resolved_at' => now(),
            ]);

            $chat = $this->createOrReuseChat($lockedChallenge->target_user_id, $lockedChallenge->challenger_id, [
                'origin_challenge_id' => $lockedChallenge->id,
                'origin_post_id' => $lockedChallenge->post_id,
            ]);

            return [
                'challenge' => $lockedChallenge->refresh()->load(['post.location', 'challenger', 'targetUser', 'sourceComment.author']),
                'post' => $post->refresh(),
                'chat' => $chat,
                'karma' => User::query()->findOrFail($lockedChallenge->challenger_id)->karma,
            ];
        });

        return response()->json([
            'message' => 'OK',
            'data' => [
                'challenge' => $this->challengePayload($result['challenge']),
                'post_id' => $result['post']->id,
                'spot_on_count' => $result['post']->spot_on_count,
                'chat_id' => $result['chat']->id,
                'karma' => $result['karma'],
            ],
        ]);
    }

    public function counterProposeClassic(Request $request, Post $post): JsonResponse
    {
        abort_if($post->author_id === $request->user()->id, 422, 'Non puoi controproporre su un tuo post.');
        abort_if(! $post->secret_answer_hash, 422, 'Questo post non ha una domanda di verifica.');

        $data = $request->validate([
            'text' => ['required', 'string', 'min:3', 'max:1000'],
        ]);

        $challenge = Challenge::query()->create([
            'post_id' => $post->id,
            'origin' => Challenge::ORIGIN_CLASSIC,
            'challenger_id' => $request->user()->id,
            'target_type' => Challenge::TARGET_POST_AUTHOR,
            'target_user_id' => $post->author_id,
            'question' => $post->secret_question,
            'answer_hash' => $post->secret_answer_hash,
            'status' => Challenge::STATUS_COUNTER_PENDING,
            'counter_text' => $data['text'],
            'counter_proposed_by' => $request->user()->id,
        ])->load(['post.location', 'challenger', 'targetUser', 'counterProposer']);

        return response()->json([
            'message' => 'OK',
            'data' => $this->challengePayload($challenge),
        ], 201);
    }

    public function counterPropose(Request $request, Challenge $challenge): JsonResponse
    {
        abort_unless($challenge->target_user_id === $request->user()->id, 403);
        abort_unless(in_array($challenge->status, [Challenge::STATUS_PENDING, Challenge::STATUS_REJECTED], true), 422);

        $data = $request->validate([
            'text' => ['required', 'string', 'min:3', 'max:1000'],
        ]);

        $challenge->update([
            'status' => Challenge::STATUS_COUNTER_PENDING,
            'counter_text' => $data['text'],
            'counter_proposed_by' => $request->user()->id,
        ]);

        return response()->json([
            'message' => 'OK',
            'data' => $this->challengePayload($challenge->refresh()->load(['post.location', 'challenger', 'targetUser', 'counterProposer'])),
        ]);
    }

    public function counterReview(Request $request, Challenge $challenge): JsonResponse
    {
        $data = $request->validate([
            'accepted' => ['required', 'boolean'],
        ]);

        abort_unless($challenge->status === Challenge::STATUS_COUNTER_PENDING, 422);
        abort_unless($this->canReviewCounter($request->user(), $challenge), 403);

        if (! $data['accepted']) {
            $challenge->update([
                'status' => Challenge::STATUS_REJECTED,
                'resolved_at' => now(),
            ]);

            return response()->json([
                'message' => 'OK',
                'data' => [
                    'accepted' => false,
                    'challenge' => $this->challengePayload($challenge->refresh()),
                ],
            ]);
        }

        $result = DB::transaction(function () use ($challenge, $request): array {
            $lockedChallenge = Challenge::query()->lockForUpdate()->findOrFail($challenge->id);
            $post = Post::query()->lockForUpdate()->findOrFail($lockedChallenge->post_id);

            if ($lockedChallenge->origin === Challenge::ORIGIN_CLASSIC || $lockedChallenge->target_type === Challenge::TARGET_POST_AUTHOR) {
                $nextCount = $post->spot_on_count + 1;
                $post->update([
                    'is_anonymous' => false,
                    'spot_on_count' => $nextCount,
                    'io_cero_count' => $nextCount,
                ]);
            }

            $counterpartId = $lockedChallenge->origin === Challenge::ORIGIN_CLASSIC
                ? $lockedChallenge->counter_proposed_by
                : $lockedChallenge->target_user_id;

            User::query()->whereKey($counterpartId)->increment('karma');

            $lockedChallenge->update([
                'status' => Challenge::STATUS_UNLOCKED,
                'resolved_at' => now(),
            ]);

            $chat = $this->createOrReuseChat($counterpartId, $request->user()->id, [
                'origin_challenge_id' => $lockedChallenge->id,
                'origin_post_id' => $lockedChallenge->post_id,
            ]);

            return [
                'challenge' => $lockedChallenge->refresh()->load(['post.location', 'challenger', 'targetUser', 'counterProposer']),
                'post' => $post->refresh(),
                'chat' => $chat,
                'karma' => User::query()->findOrFail($counterpartId)->karma,
            ];
        });

        return response()->json([
            'message' => 'OK',
            'data' => [
                'accepted' => true,
                'challenge' => $this->challengePayload($result['challenge']),
                'post_id' => $result['post']->id,
                'spot_on_count' => $result['post']->spot_on_count,
                'chat_id' => $result['chat']->id,
                'karma' => $result['karma'],
            ],
        ]);
    }

    private function resolveTargetUserId(Post $post, array $data): string
    {
        if ($data['target_type'] === Challenge::TARGET_POST_AUTHOR) {
            return $post->author_id;
        }

        $comment = Comment::query()
            ->where('post_id', $post->id)
            ->findOrFail($data['source_comment_id'] ?? null);

        return $comment->author_id;
    }

    private function canReviewCounter(User $user, Challenge $challenge): bool
    {
        if ($challenge->origin === Challenge::ORIGIN_CLASSIC) {
            return $challenge->post()->where('author_id', $user->id)->exists();
        }

        return $challenge->challenger_id === $user->id;
    }

    private function createOrReuseChat(string $firstUserId, string $secondUserId, array $origin = []): Chat
    {
        [$one, $two] = Chat::sortedPair($firstUserId, $secondUserId);

        $chat = Chat::query()->firstOrCreate([
            'user_one_id' => $one,
            'user_two_id' => $two,
        ]);

        $chat->fill(array_filter($origin))->save();

        return $chat->refresh();
    }

    private function normalizeAnswer(string $answer): string
    {
        return mb_strtolower(trim($answer));
    }

    private function challengePayload(Challenge $challenge): array
    {
        $challenge->loadMissing(['post.location', 'challenger', 'targetUser', 'sourceComment.author', 'counterProposer']);

        return [
            'id' => $challenge->id,
            'post_id' => $challenge->post_id,
            'origin' => $challenge->origin,
            'target_type' => $challenge->target_type,
            'target_user' => $challenge->targetUser ? $this->publicUserPayload($challenge->targetUser) : null,
            'challenger' => $this->publicUserPayload($challenge->challenger),
            'source_comment_id' => $challenge->source_comment_id,
            'question' => $challenge->question,
            'status' => $challenge->status,
            'counter_text' => $challenge->counter_text,
            'counter_proposer' => $challenge->counterProposer ? $this->publicUserPayload($challenge->counterProposer) : null,
            'resolved_at' => $challenge->resolved_at?->toISOString(),
            'created_at' => $challenge->created_at?->toISOString(),
        ];
    }

    private function publicUserPayload(User $user): array
    {
        return [
            'id' => $user->id,
            'display_name' => $user->display_name,
            'avatar_color' => $user->avatar_color,
            'avatar_url' => $user->avatar_url,
        ];
    }
}
