<?php

namespace App\Http\Controllers\System;

use App\Http\Controllers\Controller;
use App\Http\Resources\ApplicantResource;
use App\Http\Resources\SingleApplicantResource;
use App\Models\Base\AttachmentType;
use App\Models\Base\City;
use App\Models\Base\Label;
use App\Models\System\Credential;
use Arr;
use Illuminate\Http\Request;
use App\Models\system\Applicant as ApplicantModel;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;


class ApplicantController extends Controller
{

    /**
     * @param Request $request
     * @return AnonymousResourceCollection
     */
    public function index(Request $request)
    {

        [
            'page'     => $page,
            'per_page' => $per_page
        ] = $request->all();

        return ApplicantResource::collection(
            ApplicantModel::with([
                'credential',
                'address',
                'labels',
                'attachment'
            ])
                          ->paginate(
                              $per_page ?? 10,
                              ['*'],
                              'page',
                              $page ?? 1)
        );

    }


    /**
     * @param ApplicantModel $applicant
     * @return SingleApplicantResource
     */
    public function get(ApplicantModel $applicant)
    {
        $applicant->load([
            'labels',
            'address',
            'attachment',
            'credential'
        ]);

        return new SingleApplicantResource($applicant->find($applicant->id));
    }


    /**
     * @param Request $request
     * @return array
     */
    public function store(Request $request)
    {
        $this->validationRequest($request);

        DB::transaction(function () use ($request) {

            $applicant = ApplicantModel::forceCreate([
                'mobile'        => $request->input('mobile'),
                'national_code' => $request->input('national_code'),
                'issue_no'      => $request->input('issue_no')
            ]);

            $applicant->credential()->createMany(
                $request->collect('credentials')
                        ->map(fn($item) => Arr::only($item, [
                            'title',
                            'username',
                            'password',
                            'two_fa_code'
                        ]))
            );

            $applicant->address()->createMany(
                $request->collect('addresses')
                        ->map(fn($item) => Arr::only($item, [
                            'title',
                            'city_id',
                            'postal_code'
                        ]))
            );

            $tagIds = [];
            $request->collect('labels')->each(function ($item) use (&$tagIds) {
                $tagIds[] = Label::firstOrCreate([
                    'name' => $item
                ])->id;
            });

            $applicant->labels()->sync($tagIds);

            $applicant->attachment()->createMany(
                $request->collect('attachments')
            );

        });

        return [
            'message' => __('messages.store-success')
        ];

    }


    /**
     * @param Request        $request
     * @param ApplicantModel $applicant
     * @return array
     */
    public function update(Request $request, ApplicantModel $applicant)
    {
        $this->validationRequest($request, $applicant);

        DB::transaction(function () use ($request, $applicant) {

            $applicant->update([
                'mobile'        => $request->input('mobile'),
                'national_code' => $request->input('national_code'),
                'issue_no'      => $request->input('issue_no')
            ]);


            $newCredentials     = $request->collect('credentials')->filter(fn($item) => !isset($item['id']));
            $updatedCredentials = $request->collect('credentials')->filter(fn($item) => isset($item['id']));

            $applicant->credential()->whereNotIn('id', $updatedCredentials->pluck('id'))->delete();

            $updatedCredentials->each(fn($item) => $applicant->credential()
                                                             ->where('id', $item['id'])
                                                             ->update(Arr::only($item, [
                                                                 'title',
                                                                 'password',
                                                                 //TODO: username
                                                                 'two_fa_code'
                                                             ]))

            );

            if ($newCredentials->count() > 0) {
                $newCredentials->each(fn($item) => $applicant->credential()
                                                             ->create(Arr::only($item, [
                                                                 'title',
                                                                 'username',
                                                                 'password',
                                                                 'two_fa_code'
                                                             ]))
                );
            }


            $newAddresses     = $request->collect('addresses')->filter(fn($item) => !isset($item['id']));
            $updatedAddresses = $request->collect('addresses')->filter(fn($item) => isset($item['id']));

            $applicant->address()->whereNotIn('id', $updatedAddresses->pluck('id'))->delete();

            $updatedAddresses->each(fn($item) => $applicant->address()
                                                           ->where('id', $item['id'])
                                                           ->update(Arr::only($item, [
                                                               'title',
                                                               'city_id',
                                                               'postal_code'
                                                           ]))

            );

            if ($newAddresses->count() > 0) {
                $newAddresses->each(fn($item) => $applicant->address()
                                                           ->create(Arr::only($item, [
                                                               'title',
                                                               'city_id',
                                                               'postal_code'
                                                           ]))
                );
            }


            $labelIds = [];
            $request->collect('labels')->each(function ($item) use (&$labelIds) {
                $labelIds[] = Label::firstOrCreate([
                    'name' => $item
                ])->id;
            });

            $applicant->labels()->sync($labelIds);


            $newAttachments     = $request->collect('attachments')->filter(fn($item) => !isset($item['id']));
            $updatedAttachments = $request->collect('attachments')->filter(fn($item) => isset($item['id']));

            $applicant->attachment()->whereNotIn('id', $updatedAttachments->pluck('id'))->delete();

            $updatedAttachments->each(fn($item) => $applicant->attachment()
                                                             ->where('id', $item['id'])
                                                             ->update(Arr::only($item, [
                                                                 'attachment_type_id',
                                                                 'file_content'
                                                             ]))
            );

            if ($newAttachments->count() > 0) {
                $newAttachments->each(fn($item) => $applicant->attachment()
                                                             ->forceCreate(Arr::only($item, [
                                                                 'attachment_type_id',
                                                                 'file_content'
                                                             ]))
                );
            }

        });

        return [
            'message' => __('messages.update-success')
        ];

    }


    /**
     * @param ApplicantModel $applicant
     * @return array
     */
    public function destroy(ApplicantModel $applicant)
    {
        DB::transaction(function () use ($applicant) {

            $applicant->address()->delete();
            $applicant->credential()->delete();
            $applicant->attachment()->delete();
            $applicant->labels()->sync([]);
            $applicant->delete();

        });

        return [
            'message' => __('messages.destroy-success')
        ];

    }


    /**
     * @param Request $request
     * @param null    $applicant
     * @return void
     */
    private function validationRequest(Request $request, $applicant = null)
    {
        $this->validate($request, [
            'mobile'                           => ['required', 'ir_mobile'],
            'national_code'                    => ['required', 'ir_national_code'],
            'issue_no'                         => ['nullable', 'digits_between:1,7'],
            'credentials.*'                    => ['required', 'array'],
            'credentials.*.title'              => ['nullable', 'persian_alpha', 'max:50'],
            'credentials.*.username'           => ['max:50', Rule::requiredIf(fn() => empty($applicant) && is_null($applicant)), Rule::unique(Credential::class, 'username')],
            'credentials.*.password'           => ['required', 'max:50', 'min:5'],
            'credentials.*.two_fa_code'        => ['nullable', 'max:50'],
            'address.*'                        => ['required', 'array'],
            'address.*.title'                  => ['required', 'persian_alpha', 'max:50'],
            'address.*.city_id'                => ['nullable', Rule::exists(City::class)],
            'address.*.postal_code'            => ['nullable', 'max:10'],
            'labels'                           => ['required', 'array'],
            'attachments.*'                    => ['required', 'array'],
            'attachments.*.attachment_type_id' => ['required', Rule::exists(AttachmentType::class, 'id')],
            'attachments.*.file_content'       => ['nullable', /*'base64image'*/],  //TODO: crazybooot/base64-validation
        ]);
    }


}
