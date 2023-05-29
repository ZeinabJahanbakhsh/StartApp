<?php

namespace App\Models\System;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Credential extends Model
{
    protected $fillable = [
        'username',
        'title',
        'password',
        'two_fa_code'
    ];

    protected $casts = [
        'applicant_id' => 'integer'
    ];

   /*
   |--------------------------------------------------------------------------
   |                                 Relations
   |--------------------------------------------------------------------------
   */

    public function applicant(): BelongsTo
    {
        return $this->belongsTo(Applicant::class);
    }
}
