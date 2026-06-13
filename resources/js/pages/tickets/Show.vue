<script setup lang="ts">
import { Head, Link, router, setLayoutProps } from '@inertiajs/vue3';
import TicketController from '@/actions/App/Http/Controllers/TicketController';
import Heading from '@/components/Heading.vue';
import { Button } from '@/components/ui/button';
import { index } from '@/routes/tickets';

type Organisation = { id: number; name: string };
type User = { id: number; name: string };
type DeadlineStatus = 'on-track' | 'due-soon' | 'overdue';
type Ticket = {
    id: number;
    title: string;
    description: string;
    status: string;
    priority: string;
    sla_deadline: string;
    deadline_status: DeadlineStatus;
    created_at: string;
    organisation: Organisation | null;
    user: User | null;
    assigned_agent: User | null;
};
type Props = {
    ticket: Ticket;
    can: { edit: boolean; delete: boolean };
};

const props = defineProps<Props>();

setLayoutProps({
    breadcrumbs: [
        { title: 'Tickets', href: index() },
        { title: props.ticket.title, href: TicketController.show.url(props.ticket.id) },
    ],
});

const priorityClass: Record<string, string> = {
    urgent: 'bg-red-100 text-red-700',
    high: 'bg-orange-100 text-orange-700',
    medium: 'bg-yellow-100 text-yellow-700',
    low: 'bg-green-100 text-green-700',
};

const statusClass: Record<string, string> = {
    open: 'bg-blue-100 text-blue-700',
    in_progress: 'bg-purple-100 text-purple-700',
    resolved: 'bg-green-100 text-green-700',
    closed: 'bg-gray-100 text-gray-600',
};

const deadlineStatusConfig: Record<DeadlineStatus, { label: string; class: string }> = {
    'on-track': { label: 'On Track', class: 'bg-green-100 text-green-700' },
    'due-soon': { label: 'Due Soon', class: 'bg-yellow-100 text-yellow-700' },
    overdue: { label: 'Overdue', class: 'bg-red-100 text-red-700' },
};

function destroy() {
    if (confirm(`Delete "${props.ticket.title}"?`)) {
        router.delete(TicketController.destroy.url(props.ticket.id));
    }
}
</script>

<template>
    <Head :title="ticket.title" />

    <div class="flex h-full flex-1 flex-col p-4">
        <div class="max-w-2xl space-y-6">
            <div class="flex items-start justify-between">
                <Heading :title="ticket.title" />
                <div class="flex gap-2">
                    <Button v-if="can.edit" variant="outline" as-child>
                        <Link :href="TicketController.edit.url(ticket.id)">Edit</Link>
                    </Button>
                    <Button v-if="can.delete" variant="destructive" @click="destroy">Delete</Button>
                </div>
            </div>

            <div class="flex items-center gap-2">
                <span :class="['rounded px-2 py-0.5 text-xs font-medium capitalize', statusClass[ticket.status]]">
                    Status: {{ ticket.status.replace('_', ' ') }}
                </span>
                <span :class="['rounded px-2 py-0.5 text-xs font-medium capitalize', priorityClass[ticket.priority]]">
                    Priority: {{ ticket.priority }}
                </span>
                <span :class="['rounded px-2 py-0.5 text-xs font-medium', deadlineStatusConfig[ticket.deadline_status].class]">
                    Deadline: {{ deadlineStatusConfig[ticket.deadline_status].label }}
                </span>
            </div>

            <p class="text-sm leading-relaxed">{{ ticket.description }}</p>

            <dl class="space-y-3 text-sm">
                <div class="flex gap-4">
                    <dt class="w-36 shrink-0 text-muted-foreground">Organisation</dt>
                    <dd>{{ ticket.organisation?.name ?? '—' }}</dd>
                </div>
                <div class="flex gap-4">
                    <dt class="w-36 shrink-0 text-muted-foreground">Created by</dt>
                    <dd>{{ ticket.user?.name ?? '—' }}</dd>
                </div>
                <div class="flex gap-4">
                    <dt class="w-36 shrink-0 text-muted-foreground">Assigned to</dt>
                    <dd>{{ ticket.assigned_agent?.name ?? 'Unassigned' }}</dd>
                </div>
                <div class="flex gap-4">
                    <dt class="w-36 shrink-0 text-muted-foreground">SLA Deadline</dt>
                    <dd>{{ new Date(ticket.sla_deadline).toLocaleString() }}</dd>
                </div>
                <div class="flex gap-4">
                    <dt class="w-36 shrink-0 text-muted-foreground">Created</dt>
                    <dd>{{ new Date(ticket.created_at).toLocaleDateString() }}</dd>
                </div>
            </dl>
        </div>
    </div>
</template>
