<?php

namespace App\Http\Controllers\Web\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class UserController extends Controller
{
    public function index(Request $request): View
    {
        $users = User::query()
            ->withCount('posts')
            ->when($request->query('search'), function ($query, string $search): void {
                $query->where(function ($inner) use ($search): void {
                    $inner
                        ->where('display_name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                });
            })
            ->when($request->query('role') === 'admin', fn ($query) => $query->where('is_admin', true))
            ->when($request->query('role') === 'user', fn ($query) => $query->where('is_admin', false))
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return view('admin.users.index', [
            'users' => $users,
            'search' => $request->query('search', ''),
            'role' => $request->query('role', ''),
        ]);
    }

    public function updateStatus(Request $request, User $user): RedirectResponse
    {
        abort_if($request->user()->is($user), 422, 'Non puoi sospendere il tuo account admin.');
        abort_if($user->is_admin, 422, 'Non puoi modificare lo stato di un altro amministratore.');

        $data = $request->validate([
            'status' => ['required', 'in:active,suspended'],
            'reason' => ['nullable', 'string', 'max:1000'],
        ]);

        $suspended = $data['status'] === 'suspended';
        $user->update([
            'is_suspended' => $suspended,
            'suspended_at' => $suspended ? now() : null,
            'suspension_reason' => $suspended ? ($data['reason'] ?? 'Sospensione manuale admin.') : null,
        ]);

        if ($suspended) {
            $user->tokens()->delete();
        }

        return back()->with('status', $suspended ? 'Utente sospeso.' : 'Utente riattivato.');
    }
}
