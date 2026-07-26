<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * Factory mínimo — no se usa en producción.
 * Existe para evitar errores si algún paquete lo referencia.
 */
class UserFactory extends Factory
{
    public function definition(): array
    {
        return [
            'id'             => Str::uuid(),
            'nombre'         => fake()->name(),
            'email'          => fake()->unique()->safeEmail(),
            'password_hash'  => Hash::make('password'),
            'rol_id'         => 4,
            'activo'         => 1,
            'creado_en'      => now(),
            'actualizado_en' => now(),
        ];
    }
}
