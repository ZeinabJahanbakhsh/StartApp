<?php

namespace App\Models\System;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use App\Models\Base\Label;

class Applicant extends Model
{
    protected $fillable = [
        'mobile',
        'national_code',
    ];

    protected $casts = [
        'issue_no' => 'integer'
    ];


    /*
   |--------------------------------------------------------------------------
   |                                 Relations
   |--------------------------------------------------------------------------
   */

    public function credential(): HasMany
    {
        return $this->hasMany(Credential::class);
    }

    public function address(): HasMany
    {
        return $this->hasMany(Address::class);
    }

    public function labels(): BelongsToMany
    {
        return $this->belongsToMany(Label::class, 'applicant_label')->withTimestamps();
    }

    public function attachment(): HasMany
    {
        return $this->hasMany(Attachment::class);
    }



}
