<?php

namespace Tests\Feature\Admin;

use App\Models\FacilitySchedule;
use App\Models\Member;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class FacilityScheduleTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
    }

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

    public function test_admin_can_view_facility_schedule(): void
    {
        $admin = $this->createAdmin();
        Sanctum::actingAs($admin);

        // Create some schedules
        FacilitySchedule::create([
            'day_of_week' => 1,
            'open_time' => '08:00:00',
            'close_time' => '20:00:00',
            'is_open' => true,
        ]);

        FacilitySchedule::create([
            'day_of_week' => 2,
            'open_time' => '09:00:00',
            'close_time' => '21:00:00',
            'is_open' => true,
        ]);

        $response = $this->getJson('/api/admin/facility/schedule');

        $response->assertStatus(200)
            ->assertJsonStructure([
                '*' => [
                    'id',
                    'day_of_week',
                    'day_name',
                    'open_time',
                    'close_time',
                    'is_open',
                ],
            ]);

        $data = $response->json();
        $this->assertCount(2, $data);
        $this->assertEquals(1, $data[0]['day_of_week']);
        $this->assertEquals('Monday', $data[0]['day_name']);
    }

    public function test_empty_schedule_returns_default_structure(): void
    {
        $admin = $this->createAdmin();
        Sanctum::actingAs($admin);

        $response = $this->getJson('/api/admin/facility/schedule');

        $response->assertStatus(200);
        $data = $response->json();
        $this->assertCount(7, $data);
        
        // Check all 7 days are present (0-6)
        $daysOfWeek = array_column($data, 'day_of_week');
        $this->assertEquals([0, 1, 2, 3, 4, 5, 6], $daysOfWeek);
        
        // Check all are closed by default
        foreach ($data as $day) {
            $this->assertFalse($day['is_open']);
            $this->assertNull($day['open_time']);
            $this->assertNull($day['close_time']);
        }
    }

    public function test_admin_can_update_single_day_schedule(): void
    {
        $admin = $this->createAdmin();
        Sanctum::actingAs($admin);

        $response = $this->putJson('/api/admin/facility/schedule/1', [
            'day_of_week' => 1,
            'is_open' => true,
            'open_time' => '08:00',
            'close_time' => '20:00',
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'message',
                'schedule' => [
                    'id',
                    'day_of_week',
                    'day_name',
                    'open_time',
                    'close_time',
                    'is_open',
                ],
            ]);

        $this->assertEquals('Schedule updated successfully.', $response->json('message'));
        $schedule = $response->json('schedule');
        $this->assertEquals(1, $schedule['day_of_week']);
        $this->assertEquals('Monday', $schedule['day_name']);
        $this->assertEquals('08:00', $schedule['open_time']);
        $this->assertEquals('20:00', $schedule['close_time']);
        $this->assertTrue($schedule['is_open']);

        // Verify in database
        $dbSchedule = FacilitySchedule::where('day_of_week', 1)->first();
        $this->assertNotNull($dbSchedule);
        $this->assertTrue($dbSchedule->is_open);
        $this->assertEquals('08:00:00', $dbSchedule->open_time);
        $this->assertEquals('20:00:00', $dbSchedule->close_time);
    }

    public function test_admin_can_update_multiple_days_schedule(): void
    {
        $admin = $this->createAdmin();
        Sanctum::actingAs($admin);

        $response = $this->putJson('/api/admin/facility/schedule', [
            'schedules' => [
                [
                    'day_of_week' => 0,
                    'is_open' => true,
                    'open_time' => '09:00',
                    'close_time' => '18:00',
                ],
                [
                    'day_of_week' => 1,
                    'is_open' => true,
                    'open_time' => '08:00',
                    'close_time' => '20:00',
                ],
                [
                    'day_of_week' => 2,
                    'is_open' => false,
                    'open_time' => null,
                    'close_time' => null,
                ],
            ],
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'message',
                'schedules' => [
                    '*' => [
                        'id',
                        'day_of_week',
                        'day_name',
                        'open_time',
                        'close_time',
                        'is_open',
                    ],
                ],
            ]);

        $this->assertEquals('Facility schedule updated successfully.', $response->json('message'));
        $schedules = $response->json('schedules');
        $this->assertCount(3, $schedules);

        // Verify in database
        $monday = FacilitySchedule::where('day_of_week', 1)->first();
        $this->assertTrue($monday->is_open);
        $this->assertEquals('08:00:00', $monday->open_time);

        $tuesday = FacilitySchedule::where('day_of_week', 2)->first();
        $this->assertFalse($tuesday->is_open);
    }

    public function test_validation_close_time_must_be_after_open_time(): void
    {
        $admin = $this->createAdmin();
        Sanctum::actingAs($admin);

        $response = $this->putJson('/api/admin/facility/schedule/1', [
            'day_of_week' => 1,
            'is_open' => true,
            'open_time' => '20:00',
            'close_time' => '08:00', // Invalid: close before open
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['close_time']);
    }

    public function test_validation_day_of_week_must_be_0_to_6(): void
    {
        $admin = $this->createAdmin();
        Sanctum::actingAs($admin);

        // Test invalid day (7)
        $response = $this->putJson('/api/admin/facility/schedule/7', [
            'day_of_week' => 7,
            'is_open' => true,
            'open_time' => '08:00',
            'close_time' => '20:00',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['day_of_week']);

        // Test invalid day (-1)
        $response = $this->putJson('/api/admin/facility/schedule/-1', [
            'day_of_week' => -1,
            'is_open' => true,
            'open_time' => '08:00',
            'close_time' => '20:00',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['day_of_week']);
    }

    public function test_validation_time_format_must_be_h_i(): void
    {
        $admin = $this->createAdmin();
        Sanctum::actingAs($admin);

        // Test invalid time format
        $response = $this->putJson('/api/admin/facility/schedule/1', [
            'day_of_week' => 1,
            'is_open' => true,
            'open_time' => '8:00 AM', // Invalid format
            'close_time' => '20:00',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['open_time']);

        // Test another invalid format
        $response = $this->putJson('/api/admin/facility/schedule/1', [
            'day_of_week' => 1,
            'is_open' => true,
            'open_time' => '08:00',
            'close_time' => '8 PM', // Invalid format
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['close_time']);
    }

    public function test_non_admin_cannot_access_schedule_endpoints(): void
    {
        $member = $this->createMember();
        Sanctum::actingAs($member);

        // Test index
        $response = $this->getJson('/api/admin/facility/schedule');
        $response->assertStatus(403);

        // Test update single day
        $response = $this->putJson('/api/admin/facility/schedule/1', [
            'day_of_week' => 1,
            'is_open' => true,
            'open_time' => '08:00',
            'close_time' => '20:00',
        ]);
        $response->assertStatus(403);

        // Test update multiple days
        $response = $this->putJson('/api/admin/facility/schedule', [
            'schedules' => [],
        ]);
        $response->assertStatus(403);
    }

    public function test_unauthenticated_users_cannot_access_schedule_endpoints(): void
    {
        // Test index
        $response = $this->getJson('/api/admin/facility/schedule');
        $response->assertStatus(401);

        // Test update single day
        $response = $this->putJson('/api/admin/facility/schedule/1', [
            'day_of_week' => 1,
            'is_open' => true,
            'open_time' => '08:00',
            'close_time' => '20:00',
        ]);
        $response->assertStatus(401);

        // Test update multiple days
        $response = $this->putJson('/api/admin/facility/schedule', [
            'schedules' => [],
        ]);
        $response->assertStatus(401);
    }
}

