<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Application extends Model
{
    use HasFactory;

    protected $guarded = [
        'id',
    ];

    // ユーザーとのリレーション (多対1)
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // 承認とのリレーション (1対1)
    public function approval()
    {
        return $this->hasOne(Approvals::class);
    }

    // 勤怠とのリレーション (多対1)
    public function attendance()
    {
        return $this->belongsTo(Attendance::class);
    }
}
