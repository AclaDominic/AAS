# Court Reservation System - Logic Verification

## Test Scenarios Overview

This document outlines the test scenarios that verify the court reservation system logic works correctly.

## Scenario 1: All Courts Occupied - No Reservations During Occupied Time

### Setup:
- **Number of Courts:** 2
- **Existing Reservations:**
  - Court 1: Member 1, 8:00 AM - 12:00 PM
  - Court 2: Member 2, 8:00 AM - 12:00 PM

### Expected Behavior:

#### During 8:00 AM - 12:00 PM:
- **8:00 AM slot:** `available_courts = 0`, `is_available = false`
- **9:00 AM slot:** `available_courts = 0`, `is_available = false`
- **10:00 AM slot:** `available_courts = 0`, `is_available = false`
- **11:00 AM slot:** `available_courts = 0`, `is_available = false`
- **Attempting to book:** Should throw exception: "No courts available for the selected time slot"

#### After 12:00 PM:
- **12:00 PM slot:** `available_courts = 2`, `is_available = true`
- **12:30 PM slot:** `available_courts = 2`, `is_available = true`
- **Attempting to book:** Should succeed - reservation created successfully

### Logic Verification:
```php
// For each 30-minute slot:
// 1. Check all active reservations
// 2. Count how many courts are occupied (reservations that overlap the slot)
// 3. Calculate: available_courts = total_courts - occupied_courts
// 4. If available_courts == 0, mark slot as unavailable
```

## Scenario 2: Member Overlap Prevention - No Overlapping Reservations

### Setup:
- **Member has existing reservation:**
  - 8:00 AM - 10:00 AM (any court)

### Expected Behavior:

#### Overlapping Times (Should be BLOCKED):
- **8:00 AM - 9:30 AM:** ❌ BLOCKED (overlaps 8:00 AM - 10:00 AM)
- **8:30 AM - 10:30 AM:** ❌ BLOCKED (overlaps 8:00 AM - 10:00 AM)
- **9:00 AM - 10:30 AM:** ❌ BLOCKED (overlaps 8:00 AM - 10:00 AM)
- **9:30 AM - 10:30 AM:** ❌ BLOCKED (overlaps 8:00 AM - 10:00 AM)
- **Attempting to book:** Should throw exception: "You already have a reservation that overlaps with this time slot"

#### Non-Overlapping Times (Should be ALLOWED):
- **10:00 AM onwards:** ✅ ALLOWED (no overlap with 8:00 AM - 10:00 AM)
- **10:00 AM - 11:00 AM:** ✅ ALLOWED
- **7:00 AM - 8:00 AM:** ✅ ALLOWED (ends before existing reservation starts)
- **Attempting to book:** Should succeed - reservation created successfully

### Logic Verification:
```php
// Before creating reservation:
// 1. Get all active reservations for this member
// 2. Check if requested time overlaps with any existing reservation
// 3. Overlap check: 
//    - reservation1.start < reservation2.end AND
//    - reservation1.end > reservation2.start
// 4. If overlap found, throw exception
// 5. If no overlap, proceed with reservation
```

## Additional Test Scenarios

### Scenario 3: Different Members Can Book Different Courts Simultaneously
- **Setup:** 2 members, 2 courts
- **Expected:** Both members can book different courts at the same time slot
- **Verification:** 
  - Member 1 books Court 1 at 8:00 AM - 10:00 AM
  - Member 2 can book Court 2 at 8:00 AM - 10:00 AM
  - Both reservations should succeed

### Scenario 4: Partial Court Occupancy - Some Courts Available
- **Setup:** 2 courts, 1 court occupied 9:00 AM - 10:00 AM
- **Expected:** 
  - 9:00 AM slot should show `available_courts = 1`
  - Slot should still be `is_available = true`
  - New reservation should be allowed on the free court

## Key Logic Points

1. **Court Availability Calculation:**
   - For each 30-minute time slot, check all active reservations
   - Count occupied courts (reservations that overlap the slot)
   - `available_courts = total_courts - occupied_courts`
   - If `available_courts > 0`, slot is available

2. **Member Overlap Prevention:**
   - Check all active reservations for the same user
   - Two reservations overlap if:
     - `reservation1.start_time < reservation2.end_time` AND
     - `reservation1.end_time > reservation2.start_time`
   - If overlap detected, block new reservation

3. **Time Slot Overlap Detection:**
   - A reservation overlaps a 30-minute slot if:
     - Reservation starts before slot ends AND
     - Reservation ends after slot starts

## Running the Tests

Once the reservation system is implemented, run:

```bash
php artisan test --filter CourtReservationTest
```

Or run specific test methods:

```bash
php artisan test --filter test_all_courts_occupied_blocks_new_reservations
php artisan test --filter test_member_cannot_make_overlapping_reservations
```

