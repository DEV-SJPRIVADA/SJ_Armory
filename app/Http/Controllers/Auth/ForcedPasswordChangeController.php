<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\User;
use App\Providers\RouteServiceProvider;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

final class ForcedPasswordChangeController extends Controller
{
    public function edit(Request $request): View
    {
        return view('auth.force-password-change');
    }

    public function update(Request $request): RedirectResponse
    {
        if (!$request->user()->must_change_password) {
            return redirect()->intended(RouteServiceProvider::HOME);
        }

        $validated = $request->validate([
            'password' => ['required', Password::defaults(), 'confirmed'],
        ]);

        $user = $request->user();
        $user->password = $validated['password'];
        $user->must_change_password = false;
        $user->save();

        AuditLog::create([
            'user_id' => $user->id,
            'action' => 'password_updated',
            'auditable_type' => User::class,
            'auditable_id' => $user->id,
            'before' => null,
            'after' => null,
        ]);

        return redirect()->intended(RouteServiceProvider::HOME);
    }
}
