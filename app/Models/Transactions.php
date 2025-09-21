<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Transactions extends Model
{
    protected $fillable = [
        'id',
        'wallet_id',
        'amount',
        'type',
        'description',
        'status',
        'created_at',
        'updated_at',
    ];

    // Relationship: a wallet belongs to a user
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
