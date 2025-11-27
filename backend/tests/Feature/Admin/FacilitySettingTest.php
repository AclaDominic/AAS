<?php

namespace Tests\Feature\Admin;

use App\Models\FacilitySetting;
use App\Models\Member;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class FacilitySettingTest extends TestCase
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

    public function test_admin_can_view_current_facility_settings(): void
    {
        $admin = $this->createAdmin();
        Sanctum::actingAs($admin);

        // Create default settings
        FacilitySetting::create([
            'number_of_courts' => 2,
            'minimum_reservation_duration_minutes' => 30,
            'advance_booking_days' => 30,
        ]);

        $response = $this->getJson('/api/admin/facility/settings');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'id',
                'number_of_courts',
                'minimum_reservation_duration_minutes',
                'advance_booking_days',
            ]);

        $data = $response->json();
        $this->assertEquals(2, $data['number_of_courts']);
        $this->assertEquals(30, $data['minimum_reservation_duration_minutes']);
        $this->assertEquals(30, $data['advance_booking_days']);
    }

    public function test_admin_can_update_number_of_courts(): void
    {
        $admin = $this->createAdmin();
        Sanctum::actingAs($admin);

        // Create initial settings
        FacilitySetting::create([
            'number_of_courts' => 2,
            'minimum_reservation_duration_minutes' => 30,
            'advance_booking_days' => 30,
        ]);

        $response = $this->putJson('/api/admin/facility/settings', [
            'number_of_courts' => 4,
            'minimum_reservation_duration_minutes' => 30,
            'advance_booking_days' => 30,
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'message',
                'settings' => [
                    'id',
                    'number_of_courts',
                    'minimum_reservation_duration_minutes',
                    'advance_booking_days',
                ],
            ]);

        $this->assertEquals('Facility settings updated successfully.', $response->json('message'));
        $this->assertEquals(4, $response->json('settings.number_of_courts'));

        // Verify in database
        $settings = FacilitySetting::first();
        $this->assertEquals(4, $settings->number_of_courts);
    }

    public function test_admin_can_update_minimum_reservation_duration_minutes(): void
    {
        $admin = $this->createAdmin();
        Sanctum::actingAs($admin);

        FacilitySetting::create([
            'number_of_courts' => 2,
            'minimum_reservation_duration_minutes' => 30,
            'advance_booking_days' => 30,
        ]);

        $response = $this->putJson('/api/admin/facility/settings', [
            'number_of_courts' => 2,
            'minimum_reservation_duration_minutes' => 60,
            'advance_booking_days' => 30,
        ]);

        $response->assertStatus(200);
        $this->assertEquals(60, $response->json('settings.minimum_reservation_duration_minutes'));

        // Verify in database
        $settings = FacilitySetting::first();
        $this->assertEquals(60, $settings->minimum_reservation_duration_minutes);
    }

    public function test_admin_can_update_advance_booking_days(): void
    {
        $admin = $this->createAdmin();
        Sanctum::actingAs($admin);

        FacilitySetting::create([
            'number_of_courts' => 2,
            'minimum_reservation_duration_minutes' => 30,
            'advance_booking_days' => 30,
        ]);

        $response = $this->putJson('/api/admin/facility/settings', [
            'number_of_courts' => 2,
            'minimum_reservation_duration_minutes' => 30,
            'advance_booking_days' => 60,
        ]);

        $response->assertStatus(200);
        $this->assertEquals(60, $response->json('settings.advance_booking_days'));

        // Verify in database
        $settings = FacilitySetting::first();
        $this->assertEquals(60, $settings->advance_booking_days);
    }

    public function test_validation_number_of_courts_must_be_at_least_1(): void
    {
        $admin = $this->createAdmin();
        Sanctum::actingAs($admin);

        FacilitySetting::create([
            'number_of_courts' => 2,
            'minimum_reservation_duration_minutes' => 30,
            'advance_booking_days' => 30,
        ]);

        // Test zero courts
        $response = $this->putJson('/api/admin/facility/settings', [
            'number_of_courts' => 0,
            'minimum_reservation_duration_minutes' => 30,
            'advance_booking_days' => 30,
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['number_of_courts']);

        // Test negative courts
        $response = $this->putJson('/api/admin/facility/settings', [
            'number_of_courts' => -1,
            'minimum_reservation_duration_minutes' => 30,
            'advance_booking_days' => 30,
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['number_of_courts']);
    }

    public function test_validation_minimum_reservation_duration_must_be_allowed_value(): void
    {
        $admin = $this->createAdmin();
        Sanctum::actingAs($admin);

        FacilitySetting::create([
            'number_of_courts' => 2,
            'minimum_reservation_duration_minutes' => 30,
            'advance_booking_days' => 30,
        ]);

        $allowedValues = [30, 60, 90, 120, 150, 180];

        // Test each allowed value works
        foreach ($allowedValues as $value) {
            $response = $this->putJson('/api/admin/facility/settings', [
                'number_of_courts' => 2,
                'minimum_reservation_duration_minutes' => $value,
                'advance_booking_days' => 30,
            ]);

            $response->assertStatus(200);
        }

        // Test invalid value
        $response = $this->putJson('/api/admin/facility/settings', [
            'number_of_courts' => 2,
            'minimum_reservation_duration_minutes' => 45, // Not in allowed list
            'advance_booking_days' => 30,
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['minimum_reservation_duration_minutes']);

        // Test another invalid value
        $response = $this->putJson('/api/admin/facility/settings', [
            'number_of_courts' => 2,
            'minimum_reservation_duration_minutes' => 200, // Not in allowed list
            'advance_booking_days' => 30,
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['minimum_reservation_duration_minutes']);
    }

    public function test_validation_advance_booking_days_must_be_1_to_365(): void
    {
        $admin = $this->createAdmin();
        Sanctum::actingAs($admin);

        FacilitySetting::create([
            'number_of_courts' => 2,
            'minimum_reservation_duration_minutes' => 30,
            'advance_booking_days' => 30,
        ]);

        // Test zero days
        $response = $this->putJson('/api/admin/facility/settings', [
            'number_of_courts' => 2,
            'minimum_reservation_duration_minutes' => 30,
            'advance_booking_days' => 0,
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['advance_booking_days']);

        // Test negative days
        $response = $this->putJson('/api/admin/facility/settings', [
            'number_of_courts' => 2,
            'minimum_reservation_duration_minutes' => 30,
            'advance_booking_days' => -1,
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['advance_booking_days']);

        // Test over 365 days
        $response = $this->putJson('/api/admin/facility/settings', [
            'number_of_courts' => 2,
            'minimum_reservation_duration_minutes' => 30,
            'advance_booking_days' => 366,
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['advance_booking_days']);

        // Test valid boundary values
        $response = $this->putJson('/api/admin/facility/settings', [
            'number_of_courts' => 2,
            'minimum_reservation_duration_minutes' => 30,
            'advance_booking_days' => 1, // Minimum valid
        ]);
        $response->assertStatus(200);

        $response = $this->putJson('/api/admin/facility/settings', [
            'number_of_courts' => 2,
            'minimum_reservation_duration_minutes' => 30,
            'advance_booking_days' => 365, // Maximum valid
        ]);
        $response->assertStatus(200);
    }

    public function test_singleton_pattern_only_one_settings_record_exists(): void
    {
        $admin = $this->createAdmin();
        Sanctum::actingAs($admin);

        // Create initial settings
        $settings1 = FacilitySetting::create([
            'number_of_courts' => 2,
            'minimum_reservation_duration_minutes' => 30,
            'advance_booking_days' => 30,
        ]);

        // Try to create another one - should get the same instance
        $settings2 = FacilitySetting::getInstance();

        $this->assertEquals($settings1->id, $settings2->id);

        // Verify only one record exists in database
        $count = FacilitySetting::count();
        $this->assertEquals(1, $count);

        // Update using getInstance() should update the same record
        $settings2->update(['number_of_courts' => 5]);
        $settings1->refresh();

        $this->assertEquals(5, $settings1->number_of_courts);
        $this->assertEquals(1, FacilitySetting::count());
    }

    public function test_settings_are_created_with_defaults_if_none_exist(): void
    {
        $admin = $this->createAdmin();
        Sanctum::actingAs($admin);

        // No settings exist yet
        $this->assertEquals(0, FacilitySetting::count());

        // Call show endpoint - should create default settings
        $response = $this->getJson('/api/admin/facility/settings');

        $response->assertStatus(200);
        
        // Verify default settings were created
        $this->assertEquals(1, FacilitySetting::count());
        $settings = FacilitySetting::first();
        $this->assertEquals(2, $settings->number_of_courts);
        $this->assertEquals(30, $settings->minimum_reservation_duration_minutes);
        $this->assertEquals(30, $settings->advance_booking_days);
    }

    public function test_non_admin_cannot_access_settings_endpoints(): void
    {
        $member = $this->createMember();
        Sanctum::actingAs($member);

        // Test show
        $response = $this->getJson('/api/admin/facility/settings');
        $response->assertStatus(403);

        // Test update
        $response = $this->putJson('/api/admin/facility/settings', [
            'number_of_courts' => 3,
            'minimum_reservation_duration_minutes' => 60,
            'advance_booking_days' => 45,
        ]);
        $response->assertStatus(403);
    }

    public function test_unauthenticated_users_cannot_access_settings_endpoints(): void
    {
        // Test show
        $response = $this->getJson('/api/admin/facility/settings');
        $response->assertStatus(401);

        // Test update
        $response = $this->putJson('/api/admin/facility/settings', [
            'number_of_courts' => 3,
            'minimum_reservation_duration_minutes' => 60,
            'advance_booking_days' => 45,
        ]);
        $response->assertStatus(401);
    }
}

