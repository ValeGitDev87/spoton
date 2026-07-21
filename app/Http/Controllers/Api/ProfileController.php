<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\Concerns\SerializesUsers;
use App\Http\Controllers\Controller;
use App\Http\Requests\Profile\UpdateProfileRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class ProfileController extends Controller
{
    use SerializesUsers;

    public function update(UpdateProfileRequest $request): JsonResponse
    {
        $request->user()->update($request->validated());

        return response()->json([
            'message' => 'Profilo aggiornato.',
            'data' => [
                'user' => $this->userPayload($request->user()->refresh()),
            ],
        ]);
    }

    public function karma(Request $request): JsonResponse
    {
        return response()->json([
            'message' => 'OK',
            'data' => [
                'karma' => $request->user()->karma ?? 0,
            ],
        ]);
    }

    public function storePhoto(Request $request): JsonResponse
    {
        $data = $request->validate([
            'photo_url' => ['required_without:photo', 'nullable', 'url', 'max:2048'],
            'photo' => ['required_without:photo_url', 'nullable', 'image', 'max:4096'],
        ]);

        $user = $request->user();
        $photos = array_values($user->photos ?? []);

        if (count($photos) >= 10) {
            throw ValidationException::withMessages([
                'photo' => ['Limite di 10 foto raggiunto.'],
            ]);
        }

        if ($request->hasFile('photo')) {
            $path = $request->file('photo')->store('profile-photos', 'public');
            $photos[] = Storage::disk('public')->url($path);
        } else {
            $photos[] = $data['photo_url'];
        }

        $user->update(['photos' => $photos]);

        return response()->json([
            'message' => 'OK',
            'data' => [
                'photos' => $this->photoPayload($photos),
            ],
        ], 201);
    }

    public function destroyPhoto(Request $request, int $photoId): JsonResponse
    {
        $user = $request->user();
        $photos = array_values($user->photos ?? []);

        abort_unless(array_key_exists($photoId, $photos), 404);

        $removedUrl = $photos[$photoId];
        unset($photos[$photoId]);
        $photos = array_values($photos);

        $this->deleteLocalPublicPhoto($removedUrl);
        $user->update(['photos' => $photos]);

        return response()->json([
            'message' => 'OK',
            'data' => [
                'removed' => true,
                'photos' => $this->photoPayload($photos),
            ],
        ]);
    }

    private function photoPayload(array $photos): array
    {
        return collect(array_values($photos))
            ->map(fn (string $url, int $index) => [
                'id' => $index,
                'url' => $url,
            ])
            ->values()
            ->all();
    }

    private function deleteLocalPublicPhoto(string $url): void
    {
        if (! str_starts_with($url, '/storage/')) {
            return;
        }

        Storage::disk('public')->delete(substr($url, strlen('/storage/')));
    }
}
