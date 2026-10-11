<?php

namespace App\Http\Controllers\Web\Admin;

use App\Http\Controllers\Controller;
use App\Models\PushToken;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Str;
use Illuminate\View\View;

class PushTokenController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:255'],
            'platform' => ['nullable', 'in:ios,android'],
            'status' => ['nullable', 'in:active,revoked'],
        ]);

        $search = trim($filters['search'] ?? '');
        $platform = $filters['platform'] ?? '';
        $status = $filters['status'] ?? '';

        $tokens = PushToken::query()
            ->select([
                'id', 'user_id', 'token_hash', 'device_id', 'platform',
                'app_version', 'locale', 'timezone', 'is_active',
                'last_seen_at', 'revoked_at', 'created_at', 'updated_at',
            ])
            ->with('user:id,display_name,email')
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($inner) use ($search): void {
                    $inner->where('device_id', 'like', "%{$search}%")
                        ->orWhere('token_hash', 'like', "%{$search}%")
                        ->orWhereHas('user', fn ($user) => $user
                            ->where('display_name', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%"));

                    if (Str::isUuid($search)) {
                        $inner->orWhere('id', $search);
                    }
                });
            })
            ->when($platform !== '', fn ($query) => $query->where('platform', $platform))
            ->when($status !== '', fn ($query) => $query->where('is_active', $status === 'active'))
            ->orderByDesc('updated_at')
            ->orderByDesc('id')
            ->paginate(20)
            ->withQueryString();

        return view('admin.push-tokens.index', [
            'tokens' => $tokens,
            'search' => $search,
            'platform' => $platform,
            'status' => $status,
            'stats' => [
                'total' => PushToken::query()->count(),
                'active' => PushToken::query()->where('is_active', true)->count(),
                'ios' => PushToken::query()->where('is_active', true)->where('platform', 'ios')->count(),
                'android' => PushToken::query()->where('is_active', true)->where('platform', 'android')->count(),
            ],
        ]);
    }

    public function show(PushToken $pushToken): Response
    {
        return response()->view('admin.push-tokens.show', [
            'pushToken' => $pushToken->load('user:id,display_name,email'),
        ])->header('Cache-Control', 'private, no-store');
    }
}
