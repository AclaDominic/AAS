<?php

use App\Http\Controllers\Admin\FirstTimeDiscountController;
use App\Http\Controllers\Admin\MembershipOfferController;
use App\Http\Controllers\Admin\PaymentController as AdminPaymentController;
use App\Http\Controllers\Admin\PromoController;
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
});
