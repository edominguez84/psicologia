<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PatientProfileControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
    }

    public function test_invitado_no_puede_ver_el_perfil(): void
    {
        $this->get('/perfil')->assertRedirect(route('login'));
    }

    public function test_un_paciente_puede_ver_y_editar_su_propio_perfil(): void
    {
        $patient = User::factory()->create(['role' => 'patient', 'phone_number' => '111']);

        $this->actingAs($patient)->get('/perfil')->assertOk();

        $response = $this->actingAs($patient)->put('/perfil', [
            'name' => 'Nombre Nuevo',
            'phone_number' => '999-8888',
            'birth_date' => '1995-01-01',
        ]);

        $response->assertRedirect();
        $patient->refresh();
        $this->assertSame('Nombre Nuevo', $patient->name);
        $this->assertSame('999-8888', $patient->phone_number);
    }

    public function test_no_hay_ninguna_ruta_para_editar_el_perfil_de_otro_usuario(): void
    {
        // La ruta /perfil no acepta un {user} en el path: siempre opera
        // sobre Auth::user(). Confirmamos que editar "otro id" no es posible
        // porque no existe tal ruta (actualizar siempre afecta al propio).
        $patient = User::factory()->create(['role' => 'patient']);
        $other = User::factory()->create(['role' => 'patient', 'name' => 'Otro Paciente']);

        $this->actingAs($patient)->put('/perfil', [
            'name' => 'Intento de cambiar mi nombre',
            'phone_number' => '123',
            'birth_date' => '1990-01-01',
        ]);

        $other->refresh();
        $this->assertSame('Otro Paciente', $other->name);
    }

    public function test_puede_subir_una_foto_de_perfil_valida(): void
    {
        $patient = User::factory()->create(['role' => 'patient']);
        $file = UploadedFile::fake()->image('foto.jpg', 800, 600);

        $response = $this->actingAs($patient)->post('/perfil/foto', ['photo' => $file]);

        $response->assertRedirect();
        $patient->refresh();
        Storage::disk('public')->assertExists($patient->avatar_path);
    }

    public function test_rechaza_una_foto_demasiado_grande(): void
    {
        $patient = User::factory()->create(['role' => 'patient']);
        $file = UploadedFile::fake()->image('foto.jpg', 3000, 3000);

        $response = $this->actingAs($patient)->post('/perfil/foto', ['photo' => $file]);

        $response->assertSessionHasErrors('photo');
        $this->assertNull($patient->fresh()->avatar_path);
    }

    public function test_subir_una_nueva_foto_borra_la_anterior(): void
    {
        $patient = User::factory()->create(['role' => 'patient']);
        $first = UploadedFile::fake()->image('primera.jpg', 800, 600);
        $this->actingAs($patient)->post('/perfil/foto', ['photo' => $first]);
        $firstPath = $patient->fresh()->avatar_path;

        $second = UploadedFile::fake()->image('segunda.jpg', 800, 600);
        $this->actingAs($patient)->post('/perfil/foto', ['photo' => $second]);

        Storage::disk('public')->assertMissing($firstPath);
        Storage::disk('public')->assertExists($patient->fresh()->avatar_path);
    }

    public function test_puede_quitar_la_foto_de_perfil(): void
    {
        $patient = User::factory()->create(['role' => 'patient']);
        $file = UploadedFile::fake()->image('foto.jpg', 800, 600);
        $this->actingAs($patient)->post('/perfil/foto', ['photo' => $file]);
        $path = $patient->fresh()->avatar_path;

        $response = $this->actingAs($patient)->delete('/perfil/foto');

        $response->assertRedirect();
        Storage::disk('public')->assertMissing($path);
        $this->assertNull($patient->fresh()->avatar_path);
    }
}
