<?php

namespace Tests\Feature\Admin;

use App\Models\AppointmentSlot;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AppointmentSlotControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_invitado_no_puede_ver_el_listado(): void
    {
        $this->get('/admin/appointment-slots')->assertRedirect(route('login'));
    }

    public function test_usuario_normal_recibe_403(): void
    {
        $user = User::factory()->create(['role' => 'user']);

        $this->actingAs($user)->get('/admin/appointment-slots')->assertForbidden();
    }

    public function test_administradora_puede_crear_un_horario(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($admin)->post('/admin/appointment-slots', [
            'starts_at' => now()->addDay()->format('Y-m-d H:i:s'),
            'ends_at' => now()->addDay()->addMinutes(50)->format('Y-m-d H:i:s'),
        ]);

        $response->assertRedirect();
        $this->assertDatabaseCount('appointment_slots', 1);
    }

    public function test_rechaza_un_horario_en_el_pasado(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($admin)->post('/admin/appointment-slots', [
            'starts_at' => now()->subDay()->format('Y-m-d H:i:s'),
            'ends_at' => now()->subDay()->addMinutes(50)->format('Y-m-d H:i:s'),
        ]);

        $response->assertSessionHasErrors('starts_at');
    }

    public function test_rechaza_un_fin_anterior_al_inicio(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($admin)->post('/admin/appointment-slots', [
            'starts_at' => now()->addDay()->format('Y-m-d H:i:s'),
            'ends_at' => now()->addDay()->subMinutes(10)->format('Y-m-d H:i:s'),
        ]);

        $response->assertSessionHasErrors('ends_at');
    }

    public function test_administradora_puede_activar_y_desactivar_un_horario(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $slot = AppointmentSlot::factory()->create(['is_active' => true]);

        $this->actingAs($admin)->patch("/admin/appointment-slots/{$slot->id}/toggle");
        $this->assertDatabaseHas('appointment_slots', ['id' => $slot->id, 'is_active' => 0]);
    }

    public function test_administradora_puede_eliminar_un_horario(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $slot = AppointmentSlot::factory()->create();

        $this->actingAs($admin)->delete("/admin/appointment-slots/{$slot->id}");

        $this->assertDatabaseMissing('appointment_slots', ['id' => $slot->id]);
    }
}
