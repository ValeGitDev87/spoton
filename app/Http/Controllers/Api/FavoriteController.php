<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Favorite;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FavoriteController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = trim((string) $request->query('query', ''));
        $normalizedQuery = mb_strtolower($query);

        $perPage = min(max((int) $request->query('per_page', 25), 1), 50);
        $favorites = Favorite::query()
            ->with('targetUser')
            ->where('owner_id', $request->user()->id)
            ->when($query !== '', fn (Builder $builder) => $builder
                ->where(function (Builder $search) use ($normalizedQuery): void {
                    $search
                        ->whereRaw('LOWER(target_name) LIKE ?', ['%'.$normalizedQuery.'%'])
                        ->orWhereHas('targetUser', fn (Builder $targetUser) => $targetUser
                            ->whereRaw('LOWER(display_name) LIKE ?', ['%'.$normalizedQuery.'%']));
                }))
            ->latest()
            ->paginate($perPage);

        return response()->json([
            'message' => 'OK',
            'data' => collect($favorites->items())
                ->map(fn (Favorite $favorite) => $this->favoritePayload($favorite))
                ->values(),
            'meta' => [
                'current_page' => $favorites->currentPage(),
                'last_page' => $favorites->lastPage(),
                'per_page' => $favorites->perPage(),
                'total' => $favorites->total(),
            ],
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'target_user_id' => ['nullable', 'uuid', 'exists:users,id', 'required_without:target_name'],
            'target_name' => ['nullable', 'string', 'min:2', 'max:120', 'required_without:target_user_id'],
        ]);

        $targetUser = isset($data['target_user_id'])
            ? User::query()->findOrFail($data['target_user_id'])
            : User::query()
                ->whereRaw('LOWER(display_name) = ?', [mb_strtolower(trim($data['target_name']))])
                ->first();
        $targetName = $targetUser?->display_name ?? trim($data['target_name']);
        $normalizedTargetName = mb_strtolower($targetName);

        abort_if($targetUser?->id === $request->user()->id, 422, 'Non puoi aggiungere te stesso ai preferiti.');

        $favorite = Favorite::query()
            ->where('owner_id', $request->user()->id)
            ->where(function (Builder $query) use ($normalizedTargetName, $targetUser): void {
                if ($targetUser) {
                    $query->where('target_user_id', $targetUser->id)
                        ->orWhereRaw('LOWER(target_name) = ?', [$normalizedTargetName]);

                    return;
                }

                $query->whereRaw('LOWER(target_name) = ?', [$normalizedTargetName]);
            })
            ->first();

        if (! $favorite) {
            $favorite = Favorite::query()->create([
                'owner_id' => $request->user()->id,
                'target_user_id' => $targetUser?->id,
                'target_name' => $targetName,
            ]);
        } elseif ($targetUser && $favorite->target_user_id !== $targetUser->id) {
            $favorite->update([
                'target_user_id' => $targetUser->id,
                'target_name' => $targetUser->display_name,
            ]);
        }

        return response()->json([
            'message' => 'OK',
            'data' => $this->favoritePayload($favorite->load('targetUser')),
        ], 201);
    }

    public function destroy(Request $request, string $targetName): JsonResponse
    {
        $deleted = Favorite::query()
            ->where('owner_id', $request->user()->id)
            ->where(function (Builder $query) use ($targetName): void {
                $normalizedTargetName = mb_strtolower(trim($targetName));
                $query
                    ->whereRaw('LOWER(target_name) = ?', [$normalizedTargetName])
                    ->orWhereHas('targetUser', fn (Builder $targetUser) => $targetUser
                        ->whereRaw('LOWER(display_name) = ?', [$normalizedTargetName]));
            })
            ->delete();

        return response()->json([
            'message' => 'OK',
            'data' => [
                'removed' => $deleted > 0,
            ],
        ]);
    }

    public function destroyUser(Request $request, User $user): JsonResponse
    {
        $deleted = Favorite::query()
            ->where('owner_id', $request->user()->id)
            ->where('target_user_id', $user->id)
            ->delete();

        return response()->json([
            'message' => 'OK',
            'data' => [
                'removed' => $deleted > 0,
            ],
        ]);
    }

    private function favoritePayload(Favorite $favorite): array
    {
        return [
            'id' => $favorite->id,
            'target_name' => $favorite->targetUser?->display_name ?? $favorite->target_name,
            'target_user' => $favorite->targetUser ? [
                'id' => $favorite->targetUser->id,
                'display_name' => $favorite->targetUser->display_name,
                'avatar_color' => $favorite->targetUser->avatar_color,
                'avatar_url' => $favorite->targetUser->avatar_url,
            ] : null,
            'created_at' => $favorite->created_at?->toISOString(),
        ];
    }
}
