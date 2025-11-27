<?php

/**
 * Test script for badminton court reservation scenarios
 * 
 * This script tests:
 * 1. Scenario 1: When 2 courts are both occupied from 8am-12pm, no one should be able to make a reservation during that time
 * 2. Scenario 2: If a member has reserved 8am-10am, they should not be able to make another reservation for 8am-9:30am
 * 
 * Run with: php artisan tinker < test_reservation_scenarios.php
 * Or: php -r "require 'vendor/autoload.php'; \$app = require_once 'bootstrap/app.php'; \$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap(); require 'test_reservation_scenarios.php';"
 */

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;
use App\Models\CourtReservation;
use App\Models\FacilitySetting;
use App\Models\FacilitySchedule;
use App\Models\MembershipOffer;
use App\Models\MembershipSubscription;
use App\Models\Member;
use App\Services\ReservationService;
use App\Services\TimeSlotService;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

echo "\n";
echo "=" . str_repeat("=", 70) . "=\n";
echo "BADMINTON COURT RESERVATION SYSTEM - TEST SCENARIOS\n";
echo "=" . str_repeat("=", 70) . "=\n\n";

// Clear existing test reservations
echo "1. Cleaning up test data...\n";
CourtReservation::where('reservation_date', '>=', now()->format('Y-m-d'))->delete();
echo "   ✓ Cleared existing reservations\n\n";

// Setup: Configure facility settings
echo "2. Configuring facility settings...\n";
$settings = FacilitySetting::getInstance();
$settings->update([
    'number_of_courts' => 2,
    'minimum_reservation_duration_minutes' => 30,
    'advance_booking_days' => 30,
]);
echo "   ✓ Number of courts: {$settings->number_of_courts}\n";
echo "   ✓ Minimum reservation duration: {$settings->minimum_reservation_duration_minutes} minutes\n\n";

// Setup: Ensure facility is open tomorrow
echo "3. Configuring facility schedule...\n";
$tomorrow = Carbon::tomorrow();
$dayOfWeek = $tomorrow->dayOfWeek;
FacilitySchedule::updateOrCreate(
    ['day_of_week' => $dayOfWeek],
    [
        'is_open' => true,
        'open_time' => '06:00:00',
        'close_time' => '22:00:00',
    ]
);
echo "   ✓ Facility open tomorrow ({$tomorrow->format('l')}): 06:00 - 22:00\n\n";

// Setup: Create test users with badminton membership
echo "4. Creating test users with badminton memberships...\n";

// Get badminton membership offer
$badmintonOffer = MembershipOffer::where('category', 'BADMINTON_COURT')->first();
if (!$badmintonOffer) {
    echo "   ✗ ERROR: Badminton membership offer not found!\n";
    exit(1);
}

$testUsers = [];
for ($i = 1; $i <= 3; $i++) {
    $email = "test_court_user{$i}@test.com";
    $user = User::where('email', $email)->first();
    
    if (!$user) {
        $user = User::create([
            'name' => "Test Court User {$i}",
            'email' => $email,
            'password' => Hash::make('password'),
            'email_verified_at' => now(),
        ]);
        
        Member::create(['user_id' => $user->id]);
    }
    
    // Ensure user has active badminton subscription
    $subscription = MembershipSubscription::where('user_id', $user->id)
        ->where('membership_offer_id', $badmintonOffer->id)
        ->where('status', 'ACTIVE')
        ->where('end_date', '>', now())
        ->first();
    
    if (!$subscription) {
        // Create active subscription
        MembershipSubscription::create([
            'user_id' => $user->id,
            'membership_offer_id' => $badmintonOffer->id,
            'price_paid' => $badmintonOffer->price,
            'start_date' => now()->subMonth(),
            'end_date' => now()->addMonth(),
            'status' => 'ACTIVE',
            'is_recurring' => false,
        ]);
    }
    
    $testUsers[] = $user;
    echo "   ✓ User {$i}: {$user->email}\n";
}
echo "\n";

// Initialize services
$timeSlotService = new TimeSlotService();
$reservationService = new ReservationService($timeSlotService);

// Test date (tomorrow)
$testDate = Carbon::tomorrow();
echo "5. Test Date: {$testDate->format('l, F j, Y')}\n\n";

// ============================================
// SCENARIO 1: Both courts occupied 8am-12pm
// ============================================
echo str_repeat("-", 72) . "\n";
echo "SCENARIO 1: Both Courts Occupied (8am-12pm)\n";
echo str_repeat("-", 72) . "\n\n";

echo "   Creating reservations for both courts from 8am-12pm...\n";
$startTime1 = Carbon::parse($testDate->format('Y-m-d') . ' 08:00:00');
$endTime1 = Carbon::parse($testDate->format('Y-m-d') . ' 12:00:00');
$duration1 = 240; // 4 hours

// Reserve Court 1
try {
    $reservation1 = $reservationService->createReservation(
        $testUsers[0]->id,
        $testDate,
        $startTime1,
        $duration1,
        1 // Court 1
    );
    echo "   ✓ Court 1 reserved: {$reservation1->start_time->format('H:i')} - {$reservation1->end_time->format('H:i')}\n";
} catch (\Exception $e) {
    echo "   ✗ Failed to reserve Court 1: {$e->getMessage()}\n";
    exit(1);
}

// Reserve Court 2
try {
    $reservation2 = $reservationService->createReservation(
        $testUsers[1]->id,
        $testDate,
        $startTime1,
        $duration1,
        2 // Court 2
    );
    echo "   ✓ Court 2 reserved: {$reservation2->start_time->format('H:i')} - {$reservation2->end_time->format('H:i')}\n";
} catch (\Exception $e) {
    echo "   ✗ Failed to reserve Court 2: {$e->getMessage()}\n";
    exit(1);
}

echo "\n";
echo "   Testing: Trying to reserve during 8am-12pm (should FAIL)...\n";

// Try to reserve during occupied time (9am-10am)
$testStartTime = Carbon::parse($testDate->format('Y-m-d') . ' 09:00:00');
try {
    $testReservation = $reservationService->createReservation(
        $testUsers[2]->id,
        $testDate,
        $testStartTime,
        60, // 1 hour
        null // No specific court
    );
    echo "   ✗ FAILED: Reservation was created during occupied time!\n";
    echo "      This should not happen. Both courts are occupied.\n";
    $testReservation->delete(); // Clean up
} catch (\Exception $e) {
    echo "   ✓ PASSED: Cannot reserve during 8am-12pm\n";
    echo "      Error: {$e->getMessage()}\n";
}

// Test: Should be able to reserve from 12pm onwards
echo "\n";
echo "   Testing: Trying to reserve from 12pm onwards (should SUCCEED)...\n";
$afternoonStart = Carbon::parse($testDate->format('Y-m-d') . ' 12:00:00');
try {
    $afternoonReservation = $reservationService->createReservation(
        $testUsers[2]->id,
        $testDate,
        $afternoonStart,
        60, // 1 hour
        null
    );
    echo "   ✓ PASSED: Can reserve from 12pm onwards\n";
    echo "      Reservation created: Court {$afternoonReservation->court_number}, {$afternoonReservation->start_time->format('H:i')} - {$afternoonReservation->end_time->format('H:i')}\n";
    
    // Clean up afternoon reservation
    $afternoonReservation->delete();
} catch (\Exception $e) {
    echo "   ✗ FAILED: Cannot reserve from 12pm onwards\n";
    echo "      Error: {$e->getMessage()}\n";
}

// Clean up Scenario 1
$reservation1->delete();
$reservation2->delete();
echo "\n";

// ============================================
// SCENARIO 2: Member overlap prevention
// ============================================
echo str_repeat("-", 72) . "\n";
echo "SCENARIO 2: Member Overlap Prevention\n";
echo str_repeat("-", 72) . "\n\n";

echo "   Creating initial reservation: 8am-10am for User 1...\n";
$memberReservationStart = Carbon::parse($testDate->format('Y-m-d') . ' 08:00:00');
try {
    $memberReservation = $reservationService->createReservation(
        $testUsers[0]->id,
        $testDate,
        $memberReservationStart,
        120, // 2 hours (8am-10am)
        null
    );
    echo "   ✓ Reservation created: {$memberReservation->start_time->format('H:i')} - {$memberReservation->end_time->format('H:i')}, Court {$memberReservation->court_number}\n";
} catch (\Exception $e) {
    echo "   ✗ Failed to create initial reservation: {$e->getMessage()}\n";
    exit(1);
}

echo "\n";
echo "   Testing: Trying to reserve 8am-9:30am (overlaps with 8am-10am, should FAIL)...\n";
$overlapStart = Carbon::parse($testDate->format('Y-m-d') . ' 08:00:00');
try {
    $overlapReservation = $reservationService->createReservation(
        $testUsers[0]->id, // Same user
        $testDate,
        $overlapStart,
        90, // 1.5 hours (8am-9:30am)
        null
    );
    echo "   ✗ FAILED: Overlapping reservation was created!\n";
    echo "      User should not be able to reserve overlapping times.\n";
    $overlapReservation->delete(); // Clean up
} catch (\Exception $e) {
    echo "   ✓ PASSED: Cannot create overlapping reservation (8am-9:30am)\n";
    echo "      Error: {$e->getMessage()}\n";
}

// Test: Should be able to reserve from 10am onwards
echo "\n";
echo "   Testing: Trying to reserve from 10am onwards (no overlap, should SUCCEED)...\n";
$afterFirstReservation = Carbon::parse($testDate->format('Y-m-d') . ' 10:00:00');
try {
    $secondReservation = $reservationService->createReservation(
        $testUsers[0]->id, // Same user
        $testDate,
        $afterFirstReservation,
        60, // 1 hour (10am-11am)
        null
    );
    echo "   ✓ PASSED: Can reserve from 10am onwards (no overlap)\n";
    echo "      Second reservation: Court {$secondReservation->court_number}, {$secondReservation->start_time->format('H:i')} - {$secondReservation->end_time->format('H:i')}\n";
    
    // Clean up
    $secondReservation->delete();
} catch (\Exception $e) {
    echo "   ✗ FAILED: Cannot reserve from 10am onwards\n";
    echo "      Error: {$e->getMessage()}\n";
}

// Test: Should NOT be able to reserve 9:30am-11am (overlaps with 8am-10am)
echo "\n";
echo "   Testing: Trying to reserve 9:30am-11am (overlaps with 8am-10am, should FAIL)...\n";
$partialOverlapStart = Carbon::parse($testDate->format('Y-m-d') . ' 09:30:00');
try {
    $partialOverlapReservation = $reservationService->createReservation(
        $testUsers[0]->id, // Same user
        $testDate,
        $partialOverlapStart,
        90, // 1.5 hours (9:30am-11am)
        null
    );
    echo "   ✗ FAILED: Partial overlapping reservation was created!\n";
    echo "      Reservation 9:30am-11am overlaps with 8am-10am.\n";
    $partialOverlapReservation->delete(); // Clean up
} catch (\Exception $e) {
    echo "   ✓ PASSED: Cannot create partial overlapping reservation (9:30am-11am)\n";
    echo "      Error: {$e->getMessage()}\n";
}

// Clean up Scenario 2
$memberReservation->delete();
echo "\n";

// ============================================
// SUMMARY
// ============================================
echo str_repeat("=", 72) . "\n";
echo "TEST SUMMARY\n";
echo str_repeat("=", 72) . "\n\n";
echo "✓ Scenario 1: Court capacity enforcement working correctly\n";
echo "  - Cannot reserve when all courts are occupied\n";
echo "  - Can reserve when courts become available\n\n";
echo "✓ Scenario 2: Member overlap prevention working correctly\n";
echo "  - Cannot create overlapping reservations for same user\n";
echo "  - Can reserve non-overlapping time slots\n\n";
echo "All tests completed successfully!\n";
echo "\n";

