<?php

namespace App\Http\Controllers\Admin\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class LoginController extends Controller
{
    private const MAX_ATTEMPTS = 5;

    public function create(): View
    {
        return view('admin.auth.login');
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'login' => ['required', 'string', 'max:255'],
            'password' => ['required', 'string', 'max:255'],
        ]);

        $login = trim($data['login']);
        $throttleKey = Str::transliterate(Str::lower($login)).'|'.$request->ip();

        if (RateLimiter::tooManyAttempts($throttleKey, self::MAX_ATTEMPTS)) {
            throw ValidationException::withMessages([
                'login' => __('auth.throttle', ['seconds' => RateLimiter::availableIn($throttleKey)]),
            ]);
        }

        // مثل النظام القديم: الحقل يقبل اسم المستخدم أو البريد.
        $fields = filter_var($login, FILTER_VALIDATE_EMAIL) ? ['email', 'username'] : ['username', 'email'];
        $remember = $request->boolean('remember');

        foreach ($fields as $field) {
            if (Auth::attempt([$field => $login, 'password' => $data['password'], 'is_active' => true], $remember)) {
                RateLimiter::clear($throttleKey);
                $request->session()->regenerate();

                $request->user()->forceFill(['last_login_at' => now()])->saveQuietly();

                return redirect()->intended(route('admin.dashboard'));
            }
        }

        RateLimiter::hit($throttleKey);

        throw ValidationException::withMessages(['login' => __('auth.failed')]);
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('admin.login');
    }
}
