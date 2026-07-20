<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\Verified;
use Illuminate\Http\Request;
use Illuminate\View\View;

class EmailVerificationController extends Controller
{
    public function __invoke(Request $request, string $id, string $hash): View
    {
        $user = User::query()->findOrFail($id);

        abort_unless(hash_equals((string) $hash, sha1($user->getEmailForVerification())), 403);

        if ($user->hasVerifiedEmail()) {
            return view('auth.email-result', [
                'title' => 'Email gia verificata',
                'message' => 'Il tuo indirizzo email era gia stato verificato.',
                'status' => 'already',
            ]);
        }

        $user->markEmailAsVerified();
        event(new Verified($user));

        return view('auth.email-result', [
            'title' => 'Email verificata',
            'message' => 'Il tuo account SpotOn e ora attivo.',
            'status' => 'verified',
        ]);
    }
}
