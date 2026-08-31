<?php

namespace Database\Factories;

use App\Models\Cliente;
use App\Models\Interaccion;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Interaccion>
 */
class InteraccionFactory extends Factory
{
    public function definition(): array
    {
        return [
            'cliente_id' => Cliente::factory(),
            'usuario_id' => User::factory(),
            'tipo' => fake()->randomElement(array_keys(Interaccion::tipos())),
            'descripcion' => fake()->sentence(),
            'fecha' => fake()->dateTimeBetween('-1 month', 'now'),
        ];
    }
}
