<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FarmerPayment extends Model
{
    public const METHOD_CASH = 'cash';
    public const METHOD_BANK_TRANSFER = 'bank_transfer';
    public const METHOD_E_WALLET = 'e_wallet';

    protected $fillable = [
        'receiving_record_id', 'cooperative_id', 'amount', 'method',
        'reference', 'paid_at', 'recorded_by', 'remarks',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'paid_at' => 'datetime',
    ];

    public function receivingRecord()
    {
        return $this->belongsTo(ReceivingRecord::class);
    }

    public function recorder()
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }
}
