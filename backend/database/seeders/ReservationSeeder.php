<?php

namespace Database\Seeders;

use App\Models\CourtReservation;
use App\Models\Member;
use App\Models\MembershipOffer;
use App\Models\MembershipSubscription;
use App\Models\Payment;
use App\Models\Receipt;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class ReservationSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Update any existing reservations without category to default to BADMINTON_COURT
        CourtReservation::whereNull('category')
            ->orWhere('category', '')
            ->update(['category' => 'BADMINTON_COURT']);

        // Get membership offers
        $gymOffer = MembershipOffer::where('category', 'GYM')->first();
        $badmintonOffer = MembershipOffer::where('category', 'BADMINTON_COURT')->first();

        if (!$gymOffer || !$badmintonOffer) {
            $this->command->warn('Membership offers not found. Please run MembershipOfferSeeder first.');
            return;
        }

        // Create or get Rashmin Acuña user
        $rashmin = User::firstOrCreate(
            ['email' => 'rashmin@gmail.com'],
            [
                'name' => 'rashmin acuÑa',
                'password' => Hash::make('rashmin'),
                'email_verified_at' => now(),
            ]
        );

        // Ensure Rashmin is a member
        if (!$rashmin->member) {
            Member::create(['user_id' => $rashmin->id]);
        }

        // Ensure Rashmin has active GYM membership
        $gymSubscription = MembershipSubscription::firstOrCreate(
            [
                'user_id' => $rashmin->id,
                'membership_offer_id' => $gymOffer->id,
            ],
            [
                'price_paid' => $gymOffer->price,
                'start_date' => now()->subMonth(),
                'end_date' => now()->addMonths(2),
                'status' => 'ACTIVE',
                'is_recurring' => true,
            ]
        );

        // Create payment for gym subscription if needed
        if (!$gymSubscription->payment_id) {
            $gymPayment = Payment::create([
                'user_id' => $rashmin->id,
                'membership_offer_id' => $gymOffer->id,
                'payment_method' => 'CASH',
                'amount' => $gymOffer->price,
                'status' => 'PAID',
                'payment_date' => now()->subMonth(),
            ]);

            $gymSubscription->update(['payment_id' => $gymPayment->id]);

            Receipt::create([
                'payment_id' => $gymPayment->id,
                'receipt_number' => Receipt::generateReceiptNumber(),
                'receipt_date' => $gymPayment->payment_date,
                'amount' => $gymPayment->amount,
            ]);
        }

        // Ensure Rashmin has active BADMINTON_COURT membership
        $badmintonSubscription = MembershipSubscription::firstOrCreate(
            [
                'user_id' => $rashmin->id,
                'membership_offer_id' => $badmintonOffer->id,
            ],
            [
                'price_paid' => $badmintonOffer->price,
                'start_date' => now()->subMonth(),
                'end_date' => now()->addMonths(2),
                'status' => 'ACTIVE',
                'is_recurring' => true,
            ]
        );

        // Create payment for badminton subscription if needed
        if (!$badmintonSubscription->payment_id) {
            $badmintonPayment = Payment::create([
                'user_id' => $rashmin->id,
                'membership_offer_id' => $badmintonOffer->id,
                'payment_method' => 'CASH',
                'amount' => $badmintonOffer->price,
                'status' => 'PAID',
                'payment_date' => now()->subMonth(),
            ]);

            $badmintonSubscription->update(['payment_id' => $badmintonPayment->id]);

            Receipt::create([
                'payment_id' => $badmintonPayment->id,
                'receipt_number' => Receipt::generateReceiptNumber(),
                'receipt_date' => $badmintonPayment->payment_date,
                'amount' => $badmintonPayment->amount,
            ]);
        }

        // Get users with active memberships (including Rashmin)
        $gymUsers = User::whereHas('member')
            ->whereHas('membershipSubscriptions', function ($query) use ($gymOffer) {
                $query->where('membership_offer_id', $gymOffer->id)
                    ->where('status', 'ACTIVE')
                    ->where('end_date', '>=', now());
            })
            ->get();

        $badmintonUsers = User::whereHas('member')
            ->whereHas('membershipSubscriptions', function ($query) use ($badmintonOffer) {
                $query->where('membership_offer_id', $badmintonOffer->id)
                    ->where('status', 'ACTIVE')
                    ->where('end_date', '>=', now());
            })
            ->get();

        $this->command->info('Created/verified user: rashmin acuÑa (rashmin@gmail.com) with both GYM and BADMINTON_COURT memberships');

        $this->command->info('Creating reservations for GYM and BADMINTON_COURT categories...');

        // ============================================
        // GYM RESERVATIONS (for Rashmin)
        // ============================================
        if ($gymUsers->isNotEmpty()) {
            // Prioritize Rashmin for gym reservations
            $gymUser = $gymUsers->contains('id', $rashmin->id) ? $rashmin : $gymUsers->first();

            // PENDING gym reservation (tomorrow)
            $tomorrow = Carbon::tomorrow();
            CourtReservation::create([
                'user_id' => $gymUser->id,
                'category' => 'GYM',
                'court_number' => null,
                'reservation_date' => $tomorrow,
                'start_time' => $tomorrow->copy()->setTime(8, 0),
                'end_time' => $tomorrow->copy()->setTime(10, 0),
                'duration_minutes' => 120,
                'status' => 'PENDING',
            ]);

            // CONFIRMED gym reservation (today)
            $today = Carbon::today();
            CourtReservation::create([
                'user_id' => $gymUser->id,
                'category' => 'GYM',
                'court_number' => null,
                'reservation_date' => $today,
                'start_time' => $today->copy()->setTime(14, 0),
                'end_time' => $today->copy()->setTime(16, 0),
                'duration_minutes' => 120,
                'status' => 'CONFIRMED',
            ]);

            // COMPLETED gym reservation (yesterday)
            $yesterday = Carbon::yesterday();
            CourtReservation::create([
                'user_id' => $gymUser->id,
                'category' => 'GYM',
                'court_number' => null,
                'reservation_date' => $yesterday,
                'start_time' => $yesterday->copy()->setTime(10, 0),
                'end_time' => $yesterday->copy()->setTime(11, 30),
                'duration_minutes' => 90,
                'status' => 'COMPLETED',
            ]);

            // Additional PENDING gym reservation (2 days from now)
            $futureDate = Carbon::today()->addDays(2);
            CourtReservation::create([
                'user_id' => $gymUser->id,
                'category' => 'GYM',
                'court_number' => null,
                'reservation_date' => $futureDate,
                'start_time' => $futureDate->copy()->setTime(18, 0),
                'end_time' => $futureDate->copy()->setTime(20, 0),
                'duration_minutes' => 120,
                'status' => 'PENDING',
            ]);

            $this->command->info('Created 4 GYM reservations (1 PENDING, 1 CONFIRMED, 1 COMPLETED, 1 PENDING)');
        }

        // ============================================
        // BADMINTON COURT RESERVATIONS (for Rashmin)
        // ============================================
        if ($badmintonUsers->isNotEmpty()) {
            // Prioritize Rashmin for badminton court reservations
            $badmintonUser = $badmintonUsers->contains('id', $rashmin->id) ? $rashmin : $badmintonUsers->first();

            // PENDING badminton court reservation (tomorrow, Court 1)
            $tomorrow = Carbon::tomorrow();
            CourtReservation::create([
                'user_id' => $badmintonUser->id,
                'category' => 'BADMINTON_COURT',
                'court_number' => 1,
                'reservation_date' => $tomorrow,
                'start_time' => $tomorrow->copy()->setTime(9, 0),
                'end_time' => $tomorrow->copy()->setTime(10, 0),
                'duration_minutes' => 60,
                'status' => 'PENDING',
            ]);

            // CONFIRMED badminton court reservation (today, Court 2)
            $today = Carbon::today();
            CourtReservation::create([
                'user_id' => $badmintonUser->id,
                'category' => 'BADMINTON_COURT',
                'court_number' => 2,
                'reservation_date' => $today,
                'start_time' => $today->copy()->setTime(15, 0),
                'end_time' => $today->copy()->setTime(16, 30),
                'duration_minutes' => 90,
                'status' => 'CONFIRMED',
            ]);

            // COMPLETED badminton court reservation (yesterday, Court 1)
            $yesterday = Carbon::yesterday();
            CourtReservation::create([
                'user_id' => $badmintonUser->id,
                'category' => 'BADMINTON_COURT',
                'court_number' => 1,
                'reservation_date' => $yesterday,
                'start_time' => $yesterday->copy()->setTime(11, 0),
                'end_time' => $yesterday->copy()->setTime(12, 0),
                'duration_minutes' => 60,
                'status' => 'COMPLETED',
            ]);

            // Additional PENDING badminton court reservation (3 days from now, Court 1)
            $futureDate = Carbon::today()->addDays(3);
            CourtReservation::create([
                'user_id' => $badmintonUser->id,
                'category' => 'BADMINTON_COURT',
                'court_number' => 1,
                'reservation_date' => $futureDate,
                'start_time' => $futureDate->copy()->setTime(19, 0),
                'end_time' => $futureDate->copy()->setTime(20, 0),
                'duration_minutes' => 60,
                'status' => 'PENDING',
            ]);

            // Additional CONFIRMED badminton court reservation (tomorrow, Court 2)
            CourtReservation::create([
                'user_id' => $badmintonUser->id,
                'category' => 'BADMINTON_COURT',
                'court_number' => 2,
                'reservation_date' => $tomorrow,
                'start_time' => $tomorrow->copy()->setTime(14, 0),
                'end_time' => $tomorrow->copy()->setTime(15, 30),
                'duration_minutes' => 90,
                'status' => 'CONFIRMED',
            ]);

            $this->command->info('Created 5 BADMINTON_COURT reservations (2 PENDING, 2 CONFIRMED, 1 COMPLETED)');
        }

        // ============================================
        // ADDITIONAL RESERVATIONS WITH DIFFERENT USERS
        // ============================================
        if ($gymUsers->count() > 1) {
            $secondGymUser = $gymUsers->skip(1)->first();
            $futureDate = Carbon::today()->addDays(5);
            
            CourtReservation::create([
                'user_id' => $secondGymUser->id,
                'category' => 'GYM',
                'court_number' => null,
                'reservation_date' => $futureDate,
                'start_time' => $futureDate->copy()->setTime(12, 0),
                'end_time' => $futureDate->copy()->setTime(13, 0),
                'duration_minutes' => 60,
                'status' => 'PENDING',
            ]);
            
            $this->command->info('Created 1 additional GYM reservation for another user');
        }

        if ($badmintonUsers->count() > 1) {
            $secondBadmintonUser = $badmintonUsers->skip(1)->first();
            $futureDate = Carbon::today()->addDays(4);
            
            CourtReservation::create([
                'user_id' => $secondBadmintonUser->id,
                'category' => 'BADMINTON_COURT',
                'court_number' => 2,
                'reservation_date' => $futureDate,
                'start_time' => $futureDate->copy()->setTime(16, 0),
                'end_time' => $futureDate->copy()->setTime(17, 0),
                'duration_minutes' => 60,
                'status' => 'CONFIRMED',
            ]);
            
            $this->command->info('Created 1 additional BADMINTON_COURT reservation for another user');
        }

        $totalReservations = CourtReservation::count();
        $this->command->info("Total reservations created: {$totalReservations}");
        $this->command->info('Reservation seeding completed!');
    }
}
