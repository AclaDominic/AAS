<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class MemberController extends Controller
{
    /**
     * List all members with pagination, search, and filters.
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $query = User::with(['member', 'membershipSubscriptions.membershipOffer'])
                ->whereHas('member');

            // Search by name or email
            if ($request->has('search') && $request->search) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                      ->orWhere('email', 'like', "%{$search}%");
                });
            }

            // Filter by membership status
            if ($request->has('status') && $request->status) {
                $query->withMembershipStatus($request->status);
            }

            // Filter by membership category
            if ($request->has('category') && $request->category) {
                $query->withMembershipCategory($request->category);
            }

            // Sort options
            $sortBy = $request->get('sort_by', 'created_at');
            $sortOrder = $request->get('sort_order', 'desc');
            
            $allowedSorts = ['name', 'email', 'created_at'];
            if (in_array($sortBy, $allowedSorts)) {
                $query->orderBy($sortBy, $sortOrder);
            }

            // Pagination
            $perPage = $request->get('per_page', 15);
            $perPage = min(max(1, (int) $perPage), 100); // Limit between 1 and 100
            $members = $query->paginate($perPage);

            // Transform data to include membership status with error handling
            $members->getCollection()->transform(function ($user) {
                try {
                    $activeSubscriptions = $user->getActiveSubscriptions();
                    $lastPaymentDate = $user->payments()
                        ->where('status', 'PAID')
                        ->latest('payment_date')
                        ->value('payment_date');

                    return [
                        'id' => $user->id,
                        'name' => $user->name ?? 'N/A',
                        'email' => $user->email ?? 'N/A',
                        'created_at' => $user->created_at,
                        'membership_status' => $user->getMembershipStatus(),
                        'active_subscriptions_count' => $activeSubscriptions ? $activeSubscriptions->count() : 0,
                        'total_spent' => $user->getTotalSpent(),
                        'last_payment_date' => $lastPaymentDate,
                    ];
                } catch (\Exception $e) {
                    Log::warning('Error transforming member data', [
                        'user_id' => $user->id ?? null,
                        'error' => $e->getMessage(),
                    ]);

                    // Return safe defaults
                    return [
                        'id' => $user->id ?? null,
                        'name' => $user->name ?? 'N/A',
                        'email' => $user->email ?? 'N/A',
                        'created_at' => $user->created_at ?? null,
                        'membership_status' => 'inactive',
                        'active_subscriptions_count' => 0,
                        'total_spent' => 0,
                        'last_payment_date' => null,
                    ];
                }
            });

            return response()->json($members);
        } catch (\Exception $e) {
            Log::error('Error fetching members list', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'message' => 'An error occurred while fetching members.',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * Get member details with subscriptions and payment history.
     */
    public function show($id): JsonResponse
    {
        try {
            // Validate member ID
            if (!is_numeric($id) || $id <= 0) {
                return response()->json([
                    'message' => 'Invalid member ID.',
                ], 400);
            }

            $user = User::with([
                'member',
                'membershipSubscriptions.membershipOffer',
                'membershipSubscriptions.promo',
                'membershipSubscriptions.firstTimeDiscount',
                'payments.membershipOffer',
                'payments.promo',
                'payments.firstTimeDiscount',
            ])
            ->whereHas('member')
            ->find($id);

            if (!$user) {
                return response()->json([
                    'message' => 'Member not found.',
                ], 404);
            }

            // Get active subscriptions with error handling
            try {
                $activeSubscriptions = $user->getActiveSubscriptions();
            } catch (\Exception $e) {
                Log::warning('Error getting active subscriptions for user', [
                    'user_id' => $user->id,
                    'error' => $e->getMessage(),
                ]);
                $activeSubscriptions = collect([]);
            }

            // Get all subscriptions with error handling
            try {
                $allSubscriptions = $user->membershipSubscriptions()
                    ->with(['membershipOffer', 'promo', 'firstTimeDiscount'])
                    ->orderBy('created_at', 'desc')
                    ->get()
                    ->filter(function ($subscription) {
                        // Filter out subscriptions with deleted/null membership offers
                        return $subscription->membershipOffer !== null;
                    });
            } catch (\Exception $e) {
                Log::warning('Error getting all subscriptions for user', [
                    'user_id' => $user->id,
                    'error' => $e->getMessage(),
                ]);
                $allSubscriptions = collect([]);
            }

            // Get payments with error handling
            try {
                $payments = $user->payments()
                    ->with(['membershipOffer', 'promo', 'firstTimeDiscount'])
                    ->orderBy('created_at', 'desc')
                    ->get()
                    ->filter(function ($payment) {
                        // Filter out payments with deleted/null membership offers
                        return $payment->membershipOffer !== null;
                    });
            } catch (\Exception $e) {
                Log::warning('Error getting payments for user', [
                    'user_id' => $user->id,
                    'error' => $e->getMessage(),
                ]);
                $payments = collect([]);
            }

            // Get calculated values with error handling
            try {
                $membershipStatus = $user->getMembershipStatus();
            } catch (\Exception $e) {
                Log::warning('Error calculating membership status', [
                    'user_id' => $user->id,
                    'error' => $e->getMessage(),
                ]);
                $membershipStatus = 'inactive';
            }

            try {
                $totalSpent = $user->getTotalSpent();
                $totalOwed = $user->getTotalOwed();
                $pendingRenewalAmount = $user->getPendingRenewalAmount();
                $activeSubscriptionsCount = $user->getActiveSubscriptionsCount();
            } catch (\Exception $e) {
                Log::warning('Error calculating financial data for user', [
                    'user_id' => $user->id,
                    'error' => $e->getMessage(),
                ]);
                $totalSpent = 0;
                $totalOwed = 0;
                $pendingRenewalAmount = 0;
                $activeSubscriptionsCount = [];
            }

            return response()->json([
                'id' => $user->id,
                'name' => $user->name ?? 'N/A',
                'email' => $user->email ?? 'N/A',
                'created_at' => $user->created_at,
                'email_verified_at' => $user->email_verified_at,
                'membership_status' => $membershipStatus,
                'active_subscriptions' => $activeSubscriptions,
                'all_subscriptions' => $allSubscriptions,
                'payments' => $payments,
                'total_spent' => $totalSpent,
                'total_owed' => $totalOwed,
                'pending_renewal_amount' => $pendingRenewalAmount,
                'active_subscriptions_count' => $activeSubscriptionsCount,
            ]);
        } catch (\Exception $e) {
            Log::error('Error fetching member details', [
                'member_id' => $id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'message' => 'An error occurred while fetching member details.',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * Get statistics (active/inactive counts, etc.).
     */
    public function stats(): JsonResponse
    {
        try {
            $totalMembers = User::whereHas('member')->count();
            
            $activeMembers = User::whereHas('member')
                ->withMembershipStatus('active')
                ->count();
            
            $expiredMembers = User::whereHas('member')
                ->withMembershipStatus('expired')
                ->count();
            
            $inactiveMembers = User::whereHas('member')
                ->withMembershipStatus('inactive')
                ->count();

            $gymMembers = User::whereHas('member')
                ->withMembershipCategory('GYM')
                ->count();

            $badmintonMembers = User::whereHas('member')
                ->withMembershipCategory('BADMINTON_COURT')
                ->count();

            // Calculate total revenue with error handling
            try {
                $totalRevenue = User::whereHas('member')
                    ->get()
                    ->sum(function ($user) {
                        try {
                            return $user->getTotalSpent();
                        } catch (\Exception $e) {
                            Log::warning('Error calculating total spent for user', [
                                'user_id' => $user->id ?? null,
                                'error' => $e->getMessage(),
                            ]);
                            return 0;
                        }
                    });
            } catch (\Exception $e) {
                Log::warning('Error calculating total revenue', [
                    'error' => $e->getMessage(),
                ]);
                $totalRevenue = 0;
            }

            return response()->json([
                'total_members' => $totalMembers,
                'active_members' => $activeMembers,
                'expired_members' => $expiredMembers,
                'inactive_members' => $inactiveMembers,
                'gym_members' => $gymMembers,
                'badminton_members' => $badmintonMembers,
                'total_revenue' => $totalRevenue,
            ]);
        } catch (\Exception $e) {
            Log::error('Error fetching member statistics', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'message' => 'An error occurred while fetching statistics.',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }
}

