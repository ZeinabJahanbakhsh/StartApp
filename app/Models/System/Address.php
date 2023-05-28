<?php

namespace App\Models\System;

use App\Models\Base\City;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Address extends Model
{
    protected $fillable = [
        'title',
        'city_id',
        'postal_code'
    ];

    protected $casts = [
        'city_id'      => 'integer',
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

    public function city(): BelongsTo
    {
        return $this->belongsTo(City::class);
    }


}
