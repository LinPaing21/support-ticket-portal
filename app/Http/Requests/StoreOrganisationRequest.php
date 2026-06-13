<?php

namespace App\Http\Requests;

use App\Models\Organisation;
use Illuminate\Foundation\Http\FormRequest;

class StoreOrganisationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', Organisation::class);
    }

    /**
     * @return array<string, array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'joined_at' => ['required', 'date'],
        ];
    }
}
