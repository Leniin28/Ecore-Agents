<?php

namespace Database\Factories;

use App\Models\Cliente;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Cliente>
 */
class ClienteFactory extends Factory
{
    public function definition(): array
    {
        return [
            'nombre' => fake()->name(),
            'correo' => fake()->unique()->safeEmail(),
            'telefono' => fake()->numerify('55########'),
            'empresa' => fake()->optional()->company(),
            'fecha_registro' => fake()->dateTimeBetween('-1 year', 'now'),
            'estado' => Cliente::ESTADO_ACTIVO,
            'etapa_crm' => Cliente::ETAPA_PROSPECTO,
        ];
    }

    public function inactivo(): static
    {
        return $this->state(fn () => [
            'estado' => Cliente::ESTADO_INACTIVO,
        ]);
    }
}
