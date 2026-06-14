<x-mail::message>
# New Support Ticket

A new ticket has been submitted and requires your attention.

**Title:** {{ $ticket->title }}
**Organisation:** {{ $ticket->organisation?->name ?? '—' }}
**Priority:** {{ ucwords($ticket->priority->value) }}
**Status:** {{ ucwords(str_replace('_', ' ', $ticket->status->value)) }}
**Submitted by:** {{ $ticket->user?->name ?? '—' }}
**SLA Deadline:** {{ $ticket->sla_deadline_formatted }}

{{ $ticket->description }}

<x-mail::button :url="url('/tickets/' . $ticket->id)">
View Ticket
</x-mail::button>

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
