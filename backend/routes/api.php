<?php

use App\Http\Controllers\Admin\BillingController as AdminBillingController;
use App\Http\Controllers\Admin\CourtReservationController as AdminCourtReservationController;
use App\Http\Controllers\Admin\FacilityScheduleController;
use App\Http\Controllers\Admin\FacilitySettingController;
use App\Http\Controllers\Admin\FirstTimeDiscountController;
use App\Http\Controllers\Admin\MemberController;
use App\Http\Controllers\Admin\MembershipOfferController;
use App\Http\Controllers\Admin\PaymentController as AdminPaymentController;
use App\Http\Controllers\Admin\PromoController;
use App\Http\Controllers\Member\BillingController as MemberBillingController;
use App\Http\Controllers\Member\CourtReservationController;
use App\Http\Controllers\Member\FacilitySettingController as MemberFacilitySettingController;
use App\Http\Controllers\Member\MembershipController;
use App\Http\Controllers\Member\PaymentController;
use App\Http\Controllers\OfferController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum'])->get('/user', function (Request $request) {
    $user = $request->user()->load('member');
    return [
        'id' => $user->id,
        'name' => $user->name,
        'email' => $user->email,
        'is_admin' => $user->isAdmin(),
        'is_member' => $user->isMember(),
    ];
});

// Maya payment callback (no auth required - called by Maya)
Route::post('/payments/maya/callback', [PaymentController::class, 'handleMayaCallback']);

// Admin routes (protected by admin middleware)
Route::middleware(['auth:sanctum', 'admin'])->prefix('admin')->group(function () {
    // Membership Offers
    Route::apiResource('offers', MembershipOfferController::class);
    
    // Promos
    Route::apiResource('promos', PromoController::class);
    
    // First-Time Discounts
    Route::apiResource('first-time-discounts', FirstTimeDiscountController::class);
    
    // Payments
    Route::get('/payments/code/{code}', [AdminPaymentController::class, 'findByCode']);
    Route::post('/payments/{id}/mark-paid', [AdminPaymentController::class, 'markAsPaid']);
    
    // Members
    Route::get('/members', [MemberController::class, 'index']);
    Route::get('/members/stats', [MemberController::class, 'stats']);
    Route::get('/members/{id}', [MemberController::class, 'show']);
    
    // Billing
    Route::post('/billing/generate-statements', [AdminBillingController::class, 'generateStatements']);
    Route::get('/billing/statements', [AdminBillingController::class, 'index']);
    Route::get('/billing/statements/{id}', [AdminBillingController::class, 'show']);
    Route::get('/billing/statements/{id}/invoice', [AdminBillingController::class, 'downloadInvoice']);
    Route::get('/payments/{id}/receipt', [AdminBillingController::class, 'downloadReceipt']);
    
    // Reports
    Route::get('/reports/payment-history', [\App\Http\Controllers\Admin\ReportsController::class, 'paymentHistory']);
    Route::get('/reports/customer-balances', [\App\Http\Controllers\Admin\ReportsController::class, 'customerBalances']);
    Route::get('/reports/payments-summary', [\App\Http\Controllers\Admin\ReportsController::class, 'paymentsSummary']);
    Route::get('/reports/export', [\App\Http\Controllers\Admin\ReportsController::class, 'export']);
    
    // Facility Schedule
    Route::get('/facility/schedule', [FacilityScheduleController::class, 'index']);
    Route::put('/facility/schedule', [FacilityScheduleController::class, 'update']);
    Route::put('/facility/schedule/{day}', [FacilityScheduleController::class, 'updateDay']);
    
    // Facility Settings
    Route::get('/facility/settings', [FacilitySettingController::class, 'show']);
    Route::put('/facility/settings', [FacilitySettingController::class, 'update']);
    
    // Court Reservations
    // Custom routes must be defined before apiResource to avoid route conflicts
    Route::post('/reservations/{id}/cancel', [AdminCourtReservationController::class, 'cancel'])
        ->name('admin.reservations.cancel');
    Route::post('/reservations/{id}/update-status', [AdminCourtReservationController::class, 'updateStatus'])
        ->name('admin.reservations.update-status');
    Route::apiResource('reservations', AdminCourtReservationController::class)
        ->only(['index', 'show'])
        ->names([
            'index' => 'admin.reservations.index',
            'show' => 'admin.reservations.show',
        ]);
    
    // Notifications
    Route::get('/notifications', [\App\Http\Controllers\Admin\NotificationController::class, 'index'])
        ->name('admin.notifications.index');
    Route::get('/notifications/unread-count', [\App\Http\Controllers\Admin\NotificationController::class, 'unreadCount'])
        ->name('admin.notifications.unread-count');
    Route::post('/notifications/{id}/read', [\App\Http\Controllers\Admin\NotificationController::class, 'markAsRead'])
        ->name('admin.notifications.mark-read');
    Route::post('/notifications/read-all', [\App\Http\Controllers\Admin\NotificationController::class, 'markAllAsRead'])
        ->name('admin.notifications.mark-all-read');
});

// Public/Member routes
Route::middleware(['auth:sanctum'])->group(function () {
    // Get active offers
    Route::get('/offers', [OfferController::class, 'index']);
    
    // Get active promos
    Route::get('/promos/active', [OfferController::class, 'activePromos']);
    
    // Get eligible first-time discounts
    Route::get('/first-time-discounts/eligible', [OfferController::class, 'eligibleFirstTimeDiscounts']);
    
    // Check eligibility
    Route::get('/memberships/eligibility', [OfferController::class, 'checkEligibility']);
    
    // Purchase membership
    Route::post('/memberships/purchase', [MembershipController::class, 'purchase']);
    
    // Payments
    Route::get('/payments', [PaymentController::class, 'getAllPayments']);
    Route::post('/payments/initiate', [PaymentController::class, 'initiatePurchase']);
    Route::get('/payments/pending', [PaymentController::class, 'getPendingPayments']);
    Route::post('/payments/{id}/cancel', [PaymentController::class, 'cancelPayment']);
    Route::post('/payments/{id}/process-online', [PaymentController::class, 'processOnlinePayment']);
    
    // Billing
    Route::get('/memberships/pending-renewals', [MemberBillingController::class, 'getPendingRenewals']);
    Route::get('/billing/statements', [MemberBillingController::class, 'getStatements']);
    Route::get('/billing/statements/{id}/invoice', [MemberBillingController::class, 'downloadInvoice']);
    Route::get('/payments/{id}/receipt', [MemberBillingController::class, 'downloadReceipt']);
    
    // Bulk Payment
    Route::post('/payments/bulk-pay', [PaymentController::class, 'bulkPay']);
    
    // Facility Settings (read-only for members)
    Route::get('/facility/settings', [MemberFacilitySettingController::class, 'show']);
    
    // Court Reservations
    Route::get('/courts/available-slots', [CourtReservationController::class, 'availableSlots'])
        ->name('courts.available-slots');
    Route::get('/courts/reservation-options', [CourtReservationController::class, 'getReservationOptions'])
        ->name('courts.reservation-options');
    Route::get('/courts/check-availability', [CourtReservationController::class, 'checkAvailability'])
        ->name('courts.check-availability');
    Route::apiResource('reservations', CourtReservationController::class)
        ->only(['index', 'store', 'show'])
        ->names([
            'index' => 'reservations.index',
            'store' => 'reservations.store',
            'show' => 'reservations.show',
        ]);
    Route::post('/reservations/{id}/cancel', [CourtReservationController::class, 'cancel'])
        ->name('reservations.cancel');
});
