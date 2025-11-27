<?php

namespace Tests\Feature;

use App\Models\CourtReservation;
use App\Models\FacilitySchedule;
use App\Models\FacilitySetting;
use App\Models\MembershipOffer;
use App\Models\MembershipSubscription;
use App\Models\User;
use App\Models\Member;
use App\Services\ReservationService;
use App\Services\TimeSlotService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CourtReservationTest extends TestCase
{
    use RefreshDatabase;

    protected $reservationService;
    protected $timeSlotService;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create facility schedule (Monday - all days open 8am-10pm)
        for ($i = 0; $i < 7; $i++) {
            FacilitySchedule::create([
                'day_of_week' => $i,
                'open_time' => '08:00:00',
                'close_time' => '22:00:00',
                'is_open' => true,
            ]);
        }

        // Create facility settings (2 courts, 30min minimum, 30 days advance)
        FacilitySetting::create([
            'number_of_courts' => 2,
            'minimum_reservation_duration_minutes' => 30,
            'advance_booking_days' => 30,
        ]);

        // Create badminton membership offer
        $badmintonOffer = MembershipOffer::create([
            'category' => 'BADMINTON_COURT',
            'name' => 'Monthly Badminton Membership',
            'description' => 'Test',
            'price' => 1000,
            'billing_type' => 'RECURRING',
            'duration_type' => 'MONTH',
            'duration_value' => 1,
            'is_active' => true,
        ]);

        $this->reservationService = app(ReservationService::class);
        $this->timeSlotService = app(TimeSlotService::class);
    }

    /**
     * Scenario 1: Test that when all courts are occupied (8am-12pm),
     * no new reservations can be made during that time, but reservations
     * are possible after 12pm when courts become available.
     */
    public function test_all_courts_occupied_blocks_new_reservations(): void
    {
        // Create 2 members with active badminton memberships
        $member1 = User::factory()->create();
        Member::create(['user_id' => $member1->id]);
        $badmintonOffer = MembershipOffer::where('category', 'BADMINTON_COURT')->first();
        MembershipSubscription::create([
            'user_id' => $member1->id,
            'membership_offer_id' => $badmintonOffer->id,
            'start_date' => now(),
            'end_date' => now()->addMonth(),
            'status' => 'ACTIVE',
            'price_paid' => 1000,
            'is_recurring' => true,
        ]);

        $member2 = User::factory()->create();
        Member::create(['user_id' => $member2->id]);
        MembershipSubscription::create([
            'user_id' => $member2->id,
            'membership_offer_id' => $badmintonOffer->id,
            'start_date' => now(),
            'end_date' => now()->addMonth(),
            'status' => 'ACTIVE',
            'price_paid' => 1000,
            'is_recurring' => true,
        ]);

        $member3 = User::factory()->create();
        Member::create(['user_id' => $member3->id]);
        MembershipSubscription::create([
            'user_id' => $member3->id,
            'membership_offer_id' => $badmintonOffer->id,
            'start_date' => now(),
            'end_date' => now()->addMonth(),
            'status' => 'ACTIVE',
            'price_paid' => 1000,
            'is_recurring' => true,
        ]);

        // Create reservations: Both courts occupied 8am-12pm
        $date = Carbon::tomorrow();
        $date->setTime(8, 0, 0); // 8:00 AM

        // Member 1 reserves Court 1: 8am-12pm
        CourtReservation::create([
            'user_id' => $member1->id,
            'court_number' => 1,
            'reservation_date' => $date->format('Y-m-d'),
            'start_time' => $date->copy(),
            'end_time' => $date->copy()->addHours(4), // 12pm
            'duration_minutes' => 240,
            'status' => 'CONFIRMED',
        ]);

        // Member 2 reserves Court 2: 8am-12pm
        CourtReservation::create([
            'user_id' => $member2->id,
            'court_number' => 2,
            'reservation_date' => $date->format('Y-m-d'),
            'start_time' => $date->copy(),
            'end_time' => $date->copy()->addHours(4), // 12pm
            'duration_minutes' => 240,
            'status' => 'CONFIRMED',
        ]);

        // Test: Member 3 tries to book during 8am-12pm (should fail - all courts occupied)
        $slot8am = $date->copy()->setTime(8, 0, 0);
        $slot9am = $date->copy()->setTime(9, 0, 0);
        $slot10am = $date->copy()->setTime(10, 0, 0);
        $slot11am = $date->copy()->setTime(11, 0, 0);

        // Check availability for slots during occupied period
        $slots8am = $this->timeSlotService->getAvailableSlots($date);
        $slot8amData = collect($slots8am)->firstWhere('time_string', '08:00');
        $slot9amData = collect($slots8am)->firstWhere('time_string', '09:00');
        $slot10amData = collect($slots8am)->firstWhere('time_string', '10:00');
        $slot11amData = collect($slots8am)->firstWhere('time_string', '11:00');

        // All these slots should show 0 available courts
        $this->assertEquals(0, $slot8amData['available_courts'], '8am slot should have 0 available courts');
        $this->assertEquals(0, $slot9amData['available_courts'], '9am slot should have 0 available courts');
        $this->assertEquals(0, $slot10amData['available_courts'], '10am slot should have 0 available courts');
        $this->assertEquals(0, $slot11amData['available_courts'], '11am slot should have 0 available courts');
        $this->assertFalse($slot8amData['is_available'], '8am slot should not be available');
        $this->assertFalse($slot9amData['is_available'], '9am slot should not be available');

        // Try to create reservation during occupied time (should fail)
        try {
            $this->reservationService->createReservation(
                $member3->id,
                $date,
                $slot9am,
                60 // 1 hour
            );
            $this->fail('Should not be able to create reservation when all courts are occupied');
        } catch (\Exception $e) {
            $this->assertStringContainsString('No courts available', $e->getMessage());
        }

        // Test: Member 3 tries to book at 12pm onwards (should succeed - courts available)
        $slot12pm = $date->copy()->setTime(12, 0, 0);
        $slots12pm = $this->timeSlotService->getAvailableSlots($date);
        $slot12pmData = collect($slots12pm)->firstWhere('time_string', '12:00');
        
        // 12pm slot should show available courts
        $this->assertGreaterThan(0, $slot12pmData['available_courts'], '12pm slot should have available courts');
        $this->assertTrue($slot12pmData['is_available'], '12pm slot should be available');

        // Should be able to create reservation at 12pm
        $reservation = $this->reservationService->createReservation(
            $member3->id,
            $date,
            $slot12pm,
            60 // 1 hour
        );

        $this->assertNotNull($reservation);
        $this->assertEquals('CONFIRMED', $reservation->status);
        $this->assertEquals($member3->id, $reservation->user_id);
    }

    /**
     * Scenario 2: Test that a member with a reservation from 8am-10am
     * cannot make overlapping reservations (e.g., 8am-9:30am), but can
     * make reservations from 10am onwards when there's no overlap.
     */
    public function test_member_cannot_make_overlapping_reservations(): void
    {
        // Create member with active badminton membership
        $member = User::factory()->create();
        Member::create(['user_id' => $member->id]);
        $badmintonOffer = MembershipOffer::where('category', 'BADMINTON_COURT')->first();
        MembershipSubscription::create([
            'user_id' => $member->id,
            'membership_offer_id' => $badmintonOffer->id,
            'start_date' => now(),
            'end_date' => now()->addMonth(),
            'status' => 'ACTIVE',
            'price_paid' => 1000,
            'is_recurring' => true,
        ]);

        $date = Carbon::tomorrow();
        
        // Create existing reservation: 8am-10am
        $start8am = $date->copy()->setTime(8, 0, 0);
        $end10am = $date->copy()->setTime(10, 0, 0);
        
        CourtReservation::create([
            'user_id' => $member->id,
            'court_number' => 1,
            'reservation_date' => $date->format('Y-m-d'),
            'start_time' => $start8am,
            'end_time' => $end10am,
            'duration_minutes' => 120,
            'status' => 'CONFIRMED',
        ]);

        // Test 1: Member tries to book 8am-9:30am (overlaps with 8am-10am) - should fail
        $start8amOverlap = $date->copy()->setTime(8, 0, 0);
        try {
            $this->reservationService->createReservation(
                $member->id,
                $date,
                $start8amOverlap,
                90 // 1.5 hours (8am-9:30am)
            );
            $this->fail('Should not be able to create overlapping reservation');
        } catch (\Exception $e) {
            $this->assertStringContainsString('overlap', strtolower($e->getMessage()));
        }

        // Test 2: Member tries to book 8:30am-10:30am (overlaps with 8am-10am) - should fail
        $start830amOverlap = $date->copy()->setTime(8, 30, 0);
        try {
            $this->reservationService->createReservation(
                $member->id,
                $date,
                $start830amOverlap,
                120 // 2 hours (8:30am-10:30am)
            );
            $this->fail('Should not be able to create overlapping reservation');
        } catch (\Exception $e) {
            $this->assertStringContainsString('overlap', strtolower($e->getMessage()));
        }

        // Test 3: Member tries to book 9:00am-10:30am (overlaps with 8am-10am) - should fail
        $start9amOverlap = $date->copy()->setTime(9, 0, 0);
        try {
            $this->reservationService->createReservation(
                $member->id,
                $date,
                $start9amOverlap,
                90 // 1.5 hours (9am-10:30am)
            );
            $this->fail('Should not be able to create overlapping reservation');
        } catch (\Exception $e) {
            $this->assertStringContainsString('overlap', strtolower($e->getMessage()));
        }

        // Test 4: Member tries to book 10am onwards (no overlap) - should succeed
        $start10am = $date->copy()->setTime(10, 0, 0);
        $reservation = $this->reservationService->createReservation(
            $member->id,
            $date,
            $start10am,
            60 // 1 hour (10am-11am)
        );

        $this->assertNotNull($reservation);
        $this->assertEquals('CONFIRMED', $reservation->status);
        $this->assertEquals($member->id, $reservation->user_id);
        $this->assertEquals($start10am->format('Y-m-d H:i:s'), $reservation->start_time->format('Y-m-d H:i:s'));

        // Test 5: Member tries to book 9:30am-10:30am (still overlaps) - should fail
        $start930amOverlap = $date->copy()->setTime(9, 30, 0);
        try {
            $this->reservationService->createReservation(
                $member->id,
                $date,
                $start930amOverlap,
                60 // 1 hour (9:30am-10:30am)
            );
            $this->fail('Should not be able to create overlapping reservation');
        } catch (\Exception $e) {
            $this->assertStringContainsString('overlap', strtolower($e->getMessage()));
        }
    }

    /**
     * Test that different members can book different courts at the same time.
     */
    public function test_different_members_can_book_different_courts_simultaneously(): void
    {
        // Create 2 members
        $member1 = User::factory()->create();
        Member::create(['user_id' => $member1->id]);
        $badmintonOffer = MembershipOffer::where('category', 'BADMINTON_COURT')->first();
        MembershipSubscription::create([
            'user_id' => $member1->id,
            'membership_offer_id' => $badmintonOffer->id,
            'start_date' => now(),
            'end_date' => now()->addMonth(),
            'status' => 'ACTIVE',
            'price_paid' => 1000,
            'is_recurring' => true,
        ]);

        $member2 = User::factory()->create();
        Member::create(['user_id' => $member2->id]);
        MembershipSubscription::create([
            'user_id' => $member2->id,
            'membership_offer_id' => $badmintonOffer->id,
            'start_date' => now(),
            'end_date' => now()->addMonth(),
            'status' => 'ACTIVE',
            'price_paid' => 1000,
            'is_recurring' => true,
        ]);

        $date = Carbon::tomorrow();
        $startTime = $date->copy()->setTime(8, 0, 0);

        // Member 1 books court at 8am
        $reservation1 = $this->reservationService->createReservation(
            $member1->id,
            $date,
            $startTime,
            120 // 2 hours
        );

        // Member 2 should be able to book the other court at the same time
        $reservation2 = $this->reservationService->createReservation(
            $member2->id,
            $date,
            $startTime,
            120 // 2 hours
        );

        $this->assertNotNull($reservation1);
        $this->assertNotNull($reservation2);
        $this->assertNotEquals($reservation1->court_number, $reservation2->court_number, 'Reservations should be on different courts');
        $this->assertEquals($member1->id, $reservation1->user_id);
        $this->assertEquals($member2->id, $reservation2->user_id);
    }

    /**
     * Test that time slot availability correctly calculates available courts.
     */
    public function test_time_slot_availability_calculation(): void
    {
        $member1 = User::factory()->create();
        Member::create(['user_id' => $member1->id]);
        $badmintonOffer = MembershipOffer::where('category', 'BADMINTON_COURT')->first();
        MembershipSubscription::create([
            'user_id' => $member1->id,
            'membership_offer_id' => $badmintonOffer->id,
            'start_date' => now(),
            'end_date' => now()->addMonth(),
            'status' => 'ACTIVE',
            'price_paid' => 1000,
            'is_recurring' => true,
        ]);

        $date = Carbon::tomorrow();
        $startTime = $date->copy()->setTime(9, 0, 0);

        // Create reservation on Court 1: 9am-10am
        CourtReservation::create([
            'user_id' => $member1->id,
            'court_number' => 1,
            'reservation_date' => $date->format('Y-m-d'),
            'start_time' => $startTime,
            'end_time' => $startTime->copy()->addHour(),
            'duration_minutes' => 60,
            'status' => 'CONFIRMED',
        ]);

        // Get available slots
        $slots = $this->timeSlotService->getAvailableSlots($date);
        
        // Find 9am slot (30-minute slot that overlaps with reservation)
        $slot9am = collect($slots)->firstWhere('time_string', '09:00');
        $slot915am = collect($slots)->firstWhere('time_string', '09:30');
        
        // With 2 courts and 1 reservation on Court 1, 9am slot should have 1 available court
        $this->assertEquals(1, $slot9am['available_courts'], '9am slot should have 1 available court');
        $this->assertTrue($slot9am['is_available'], '9am slot should still be available (court 2 is free)');
        
        // 9:30am slot also overlaps, should have 1 available court
        $this->assertEquals(1, $slot915am['available_courts'], '9:30am slot should have 1 available court');

        // 8am slot should have 2 available courts (no reservations)
        $slot8am = collect($slots)->firstWhere('time_string', '08:00');
        $this->assertEquals(2, $slot8am['available_courts'], '8am slot should have 2 available courts');
        $this->assertTrue($slot8am['is_available'], '8am slot should be available');

        // 10am slot should have 2 available courts (reservation ends at 10am)
        $slot10am = collect($slots)->firstWhere('time_string', '10:00');
        $this->assertEquals(2, $slot10am['available_courts'], '10am slot should have 2 available courts');
    }
}

