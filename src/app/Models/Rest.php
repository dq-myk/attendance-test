<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Rest extends Model
{
    use HasFactory;

    protected $guarded = [
        'id',
    ];

    // 勤怠とのリレーション (多対1)
    public function attendance()
    {
        return $this->belongsTo(Attendance::class);
    }

}
