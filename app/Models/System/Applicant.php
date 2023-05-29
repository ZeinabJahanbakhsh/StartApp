<?php

namespace App\Models\System;

use App\Models\Base\Label;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Applicant extends Model
{
    protected $fillable = [
        'mobile',
        'national_code',
        'issue_no'
    ];

    protected $casts = [
        'issue_no' => 'integer'
    ];

   /*
   |--------------------------------------------------------------------------
   |                                 Relations
   |--------------------------------------------------------------------------
   */

    public function credentials(): HasMany
    {
        return $this->hasMany(Credential::class);
    }

    public function addresses(): HasMany
    {
        return $this->hasMany(Address::class);
    }

    public function labels(): BelongsToMany
    {
        return $this->belongsToMany(Label::class/*, 'applicant_label'*/)->withTimestamps();
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(Attachment::class);
    }
}
