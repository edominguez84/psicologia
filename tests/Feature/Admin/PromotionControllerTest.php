<?php

namespace Tests\Feature\Admin;

use App\Models\Promotion;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PromotionControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_invitado_no_puede_ver_el_listado(): void
    {
        $this->get('/admin/promotions')->assertRedirect(route('login'));
    }

    public function test_usuario_normal_recibe_403(): void
    {
        $user = User::factory()->create(['role' => 'user']);

        $this->actingAs($user)->get('/admin/promotions')->assertForbidden();
    }

    public function test_administradora_puede_crear_una_promocion(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($admin)->post('/admin/promotions', [
            'title' => 'Paquete de 4 sesiones',
            'price' => 120,
            'description' => '4 sesiones de 50 minutos.',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('promotions', [
            'title' => 'Paquete de 4 sesiones',
            'price' => 120,
            'is_active' => 1,
        ]);
    }

    public function test_administradora_puede_editar_una_promocion(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $promotion = Promotion::create(['title' => 'Vieja', 'price' => 10, 'description' => 'x', 'is_active' => true]);

        $response = $this->actingAs($admin)->put("/admin/promotions/{$promotion->id}", [
            'title' => 'Nueva',
            'price' => 20,
            'description' => 'y',
        ]);

        $response->assertRedirect();
        $this->assertSame('Nueva', $promotion->fresh()->title);
        $this->assertEquals(20, $promotion->fresh()->price);
    }

    public function test_administradora_puede_ocultar_una_promocion(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $promotion = Promotion::create(['title' => 'x', 'price' => 10, 'description' => 'x', 'is_active' => true]);

        $this->actingAs($admin)->patch("/admin/promotions/{$promotion->id}/toggle");

        $this->assertFalse($promotion->fresh()->is_active);
    }

    public function test_administradora_puede_eliminar_una_promocion(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $promotion = Promotion::create(['title' => 'x', 'price' => 10, 'description' => 'x', 'is_active' => true]);

        $this->actingAs($admin)->delete("/admin/promotions/{$promotion->id}");

        $this->assertDatabaseMissing('promotions', ['id' => $promotion->id]);
    }

    public function test_una_promocion_vencida_no_aparece_en_el_scope_activo(): void
    {
        Promotion::create([
            'title' => 'Vencida', 'price' => 10, 'description' => 'x',
            'is_active' => true, 'valid_until' => now()->subDay()->toDateString(),
        ]);
        $vigente = Promotion::create([
            'title' => 'Vigente', 'price' => 10, 'description' => 'x',
            'is_active' => true, 'valid_until' => now()->addDay()->toDateString(),
        ]);

        $active = Promotion::active()->get();

        $this->assertCount(1, $active);
        $this->assertSame($vigente->id, $active->first()->id);
    }
}
