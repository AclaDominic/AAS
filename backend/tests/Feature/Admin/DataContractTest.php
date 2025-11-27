<?php

namespace Tests\Feature\Admin;

use App\Models\CourtReservation;
use App\Models\FacilitySchedule;
use App\Models\FacilitySetting;
use App\Models\Member;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * Data Contract Tests
 * Verify that frontend and backend data structures match exactly
 */
class DataContractTest extends TestCase
{
    use RefreshDatabase;

    protected function createAdmin(): User
    {
        return User::factory()->create();
    }

    protected function createMember(): User
    {
        $user = User::factory()->create();
        Member::create(['user_id' => $user->id]);
        return $user;
    }

    /**
     * Test Facility Schedule GET response matches frontend expectations
     * Frontend expects: response.data to be array with day_of_week, is_open, open_time, close_time
     */
    public function test_facility_schedule_get_response_matches_frontend_expectations(): void
    {
        $admin = $this->createAdmin();
        Sanctum::actingAs($admin);

        FacilitySchedule::create([
            'day_of_week' => 1,
            'open_time' => '08:00:00',
            'close_time' => '20:00:00',
            'is_open' => true,
        ]);

        $response = $this->getJson('/api/admin/facility/schedule');
        $response->assertStatus(200);

        $data = $response->json();

        // Frontend expects array (line 21: setSchedules(response.data))
        $this->assertIsArray($data);
        $this->assertNotEmpty($data);

        // Frontend accesses these fields (lines 33, 139, 149, 164)
        $schedule = $data[0];
        $this->assertArrayHasKey('day_of_week', $schedule); // Frontend uses: schedule.day_of_week
        $this->assertArrayHasKey('is_open', $schedule); // Frontend uses: schedule.is_open
        $this->assertArrayHasKey('open_time', $schedule); // Frontend uses: schedule.open_time
        $this->assertArrayHasKey('close_time', $schedule); // Frontend uses: schedule.close_time

        // Frontend expects times in HH:MM format (HTML time input format)
        if ($schedule['open_time']) {
            $this->assertMatchesRegularExpression('/^\d{2}:\d{2}$/', $schedule['open_time']);
        }
        if ($schedule['close_time']) {
            $this->assertMatchesRegularExpression('/^\d{2}:\d{2}$/', $schedule['close_time']);
        }
    }

    /**
     * Test Facility Schedule PUT request/response matches frontend format
     * Frontend sends (lines 56-65): { schedules: [{ day_of_week, is_open, open_time, close_time }] }
     */
    public function test_facility_schedule_put_request_response_matches_frontend_format(): void
    {
        $admin = $this->createAdmin();
        Sanctum::actingAs($admin);

        // Frontend sends exactly this format (FacilitySchedule.jsx lines 56-65)
        $frontendPayload = [
            'schedules' => [
                [
                    'day_of_week' => 1,
                    'is_open' => true,
                    'open_time' => '08:00',
                    'close_time' => '20:00',
                ],
            ],
        ];

        $response = $this->putJson('/api/admin/facility/schedule', $frontendPayload);
        $response->assertStatus(200);

        // Frontend expects response.data.message and response.data.schedules (line 67)
        $data = $response->json();
        $this->assertArrayHasKey('message', $data);
        $this->assertArrayHasKey('schedules', $data);
        $this->assertIsArray($data['schedules']);

        // Verify schedule structure matches what frontend expects to receive back
        $schedule = $data['schedules'][0];
        $this->assertArrayHasKey('day_of_week', $schedule);
        $this->assertArrayHasKey('is_open', $schedule);
        $this->assertArrayHasKey('open_time', $schedule);
        $this->assertArrayHasKey('close_time', $schedule);
    }

    /**
     * Test Facility Settings GET response matches frontend expectations
     * Frontend expects (line 23): response.data with number_of_courts, minimum_reservation_duration_minutes, advance_booking_days
     */
    public function test_facility_settings_get_response_matches_frontend_expectations(): void
    {
        $admin = $this->createAdmin();
        Sanctum::actingAs($admin);

        FacilitySetting::create([
            'number_of_courts' => 2,
            'minimum_reservation_duration_minutes' => 30,
            'advance_booking_days' => 30,
        ]);

        $response = $this->getJson('/api/admin/facility/settings');
        $response->assertStatus(200);

        $data = $response->json();

        // Frontend reads response.data directly (line 23: setSettings(response.data))
        // Frontend expects these exact fields (lines 7-10, 122, 146, 179)
        $this->assertArrayHasKey('number_of_courts', $data);
        $this->assertArrayHasKey('minimum_reservation_duration_minutes', $data);
        $this->assertArrayHasKey('advance_booking_days', $data);

        // Verify types match frontend expectations
        $this->assertIsInt($data['number_of_courts']);
        $this->assertIsInt($data['minimum_reservation_duration_minutes']);
        $this->assertIsInt($data['advance_booking_days']);
    }

    /**
     * Test Facility Settings PUT request/response matches frontend format
     * Frontend sends (line 44): settings object directly
     * Frontend expects (line 46): response.data.message
     */
    public function test_facility_settings_put_request_response_matches_frontend_format(): void
    {
        $admin = $this->createAdmin();
        Sanctum::actingAs($admin);

        FacilitySetting::create([
            'number_of_courts' => 2,
            'minimum_reservation_duration_minutes' => 30,
            'advance_booking_days' => 30,
        ]);

        // Frontend sends settings object directly (FacilitySettings.jsx line 44)
        $frontendPayload = [
            'number_of_courts' => 3,
            'minimum_reservation_duration_minutes' => 60,
            'advance_booking_days' => 45,
        ];

        $response = $this->putJson('/api/admin/facility/settings', $frontendPayload);
        $response->assertStatus(200);

        // Frontend expects response.data.message (line 46)
        $data = $response->json();
        $this->assertArrayHasKey('message', $data);
        $this->assertStringContainsString('successfully', $data['message']);
    }

    /**
     * Test Court Reservations GET response matches frontend expectations
     * Frontend expects (line 30): response.data.data (paginated Laravel response)
     * Frontend accesses (lines 236-257): reservation.user.name, reservation.user.email, reservation.start_time, etc.
     */
    public function test_court_reservations_get_response_matches_frontend_expectations(): void
    {
        $admin = $this->createAdmin();
        Sanctum::actingAs($admin);

        $user = $this->createMember();
        $today = Carbon::today();

        CourtReservation::create([
            'user_id' => $user->id,
            'court_number' => 1,
            'reservation_date' => $today,
            'start_time' => $today->copy()->setTime(10, 0),
            'end_time' => $today->copy()->setTime(11, 0),
            'duration_minutes' => 60,
            'status' => 'CONFIRMED',
        ]);

        $response = $this->getJson('/api/admin/reservations');
        $response->assertStatus(200);

        $data = $response->json();

        // Frontend expects paginated Laravel response with data key (line 30: response.data.data)
        $this->assertArrayHasKey('data', $data);
        $this->assertIsArray($data['data']);

        if (!empty($data['data'])) {
            $reservation = $data['data'][0];

            // Frontend accesses these fields (CourtReservations.jsx lines 234-257)
            $this->assertArrayHasKey('id', $reservation);
            $this->assertArrayHasKey('court_number', $reservation); // Line 249: reservation.court_number
            $this->assertArrayHasKey('status', $reservation); // Line 269: reservation.status
            $this->assertArrayHasKey('duration_minutes', $reservation); // Line 257: reservation.duration_minutes
            $this->assertArrayHasKey('start_time', $reservation); // Line 236: new Date(reservation.start_time)
            $this->assertArrayHasKey('end_time', $reservation); // Line 244: new Date(reservation.end_time)

            // Frontend accesses user nested object (lines 251-254)
            $this->assertArrayHasKey('user', $reservation);
            $this->assertArrayHasKey('name', $reservation['user']); // Line 251: reservation.user?.name
            $this->assertArrayHasKey('email', $reservation['user']); // Line 254: reservation.user?.email

            // Frontend expects start_time and end_time to be parseable by new Date()
            // Should be ISO datetime string or valid datetime format
            $this->assertNotNull($reservation['start_time']);
            $this->assertNotNull($reservation['end_time']);
        }
    }

    /**
     * Test Court Reservations filters match frontend query params
     * Frontend sends (lines 24-27): date, court_number, status, member_search as query params
     */
    public function test_court_reservations_filters_match_frontend_query_params(): void
    {
        $admin = $this->createAdmin();
        Sanctum::actingAs($admin);

        $user = User::factory()->create(['name' => 'John Doe', 'email' => 'john@example.com']);
        Member::create(['user_id' => $user->id]);
        $today = Carbon::today();

        CourtReservation::create([
            'user_id' => $user->id,
            'court_number' => 1,
            'reservation_date' => $today,
            'start_time' => $today->copy()->setTime(10, 0),
            'end_time' => $today->copy()->setTime(11, 0),
            'duration_minutes' => 60,
            'status' => 'CONFIRMED',
        ]);

        // Frontend sends filters exactly like this (CourtReservations.jsx lines 24-27)
        $response = $this->getJson('/api/admin/reservations?date=' . $today->format('Y-m-d') . '&court_number=1&status=CONFIRMED&member_search=John');

        $response->assertStatus(200);
        $data = $response->json('data');
        
        // Should return filtered results
        $this->assertIsArray($data);
        if (!empty($data)) {
            $this->assertEquals(1, $data[0]['court_number']);
            $this->assertEquals('CONFIRMED', $data[0]['status']);
            $this->assertEquals('John Doe', $data[0]['user']['name']);
        }
    }

    /**
     * Test Court Reservation Cancel request/response matches frontend format
     * Frontend sends (lines 53-54): { reason: 'Cancelled by admin' }
     * Frontend expects (line 56): response.data.message
     */
    public function test_court_reservation_cancel_request_response_matches_frontend_format(): void
    {
        $admin = $this->createAdmin();
        Sanctum::actingAs($admin);

        $user = $this->createMember();
        $today = Carbon::today();

        $reservation = CourtReservation::create([
            'user_id' => $user->id,
            'court_number' => 1,
            'reservation_date' => $today,
            'start_time' => $today->copy()->setTime(10, 0),
            'end_time' => $today->copy()->setTime(11, 0),
            'duration_minutes' => 60,
            'status' => 'CONFIRMED',
        ]);

        // Frontend sends exactly this format (CourtReservations.jsx lines 53-54)
        $frontendPayload = [
            'reason' => 'Cancelled by admin',
        ];

        $response = $this->postJson("/api/admin/reservations/{$reservation->id}/cancel", $frontendPayload);
        $response->assertStatus(200);

        // Frontend expects response.data.message (line 56)
        $data = $response->json();
        $this->assertArrayHasKey('message', $data);
        $this->assertStringContainsString('successfully', $data['message']);
    }
}

