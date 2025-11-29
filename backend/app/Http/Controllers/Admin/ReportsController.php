<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReportsController extends Controller
{
    /**
     * Comprehensive payment history report.
     */
    public function paymentHistory(Request $request): JsonResponse
    {
        $query = Payment::with(['user', 'membershipOffer', 'promo', 'firstTimeDiscount']);

        // Date range filter
        if ($request->has('start_date') && $request->has('end_date')) {
            $query->whereBetween('created_at', [$request->start_date, $request->end_date]);
        }

        // Status filter
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        // User filter
        if ($request->has('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        // Payment method filter
        if ($request->has('payment_method')) {
            $query->where('payment_method', $request->payment_method);
        }

        $payments = $query->orderBy('created_at', 'desc')->paginate($request->get('per_page', 50));

        // Calculate summary
        $summary = [
            'total_payments' => $payments->total(),
            'total_amount' => Payment::whereIn('id', $payments->pluck('id'))->sum('amount'),
            'paid_amount' => Payment::whereIn('id', $payments->pluck('id'))->where('status', 'PAID')->sum('amount'),
            'pending_amount' => Payment::whereIn('id', $payments->pluck('id'))->where('status', 'PENDING')->sum('amount'),
        ];

        return response()->json([
            'payments' => $payments,
            'summary' => $summary,
        ]);
    }

    /**
     * All customer balances.
     */
    public function customerBalances(Request $request): JsonResponse
    {
        $users = User::whereHas('member')
            ->with(['payments', 'membershipSubscriptions'])
            ->get()
            ->map(function ($user) {
                return [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'total_paid' => $user->getTotalPaid(),
                    'total_owed' => $user->getTotalOwed(),
                    'pending_renewal_amount' => $user->getPendingRenewalAmount(),
                    'balance' => $user->getTotalPaid() - $user->getTotalOwed(),
                ];
            });

        // Sort by balance (descending)
        $users = $users->sortByDesc('total_paid')->values();

        return response()->json($users);
    }

    /**
     * Payment summary (daily/weekly/monthly).
     */
    public function paymentsSummary(Request $request): JsonResponse
    {
        $period = $request->get('period', 'monthly'); // daily, weekly, monthly
        $startDate = $request->get('start_date', now()->subMonths(6)->startOfMonth());
        $endDate = $request->get('end_date', now()->endOfMonth());

        $query = Payment::where('status', 'PAID')
            ->whereBetween('payment_date', [$startDate, $endDate]);

        $summary = [];

        if ($period === 'daily') {
            $payments = $query->select(
                DB::raw("strftime('%Y-%m-%d', payment_date) as date"),
                DB::raw('COUNT(*) as count'),
                DB::raw('SUM(amount) as total')
            )
                ->groupBy('date')
                ->orderBy('date', 'asc')
                ->get();

            $summary = $payments->map(function ($item) {
                return [
                    'date' => $item->date,
                    'count' => $item->count,
                    'total' => (float) $item->total,
                ];
            });
        } elseif ($period === 'weekly') {
            $payments = $query->select(
                DB::raw("CAST(strftime('%Y', payment_date) AS INTEGER) as year"),
                DB::raw("CAST(strftime('%W', payment_date) AS INTEGER) as week"),
                DB::raw('COUNT(*) as count'),
                DB::raw('SUM(amount) as total')
            )
                ->groupBy('year', 'week')
                ->orderBy('year', 'asc')
                ->orderBy('week', 'asc')
                ->get();

            $summary = $payments->map(function ($item) {
                return [
                    'year' => $item->year,
                    'week' => $item->week,
                    'count' => $item->count,
                    'total' => (float) $item->total,
                ];
            });
        } else { // monthly
            $payments = $query->select(
                DB::raw("CAST(strftime('%Y', payment_date) AS INTEGER) as year"),
                DB::raw("CAST(strftime('%m', payment_date) AS INTEGER) as month"),
                DB::raw('COUNT(*) as count'),
                DB::raw('SUM(amount) as total')
            )
                ->groupBy('year', 'month')
                ->orderBy('year', 'asc')
                ->orderBy('month', 'asc')
                ->get();

            $summary = $payments->map(function ($item) {
                return [
                    'year' => $item->year,
                    'month' => $item->month,
                    'count' => $item->count,
                    'total' => (float) $item->total,
                ];
            });
        }

        return response()->json([
            'period' => $period,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'summary' => $summary,
            'grand_total' => $summary->sum('total'),
        ]);
    }

    /**
     * Export reports (CSV).
     */
    public function export(Request $request)
    {
        $type = $request->get('type', 'payment_history');

        if ($type === 'payment_history') {
            $query = Payment::with(['user', 'membershipOffer']);

            // Apply date range filter
            if ($request->has('start_date') && $request->has('end_date')) {
                $query->whereBetween('created_at', [$request->start_date, $request->end_date]);
            }

            // Apply status filter
            if ($request->has('status') && $request->status !== '') {
                $query->where('status', $request->status);
            }

            $payments = $query->orderBy('created_at', 'desc')->get();

            // Build CSV with Excel-friendly formatting
            // Add UTF-8 BOM for proper Excel encoding
            $csv = "\xEF\xBB\xBF";
            
            // Header row with proper formatting
            $csv .= "ID,User,Email,Membership Offer,Amount,Status,Payment Method,Payment Date\n";
            
            foreach ($payments as $payment) {
                $userName = $payment->user ? str_replace([',', '"'], [';', '""'], $payment->user->name) : 'N/A';
                $userEmail = $payment->user ? $payment->user->email : 'N/A';
                $membershipName = $payment->membershipOffer ? str_replace([',', '"'], [';', '""'], $payment->membershipOffer->name) : 'N/A';
                $paymentMethod = $payment->payment_method ? ucwords(str_replace('_', ' ', strtolower($payment->payment_method))) : 'N/A';
                
                // Format date as Excel-friendly format (MM/DD/YYYY HH:MM:SS)
                $paymentDate = 'N/A';
                if ($payment->payment_date) {
                    $paymentDate = $payment->payment_date->format('m/d/Y H:i:s');
                } elseif ($payment->created_at) {
                    $paymentDate = $payment->created_at->format('m/d/Y H:i:s');
                }

                // Format amount with proper decimal places
                $amount = number_format($payment->amount, 2, '.', '');

                $csv .= sprintf(
                    "%d,\"%s\",\"%s\",\"%s\",%s,%s,\"%s\",\"%s\"\n",
                    $payment->id,
                    $userName,
                    $userEmail,
                    $membershipName,
                    $amount,
                    $payment->status,
                    $paymentMethod,
                    $paymentDate
                );
            }

            $filename = 'payment_history_' . ($request->has('start_date') ? str_replace('-', '', $request->start_date) : 'all') . '_to_' . ($request->has('end_date') ? str_replace('-', '', $request->end_date) : 'all') . '.csv';

            return response($csv)
                ->header('Content-Type', 'text/csv; charset=UTF-8')
                ->header('Content-Disposition', 'attachment; filename="' . $filename . '"');
        }

        return response()->json(['message' => 'Invalid export type.'], 400);
    }
}

