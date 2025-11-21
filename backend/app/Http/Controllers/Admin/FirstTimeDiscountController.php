<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreFirstTimeDiscountRequest;
use App\Models\FirstTimeDiscount;
use Illuminate\Http\JsonResponse;

class FirstTimeDiscountController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(): JsonResponse
    {
        $discounts = FirstTimeDiscount::orderBy('created_at', 'desc')->get();
        return response()->json($discounts);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreFirstTimeDiscountRequest $request): JsonResponse
    {
        $discount = FirstTimeDiscount::create($request->validated());
        return response()->json($discount, 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id): JsonResponse
    {
        $discount = FirstTimeDiscount::findOrFail($id);
        return response()->json($discount);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(StoreFirstTimeDiscountRequest $request, string $id): JsonResponse
    {
        $discount = FirstTimeDiscount::findOrFail($id);
        $discount->update($request->validated());
        return response()->json($discount);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id): JsonResponse
    {
        $discount = FirstTimeDiscount::findOrFail($id);
        $discount->delete();
        return response()->json(['message' => 'First-time discount deleted successfully']);
    }
}
