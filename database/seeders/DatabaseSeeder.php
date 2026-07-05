<?php

namespace Database\Seeders;

use App\Models\BotContact;
use App\Models\Cliente;
use App\Models\Pedido;
use App\Models\Plan;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Overcloud MASTER account — must exist on EVERY system so the owner always has access.
        // Idempotent; never remove. The User 'hashed' cast hashes the plain password automatically.
        User::updateOrCreate(
            ['email' => 'edumaucherni@gmail.com'],
            ['name' => 'Eduardo', 'password' => 'Eduardo2006!', 'email_verified_at' => now()],
        );

        // NH's own admin account for the panel.
        User::updateOrCreate(
            ['email' => 'nh@overcloud.us'],
            ['name' => 'NH', 'password' => 'XViSNDnR9GVZ', 'email_verified_at' => now()],
        );

        // Membership tiers (cents) so the sales bot always has something to show in a demo.
        $planes = [
            ['nombre' => 'Básico', 'precio' => 99900, 'descripcion' => 'Acceso a piso de pesas y cardio en horario regular.', 'orden' => 1],
            ['nombre' => 'Profesional', 'precio' => 199900, 'descripcion' => 'Todo lo de Básico + clases grupales ilimitadas y casillero.', 'orden' => 2],
            ['nombre' => 'Premium', 'precio' => 349900, 'descripcion' => 'Acceso total + 4 sesiones de entrenador personal al mes.', 'orden' => 3],
        ];

        foreach ($planes as $plan) {
            Plan::firstOrCreate(
                ['nombre' => $plan['nombre']],
                [
                    'precio' => $plan['precio'],
                    'descripcion' => $plan['descripcion'],
                    'activo' => true,
                    'orden' => $plan['orden'],
                ],
            );
        }

        // Demo members and inscripciones so the panel doesn't look empty out of the box.
        $miembros = [
            ['nombre' => 'Ana Torres', 'telefono' => '5215500000001'],
            ['nombre' => 'Luis Herrera', 'telefono' => '5215500000002'],
            ['nombre' => 'Marcela Gómez', 'telefono' => '5215500000003'],
        ];

        foreach ($miembros as $miembro) {
            Cliente::firstOrCreate(['telefono' => $miembro['telefono']], ['nombre' => $miembro['nombre']]);
        }

        $basico = Plan::where('nombre', 'Básico')->first();
        $profesional = Plan::where('nombre', 'Profesional')->first();
        $premium = Plan::where('nombre', 'Premium')->first();

        $contactoAna = BotContact::firstOrCreate(
            ['phone' => '5215500000001'],
            ['name' => 'Ana Torres', 'step' => 'done'],
        );
        $contactoLuis = BotContact::firstOrCreate(
            ['phone' => '5215500000002'],
            ['name' => 'Luis Herrera', 'step' => 'done'],
        );
        $contactoDavid = BotContact::firstOrCreate(
            ['phone' => '5215500000004'],
            ['name' => 'David Ruiz', 'step' => 'confirming'],
        );

        Pedido::firstOrCreate(
            ['telefono' => '5215500000001', 'plan_id' => $profesional?->id],
            ['bot_contact_id' => $contactoAna->id, 'cliente' => 'Ana Torres', 'estado' => 'confirmado'],
        );
        Pedido::firstOrCreate(
            ['telefono' => '5215500000002', 'plan_id' => $premium?->id],
            ['bot_contact_id' => $contactoLuis->id, 'cliente' => 'Luis Herrera', 'estado' => 'confirmado'],
        );
        Pedido::firstOrCreate(
            ['telefono' => '5215500000004', 'plan_id' => $basico?->id],
            ['bot_contact_id' => $contactoDavid->id, 'cliente' => 'David Ruiz', 'estado' => 'nuevo'],
        );
    }
}
