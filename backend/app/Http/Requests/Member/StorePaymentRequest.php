<?php

namespace App\Http\Requests\Member;

use Illuminate\Foundation\Http\FormRequest;

class StorePaymentRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user() && $this->user()->isMember();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'membership_offer_id' => ['required', 'exists:membership_offers,id'],
            'promo_id' => ['nullable', 'exists:promos,id'],
            'first_time_discount_id' => ['nullable', 'exists:first_time_discounts,id'],
            'payment_method' => ['required', 'in:CASH,ONLINE_CARD,ONLINE_MAYA'],
        ];
    }
}
