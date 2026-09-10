<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EmotionalCheckupTest extends TestCase
{
    use RefreshDatabase;

    public function test_calcula_puntuacion_y_banda_alta(): void
    {
        $response = $this->postJson('/chequeo-emocional', [
            'answers' => [3, 3, 3, 2, 2],
            'website' => '',
        ]);

        $response->assertOk()->assertJson([
            'ok' => true,
            'score' => 13,
            'band' => 'alto',
        ]);
        $this->assertDatabaseHas('emotional_checkups', ['score' => 13, 'band' => 'alto']);
    }

    public function test_banda_baja(): void
    {
        $response = $this->postJson('/chequeo-emocional', [
            'answers' => [0, 1, 1, 0, 1],
            'website' => '',
        ]);

        $response->assertOk()->assertJson(['score' => 3, 'band' => 'bajo']);
    }

    public function test_valida_numero_de_respuestas(): void
    {
        $this->postJson('/chequeo-emocional', ['answers' => [1, 2, 3]])
            ->assertStatus(422)
            ->assertJsonValidationErrors('answers');
    }

    public function test_valida_rango_de_cada_respuesta(): void
    {
        $this->postJson('/chequeo-emocional', ['answers' => [0, 1, 2, 3, 9]])
            ->assertStatus(422)
            ->assertJsonValidationErrors('answers.4');
    }
}
