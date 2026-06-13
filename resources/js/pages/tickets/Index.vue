<script setup lang="ts">
import { ref, watch } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { ArrowDown, ArrowUp, ArrowUpDown } from '@lucide/vue';
import TicketController from '@/actions/App/Http/Controllers/TicketController';
import Heading from '@/components/Heading.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { index } from '@/routes/tickets';

type Organisation = { id: number; name: string };
type User = { id: number; name: string };
type DeadlineStatus = 'on-track' | 'due-soon' | 'overdue';
type Ticket = {
    id: number;
    title: string;
    status: string;
    priority: string;
    sla_deadline: string;
    deadline_status: DeadlineStatus;
    created_at: string;
    organisation: Organisation | null;
    assigned_agent: User | null;
};
type PaginationLink = { url: string | null; label: string; active: boolean };
type Filters = { sort: string; direction: 'asc' | 'desc'; search: string };
type Props = {
    tickets: { data: Ticket[]; links: PaginationLink[] };
    sortable: string[];
    filters: Filters;
};

const props = defineProps<Props>();

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Tickets', href: index() }],
    },
});

const search = ref(props.filters.search);

let searchTimeout: ReturnType<typeof setTimeout>;
watch(search, (value) => {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
        router.get(index(), { search: value, sort: props.filters.sort, direction: props.filters.direction }, { preserveState: true, preserveScroll: true });
    }, 300);
});

function sortBy(column: string) {
    if (!props.sortable.includes(column)) { return; }
    const direction = props.filters.sort === column && props.filters.direction === 'asc' ? 'desc' : 'asc';
    router.get(index(), { search: search.value, sort: column, direction }, { preserveState: true, preserveScroll: true });
}

function sortIcon(column: string) {
    if (props.filters.sort !== column) { return ArrowUpDown; }
    return props.filters.direction === 'asc' ? ArrowUp : ArrowDown;
}

function destroy(ticket: Ticket) {
    if (confirm(`Delete "${ticket.title}"?`)) {
        router.delete(TicketController.destroy.url(ticket.id));
    }
}

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

const columns: { key: string; label: string }[] = [
    { key: 'title', label: 'Title' },
    { key: 'status', label: 'Status' },
    { key: 'priority', label: 'Priority' },
    { key: 'organisation', label: 'Organisation' },
    { key: 'assigned_agent', label: 'Assigned To' },
    { key: 'sla_deadline', label: 'SLA Deadline' },
    { key: 'deadline_status', label: 'Deadline' },
    { key: 'created_at', label: 'Created' },
];
</script>

<template>
    <Head title="Tickets" />

    <div class="flex h-full flex-1 flex-col gap-4 p-4">
        <div class="flex items-center justify-between">
            <Heading title="Tickets" description="Manage support tickets" />
            <Button as-child>
                <Link :href="TicketController.create.url()">New ticket</Link>
            </Button>
        </div>

        <div class="flex items-center gap-2">
            <Input v-model="search" placeholder="Search by title or description…" class="max-w-sm" />
        </div>

        <div class="rounded-xl border">
            <Table>
                <TableHeader>
                    <TableRow>
                        <TableHead v-for="col in columns" :key="col.key">
                            <button
                                v-if="sortable.includes(col.key)"
                                class="flex items-center gap-1 hover:text-foreground"
                                @click="sortBy(col.key)"
                            >
                                {{ col.label }}
                                <component :is="sortIcon(col.key)" class="size-4" />
                            </button>
                            <span v-else>{{ col.label }}</span>
                        </TableHead>
                        <TableHead class="w-24" />
                    </TableRow>
                </TableHeader>
                <TableBody>
                    <TableRow v-for="ticket in tickets.data" :key="ticket.id">
                        <TableCell class="font-medium">{{ ticket.title }}</TableCell>
                        <TableCell>
                            <span :class="['rounded px-1.5 py-0.5 text-xs font-medium capitalize', statusClass[ticket.status]]">
                                {{ ticket.status.replace('_', ' ') }}
                            </span>
                        </TableCell>
                        <TableCell>
                            <span :class="['rounded px-1.5 py-0.5 text-xs font-medium capitalize', priorityClass[ticket.priority]]">
                                {{ ticket.priority }}
                            </span>
                        </TableCell>
                        <TableCell class="text-muted-foreground">{{ ticket.organisation?.name ?? '—' }}</TableCell>
                        <TableCell class="text-muted-foreground">{{ ticket.assigned_agent?.name ?? '—' }}</TableCell>
                        <TableCell class="text-muted-foreground text-sm">{{ new Date(ticket.sla_deadline).toLocaleString() }}</TableCell>
                        <TableCell>
                            <span :class="['rounded px-1.5 py-0.5 text-xs font-medium', deadlineStatusConfig[ticket.deadline_status].class]">
                                {{ deadlineStatusConfig[ticket.deadline_status].label }}
                            </span>
                        </TableCell>
                        <TableCell class="text-muted-foreground text-sm">{{ new Date(ticket.created_at).toLocaleDateString() }}</TableCell>
                        <TableCell>
                            <Button variant="outline" size="sm" as-child>
                                <Link :href="TicketController.show.url(ticket.id)">View</Link>
                            </Button>
                        </TableCell>
                    </TableRow>
                </TableBody>
            </Table>
        </div>

        <div class="flex gap-1">
            <template v-for="link in tickets.links" :key="link.label">
                <Button v-if="link.url" variant="outline" size="sm" :disabled="link.active" as-child>
                    <Link :href="link.url" v-html="link.label" />
                </Button>
                <Button v-else variant="ghost" size="sm" disabled v-html="link.label" />
            </template>
        </div>
    </div>
</template>
