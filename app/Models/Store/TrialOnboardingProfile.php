<?php

namespace App\Models\Store;

use App\Models\Core\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TrialOnboardingProfile extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'plan',
        'employee_range',
        'branch_range',
        'modules',
        'primary_goal',
        'first_team',
        'completed_at',
    ];

    protected $casts = [
        'modules' => 'array',
        'completed_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
