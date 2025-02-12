<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Attendance extends Model
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

    // 休憩とのリレーション (1対多)
    public function rests()
    {
        return $this->hasMany(Rest::class);
    }

    // 申請とのリレーション (1対多)
    public function applications()
    {
        return $this->hasMany(Application::class);
    }
}
