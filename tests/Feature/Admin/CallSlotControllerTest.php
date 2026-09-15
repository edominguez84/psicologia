<?php

namespace Tests\Feature\Admin;

use App\Models\CallSlot;
use App\Models\ContactMessage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CallSlotControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_invitado_no_puede_ver_el_listado(): void
    {
        $this->get('/admin/call-slots')->assertRedirect(route('login'));
    }

    public function test_usuario_normal_recibe_403(): void
    {
        $user = User::factory()->create(['role' => 'user']);

        $this->actingAs($user)->get('/admin/call-slots')->assertForbidden();
    }

    public function test_administradora_puede_crear_un_horario(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($admin)->post('/admin/call-slots', [
            'starts_at' => now()->addDay()->format('Y-m-d H:i:s'),
            'ends_at' => now()->addDay()->addMinutes(15)->format('Y-m-d H:i:s'),
        ]);

        $response->assertRedirect();
        $this->assertDatabaseCount('call_slots', 1);
    }

    public function test_rechaza_un_horario_en_el_pasado(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($admin)->post('/admin/call-slots', [
            'starts_at' => now()->subDay()->format('Y-m-d H:i:s'),
            'ends_at' => now()->subDay()->addMinutes(15)->format('Y-m-d H:i:s'),
        ]);

        $response->assertSessionHasErrors('starts_at');
    }

    public function test_rechaza_un_fin_anterior_al_inicio(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($admin)->post('/admin/call-slots', [
            'starts_at' => now()->addDay()->format('Y-m-d H:i:s'),
            'ends_at' => now()->addDay()->subMinutes(10)->format('Y-m-d H:i:s'),
        ]);

        $response->assertSessionHasErrors('ends_at');
    }

    public function test_administradora_puede_activar_y_desactivar_un_horario(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $slot = CallSlot::create(['starts_at' => now()->addDay(), 'ends_at' => now()->addDay()->addMinutes(15), 'is_active' => true]);

        $this->actingAs($admin)->patch("/admin/call-slots/{$slot->id}/toggle");
        $this->assertDatabaseHas('call_slots', ['id' => $slot->id, 'is_active' => 0]);
    }

    public function test_administradora_puede_eliminar_un_horario(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $slot = CallSlot::create(['starts_at' => now()->addDay(), 'ends_at' => now()->addDay()->addMinutes(15), 'is_active' => true]);

        $this->actingAs($admin)->delete("/admin/call-slots/{$slot->id}");

        $this->assertDatabaseMissing('call_slots', ['id' => $slot->id]);
    }

    public function test_un_horario_ya_reservado_no_aparece_como_disponible(): void
    {
        $free = CallSlot::create(['starts_at' => now()->addDay(), 'ends_at' => now()->addDay()->addMinutes(15), 'is_active' => true]);
        $taken = CallSlot::create(['starts_at' => now()->addDays(2), 'ends_at' => now()->addDays(2)->addMinutes(15), 'is_active' => true]);
        ContactMessage::create(['name' => 'X', 'email' => 'x@example.com', 'message' => 'x', 'call_slot_id' => $taken->id]);

        $available = CallSlot::available()->get();

        $this->assertTrue($available->contains('id', $free->id));
        $this->assertFalse($available->contains('id', $taken->id));
    }
}
