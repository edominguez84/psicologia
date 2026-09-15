<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use App\Services\SiteSettingsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PaymentSettingsControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
    }

    public function test_invitado_no_puede_ver_la_configuracion(): void
    {
        $this->get('/admin/payment-settings')->assertRedirect(route('login'));
    }

    public function test_administradora_no_super_recibe_403(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)->get('/admin/payment-settings')->assertForbidden();
    }

    public function test_super_admin_puede_guardar_transferencia_bancaria(): void
    {
        $superAdmin = User::factory()->create(['role' => 'super_admin']);

        $response = $this->actingAs($superAdmin)->put('/admin/payment-settings', [
            'method' => 'bank_transfer',
            'bank_name' => 'Banco Agrícola',
            'account_number' => '1234567890',
            'account_holder' => 'Erika Magaña',
            'instructions' => 'Envía tu comprobante por WhatsApp.',
        ]);

        $response->assertRedirect();
        $payment = app(SiteSettingsService::class)->get('payment');
        $this->assertSame('bank_transfer', $payment['method']);
        $this->assertSame('Banco Agrícola', $payment['bank_transfer']['bank_name']);
    }

    public function test_super_admin_puede_guardar_credenciales_de_wompi(): void
    {
        $superAdmin = User::factory()->create(['role' => 'super_admin']);

        $this->actingAs($superAdmin)->put('/admin/payment-settings', [
            'method' => 'wompi',
            'wompi_mode' => 'sandbox',
            'wompi_app_id' => '44904ee8-2cb3-40fa-b565-6bb89c13d185',
            'wompi_api_secret' => '708b5783-8de0-4bbb-ae6e-1700afa67627',
        ]);

        $payment = app(SiteSettingsService::class)->get('payment');
        $this->assertSame('wompi', $payment['method']);
        $this->assertSame('sandbox', $payment['wompi']['mode']);
        $this->assertSame('44904ee8-2cb3-40fa-b565-6bb89c13d185', $payment['wompi']['app_id']);
        $this->assertSame('708b5783-8de0-4bbb-ae6e-1700afa67627', $payment['wompi']['api_secret']);
    }

    public function test_puede_subir_la_imagen_del_numero_de_cuenta(): void
    {
        $superAdmin = User::factory()->create(['role' => 'super_admin']);
        $image = UploadedFile::fake()->image('cuenta.jpg', 400, 300);

        $this->actingAs($superAdmin)->put('/admin/payment-settings', [
            'method' => 'bank_transfer',
            'account_image' => $image,
        ]);

        $payment = app(SiteSettingsService::class)->get('payment');
        $this->assertNotEmpty($payment['bank_transfer']['account_image_path']);
        Storage::disk('public')->assertExists($payment['bank_transfer']['account_image_path']);
    }
}
