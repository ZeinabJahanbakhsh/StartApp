<?php

namespace App\Http\Resources;

use App\Models\Base\Label;
use App\Models\System\Address;
use App\Models\System\Attachment;
use App\Models\System\Credential;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Models\system\Applicant;
use JsonSerializable;


/***
 * @mixin Applicant
 */
class ApplicantResource extends JsonResource
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

            'credentials' => $this->credentials->map(fn(Credential $credential) => [
                'id'          => $credential->id,
                'title'       => $credential->title,
                'username'    => $credential->username,
                //'password'    => $this->credential->password,  //** Why I should show password
                'two_fa_code' => $credential->two_fa_code
            ]),

            'address' => $this->addresses->map(fn(Address $address) => [
                'id'          => $address->id,
                'title'       => $address->title,
                'city_id'     => $address->city_id,
                'postal_code' => $address->postal_code,
            ]),

            //'labels' => $this->labels->map(fn(Label $label) => $label['name']),
            'labels' => $this->labels->pluck('name'),

            'attachments' => $this->attachments->map(fn(Attachment $attachment) => [
                'id'                 => $attachment->id,
                'attachment_type_id' => $attachment->attachment_type_id,
                'file_content'       => $attachment->file_content,
            ]),

        ];

    }

}
