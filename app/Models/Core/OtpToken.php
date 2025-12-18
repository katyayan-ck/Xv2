<?php

namespace App\Models\Core;

use App\Models\BaseModel;

class OtpToken extends BaseModel
{
    protected $fillable = [
        'user_id',
        'otp_hash',
        'mobile',
        'expires_at',
    ];
}
