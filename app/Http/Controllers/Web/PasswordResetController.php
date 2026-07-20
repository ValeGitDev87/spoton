<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Services\Auth\PasswordRules;
use App\Services\Auth\ResetsPasswords;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\View\View;

class PasswordResetController extends Controller
{
    public function show(Request $request): View
    {
        return view('auth.reset-password', [
            'token' => $request->query('token', ''),
            'email' => $request->query('email', ''),
        ]);
    }

    public function store(Request $request, ResetsPasswords $resetsPasswords): RedirectResponse
    {
        $data = $request->validate([
            'email' => ['required', 'email', 'max:255'],
            'token' => ['required', 'string'],
            'password' => PasswordRules::rules(),
        ]);

        $status = $resetsPasswords->reset([
            'email' => $data['email'],
            'token' => $data['token'],
            'password' => $data['password'],
            'password_confirmation' => $request->input('password_confirmation'),
        ]);

        if ($status !== Password::PASSWORD_RESET) {
            return back()
                ->withInput($request->only('email'))
                ->withErrors(['email' => 'Il link di reset non e valido o e scaduto.']);
        }

        return redirect()
            ->route('password.reset.result')
            ->with('status', 'Password aggiornata. Ora puoi effettuare il login.');
    }

    public function result(): View
    {
        return view('auth.password-result', [
            'message' => session('status', 'Operazione completata.'),
        ]);
    }
}
