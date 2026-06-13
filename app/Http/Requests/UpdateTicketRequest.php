<?php

namespace App\Http\Requests;

use App\Enums\TicketPriority;
use App\Enums\TicketStatus;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;

class UpdateTicketRequest extends FormRequest
{
    public function authorize(): bool
    {
        $ticket = $this->route('ticket');

        return $this->user()->can('update', $ticket) || $this->user()->can('updateByAgent', $ticket);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $isAgent = $this->user()->can('updateByAgent', $this->route('ticket'));

        return [
            'title' => [Rule::prohibitedIf($isAgent), 'sometimes', 'string', 'max:255'],
            'description' => [Rule::prohibitedIf($isAgent), 'sometimes', 'string'],
            'status' => ['sometimes', new Enum(TicketStatus::class)],
            'priority' => ['sometimes', new Enum(TicketPriority::class)],
            'assigned_agent_id' => ['sometimes', 'nullable', 'integer', 'exists:users,id'],
        ];
    }
}
