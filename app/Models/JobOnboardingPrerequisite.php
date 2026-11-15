<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class JobOnboardingPrerequisite extends Model
{
    protected $fillable = ['application_id', 'documents', 'contract_path', 'contract_signed_at', 'contract_confirmed_by'];

    protected $casts = ['documents' => 'array', 'contract_signed_at' => 'datetime'];
}
