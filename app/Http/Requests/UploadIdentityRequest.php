<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UploadIdentityRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'id_photo' => ['required', 'image', 'max:5120'],
            'selfie'   => ['required', 'image', 'max:5120'],
        ];
    }

    public function messages(): array
    {
        return [
            'id_photo.required' => 'Please upload a photo of your valid ID.',
            'id_photo.image'    => 'The ID file must be an image.',
            'id_photo.max'      => 'ID photo must not exceed 5MB.',
            'selfie.required'   => 'Please upload a selfie photo.',
            'selfie.image'      => 'The selfie file must be an image.',
            'selfie.max'        => 'Selfie must not exceed 5MB.',
        ];
    }
}
