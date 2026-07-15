<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Favorite;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FavoriteController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = trim((string) $request->query('query', ''));
        $normalizedQuery = mb_strtolower($query);

        $favorites = Favorite::query()
            ->where('owner_id', $request->user()->id)
            ->when($query !== '', fn (Builder $builder) => $builder
                ->whereRaw('LOWER(target_name) LIKE ?', ['%'.$normalizedQuery.'%']))
            ->latest()
            ->limit(20)
            ->get();

        return response()->json([
            'message' => 'OK',
            'data' => $favorites->map(fn (Favorite $favorite) => $this->favoritePayload($favorite))->values(),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'target_name' => ['required', 'string', 'min:2', 'max:120'],
        ]);

        $targetName = trim($data['target_name']);
        $normalizedTargetName = mb_strtolower($targetName);

        $favorite = Favorite::query()
            ->where('owner_id', $request->user()->id)
            ->whereRaw('LOWER(target_name) = ?', [$normalizedTargetName])
            ->first();

        if (! $favorite) {
            $favorite = Favorite::query()->create([
                'owner_id' => $request->user()->id,
                'target_name' => $targetName,
            ]);
        }

        return response()->json([
            'message' => 'OK',
            'data' => $this->favoritePayload($favorite),
        ], 201);
    }

    public function destroy(Request $request, string $targetName): JsonResponse
    {
        $deleted = Favorite::query()
            ->where('owner_id', $request->user()->id)
            ->whereRaw('LOWER(target_name) = ?', [mb_strtolower(trim($targetName))])
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
            'target_name' => $favorite->target_name,
            'created_at' => $favorite->created_at?->toISOString(),
        ];
    }
}
