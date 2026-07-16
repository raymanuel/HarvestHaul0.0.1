<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AdminStoreUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() && $this->user()->role === 'admin';
    }

    public function rules(): array
    {
        $rules = [
            'name'     => ['required', 'string', 'max:255', 'unique:users,name'],
            'email'    => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'regex:/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[^\w\s])[A-Za-z\d\W_]{8,}$/'],
            'role'     => ['required', 'in:admin,farmer,logistics_partner,driver,buyer'],
            'status'   => ['required', 'in:active,inactive'],
        ];

        return array_merge($rules, $this->roleSpecificRules());
    }

    private function roleSpecificRules(): array
    {
        $role = $this->input('role');

        $rules = [];

        if ($role === 'farmer') {
            $rules['phone'] = ['required', 'string', 'max:20'];
            $rules['farm_location'] = ['required', 'string', 'max:255'];
            $rules['affiliation_type'] = ['required', 'in:cooperative,independent'];
            $rules['cooperative_id'] = ['required_if:affiliation_type,cooperative', 'nullable', Rule::exists('logistics_profiles', 'id')->where('logistics_type', 'cooperative')];
        } elseif ($role === 'logistics_partner') {
            $rules['phone'] = ['required', 'string', 'max:20'];
            $rules['company_name'] = ['required', 'string', 'max:255'];
            $rules['business_permit_no'] = ['required', 'string', 'max:255'];
            $rules['logistics_type'] = ['required', 'in:cooperative,company'];
            $rules['cda_registration_no'] = ['required_if:logistics_type,cooperative', 'nullable', 'string', 'max:255'];
        } elseif ($role === 'driver') {
            $rules['phone'] = ['required', 'string', 'max:20'];
            $rules['license_number'] = ['required', 'string', 'max:50', 'unique:driver_profiles,license_no'];
            $rules['partner_id'] = ['required', 'exists:logistics_profiles,id'];
        } elseif ($role === 'buyer') {
            $rules['phone'] = ['required', 'string', 'max:20'];
            $rules['affiliation_type'] = ['required', 'in:cooperative,independent'];
            $rules['cooperative_id'] = ['required_if:affiliation_type,cooperative', 'nullable', Rule::exists('logistics_profiles', 'id')->where('logistics_type', 'cooperative')];
        }

        return $rules;
    }

    public function messages(): array
    {
        return [
            'name.required'              => 'Please enter a name.',
            'name.unique'                => 'This name is already taken.',
            'email.required'             => 'Please enter an email address.',
            'email.email'                => 'Please enter a valid email address.',
            'email.unique'               => 'This email is already registered.',
            'password.required'          => 'Please enter a password.',
            'password.min'               => 'Password must be at least 8 characters.',
            'password.regex'             => 'Password must include uppercase, lowercase, number, and special character.',
            'role.required'              => 'Please select a role.',
            'role.in'                    => 'Invalid role.',
            'status.required'            => 'Please select a status.',
            'phone.required'             => 'Phone number is required for this role.',
        ];
    }
}
