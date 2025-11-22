<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Invoice extends Model
{
    protected $fillable = [
        'billing_statement_id',
        'invoice_number',
        'invoice_date',
        'amount',
        'status',
        'pdf_path',
    ];

    protected $casts = [
        'invoice_date' => 'date',
        'amount' => 'decimal:2',
    ];

    public function billingStatement(): BelongsTo
    {
        return $this->belongsTo(BillingStatement::class);
    }

    /**
     * Generate a unique invoice number.
     */
    public static function generateInvoiceNumber(): string
    {
        $date = now()->format('Ymd');
        $lastInvoice = self::whereDate('created_at', today())
            ->orderBy('id', 'desc')
            ->first();

        if ($lastInvoice && $lastInvoice->invoice_number) {
            $lastNumber = (int) substr($lastInvoice->invoice_number, -4);
            $newNumber = str_pad($lastNumber + 1, 4, '0', STR_PAD_LEFT);
        } else {
            $newNumber = '0001';
        }

        return "INV-{$date}-{$newNumber}";
    }
}

