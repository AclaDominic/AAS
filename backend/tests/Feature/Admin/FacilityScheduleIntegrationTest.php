<?php

namespace Tests\Feature\Admin;

use App\Models\FacilitySchedule;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * Integration test to verify frontend-backend API connection
 * These tests simulate what the frontend actually sends/receives
 */
class FacilityScheduleIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected function createAdmin(): User
    {
        return User::factory()->create();
    }

    public function test_frontend_can_fetch_schedule_list(): void
    {
        // Simulate frontend request
        $admin = $this->createAdmin();
        Sanctum::actingAs($admin);

        // Create some schedules (what would be in database)
        FacilitySchedule::create([
            'day_of_week' => 1,
            'open_time' => '08:00:00',
            'close_time' => '20:00:00',
            'is_open' => true,
        ]);

        // Frontend makes this exact request (from FacilitySchedule.jsx line 20)
        $response = $this->getJson('/api/admin/facility/schedule', [
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
        ]);

        // Verify response structure matches what frontend expects
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

        // Verify data format matches frontend expectations
        $data = $response->json();
        $this->assertIsArray($data);
        $this->assertCount(1, $data);
        $this->assertEquals('08:00', $data[0]['open_time']); // Frontend expects HH:MM format
        $this->assertEquals('20:00', $data[0]['close_time']);
    }

    public function test_frontend_can_update_schedule(): void
    {
        // Simulate frontend update request
        $admin = $this->createAdmin();
        Sanctum::actingAs($admin);

        // Frontend sends this exact format (from FacilitySchedule.jsx lines 56-65)
        $schedulesToUpdate = [
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
        ];

        $response = $this->putJson('/api/admin/facility/schedule', [
            'schedules' => $schedulesToUpdate,
        ], [
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
        ]);

        // Verify response matches what frontend expects
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

        // Verify success message format
        $this->assertStringContainsString('successfully', $response->json('message'));
        
        // Verify data was actually saved
        $this->assertDatabaseHas('facility_schedules', [
            'day_of_week' => 0,
            'is_open' => true,
        ]);
    }

    public function test_frontend_receives_proper_error_on_unauthorized(): void
    {
        // Test without authentication (simulating expired token or no login)
        $response = $this->getJson('/api/admin/facility/schedule', [
            'Accept' => 'application/json',
        ]);

        // Frontend should receive 401 (frontend handles this in api.js interceptors)
        $response->assertStatus(401);
    }
}

