<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StorePromoRequest;
use App\Models\Promo;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PromoController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(): JsonResponse
    {
        $promos = Promo::orderBy('created_at', 'desc')->get();
        return response()->json($promos);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StorePromoRequest $request): JsonResponse
    {
        $promo = Promo::create($request->validated());
        return response()->json($promo, 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id): JsonResponse
    {
        $promo = Promo::findOrFail($id);
        return response()->json($promo);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(StorePromoRequest $request, string $id): JsonResponse
    {
        $promo = Promo::findOrFail($id);
        $promo->update($request->validated());
        return response()->json($promo);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id): JsonResponse
    {
        $promo = Promo::findOrFail($id);
        $promo->delete();
        return response()->json(['message' => 'Promo deleted successfully']);
    }

    /**
     * Get active promos (for members to view).
     */
    public function active(): JsonResponse
    {
        $promos = Promo::active()->get();
        return response()->json($promos);
    }
}
