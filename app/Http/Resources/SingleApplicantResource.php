<?php

namespace App\Http\Resources;

use App\Models\Base\Label;
use App\Models\System\Applicant;
use App\Models\System\Attachment;
use App\Models\System\Credential;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use JsonSerializable;


/**
 * @mixin Applicant
 */

class SingleApplicantResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param Request $request
     * @return array|Arrayable|JsonSerializable
     */
    public function toArray($request): array|JsonSerializable|Arrayable
    {

        return [

            'id'            => $this->id,
            'mobile'        => $this->mobile,
            'national_code' => $this->national_code,
            'issue_no'      => $this->issue_no,
            'credentials'   => $this->credential->map(fn(Credential $credential) => [
                'id'          => $credential?->id,
                'title'       => $credential?->title,
                'username'    => $credential?->username,
                //'password'    => $this->credential?->password,  //TODO: Why should I show password?
                'two_fa_code' => $credential?->two_fa_code
            ]),
            'address'       => $this->address->map(fn($address) => [
                'id'          => $address?->id,
                'title'       => $address?->title,
                'city_id'     => $address?->city_id,
                'postal_code' => $address?->postal_code,
            ]),
            'labels'        => $this->labels->map(fn(Label $label) => $label['name']),
            'attachments'   => $this->attachment->map(fn(Attachment $attachment) => [
                'id'                 => $attachment?->id,
                'attachment_type_id' => $attachment?->attachment_type_id,
                'file_content'       => $attachment?->file_content,
            ]),

        ];

    }
}
