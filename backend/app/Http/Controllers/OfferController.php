<?php

namespace App\Http\Controllers;

use App\Models\FirstTimeDiscount;
use App\Models\MembershipOffer;
use App\Models\Promo;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OfferController extends Controller
{
    /**
     * Get active membership offers for members to view.
     */
    public function index(): JsonResponse
    {
        $offers = MembershipOffer::active()->orderBy('category')->orderBy('price')->get();
        return response()->json($offers);
    }

    /**
     * Get active promos for members to view.
     */
    public function activePromos(): JsonResponse
    {
        $promos = Promo::active()->get();
        return response()->json($promos);
    }

    /**
     * Get eligible first-time discounts for the authenticated user.
     */
    public function eligibleFirstTimeDiscounts(Request $request): JsonResponse
    {
        $user = $request->user();
        
        if (!$user || !$user->isEligibleForFirstTimeDiscount()) {
            return response()->json([]);
        }

        $discounts = FirstTimeDiscount::active()->get();
        return response()->json($discounts);
    }

    /**
     * Check eligibility for first-time discounts and new user promos.
     */
    public function checkEligibility(Request $request): JsonResponse
    {
        $user = $request->user();
        
        if (!$user) {
            return response()->json([
                'eligible' => false,
                'message' => 'User not authenticated',
            ]);
        }

        $eligible = $user->isEligibleForFirstTimeDiscount();
        
        return response()->json([
            'eligible' => $eligible,
            'message' => $eligible 
                ? 'You are eligible for first-time discounts and new user promos'
                : 'You are not eligible. You have already used a promo or purchased a membership.',
        ]);
    }
}
