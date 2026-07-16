<?php

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AdminUpdateUserRequest extends FormRequest
{
    private ?User $targetUser = null;

    public function authorize(): bool
    {
        return $this->user() && $this->user()->role === 'admin';
    }

    public function rules(): array
    {
        $userId = $this->route('user')?->id ?? 0;

        $rules = [
            'name'     => ['required', 'string', 'max:255', 'unique:users,name,' . $userId],
            'email'    => ['required', 'string', 'email', 'max:255', 'unique:users,email,' . $userId],
            'password' => ['nullable', 'string', 'min:8', 'regex:/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[^\w\s])[A-Za-z\d\W_]{8,}$/'],
            'role'     => ['required', 'in:admin,farmer,logistics_partner,driver,buyer'],
            'status'   => ['required', 'in:active,inactive'],
        ];

        return array_merge($rules, $this->roleSpecificRules($userId));
    }

    private function roleSpecificRules(int $userId): array
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
            $rules['license_number'] = ['required', 'string', 'max:50'];
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
            'name.required'   => 'Please enter a name.',
            'email.required'  => 'Please enter an email address.',
            'email.email'     => 'Please enter a valid email address.',
            'role.required'   => 'Please select a role.',
            'status.required' => 'Please select a status.',
            'phone.required'  => 'Phone number is required for this role.',
        ];
    }
}
