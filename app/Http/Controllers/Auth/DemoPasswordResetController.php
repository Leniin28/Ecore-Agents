<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class DemoPasswordResetController extends Controller
{
    public function create(): View
    {
        abort_unless(app()->environment('local'), 404);

        return view('auth.password-demo');
    }

    public function store(Request $request): RedirectResponse
    {
        abort_unless(app()->environment('local'), 404);
        $data = $request->validate([
            'email' => ['required', 'email', 'max:255'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);
        $user = User::query()->where('email', $data['email'])->first();
        if (! $user) {
            throw ValidationException::withMessages(['email' => 'No se encontró una cuenta con ese correo.']);
        }

        $user->forceFill(['password' => $data['password'], 'remember_token' => Str::random(60)])->save();

        return redirect()->route('login')->with('success', 'Contraseña actualizada en el entorno de demostración.');
    }
}
