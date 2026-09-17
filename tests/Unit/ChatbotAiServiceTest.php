<?php

namespace Tests\Unit;

use App\Mail\AccountCreatedByChatbot;
use App\Models\Appointment;
use App\Models\AppointmentSlot;
use App\Models\ChatbotFaq;
use App\Models\ChatbotLead;
use App\Models\User;
use App\Services\ChatbotAiService;
use App\Services\SiteSettingsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use RuntimeException;
use Tests\TestCase;

class ChatbotAiServiceTest extends TestCase
{
    use RefreshDatabase;

    private function configureCredentials(): void
    {
        app(SiteSettingsService::class)->set('chatbot_channels', [
            'anthropic' => ['enabled' => true, 'api_key' => 'sk-ant-test', 'model' => 'claude-haiku-4-5-20251001'],
        ]);
    }

    public function test_no_esta_configurado_sin_api_key(): void
    {
        $this->assertFalse(app(ChatbotAiService::class)->isConfigured());
    }

    public function test_esta_configurado_con_api_key(): void
    {
        $this->configureCredentials();

        $this->assertTrue(app(ChatbotAiService::class)->isConfigured());
    }

    public function test_reply_incluye_las_faqs_activas_en_el_system_prompt(): void
    {
        $this->configureCredentials();
        ChatbotFaq::create(['question' => '¿Cuánto dura una sesión?', 'answer' => '50 minutos.', 'is_active' => true]);
        Http::fake([
            'api.anthropic.com/*' => Http::response([
                'content' => [['type' => 'text', 'text' => 'Las sesiones duran 50 minutos.']],
                'stop_reason' => 'end_turn',
            ], 200),
        ]);

        $reply = app(ChatbotAiService::class)->reply([['role' => 'user', 'content' => '¿Cuánto dura la sesión?']]);

        $this->assertSame('Las sesiones duran 50 minutos.', $reply);
        Http::assertSent(fn ($request) => str_contains($request['system'][0]['text'], '¿Cuánto dura una sesión?'));
    }

    public function test_lanza_excepcion_sin_api_key(): void
    {
        $this->expectException(RuntimeException::class);

        app(ChatbotAiService::class)->reply([['role' => 'user', 'content' => 'Hola']]);
    }

    public function test_lanza_excepcion_si_anthropic_rechaza_la_solicitud(): void
    {
        $this->configureCredentials();
        Http::fake(['api.anthropic.com/*' => Http::response(['error' => ['message' => 'invalid_api_key']], 401)]);

        $this->expectException(RuntimeException::class);

        app(ChatbotAiService::class)->reply([['role' => 'user', 'content' => 'Hola']]);
    }

    public function test_no_es_usable_si_esta_apagada_aunque_haya_api_key(): void
    {
        app(SiteSettingsService::class)->set('chatbot_channels', [
            'anthropic' => ['enabled' => false, 'api_key' => 'sk-ant-test'],
        ]);

        $this->assertFalse(app(ChatbotAiService::class)->isUsable());
    }

    public function test_es_usable_si_esta_activada_y_con_api_key(): void
    {
        $this->configureCredentials();

        $this->assertTrue(app(ChatbotAiService::class)->isUsable());
    }

    public function test_usa_la_tool_de_horarios_cuando_el_modelo_la_pide(): void
    {
        $this->configureCredentials();
        AppointmentSlot::create(['starts_at' => now()->addDays(2), 'ends_at' => now()->addDays(2)->addHour(), 'is_active' => true]);

        Http::fake([
            'api.anthropic.com/*' => Http::sequence()
                ->push([
                    'stop_reason' => 'tool_use',
                    'content' => [['type' => 'tool_use', 'id' => 'tool_1', 'name' => 'get_available_slots', 'input' => []]],
                ], 200)
                ->push([
                    'stop_reason' => 'end_turn',
                    'content' => [['type' => 'text', 'text' => 'Tengo un horario disponible para ti.']],
                ], 200),
        ]);

        $reply = app(ChatbotAiService::class)->reply([['role' => 'user', 'content' => '¿Qué horarios tienes?']]);

        $this->assertSame('Tengo un horario disponible para ti.', $reply);
        Http::assertSentCount(2);
    }

    /**
     * Regla de negocio confirmada: si no hay llamadas gratis disponibles, el
     * bot no debe mencionarlas en absoluto — la nota que recibe la IA debe
     * indicarle tratar cada lista de forma independiente.
     */
    public function test_get_available_slots_indica_omitir_categorias_vacias_por_separado(): void
    {
        $this->configureCredentials();
        AppointmentSlot::create(['starts_at' => now()->addDays(2), 'ends_at' => now()->addDays(2)->addHour(), 'is_active' => true]);
        // Sin CallSlot creado — la lista de llamadas gratis queda vacía.

        $capturedToolResult = null;
        Http::fake([
            'api.anthropic.com/*' => Http::sequence()
                ->push(['stop_reason' => 'tool_use', 'content' => [['type' => 'tool_use', 'id' => 'tool_1', 'name' => 'get_available_slots', 'input' => []]]], 200)
                ->push(['stop_reason' => 'end_turn', 'content' => [['type' => 'text', 'text' => 'Ok.']]], 200),
        ]);

        app(ChatbotAiService::class)->reply([['role' => 'user', 'content' => '¿Qué horarios tienes?']]);

        Http::assertSent(function ($request) use (&$capturedToolResult) {
            foreach (($request['messages'] ?? []) as $message) {
                foreach ((is_array($message['content'] ?? null) ? $message['content'] : []) as $block) {
                    if (($block['type'] ?? null) === 'tool_result') {
                        $capturedToolResult = $block['content'];
                    }
                }
            }

            return true;
        });

        $this->assertNotNull($capturedToolResult);
        $decoded = json_decode($capturedToolResult, true);
        $this->assertNotEmpty($decoded['citas_disponibles']);
        $this->assertEmpty($decoded['llamadas_gratis_disponibles']);
        $this->assertStringContainsString('de forma independiente', $decoded['nota']);
    }

    public function test_book_appointment_incluye_las_instrucciones_de_transferencia_para_que_la_ia_las_transmita(): void
    {
        Mail::fake();
        $this->configureCredentials();
        app(SiteSettingsService::class)->set('payment', [
            'method' => 'bank_transfer',
            'bank_transfer' => ['bank_name' => 'Banco Agrícola', 'account_number' => '123456', 'account_holder' => 'Erika Magaña', 'instructions' => 'Envía el comprobante.'],
        ]);
        $slot = AppointmentSlot::create(['starts_at' => now()->addDays(2), 'ends_at' => now()->addDays(2)->addHour(), 'is_active' => true]);

        $capturedToolResult = null;
        Http::fake([
            'api.anthropic.com/*' => Http::sequence()
                ->push($this->bookAppointmentToolUseResponse($this->validAppointmentInput($slot->id)), 200)
                ->push(['stop_reason' => 'end_turn', 'content' => [['type' => 'text', 'text' => 'Listo.']]], 200),
        ]);

        app(ChatbotAiService::class)->reply([['role' => 'user', 'content' => 'Quiero agendar']]);

        Http::assertSent(function ($request) use (&$capturedToolResult) {
            foreach (($request['messages'] ?? []) as $message) {
                foreach ((is_array($message['content'] ?? null) ? $message['content'] : []) as $block) {
                    if (($block['type'] ?? null) === 'tool_result') {
                        $capturedToolResult = $block['content'];
                    }
                }
            }

            return true;
        });

        $this->assertStringContainsString('Banco Agrícola', $capturedToolResult);
        $this->assertStringContainsString('123456', $capturedToolResult);
        $this->assertStringContainsString('WhatsApp', $capturedToolResult);
    }

    /**
     * Regresión: un tool_use.input vacío llega de Anthropic como array PHP
     * ([]) — al reenviarlo tal cual en el eco del historial del segundo
     * request, json_encode() lo codifica como array JSON ([]) en vez de
     * objeto ({}), y Anthropic rechaza la request completa con
     * "Input should be an object". Esto rompía CUALQUIER tool sin
     * parámetros (get_available_slots) apenas se necesitaba una segunda
     * ronda, cortando la conversación completa (la IA nunca llegaba a pedir
     * los datos de contacto porque el reply entero fallaba).
     */
    public function test_el_segundo_request_reenvia_un_input_vacio_como_objeto_no_como_array(): void
    {
        $this->configureCredentials();

        Http::fake([
            'api.anthropic.com/*' => Http::sequence()
                ->push([
                    'stop_reason' => 'tool_use',
                    'content' => [['type' => 'tool_use', 'id' => 'tool_1', 'name' => 'get_available_slots', 'input' => []]],
                ], 200)
                ->push([
                    'stop_reason' => 'end_turn',
                    'content' => [['type' => 'text', 'text' => 'Ok.']],
                ], 200),
        ]);

        app(ChatbotAiService::class)->reply([['role' => 'user', 'content' => '¿Qué horarios tienes?']]);

        Http::assertSent(function ($request) {
            $rawBody = $request->body();
            // Si input se codificó como array vacío, el JSON crudo contiene
            // "input":[] — con el fix, debe contener "input":{}.
            if (! str_contains($rawBody, '"tool_use"')) {
                return true; // primer request, no aplica esta aserción.
            }

            return str_contains($rawBody, '"input":{}') && ! str_contains($rawBody, '"input":[]');
        });
    }

    public function test_usa_la_tool_de_guardar_lead_y_persiste_el_cliente_potencial(): void
    {
        $this->configureCredentials();

        Http::fake([
            'api.anthropic.com/*' => Http::sequence()
                ->push([
                    'stop_reason' => 'tool_use',
                    'content' => [['type' => 'tool_use', 'id' => 'tool_1', 'name' => 'save_lead', 'input' => [
                        'name' => 'María Pérez', 'email' => 'maria@example.com', 'phone' => '77778888',
                    ]]],
                ], 200)
                ->push([
                    'stop_reason' => 'end_turn',
                    'content' => [['type' => 'text', 'text' => 'Gracias, María, ya tengo tus datos.']],
                ], 200),
        ]);

        $captured = false;
        $reply = app(ChatbotAiService::class)->reply(
            [['role' => 'user', 'content' => 'Quiero agendar, mi nombre es María Pérez, mi correo es maria@example.com y mi teléfono 77778888']],
            ['channel' => 'telegram', 'external_chat_id' => '999'],
            function () use (&$captured) { $captured = true; }
        );

        $this->assertSame('Gracias, María, ya tengo tus datos.', $reply);
        $this->assertTrue($captured);
        $this->assertDatabaseHas('chatbot_leads', ['name' => 'María Pérez', 'email' => 'maria@example.com', 'phone' => '77778888']);
    }

    public function test_no_guarda_un_lead_con_correo_invalido(): void
    {
        $this->configureCredentials();

        Http::fake([
            'api.anthropic.com/*' => Http::sequence()
                ->push([
                    'stop_reason' => 'tool_use',
                    'content' => [['type' => 'tool_use', 'id' => 'tool_1', 'name' => 'save_lead', 'input' => [
                        'name' => 'María', 'email' => 'no-es-un-correo',
                    ]]],
                ], 200)
                ->push([
                    'stop_reason' => 'end_turn',
                    'content' => [['type' => 'text', 'text' => '¿Me confirmas tu correo?']],
                ], 200),
        ]);

        app(ChatbotAiService::class)->reply([['role' => 'user', 'content' => 'Mi correo es no-es-un-correo']]);

        $this->assertSame(0, ChatbotLead::count());
    }

    public function test_envia_la_temperatura_configurada_a_la_api(): void
    {
        app(SiteSettingsService::class)->set('chatbot_channels', [
            'anthropic' => ['enabled' => true, 'api_key' => 'sk-ant-test', 'temperature' => 0.7],
        ]);
        Http::fake([
            'api.anthropic.com/*' => Http::response([
                'content' => [['type' => 'text', 'text' => 'Ok.']],
                'stop_reason' => 'end_turn',
            ], 200),
        ]);

        app(ChatbotAiService::class)->reply([['role' => 'user', 'content' => 'Hola']]);

        Http::assertSent(fn ($request) => ($request['temperature'] ?? null) === 0.7);
    }

    /**
     * Regresión: Admin\ChatbotChannelsController::update() guardaba
     * temperature tal cual llegaba del <input type="range"> (un string tipo
     * "0.3"), y Anthropic rechaza la request completa si temperature no es
     * un número JSON real — la IA nunca respondía, siempre caía al modo FAQ.
     */
    public function test_convierte_la_temperatura_a_numero_aunque_se_haya_guardado_como_texto(): void
    {
        app(SiteSettingsService::class)->set('chatbot_channels', [
            'anthropic' => ['enabled' => true, 'api_key' => 'sk-ant-test', 'temperature' => '0.3'],
        ]);
        Http::fake([
            'api.anthropic.com/*' => Http::response([
                'content' => [['type' => 'text', 'text' => 'Ok.']],
                'stop_reason' => 'end_turn',
            ], 200),
        ]);

        app(ChatbotAiService::class)->reply([['role' => 'user', 'content' => 'Hola']]);

        Http::assertSent(fn ($request) => $request['temperature'] === 0.3 && is_float($request['temperature']));
    }

    public function test_incluye_el_texto_del_pdf_fuente_en_el_system_prompt(): void
    {
        app(SiteSettingsService::class)->set('chatbot_channels', [
            'anthropic' => ['enabled' => true, 'api_key' => 'sk-ant-test', 'pdf_source_text' => 'Tarifario especial: consulta $40.'],
        ]);
        Http::fake([
            'api.anthropic.com/*' => Http::response([
                'content' => [['type' => 'text', 'text' => 'Ok.']],
                'stop_reason' => 'end_turn',
            ], 200),
        ]);

        app(ChatbotAiService::class)->reply([['role' => 'user', 'content' => 'Hola']]);

        Http::assertSent(fn ($request) => str_contains($request['system'][0]['text'], 'Tarifario especial: consulta $40.'));
    }

    public function test_marca_el_system_prompt_y_las_tools_con_cache_control_para_prompt_caching(): void
    {
        $this->configureCredentials();
        Http::fake([
            'api.anthropic.com/*' => Http::response([
                'content' => [['type' => 'text', 'text' => 'Ok.']],
                'stop_reason' => 'end_turn',
            ], 200),
        ]);

        app(ChatbotAiService::class)->reply([['role' => 'user', 'content' => 'Hola']]);

        Http::assertSent(function ($request) {
            $systemHasCacheControl = ($request['system'][0]['cache_control']['type'] ?? null) === 'ephemeral';
            $lastTool = collect($request['tools'])->last();
            $toolsHaveCacheControl = ($lastTool['cache_control']['type'] ?? null) === 'ephemeral';

            return $systemHasCacheControl && $toolsHaveCacheControl;
        });
    }

    public function test_incluye_el_nombre_personalizado_del_bot_en_el_system_prompt(): void
    {
        app(SiteSettingsService::class)->set('chatbot_channels', [
            'anthropic' => ['enabled' => true, 'api_key' => 'sk-ant-test', 'bot_name' => 'Sofía'],
        ]);
        Http::fake([
            'api.anthropic.com/*' => Http::response([
                'content' => [['type' => 'text', 'text' => 'Ok.']],
                'stop_reason' => 'end_turn',
            ], 200),
        ]);

        app(ChatbotAiService::class)->reply([['role' => 'user', 'content' => 'Hola']]);

        Http::assertSent(fn ($request) => str_contains($request['system'][0]['text'], 'Te llamas Sofía'));
    }

    public function test_incluye_la_personalidad_configurada_en_el_system_prompt(): void
    {
        app(SiteSettingsService::class)->set('chatbot_channels', [
            'anthropic' => ['enabled' => true, 'api_key' => 'sk-ant-test', 'personality' => 'Usa modismos salvadoreños como "va pues".'],
        ]);
        Http::fake([
            'api.anthropic.com/*' => Http::response([
                'content' => [['type' => 'text', 'text' => 'Ok.']],
                'stop_reason' => 'end_turn',
            ], 200),
        ]);

        app(ChatbotAiService::class)->reply([['role' => 'user', 'content' => 'Hola']]);

        Http::assertSent(fn ($request) => str_contains($request['system'][0]['text'], 'va pues'));
    }

    public function test_incluye_el_saludo_configurado_en_el_system_prompt(): void
    {
        app(SiteSettingsService::class)->set('chatbot_channels', [
            'anthropic' => ['enabled' => true, 'api_key' => 'sk-ant-test', 'greeting' => '¡Qué tal! Soy tu asistente.'],
        ]);
        Http::fake([
            'api.anthropic.com/*' => Http::response([
                'content' => [['type' => 'text', 'text' => 'Ok.']],
                'stop_reason' => 'end_turn',
            ], 200),
        ]);

        app(ChatbotAiService::class)->reply([['role' => 'user', 'content' => 'Hola']]);

        Http::assertSent(fn ($request) => str_contains($request['system'][0]['text'], '¡Qué tal! Soy tu asistente.'));
    }

    public function test_incluye_los_datos_a_solicitar_configurados_en_el_system_prompt(): void
    {
        app(SiteSettingsService::class)->set('chatbot_channels', [
            'anthropic' => ['enabled' => true, 'api_key' => 'sk-ant-test', 'data_to_request' => 'Solo el nombre y el número de WhatsApp.'],
        ]);
        Http::fake([
            'api.anthropic.com/*' => Http::response([
                'content' => [['type' => 'text', 'text' => 'Ok.']],
                'stop_reason' => 'end_turn',
            ], 200),
        ]);

        app(ChatbotAiService::class)->reply([['role' => 'user', 'content' => 'Hola']]);

        Http::assertSent(fn ($request) => str_contains($request['system'][0]['text'], 'Solo el nombre y el número de WhatsApp.'));
    }

    public function test_no_es_usable_si_la_conversacion_alcanzo_el_limite_diario(): void
    {
        app(SiteSettingsService::class)->set('chatbot_channels', [
            'anthropic' => ['enabled' => true, 'api_key' => 'sk-ant-test', 'daily_message_limit' => 3],
        ]);
        $conversation = \App\Models\ChatbotConversation::create([
            'channel' => 'telegram', 'external_chat_id' => '1',
            'ai_message_count' => 3, 'ai_message_count_date' => now()->toDateString(),
        ]);

        $this->assertFalse(app(ChatbotAiService::class)->isUsable($conversation));
    }

    public function test_es_usable_si_el_limite_diario_es_de_un_dia_anterior(): void
    {
        app(SiteSettingsService::class)->set('chatbot_channels', [
            'anthropic' => ['enabled' => true, 'api_key' => 'sk-ant-test', 'daily_message_limit' => 3],
        ]);
        $conversation = \App\Models\ChatbotConversation::create([
            'channel' => 'telegram', 'external_chat_id' => '1',
            'ai_message_count' => 3, 'ai_message_count_date' => now()->subDay()->toDateString(),
        ]);

        $this->assertTrue(app(ChatbotAiService::class)->isUsable($conversation));
    }

    public function test_es_usable_por_debajo_del_limite_diario(): void
    {
        app(SiteSettingsService::class)->set('chatbot_channels', [
            'anthropic' => ['enabled' => true, 'api_key' => 'sk-ant-test', 'daily_message_limit' => 3],
        ]);
        $conversation = \App\Models\ChatbotConversation::create([
            'channel' => 'telegram', 'external_chat_id' => '1',
            'ai_message_count' => 2, 'ai_message_count_date' => now()->toDateString(),
        ]);

        $this->assertTrue(app(ChatbotAiService::class)->isUsable($conversation));
    }

    private function bookAppointmentToolUseResponse(array $input): array
    {
        return [
            'stop_reason' => 'tool_use',
            'content' => [['type' => 'tool_use', 'id' => 'tool_1', 'name' => 'book_appointment', 'input' => $input]],
        ];
    }

    private function validAppointmentInput(int $slotId): array
    {
        return [
            'name' => 'Elian Domínguez',
            'email' => 'elian@example.com',
            'phone' => '77778888',
            'birth_date' => '1995-05-20',
            'sex' => 'male',
            'department' => 'san-salvador',
            'municipality' => 'San Salvador',
            'appointment_slot_id' => $slotId,
        ];
    }

    public function test_book_appointment_crea_cuenta_nueva_y_cita_pendiente(): void
    {
        Mail::fake();
        $this->configureCredentials();
        $slot = AppointmentSlot::create(['starts_at' => now()->addDays(2), 'ends_at' => now()->addDays(2)->addHour(), 'is_active' => true]);

        Http::fake([
            'api.anthropic.com/*' => Http::sequence()
                ->push($this->bookAppointmentToolUseResponse($this->validAppointmentInput($slot->id)), 200)
                ->push(['stop_reason' => 'end_turn', 'content' => [['type' => 'text', 'text' => 'Listo, tu cita quedó pendiente.']]], 200),
        ]);

        $reply = app(ChatbotAiService::class)->reply([['role' => 'user', 'content' => 'Quiero agendar']]);

        $this->assertSame('Listo, tu cita quedó pendiente.', $reply);
        $user = User::where('email', 'elian@example.com')->first();
        $this->assertNotNull($user);
        $this->assertSame('patient', $user->role);
        $this->assertSame('77778888', $user->phone_number);

        $appointment = Appointment::where('user_id', $user->id)->first();
        $this->assertNotNull($appointment);
        $this->assertSame($slot->id, $appointment->appointment_slot_id);
        $this->assertSame('pending', $appointment->status->value);

        Mail::assertSent(AccountCreatedByChatbot::class, fn ($mail) => $mail->hasTo('elian@example.com'));
    }

    public function test_book_appointment_reutiliza_la_cuenta_si_el_correo_ya_existe(): void
    {
        Mail::fake();
        $this->configureCredentials();
        $existingUser = User::factory()->create(['email' => 'elian@example.com', 'role' => 'patient']);
        $slot = AppointmentSlot::create(['starts_at' => now()->addDays(2), 'ends_at' => now()->addDays(2)->addHour(), 'is_active' => true]);

        Http::fake([
            'api.anthropic.com/*' => Http::sequence()
                ->push($this->bookAppointmentToolUseResponse($this->validAppointmentInput($slot->id)), 200)
                ->push(['stop_reason' => 'end_turn', 'content' => [['type' => 'text', 'text' => 'Listo.']]], 200),
        ]);

        app(ChatbotAiService::class)->reply([['role' => 'user', 'content' => 'Quiero agendar']]);

        $this->assertSame(1, User::where('email', 'elian@example.com')->count());
        $appointment = Appointment::where('user_id', $existingUser->id)->first();
        $this->assertNotNull($appointment);
        // No se crea cuenta nueva, así que no se manda correo de credenciales.
        Mail::assertNotSent(AccountCreatedByChatbot::class);
    }

    public function test_book_appointment_no_reserva_si_faltan_datos(): void
    {
        Mail::fake();
        $this->configureCredentials();
        $slot = AppointmentSlot::create(['starts_at' => now()->addDays(2), 'ends_at' => now()->addDays(2)->addHour(), 'is_active' => true]);
        $incompleteInput = ['name' => 'Elian', 'email' => 'elian@example.com', 'appointment_slot_id' => $slot->id];

        Http::fake([
            'api.anthropic.com/*' => Http::sequence()
                ->push($this->bookAppointmentToolUseResponse($incompleteInput), 200)
                ->push(['stop_reason' => 'end_turn', 'content' => [['type' => 'text', 'text' => '¿Me das tu teléfono?']]], 200),
        ]);

        app(ChatbotAiService::class)->reply([['role' => 'user', 'content' => 'Quiero agendar']]);

        $this->assertDatabaseCount('users', 0);
        $this->assertDatabaseCount('appointments', 0);
    }

    public function test_book_appointment_rechaza_un_departamento_invalido(): void
    {
        Mail::fake();
        $this->configureCredentials();
        $slot = AppointmentSlot::create(['starts_at' => now()->addDays(2), 'ends_at' => now()->addDays(2)->addHour(), 'is_active' => true]);
        $input = array_merge($this->validAppointmentInput($slot->id), ['department' => 'no-existe']);

        Http::fake([
            'api.anthropic.com/*' => Http::sequence()
                ->push($this->bookAppointmentToolUseResponse($input), 200)
                ->push(['stop_reason' => 'end_turn', 'content' => [['type' => 'text', 'text' => 'Ok.']]], 200),
        ]);

        app(ChatbotAiService::class)->reply([['role' => 'user', 'content' => 'Quiero agendar']]);

        $this->assertDatabaseCount('appointments', 0);
    }

    public function test_book_appointment_rechaza_un_municipio_que_no_pertenece_al_departamento(): void
    {
        Mail::fake();
        $this->configureCredentials();
        $slot = AppointmentSlot::create(['starts_at' => now()->addDays(2), 'ends_at' => now()->addDays(2)->addHour(), 'is_active' => true]);
        $input = array_merge($this->validAppointmentInput($slot->id), ['department' => 'san-salvador', 'municipality' => 'Ahuachapán']);

        Http::fake([
            'api.anthropic.com/*' => Http::sequence()
                ->push($this->bookAppointmentToolUseResponse($input), 200)
                ->push(['stop_reason' => 'end_turn', 'content' => [['type' => 'text', 'text' => 'Ok.']]], 200),
        ]);

        app(ChatbotAiService::class)->reply([['role' => 'user', 'content' => 'Quiero agendar']]);

        $this->assertDatabaseCount('appointments', 0);
    }

    public function test_book_appointment_no_reserva_un_horario_ya_tomado(): void
    {
        Mail::fake();
        $this->configureCredentials();
        $slot = AppointmentSlot::create(['starts_at' => now()->addDays(2), 'ends_at' => now()->addDays(2)->addHour(), 'is_active' => true]);
        $existingUser = User::factory()->create(['role' => 'patient']);
        Appointment::create(['user_id' => $existingUser->id, 'appointment_slot_id' => $slot->id, 'payment_method' => 'bank_transfer']);

        Http::fake([
            'api.anthropic.com/*' => Http::sequence()
                ->push($this->bookAppointmentToolUseResponse($this->validAppointmentInput($slot->id)), 200)
                ->push(['stop_reason' => 'end_turn', 'content' => [['type' => 'text', 'text' => 'Ese horario ya no está disponible.']]], 200),
        ]);

        app(ChatbotAiService::class)->reply([['role' => 'user', 'content' => 'Quiero agendar']]);

        // Solo el usuario ya existente (el que reservó el slot en el setup)
        // — no se crea una cuenta nueva para el correo de validAppointmentInput().
        $this->assertDatabaseCount('users', 1);
        $this->assertDatabaseMissing('users', ['email' => 'elian@example.com']);
    }
}
