<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

/**
 * A wholesale application, not a consumer sign-up. It asks for the things a
 * credit and tax decision needs, and it creates an account that cannot yet
 * see a price — approval is a human step in the back office.
 */
class RegisterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            // The business
            'company_name' => ['required', 'string', 'max:160'],
            'trading_name' => ['nullable', 'string', 'max:160'],
            'website' => ['nullable', 'string', 'max:160'],
            'business_type' => ['required', 'string', 'max:64'],
            'ein' => ['nullable', 'string', 'max:32'],
            'resale_certificate' => ['nullable', 'string', 'max:64'],

            // Where it bills
            'billing_street' => ['required', 'string', 'max:160'],
            'billing_city' => ['required', 'string', 'max:80'],
            'billing_state' => ['required', 'string', 'max:32'],
            'billing_postcode' => ['required', 'string', 'max:16'],

            // The first login, which becomes the account admin
            'name' => ['required', 'string', 'max:120'],
            'job_title' => ['nullable', 'string', 'max:80'],
            'phone' => ['required', 'string', 'max:40'],
            'email' => ['required', 'string', 'email', 'max:160', Rule::unique('users', 'email')],
            'password' => ['required', 'confirmed', Password::defaults()->min(10)],

            'terms' => ['accepted'],
        ];
    }

    public function messages(): array
    {
        return [
            'terms.accepted' => 'Please confirm you have read the wholesale terms.',
            'email.unique' => 'There is already a login on that address. Sign in, or ask your account admin to add you.',
        ];
    }

    public function attributes(): array
    {
        return [
            'ein' => 'EIN',
            'billing_street' => 'street',
            'billing_city' => 'city',
            'billing_state' => 'state',
            'billing_postcode' => 'ZIP code',
        ];
    }
}
