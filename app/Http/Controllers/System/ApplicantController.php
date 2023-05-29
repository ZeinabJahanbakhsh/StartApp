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
use App\Models\system\Applicant;
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
            Applicant::query()
                          ->with([
                              'labels',
                              'addresses'    => ['city'],
                              'attachments' => ['attachmentType'],
                              'credentials'
                          ])
                          ->paginate(
                              $per_page ?? 10,
                              ['*'],
                              'page',
                              $page ?? 1)
        );

    }

    /**
     * @param Applicant $applicant
     * @return ApplicantResource
     */
    public function get(Applicant $applicant)
    {
        $applicant->load([
            'labels',
            'addresses'    => ['city'],
            'attachments' => ['attachmentType'],
            'credentials'
        ]);

        return new ApplicantResource($applicant);
        //return new SingleApplicantResource($applicant->find($applicant->id));
    }

    /**
     * @param Request $request
     * @return array
     */
    public function store(Request $request)
    {
        $this->validationRequest($request);

        DB::transaction(function () use ($request) {

            $applicant = Applicant::forceCreate([
                'mobile'        => $request->input('mobile'),
                'national_code' => $request->input('national_code'),
                'issue_no'      => $request->input('issue_no')
            ]);

            $applicant->credentials()->createMany(
                $request->collect('credentials')
                        ->map(fn($item) => Arr::only($item, [
                            'title',
                            'username',
                            'password',
                            'two_fa_code'
                        ]))
            );

            $applicant->addresses()->createMany(
                $request->collect('addresses')
                        ->map(fn($item) => Arr::only($item, [
                            'title',
                            'city_id',
                            'postal_code'
                        ]))
            );

            $labelIds = [];
            $request->collect('labels')->each(function ($item) use (&$labelIds) {
                $labelIds[] = Label::firstOrCreate(['name' => $item])->id;
            });

            $applicant->labels()->sync($labelIds);

            $applicant->attachments()->createMany(
                $request->collect('attachments')->toArray()
            );

        });

        return [
            'message' => __('messages.store-success')
        ];

    }

    /**
     * @param Request        $request
     * @param Applicant $applicant
     * @return array
     */
    public function update(Request $request, Applicant $applicant)
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

            $applicant->credentials()->whereNotIn('id', $updatedCredentials->pluck('id')/*->toArray()*/)->delete();

            $updatedCredentials->each(fn(array $item) => $applicant->credential()
                                                                   ->where('id', $item['id'])
                                                                   ->update(Arr::only($item, [
                                                                       'title',
                                                                       'password',
                                                                       'username',
                                                                       'two_fa_code'
                                                                   ]))

            );

            if ($newCredentials->count() > 0) {
                //createMany
                $newCredentials->each(fn($item) => $applicant->credentials()
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

            $applicant->addresses()->whereNotIn('id', $updatedAddresses->pluck('id')/*->toArray()*/)->delete();

            $updatedAddresses->each(fn($item) => $applicant->addresses()
                                                           ->where('id', $item['id'])
                                                           ->update(Arr::only($item, [
                                                               'title',
                                                               'city_id',
                                                               'postal_code'
                                                           ]))

            );

            if ($newAddresses->count() > 0) {
                //createMany
                $newAddresses->each(fn(array $item) => $applicant->addresses()
                                                                 ->create(Arr::only($item, [
                                                                     'title',
                                                                     'city_id',
                                                                     'postal_code'
                                                                 ]))
                );
            }

            $labelIds = [];
            $request->collect('labels')->each(function ($item) use (&$labelIds) {
                $labelIds[] = Label::firstOrCreate(['name' => $item])->id;
            });

            $applicant->labels()->sync($labelIds);

            $newAttachments     = $request->collect('attachments')->filter(fn($item) => !isset($item['id']));
            $updatedAttachments = $request->collect('attachments')->filter(fn($item) => isset($item['id']));

            $applicant->attachments()->whereNotIn('id', $updatedAttachments->pluck('id')/*->toArray()*/)->delete();

            $updatedAttachments->each(fn($item) => $applicant->attachments()
                                                             ->where('id', $item['id'])
                                                             ->update(Arr::only($item, [
                                                                 'attachment_type_id',
                                                                 'file_content'
                                                             ]))
            );

            if ($newAttachments->count() > 0) {
                $newAttachments->each(fn(array $item) => $applicant->attachments()
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
     * @param Applicant $applicant
     * @return array
     */
    public function destroy(Applicant $applicant)
    {
        DB::transaction(function () use ($applicant) {

            $applicant->addresses()->delete();
            $applicant->credentials()->delete();
            $applicant->attachments()->delete();
            $applicant->labels()->sync([]); //score=20
            $applicant->delete();

        });

        return [
            'message' => __('messages.destroy-success')
        ];

    }

    /**
     * @param Request             $request
     * @param Applicant|null $applicant
     * @return void
     */
    private function validationRequest(Request $request, Applicant $applicant = null): void
    {
        $this->validate($request, [
            'mobile'                           => ['required', 'ir_mobile'],
            'national_code'                    => ['required', 'ir_national_code'],
            'issue_no'                         => ['nullable', 'string', 'min:1', 'max:10'],
            'credentials.*'                    => ['required', 'array'],
            'credentials.*.title'              => ['nullable', 'persian_alpha', 'max:50'],
            'credentials.*.username'           => ['max:50', Rule::requiredIf(fn() => empty($applicant)), Rule::unique(Credential::class,
                'username')->ignore($applicant)],
            'credentials.*.password'           => ['required', 'max:50', 'min:5'],
            'credentials.*.two_fa_code'        => ['nullable', 'max:50'],
            'address.*'                        => ['required', 'array'],
            'address.*.title'                  => ['required', 'persian_alpha', 'max:50'],
            'address.*.city_id'                => ['nullable', Rule::exists(City::class)],
            'address.*.postal_code'            => ['nullable', 'ir_postal_code'],
            'labels'                           => ['required', 'array'],
            'labels.*'                         => ['required', 'string', 'min:2', 'max:30'],
            'attachments.*'                    => ['required', 'array'],
            'attachments.*.attachment_type_id' => ['required', Rule::exists(AttachmentType::class, 'id')],
            'attachments.*.file_content'       => ['nullable', /*'base64image'*/],  //TODO: crazybooot/base64-validation
        ]);
    }


}
