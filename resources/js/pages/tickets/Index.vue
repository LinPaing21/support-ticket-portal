<script setup lang="ts">
import { computed, ref, watch } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { ArrowDown, ArrowUp, ArrowUpDown } from '@lucide/vue';
import TicketController from '@/actions/App/Http/Controllers/TicketController';
import ComboboxSelect from '@/components/ComboboxSelect.vue';
import Heading from '@/components/Heading.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { index } from '@/routes/tickets';

type Organisation = { id: number; name: string };
type User = { id: number; name: string };
type DeadlineStatus = 'on-track' | 'due-soon' | 'overdue' | 'completed';
type Option = { value: string; label: string };
type Ticket = {
    id: number;
    title: string;
    status: string;
    priority: string;
    sla_deadline_formatted: string;
    deadline_status: DeadlineStatus;
    created_at_formatted: string;
    organisation: Organisation | null;
    assigned_agent: User | null;
};
type PaginationLink = { url: string | null; label: string; active: boolean };
type Filters = {
    sort: string;
    direction: 'asc' | 'desc';
    search: string;
    deadline_status: string;
    status: string;
    priority: string;
    organisation_id: string | number;
};
type Props = {
    tickets: { data: Ticket[]; links: PaginationLink[] };
    sortable: string[];
    deadlineStatusOptions: Option[];
    statusOptions: Option[];
    priorityOptions: Option[];
    organisations: Organisation[];
    filters: Filters;
};

const props = defineProps<Props>();

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Tickets', href: index() }],
    },
});

const search = ref(props.filters.search);
const deadlineStatus = ref<string | null>(props.filters.deadline_status || null);
const status = ref<string | null>(props.filters.status || null);
const priority = ref<string | null>(props.filters.priority || null);
const organisationId = ref<string | number | null>(props.filters.organisation_id || null);

function navigate(overrides: Record<string, unknown> = {}) {
    router.get(
        index(),
        {
            search: search.value,
            sort: props.filters.sort,
            direction: props.filters.direction,
            deadline_status: deadlineStatus.value,
            status: status.value,
            priority: priority.value,
            organisation_id: organisationId.value,
            ...overrides,
        },
        { preserveState: true, preserveScroll: true },
    );
}

let searchTimeout: ReturnType<typeof setTimeout>;
watch(search, (value) => {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => navigate({ search: value }), 300);
});

let clearing = false;
watch(deadlineStatus, () => { if (!clearing) { navigate(); } });
watch(status, () => { if (!clearing) { navigate(); } });
watch(priority, () => { if (!clearing) { navigate(); } });
watch(organisationId, () => { if (!clearing) { navigate(); } });

const hasActiveFilters = computed(
    () => !!(search.value || deadlineStatus.value || status.value || priority.value || organisationId.value),
);

function clearFilters() {
    clearing = true;
    search.value = '';
    deadlineStatus.value = null;
    status.value = null;
    priority.value = null;
    organisationId.value = null;
    clearing = false;
    navigate({ search: '', deadline_status: null, status: null, priority: null, organisation_id: null });
}

function sortBy(column: string) {
    if (!props.sortable.includes(column)) { return; }
    const direction = props.filters.sort === column && props.filters.direction === 'asc' ? 'desc' : 'asc';
    navigate({ sort: column, direction });
}

function sortIcon(column: string) {
    if (props.filters.sort !== column) { return ArrowUpDown; }
    return props.filters.direction === 'asc' ? ArrowUp : ArrowDown;
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
    completed: { label: 'Completed', class: 'bg-gray-100 text-gray-600' },
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

        <div class="flex flex-wrap items-center gap-2">
            <Input v-model="search" placeholder="Search by title or description…" class="max-w-sm" />
            <ComboboxSelect v-model="status" :options="statusOptions" placeholder="Status…" class="w-40" />
            <ComboboxSelect v-model="priority" :options="priorityOptions" placeholder="Priority…" class="w-40" />
            <ComboboxSelect v-model="deadlineStatus" :options="deadlineStatusOptions" placeholder="Deadline status…" class="w-48" />
            <ComboboxSelect
                v-if="organisations.length > 0"
                v-model="organisationId"
                :options="organisations.map((o) => ({ value: String(o.id), label: o.name }))"
                placeholder="Organisation…"
                class="w-48"
            />
            <Button v-if="hasActiveFilters" class="bg-red-400 text-white" variant="ghost" size="sm" @click="clearFilters">Clear filters</Button>
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
                    <TableRow v-if="tickets.data.length === 0">
                        <TableCell :colspan="columns.length + 1" class="text-muted-foreground py-10 text-center">
                            No tickets found.
                        </TableCell>
                    </TableRow>
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
                        <TableCell class="text-muted-foreground text-sm">{{ ticket.sla_deadline_formatted }}</TableCell>
                        <TableCell>
                            <span :class="['rounded px-1.5 py-0.5 text-xs font-medium', deadlineStatusConfig[ticket.deadline_status].class]">
                                {{ deadlineStatusConfig[ticket.deadline_status].label }}
                            </span>
                        </TableCell>
                        <TableCell class="text-muted-foreground text-sm">{{ ticket.created_at_formatted }}</TableCell>
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
