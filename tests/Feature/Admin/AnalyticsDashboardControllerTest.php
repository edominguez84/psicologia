<?php

namespace Tests\Feature\Admin;

use App\Models\AnalyticsEvent;
use App\Models\Appointment;
use App\Models\AppointmentSlot;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AnalyticsDashboardControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_invitado_no_puede_ver_el_dashboard(): void
    {
        $this->get('/admin/analytics')->assertRedirect(route('login'));
    }

    public function test_un_administrador_normal_no_puede_ver_el_dashboard(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)->get('/admin/analytics')->assertForbidden();
    }

    public function test_super_admin_puede_ver_el_dashboard(): void
    {
        $superAdmin = User::factory()->create(['role' => 'super_admin']);

        $this->actingAs($superAdmin)->get('/admin/analytics')->assertOk();
    }

    public function test_cuenta_las_visitas_al_sitio(): void
    {
        AnalyticsEvent::create(['type' => 'page_view', 'session_id' => 'a']);
        AnalyticsEvent::create(['type' => 'page_view', 'session_id' => 'b']);
        AnalyticsEvent::create(['type' => 'social_click', 'meta' => ['network' => 'facebook'], 'session_id' => 'c']);

        $superAdmin = User::factory()->create(['role' => 'super_admin']);
        $response = $this->actingAs($superAdmin)->get('/admin/analytics');

        $response->assertViewHas('siteVisits', fn ($visits) => $visits['total'] === 2);
    }

    public function test_cuenta_clics_por_red_social(): void
    {
        AnalyticsEvent::create(['type' => 'social_click', 'meta' => ['network' => 'instagram'], 'session_id' => 'a']);
        AnalyticsEvent::create(['type' => 'social_click', 'meta' => ['network' => 'instagram'], 'session_id' => 'b']);
        AnalyticsEvent::create(['type' => 'social_click', 'meta' => ['network' => 'facebook'], 'session_id' => 'c']);

        $superAdmin = User::factory()->create(['role' => 'super_admin']);
        $response = $this->actingAs($superAdmin)->get('/admin/analytics');

        $response->assertViewHas('socialClicks', function ($chart) {
            $instagramIndex = array_search('Instagram', $chart['labels']);

            return $chart['total'] === 3 && $chart['data'][$instagramIndex] === 2;
        });
    }

    public function test_calcula_el_embudo_de_conversion(): void
    {
        User::factory()->create(['role' => 'patient']);
        $patientWithAppointment = User::factory()->create(['role' => 'patient']);
        $slot = AppointmentSlot::factory()->create();
        Appointment::factory()->create(['user_id' => $patientWithAppointment->id, 'appointment_slot_id' => $slot->id]);

        $superAdmin = User::factory()->create(['role' => 'super_admin']);
        $response = $this->actingAs($superAdmin)->get('/admin/analytics');

        $response->assertViewHas('conversionFunnel', fn ($funnel) => $funnel['registered'] === 2 && $funnel['withAppointment'] === 1);
    }

    public function test_desglosa_genero_de_registrados_y_con_cita(): void
    {
        User::factory()->create(['role' => 'patient', 'sex' => 'female']);
        $maleWithAppointment = User::factory()->create(['role' => 'patient', 'sex' => 'male']);
        $slot = AppointmentSlot::factory()->create();
        Appointment::factory()->create(['user_id' => $maleWithAppointment->id, 'appointment_slot_id' => $slot->id]);

        $superAdmin = User::factory()->create(['role' => 'super_admin']);
        $response = $this->actingAs($superAdmin)->get('/admin/analytics');

        $response->assertViewHas('genderBreakdown', function ($breakdown) {
            $maleIndex = array_search('Hombres', $breakdown['labels']);
            $femaleIndex = array_search('Mujeres', $breakdown['labels']);

            return $breakdown['registered'][$maleIndex] === 1
                && $breakdown['registered'][$femaleIndex] === 1
                && $breakdown['withAppointment'][$maleIndex] === 1
                && $breakdown['withAppointment'][$femaleIndex] === 0;
        });
    }

    public function test_desglosa_registros_por_departamento(): void
    {
        User::factory()->create(['role' => 'patient', 'department' => 'san-salvador']);
        User::factory()->create(['role' => 'patient', 'department' => 'san-salvador']);
        User::factory()->create(['role' => 'patient', 'department' => 'santa-ana']);

        $superAdmin = User::factory()->create(['role' => 'super_admin']);
        $response = $this->actingAs($superAdmin)->get('/admin/analytics');

        $response->assertViewHas('departmentBreakdown', function ($breakdown) {
            $sanSalvadorIndex = array_search('San Salvador', $breakdown['labels']);

            return $breakdown['data'][$sanSalvadorIndex] === 2;
        });
    }

    public function test_desglosa_citas_aprobadas_y_canceladas_por_mes(): void
    {
        $slot1 = AppointmentSlot::factory()->create();
        $slot2 = AppointmentSlot::factory()->create();
        Appointment::factory()->create(['appointment_slot_id' => $slot1->id, 'status' => 'approved']);
        Appointment::factory()->create(['appointment_slot_id' => $slot2->id, 'status' => 'cancelled']);

        $superAdmin = User::factory()->create(['role' => 'super_admin']);
        $response = $this->actingAs($superAdmin)->get('/admin/analytics');

        $response->assertViewHas('appointmentsByMonth', function ($chart) {
            return array_sum($chart['approved']) === 1 && array_sum($chart['cancelled']) === 1;
        });
    }
}
