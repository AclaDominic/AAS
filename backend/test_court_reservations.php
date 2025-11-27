<?php

/**
 * Test script for badminton court reservation scenarios
 * Run: php test_court_reservations.php
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
use Illuminate\Support\Facades\Hash;

echo "\n";
echo str_repeat("=", 72) . "\n";
echo "BADMINTON COURT RESERVATION SYSTEM - TEST SCENARIOS\n";
echo str_repeat("=", 72) . "\n\n";

$passedTests = 0;
$failedTests = 0;

function testPass($message) {
    global $passedTests;
    echo "  ✓ PASS: $message\n";
    $passedTests++;
}

function testFail($message, $error = null) {
    global $failedTests;
    echo "  ✗ FAIL: $message\n";
    if ($error) {
        echo "      Error: $error\n";
    }
    $failedTests++;
}

// Cleanup
echo "1. Cleaning up test data...\n";
$testDate = Carbon::tomorrow();
CourtReservation::where('reservation_date', $testDate->format('Y-m-d'))->delete();
testPass("Cleared existing reservations for test date");

// Setup facility
echo "\n2. Configuring facility...\n";
$settings = FacilitySetting::getInstance();
$settings->update(['number_of_courts' => 2, 'minimum_reservation_duration_minutes' => 30]);
testPass("Facility configured: {$settings->number_of_courts} courts");

$dayOfWeek = $testDate->dayOfWeek;
FacilitySchedule::updateOrCreate(
    ['day_of_week' => $dayOfWeek],
    ['is_open' => true, 'open_time' => '06:00:00', 'close_time' => '22:00:00']
);
testPass("Facility schedule set for {$testDate->format('l')}");

// Setup users
echo "\n3. Setting up test users...\n";
$badmintonOffer = MembershipOffer::where('category', 'BADMINTON_COURT')->first();
if (!$badmintonOffer) {
    echo "  ✗ ERROR: Badminton membership offer not found!\n";
    exit(1);
}

$users = [];
for ($i = 1; $i <= 3; $i++) {
    $email = "court_test_user{$i}@test.com";
    $user = User::firstOrCreate(
        ['email' => $email],
        [
            'name' => "Court Test User $i",
            'password' => Hash::make('password'),
            'email_verified_at' => now(),
        ]
    );
    
    if (!$user->member) {
        Member::create(['user_id' => $user->id]);
    }
    
    // Ensure active badminton subscription
    MembershipSubscription::firstOrCreate(
        [
            'user_id' => $user->id,
            'membership_offer_id' => $badmintonOffer->id,
        ],
        [
            'price_paid' => $badmintonOffer->price,
            'start_date' => now()->subMonth(),
            'end_date' => now()->addMonth(),
            'status' => 'ACTIVE',
            'is_recurring' => false,
        ]
    )->update(['status' => 'ACTIVE', 'end_date' => now()->addMonth()]);
    
    $users[] = $user;
}
testPass("Created 3 test users with badminton memberships");

// Initialize services
$timeSlotService = new TimeSlotService();
$reservationService = new ReservationService($timeSlotService);

echo "\n4. Test Date: {$testDate->format('l, F j, Y')}\n";

// ============================================
// SCENARIO 1: Both courts occupied 8am-12pm
// ============================================
echo "\n" . str_repeat("-", 72) . "\n";
echo "SCENARIO 1: Both Courts Occupied (8am-12pm)\n";
echo str_repeat("-", 72) . "\n\n";

echo "   Creating reservations for both courts...\n";
$start1 = Carbon::parse($testDate->format('Y-m-d') . ' 08:00:00');

try {
    $res1 = $reservationService->createReservation($users[0]->id, $testDate, $start1, 240, 1);
    testPass("Court 1 reserved: 08:00-12:00");
    
    $res2 = $reservationService->createReservation($users[1]->id, $testDate, $start1, 240, 2);
    testPass("Court 2 reserved: 08:00-12:00");
    
    // Try to reserve during occupied time (should fail)
    echo "\n   Testing reservation during occupied time (9am-10am)...\n";
    try {
        $testStart = Carbon::parse($testDate->format('Y-m-d') . ' 09:00:00');
        $testRes = $reservationService->createReservation($users[2]->id, $testDate, $testStart, 60, null);
        testFail("Should not be able to reserve during 8am-12pm", "Reservation was created");
        $testRes->delete();
    } catch (\Exception $e) {
        if (strpos($e->getMessage(), 'No courts available') !== false) {
            testPass("Cannot reserve during occupied time (8am-12pm)");
        } else {
            testFail("Unexpected error", $e->getMessage());
        }
    }
    
    // Try to reserve from 12pm onwards (should succeed)
    echo "\n   Testing reservation from 12pm onwards...\n";
    try {
        $afternoonStart = Carbon::parse($testDate->format('Y-m-d') . ' 12:00:00');
        $afternoonRes = $reservationService->createReservation($users[2]->id, $testDate, $afternoonStart, 60, null);
        testPass("Can reserve from 12pm onwards (Court {$afternoonRes->court_number})");
        $afternoonRes->delete();
    } catch (\Exception $e) {
        testFail("Should be able to reserve from 12pm", $e->getMessage());
    }
    
    // Cleanup
    $res1->delete();
    $res2->delete();
    
} catch (\Exception $e) {
    testFail("Failed to create initial reservations", $e->getMessage());
}

// ============================================
// SCENARIO 2: Member overlap prevention
// ============================================
echo "\n" . str_repeat("-", 72) . "\n";
echo "SCENARIO 2: Member Overlap Prevention\n";
echo str_repeat("-", 72) . "\n\n";

echo "   Creating initial reservation: 8am-10am...\n";
$memberStart = Carbon::parse($testDate->format('Y-m-d') . ' 08:00:00');

try {
    $memberRes = $reservationService->createReservation($users[0]->id, $testDate, $memberStart, 120, null);
    testPass("Initial reservation created: 08:00-10:00, Court {$memberRes->court_number}");
    
    // Try overlapping reservation (8am-9:30am) - should fail
    echo "\n   Testing overlapping reservation (8am-9:30am)...\n";
    try {
        $overlapStart = Carbon::parse($testDate->format('Y-m-d') . ' 08:00:00');
        $overlapRes = $reservationService->createReservation($users[0]->id, $testDate, $overlapStart, 90, null);
        testFail("Should not be able to create overlapping reservation (8am-9:30am)", "Reservation was created");
        $overlapRes->delete();
    } catch (\Exception $e) {
        if (strpos($e->getMessage(), 'overlap') !== false) {
            testPass("Cannot create overlapping reservation (8am-9:30am)");
        } else {
            testFail("Unexpected error", $e->getMessage());
        }
    }
    
    // Try reservation from 10am onwards - should succeed
    echo "\n   Testing reservation from 10am onwards (no overlap)...\n";
    try {
        $afterStart = Carbon::parse($testDate->format('Y-m-d') . ' 10:00:00');
        $afterRes = $reservationService->createReservation($users[0]->id, $testDate, $afterStart, 60, null);
        testPass("Can reserve from 10am onwards (Court {$afterRes->court_number})");
        $afterRes->delete();
    } catch (\Exception $e) {
        testFail("Should be able to reserve from 10am", $e->getMessage());
    }
    
    // Try partial overlap (9:30am-11am) - should fail
    echo "\n   Testing partial overlapping reservation (9:30am-11am)...\n";
    try {
        $partialStart = Carbon::parse($testDate->format('Y-m-d') . ' 09:30:00');
        $partialRes = $reservationService->createReservation($users[0]->id, $testDate, $partialStart, 90, null);
        testFail("Should not be able to create partial overlapping reservation", "Reservation was created");
        $partialRes->delete();
    } catch (\Exception $e) {
        if (strpos($e->getMessage(), 'overlap') !== false) {
            testPass("Cannot create partial overlapping reservation (9:30am-11am)");
        } else {
            testFail("Unexpected error", $e->getMessage());
        }
    }
    
    // Cleanup
    $memberRes->delete();
    
} catch (\Exception $e) {
    testFail("Failed to create initial member reservation", $e->getMessage());
}

// Summary
echo "\n" . str_repeat("=", 72) . "\n";
echo "TEST SUMMARY\n";
echo str_repeat("=", 72) . "\n";
echo "Passed: $passedTests\n";
echo "Failed: $failedTests\n";

if ($failedTests === 0) {
    echo "\n✓ All tests passed successfully!\n";
    echo "\n";
    exit(0);
} else {
    echo "\n✗ Some tests failed. Please review the output above.\n";
    echo "\n";
    exit(1);
}

