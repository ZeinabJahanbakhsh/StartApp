<?php

namespace App\Models\System;

use App\Models\Base\AttachmentType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Attachment extends Model
{
    protected $fillable = [
        'file_content',
        'attachment_type_id'
    ];

    protected $casts = [
        'attachment_type_id' => 'integer',
        'applicant_id'       => 'integer'
    ];


    /*
    |--------------------------------------------------------------------------
    |                                 Relations
    |--------------------------------------------------------------------------
    */

    public function attachmentType(): BelongsTo
    {
        return $this->belongsTo(AttachmentType::class);
    }

    public function applicant(): BelongsTo
    {
        return $this->belongsTo(Applicant::class);
    }
}
