<?php

namespace App\Http\Requests;

use App\Enums\UserRole;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;

class UpdateUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('user'));
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $user = $this->route('user');

        return [
            'organisation_id' => [
                'sometimes',
                Rule::requiredIf(fn () => in_array($this->input('role'), [
                    UserRole::CLIENT->value,
                    UserRole::ORGANISATION_OWNER->value,
                ])),
                'nullable', 'integer', 'exists:organisations,id',
            ],
            'name' => ['sometimes', 'string', 'max:255'],
            'email' => ['sometimes', 'string', 'email', 'max:255', 'unique:users,email,'.$user->id],
            'role' => ['sometimes', new Enum(UserRole::class)],
        ];
    }
}
