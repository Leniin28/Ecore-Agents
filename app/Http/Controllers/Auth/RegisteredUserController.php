<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Cliente;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class RegisteredUserController extends Controller
{
    public function create(): View
    {
        return view('auth.register');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate(
            [
                'name' => ['required', 'string', 'max:255'],
                'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
                'password' => ['required', 'confirmed', Password::min(8)],
                'telefono' => ['required', 'string', 'max:30'],
                'empresa' => ['nullable', 'string', 'max:255'],
            ],
            [
                'name.required' => 'El nombre es obligatorio.',
                'email.required' => 'El correo electrónico es obligatorio.',
                'email.email' => 'El correo electrónico debe tener un formato válido.',
                'email.unique' => 'Ya existe una cuenta con este correo electrónico.',
                'password.required' => 'La contraseña es obligatoria.',
                'password.confirmed' => 'La confirmación de contraseña no coincide.',
                'password.min' => 'La contraseña debe tener al menos :min caracteres.',
                'telefono.required' => 'El teléfono es obligatorio.',
                'telefono.max' => 'El teléfono no debe superar :max caracteres.',
                'empresa.max' => 'La empresa no debe superar :max caracteres.',
            ],
        );

        $user = DB::transaction(function () use ($validated): User {
            $user = User::create([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'password' => $validated['password'],
            ]);

            $cliente = Cliente::query()->where('correo', $user->email)->first();

            if ($cliente) {
                $cliente->update(['user_id' => $user->id]);
            } else {
                Cliente::create([
                    'user_id' => $user->id,
                    'nombre' => $validated['name'],
                    'correo' => $validated['email'],
                    'telefono' => $validated['telefono'],
                    'empresa' => $validated['empresa'] ?? null,
                    'fecha_registro' => now()->toDateString(),
                    'estado' => Cliente::ESTADO_ACTIVO,
                    'etapa_crm' => Cliente::ETAPA_PROSPECTO,
                ]);
            }

            return $user;
        });

        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->route('profile');
    }
}
