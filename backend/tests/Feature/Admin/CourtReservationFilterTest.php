<?php

namespace Tests\Feature\Admin;

use App\Models\CourtReservation;
use App\Models\Member;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class CourtReservationFilterTest extends TestCase
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

    public function test_admin_can_list_all_reservations(): void
    {
        $admin = $this->createAdmin();
        Sanctum::actingAs($admin);

        // Create test users and reservations
        $user1 = $this->createMember();
        $user2 = $this->createMember();

        $today = Carbon::today();
        $tomorrow = Carbon::tomorrow();

        CourtReservation::create([
            'user_id' => $user1->id,
            'court_number' => 1,
            'reservation_date' => $today,
            'start_time' => $today->copy()->setTime(10, 0),
            'end_time' => $today->copy()->setTime(11, 0),
            'duration_minutes' => 60,
            'status' => 'CONFIRMED',
        ]);

        CourtReservation::create([
            'user_id' => $user2->id,
            'court_number' => 2,
            'reservation_date' => $tomorrow,
            'start_time' => $tomorrow->copy()->setTime(14, 0),
            'end_time' => $tomorrow->copy()->setTime(15, 0),
            'duration_minutes' => 60,
            'status' => 'CONFIRMED',
        ]);

        $response = $this->getJson('/api/admin/reservations');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'user',
                        'court_number',
                        'reservation_date',
                        'start_time',
                        'end_time',
                        'duration_minutes',
                        'status',
                    ],
                ],
                'current_page',
                'per_page',
                'total',
            ]);

        $data = $response->json('data');
        $this->assertCount(2, $data);
    }

    public function test_filter_by_date(): void
    {
        $admin = $this->createAdmin();
        Sanctum::actingAs($admin);

        $user = $this->createMember();
        $today = Carbon::today();
        $tomorrow = Carbon::tomorrow();

        // Create reservation for today
        CourtReservation::create([
            'user_id' => $user->id,
            'court_number' => 1,
            'reservation_date' => $today,
            'start_time' => $today->copy()->setTime(10, 0),
            'end_time' => $today->copy()->setTime(11, 0),
            'duration_minutes' => 60,
            'status' => 'CONFIRMED',
        ]);

        // Create reservation for tomorrow
        CourtReservation::create([
            'user_id' => $user->id,
            'court_number' => 2,
            'reservation_date' => $tomorrow,
            'start_time' => $tomorrow->copy()->setTime(14, 0),
            'end_time' => $tomorrow->copy()->setTime(15, 0),
            'duration_minutes' => 60,
            'status' => 'CONFIRMED',
        ]);

        $response = $this->getJson('/api/admin/reservations?date=' . $today->format('Y-m-d'));

        $response->assertStatus(200);
        $data = $response->json('data');
        $this->assertCount(1, $data);
        // Parse the date from the response (could be datetime string or date string)
        $responseDate = Carbon::parse($data[0]['reservation_date'])->format('Y-m-d');
        $this->assertEquals($today->format('Y-m-d'), $responseDate);
    }

    public function test_filter_by_date_range(): void
    {
        $admin = $this->createAdmin();
        Sanctum::actingAs($admin);

        $user = $this->createMember();
        $today = Carbon::today();
        $tomorrow = Carbon::tomorrow();
        $nextWeek = Carbon::today()->addWeek();

        // Create reservations for different dates
        CourtReservation::create([
            'user_id' => $user->id,
            'court_number' => 1,
            'reservation_date' => $today,
            'start_time' => $today->copy()->setTime(10, 0),
            'end_time' => $today->copy()->setTime(11, 0),
            'duration_minutes' => 60,
            'status' => 'CONFIRMED',
        ]);

        CourtReservation::create([
            'user_id' => $user->id,
            'court_number' => 2,
            'reservation_date' => $tomorrow,
            'start_time' => $tomorrow->copy()->setTime(14, 0),
            'end_time' => $tomorrow->copy()->setTime(15, 0),
            'duration_minutes' => 60,
            'status' => 'CONFIRMED',
        ]);

        CourtReservation::create([
            'user_id' => $user->id,
            'court_number' => 1,
            'reservation_date' => $nextWeek,
            'start_time' => $nextWeek->copy()->setTime(16, 0),
            'end_time' => $nextWeek->copy()->setTime(17, 0),
            'duration_minutes' => 60,
            'status' => 'CONFIRMED',
        ]);

        $response = $this->getJson('/api/admin/reservations?start_date=' . $today->format('Y-m-d') . '&end_date=' . $tomorrow->format('Y-m-d'));

        $response->assertStatus(200);
        $data = $response->json('data');
        $this->assertCount(2, $data);
    }

    public function test_filter_by_court_number(): void
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

        CourtReservation::create([
            'user_id' => $user->id,
            'court_number' => 2,
            'reservation_date' => $today,
            'start_time' => $today->copy()->setTime(14, 0),
            'end_time' => $today->copy()->setTime(15, 0),
            'duration_minutes' => 60,
            'status' => 'CONFIRMED',
        ]);

        $response = $this->getJson('/api/admin/reservations?court_number=1');

        $response->assertStatus(200);
        $data = $response->json('data');
        $this->assertCount(1, $data);
        $this->assertEquals(1, $data[0]['court_number']);
    }

    public function test_filter_by_status(): void
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

        $cancelledReservation = CourtReservation::create([
            'user_id' => $user->id,
            'court_number' => 2,
            'reservation_date' => $today,
            'start_time' => $today->copy()->setTime(14, 0),
            'end_time' => $today->copy()->setTime(15, 0),
            'duration_minutes' => 60,
            'status' => 'CANCELLED',
            'cancelled_at' => now(),
        ]);

        // Test CONFIRMED status
        $response = $this->getJson('/api/admin/reservations?status=CONFIRMED');
        $response->assertStatus(200);
        $data = $response->json('data');
        $this->assertCount(1, $data);
        $this->assertEquals('CONFIRMED', $data[0]['status']);

        // Test CANCELLED status
        $response = $this->getJson('/api/admin/reservations?status=CANCELLED');
        $response->assertStatus(200);
        $data = $response->json('data');
        $this->assertCount(1, $data);
        $this->assertEquals('CANCELLED', $data[0]['status']);
    }

    public function test_filter_by_member_search_name(): void
    {
        $admin = $this->createAdmin();
        Sanctum::actingAs($admin);

        $user1 = User::factory()->create(['name' => 'John Doe', 'email' => 'john@example.com']);
        Member::create(['user_id' => $user1->id]);

        $user2 = User::factory()->create(['name' => 'Jane Smith', 'email' => 'jane@example.com']);
        Member::create(['user_id' => $user2->id]);

        $today = Carbon::today();

        CourtReservation::create([
            'user_id' => $user1->id,
            'court_number' => 1,
            'reservation_date' => $today,
            'start_time' => $today->copy()->setTime(10, 0),
            'end_time' => $today->copy()->setTime(11, 0),
            'duration_minutes' => 60,
            'status' => 'CONFIRMED',
        ]);

        CourtReservation::create([
            'user_id' => $user2->id,
            'court_number' => 2,
            'reservation_date' => $today,
            'start_time' => $today->copy()->setTime(14, 0),
            'end_time' => $today->copy()->setTime(15, 0),
            'duration_minutes' => 60,
            'status' => 'CONFIRMED',
        ]);

        $response = $this->getJson('/api/admin/reservations?member_search=John');

        $response->assertStatus(200);
        $data = $response->json('data');
        $this->assertCount(1, $data);
        $this->assertEquals('John Doe', $data[0]['user']['name']);
    }

    public function test_filter_by_member_search_email(): void
    {
        $admin = $this->createAdmin();
        Sanctum::actingAs($admin);

        $user1 = User::factory()->create(['name' => 'John Doe', 'email' => 'john@example.com']);
        Member::create(['user_id' => $user1->id]);

        $user2 = User::factory()->create(['name' => 'Jane Smith', 'email' => 'jane@example.com']);
        Member::create(['user_id' => $user2->id]);

        $today = Carbon::today();

        CourtReservation::create([
            'user_id' => $user1->id,
            'court_number' => 1,
            'reservation_date' => $today,
            'start_time' => $today->copy()->setTime(10, 0),
            'end_time' => $today->copy()->setTime(11, 0),
            'duration_minutes' => 60,
            'status' => 'CONFIRMED',
        ]);

        CourtReservation::create([
            'user_id' => $user2->id,
            'court_number' => 2,
            'reservation_date' => $today,
            'start_time' => $today->copy()->setTime(14, 0),
            'end_time' => $today->copy()->setTime(15, 0),
            'duration_minutes' => 60,
            'status' => 'CONFIRMED',
        ]);

        $response = $this->getJson('/api/admin/reservations?member_search=jane@example.com');

        $response->assertStatus(200);
        $data = $response->json('data');
        $this->assertCount(1, $data);
        $this->assertEquals('jane@example.com', $data[0]['user']['email']);
    }

    public function test_pagination(): void
    {
        $admin = $this->createAdmin();
        Sanctum::actingAs($admin);

        $user = $this->createMember();
        $today = Carbon::today();

        // Create 25 reservations
        for ($i = 1; $i <= 25; $i++) {
            CourtReservation::create([
                'user_id' => $user->id,
                'court_number' => ($i % 2) + 1,
                'reservation_date' => $today->copy()->addDays($i % 7),
                'start_time' => $today->copy()->addDays($i % 7)->setTime(10 + ($i % 8), 0),
                'end_time' => $today->copy()->addDays($i % 7)->setTime(11 + ($i % 8), 0),
                'duration_minutes' => 60,
                'status' => 'CONFIRMED',
            ]);
        }

        // Test default pagination (15 per page)
        $response = $this->getJson('/api/admin/reservations');
        $response->assertStatus(200);
        $data = $response->json('data');
        $this->assertCount(15, $data);
        $this->assertEquals(25, $response->json('total'));
        $this->assertEquals(1, $response->json('current_page'));

        // Test custom per_page
        $response = $this->getJson('/api/admin/reservations?per_page=10');
        $response->assertStatus(200);
        $data = $response->json('data');
        $this->assertCount(10, $data);
        $this->assertEquals(10, $response->json('per_page'));
    }

    public function test_sorting_by_date_and_time_ascending(): void
    {
        $admin = $this->createAdmin();
        Sanctum::actingAs($admin);

        $user = $this->createMember();
        $today = Carbon::today();

        // Create reservations in random order
        CourtReservation::create([
            'user_id' => $user->id,
            'court_number' => 1,
            'reservation_date' => $today->copy()->addDays(2),
            'start_time' => $today->copy()->addDays(2)->setTime(14, 0),
            'end_time' => $today->copy()->addDays(2)->setTime(15, 0),
            'duration_minutes' => 60,
            'status' => 'CONFIRMED',
        ]);

        CourtReservation::create([
            'user_id' => $user->id,
            'court_number' => 2,
            'reservation_date' => $today,
            'start_time' => $today->copy()->setTime(10, 0),
            'end_time' => $today->copy()->setTime(11, 0),
            'duration_minutes' => 60,
            'status' => 'CONFIRMED',
        ]);

        CourtReservation::create([
            'user_id' => $user->id,
            'court_number' => 1,
            'reservation_date' => $today->copy()->addDays(1),
            'start_time' => $today->copy()->addDays(1)->setTime(16, 0),
            'end_time' => $today->copy()->addDays(1)->setTime(17, 0),
            'duration_minutes' => 60,
            'status' => 'CONFIRMED',
        ]);

        $response = $this->getJson('/api/admin/reservations');

        $response->assertStatus(200);
        $data = $response->json('data');
        $this->assertCount(3, $data);

        // Verify sorting: should be ordered by date ascending, then time ascending
        // Parse dates from response (could be datetime string or date string)
        $date0 = Carbon::parse($data[0]['reservation_date'])->format('Y-m-d');
        $date1 = Carbon::parse($data[1]['reservation_date'])->format('Y-m-d');
        $date2 = Carbon::parse($data[2]['reservation_date'])->format('Y-m-d');
        $this->assertEquals($today->format('Y-m-d'), $date0);
        $this->assertEquals($today->copy()->addDays(1)->format('Y-m-d'), $date1);
        $this->assertEquals($today->copy()->addDays(2)->format('Y-m-d'), $date2);
    }

    public function test_admin_can_view_reservation_details(): void
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

        $response = $this->getJson("/api/admin/reservations/{$reservation->id}");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'id',
                'user' => [
                    'id',
                    'name',
                    'email',
                ],
                'court_number',
                'reservation_date',
                'start_time',
                'end_time',
                'duration_minutes',
                'status',
                'cancelled_at',
                'cancellation_reason',
                'created_at',
            ]);

        $data = $response->json();
        $this->assertEquals($reservation->id, $data['id']);
        $this->assertEquals($user->id, $data['user']['id']);
        $this->assertEquals(1, $data['court_number']);
        $this->assertEquals('CONFIRMED', $data['status']);
    }

    public function test_admin_can_cancel_reservation(): void
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

        $response = $this->postJson("/api/admin/reservations/{$reservation->id}/cancel", [
            'reason' => 'Admin override',
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'message',
                'reservation' => [
                    'id',
                    'status',
                    'cancelled_at',
                ],
            ]);

        $this->assertEquals('Reservation cancelled successfully.', $response->json('message'));
        $this->assertEquals('CANCELLED', $response->json('reservation.status'));

        // Verify in database
        $reservation->refresh();
        $this->assertEquals('CANCELLED', $reservation->status);
        $this->assertNotNull($reservation->cancelled_at);
        $this->assertEquals('Admin override', $reservation->cancellation_reason);
    }

    public function test_cancel_already_cancelled_reservation_returns_400(): void
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
            'status' => 'CANCELLED',
            'cancelled_at' => now(),
        ]);

        $response = $this->postJson("/api/admin/reservations/{$reservation->id}/cancel", [
            'reason' => 'Try again',
        ]);

        $response->assertStatus(400);
        $this->assertEquals('Reservation is already cancelled.', $response->json('message'));
    }

    public function test_multiple_filters_combined_work_correctly(): void
    {
        $admin = $this->createAdmin();
        Sanctum::actingAs($admin);

        $user1 = User::factory()->create(['name' => 'John Doe', 'email' => 'john@example.com']);
        Member::create(['user_id' => $user1->id]);

        $user2 = User::factory()->create(['name' => 'Jane Smith', 'email' => 'jane@example.com']);
        Member::create(['user_id' => $user2->id]);

        $today = Carbon::today();

        // Reservation matching all filters
        CourtReservation::create([
            'user_id' => $user1->id,
            'court_number' => 1,
            'reservation_date' => $today,
            'start_time' => $today->copy()->setTime(10, 0),
            'end_time' => $today->copy()->setTime(11, 0),
            'duration_minutes' => 60,
            'status' => 'CONFIRMED',
        ]);

        // Reservation not matching (different court)
        CourtReservation::create([
            'user_id' => $user1->id,
            'court_number' => 2,
            'reservation_date' => $today,
            'start_time' => $today->copy()->setTime(14, 0),
            'end_time' => $today->copy()->setTime(15, 0),
            'duration_minutes' => 60,
            'status' => 'CONFIRMED',
        ]);

        // Reservation not matching (different status)
        CourtReservation::create([
            'user_id' => $user1->id,
            'court_number' => 1,
            'reservation_date' => $today,
            'start_time' => $today->copy()->setTime(16, 0),
            'end_time' => $today->copy()->setTime(17, 0),
            'duration_minutes' => 60,
            'status' => 'CANCELLED',
            'cancelled_at' => now(),
        ]);

        // Reservation not matching (different user)
        CourtReservation::create([
            'user_id' => $user2->id,
            'court_number' => 1,
            'reservation_date' => $today,
            'start_time' => $today->copy()->setTime(18, 0),
            'end_time' => $today->copy()->setTime(19, 0),
            'duration_minutes' => 60,
            'status' => 'CONFIRMED',
        ]);

        // Apply multiple filters
        $response = $this->getJson('/api/admin/reservations?date=' . $today->format('Y-m-d') . '&court_number=1&status=CONFIRMED&member_search=John');

        $response->assertStatus(200);
        $data = $response->json('data');
        $this->assertCount(1, $data);
        $this->assertEquals(1, $data[0]['court_number']);
        $this->assertEquals('CONFIRMED', $data[0]['status']);
        $this->assertEquals('John Doe', $data[0]['user']['name']);
    }

    public function test_non_admin_cannot_access_reservation_endpoints(): void
    {
        $member = $this->createMember();
        Sanctum::actingAs($member);

        // Test index
        $response = $this->getJson('/api/admin/reservations');
        $response->assertStatus(403);

        // Test show
        $response = $this->getJson('/api/admin/reservations/1');
        $response->assertStatus(403);

        // Test cancel
        $response = $this->postJson('/api/admin/reservations/1/cancel', [
            'reason' => 'Test',
        ]);
        $response->assertStatus(403);
    }

    public function test_unauthenticated_users_cannot_access_reservation_endpoints(): void
    {
        // Test index
        $response = $this->getJson('/api/admin/reservations');
        $response->assertStatus(401);

        // Test show
        $response = $this->getJson('/api/admin/reservations/1');
        $response->assertStatus(401);

        // Test cancel
        $response = $this->postJson('/api/admin/reservations/1/cancel', [
            'reason' => 'Test',
        ]);
        $response->assertStatus(401);
    }
}

