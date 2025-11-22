<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Receipt extends Model
{
    protected $fillable = [
        'payment_id',
        'receipt_number',
        'receipt_date',
        'amount',
        'pdf_path',
    ];

    protected $casts = [
        'receipt_date' => 'date',
        'amount' => 'decimal:2',
    ];

    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class);
    }

    /**
     * Generate a unique receipt number.
     */
    public static function generateReceiptNumber(): string
    {
        $date = now()->format('Ymd');
        $lastReceipt = self::whereDate('created_at', today())
            ->orderBy('id', 'desc')
            ->first();

        if ($lastReceipt && $lastReceipt->receipt_number) {
            $lastNumber = (int) substr($lastReceipt->receipt_number, -4);
            $newNumber = str_pad($lastNumber + 1, 4, '0', STR_PAD_LEFT);
        } else {
            $newNumber = '0001';
        }

        return "RCP-{$date}-{$newNumber}";
    }
}

