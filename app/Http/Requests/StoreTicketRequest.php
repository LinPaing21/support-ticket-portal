<?php

namespace App\Http\Requests;

use App\Enums\TicketPriority;
use App\Enums\UserRole;
use App\Models\Ticket;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;

class StoreTicketRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', Ticket::class);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string'],
            'priority' => ['required', new Enum(TicketPriority::class)],
            'organisation_id' => [
                Rule::requiredIf(fn () => $this->user()->isStaff),
                'nullable', 'integer', 'exists:organisations,id',
            ],
            'assigned_agent_id' => ['nullable', 'integer', 'exists:users,id'],
        ];
    }
}
