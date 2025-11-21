<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreMembershipOfferRequest;
use App\Http\Requests\Admin\UpdateMembershipOfferRequest;
use App\Models\MembershipOffer;
use Illuminate\Http\JsonResponse;

class MembershipOfferController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(): JsonResponse
    {
        $offers = MembershipOffer::orderBy('created_at', 'desc')->get();
        return response()->json($offers);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreMembershipOfferRequest $request): JsonResponse
    {
        $offer = MembershipOffer::create($request->validated());
        return response()->json($offer, 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id): JsonResponse
    {
        $offer = MembershipOffer::findOrFail($id);
        return response()->json($offer);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateMembershipOfferRequest $request, string $id): JsonResponse
    {
        $offer = MembershipOffer::findOrFail($id);
        $offer->update($request->validated());
        return response()->json($offer);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id): JsonResponse
    {
        $offer = MembershipOffer::findOrFail($id);
        $offer->delete();
        return response()->json(['message' => 'Membership offer deleted successfully']);
    }
}
