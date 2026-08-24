<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RfidChangeLog extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = [
        'cardholder_type',
        'cardholder_id',
        'cardholder_identifier',
        'cardholder_name',
        'old_rfid_code',
        'new_rfid_code',
        'changed_by',
    ];

    public function changedBy()
    {
        return $this->belongsTo(User::class, 'changed_by');
    }
}
