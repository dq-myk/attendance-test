<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Approval extends Model
{
    use HasFactory;

    protected $guarded = [
        'id',
    ];

    // 申請とのリレーション (1対1)
    public function request()
    {
        return $this->belongsTo(Requests::class);
    }
}
