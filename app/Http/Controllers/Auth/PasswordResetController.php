<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\View\View;

class PasswordResetController extends Controller
{
    public function requestForm(): View
    {
        return view('auth.password-request');
    }

    public function sendLink(Request $request): RedirectResponse
    {
        $data = $request->validate(['email' => ['required', 'email', 'max:255']]);
        if (in_array(config('mail.default'), ['log', 'array', 'failover'], true) && ! app()->runningUnitTests()) {
            return back()->with('error', 'El envío de correo todavía no está configurado.');
        }
        try {
            $user = User::where('email', $data['email'])->first();
            if ($user) {
                $existing = DB::table('password_reset_codes')->where('email', $user->email)->first();
                if (! $existing || now()->diffInSeconds(\Illuminate\Support\Carbon::parse($existing->created_at), true) >= 60) {
                    $code = (string) random_int(100000, 999999);
                    DB::table('password_reset_codes')->updateOrInsert(['email' => $user->email], [
                        'code_hash' => Hash::make($code), 'attempts' => 0, 'created_at' => now(),
                    ]);
                    $user->sendPasswordResetNotification($code);
                }
            }
        } catch (\Throwable $e) {
            report($e);
            return back()->with('error', 'No fue posible procesar la solicitud. Inténtalo más tarde.');
        }

        return redirect()->route('password.reset')->with('status', 'Si existe una cuenta con ese correo, recibirás un código para restablecer tu contraseña.');
    }

    public function resetForm(): View
    {
        return view('auth.password-reset');
    }

    public function reset(Request $request): RedirectResponse
    {
        $data = $request->validate(['code' => ['required', 'digits:6'], 'email' => ['required', 'email', 'max:255'], 'password' => ['required', 'string', 'min:8', 'confirmed']]);
        $reset = DB::transaction(function () use ($data) {
            $entry = DB::table('password_reset_codes')->where('email', $data['email'])->lockForUpdate()->first();
            $user = User::where('email', $data['email'])->first();
            if (! $entry || ! $user || $entry->attempts >= 5 || \Illuminate\Support\Carbon::parse($entry->created_at)->addMinutes(15)->isPast()) {
                return false;
            }
            if (! Hash::check($data['code'], $entry->code_hash)) {
                DB::table('password_reset_codes')->where('email', $data['email'])->increment('attempts');
                return false;
            }
            $user->forceFill(['password' => $data['password'], 'remember_token' => Str::random(60)])->save();
            DB::table('password_reset_codes')->where('email', $data['email'])->delete();
            event(new PasswordReset($user));
            return true;
        });
        if (! $reset) {
            return back()->withInput($request->only('email'))->withErrors(['email' => 'El código es inválido, caducó o alcanzó el límite de intentos. Solicita uno nuevo.']);
        }

        return redirect()->route('login')->with('status', 'Contraseña actualizada. Ya puedes iniciar sesión.');
    }
}
