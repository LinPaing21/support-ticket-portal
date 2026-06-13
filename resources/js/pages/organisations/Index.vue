<script setup lang="ts">
import { ref, watch } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { ArrowDown, ArrowUp, ArrowUpDown } from '@lucide/vue';
import OrganisationController from '@/actions/App/Http/Controllers/OrganisationController';
import Heading from '@/components/Heading.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { index } from '@/routes/organisations';

type Organisation = { id: number; name: string; short_code: string; joined_at: string; created_at: string };
type PaginationLink = { url: string | null; label: string; active: boolean };
type Filters = { sort: string; direction: 'asc' | 'desc'; search: string };
type Props = {
    organisations: { data: Organisation[]; links: PaginationLink[] };
    sortable: string[];
    filters: Filters;
};

const props = defineProps<Props>();

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Organisations', href: index() }],
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

function destroy(org: Organisation) {
    if (confirm(`Delete "${org.name}"?`)) {
        router.delete(OrganisationController.destroy.url(org.id));
    }
}

const columns: { key: string; label: string }[] = [
    { key: 'name', label: 'Name' },
    { key: 'short_code', label: 'Short Code' },
    { key: 'joined_at', label: 'Joined' },
    { key: 'created_at', label: 'Created' },
];
</script>

<template>
    <Head title="Organisations" />

    <div class="flex h-full flex-1 flex-col gap-4 p-4">
        <div class="flex items-center justify-between">
            <Heading title="Organisations" description="Manage organisations" />
            <Button as-child>
                <Link :href="OrganisationController.create.url()">New organisation</Link>
            </Button>
        </div>

        <div class="flex items-center gap-2">
            <Input v-model="search" placeholder="Search by name or short code…" class="max-w-sm" />
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
                        <TableHead class="w-36" />
                    </TableRow>
                </TableHeader>
                <TableBody>
                    <TableRow v-if="organisations.data.length === 0">
                        <TableCell :colspan="columns.length + 1" class="text-muted-foreground py-10 text-center">
                            No organisations found.
                        </TableCell>
                    </TableRow>
                    <TableRow v-for="org in organisations.data" :key="org.id">
                        <TableCell class="font-medium">{{ org.name }}</TableCell>
                        <TableCell>
                            <span class="bg-muted rounded px-1.5 py-0.5 font-mono text-xs">{{ org.short_code }}</span>
                        </TableCell>
                        <TableCell class="text-muted-foreground">{{ new Date(org.joined_at).toLocaleDateString() }}</TableCell>
                        <TableCell class="text-muted-foreground text-sm">{{ new Date(org.created_at).toLocaleDateString() }}</TableCell>
                        <TableCell>
                            <div class="flex gap-2">
                                <Button variant="outline" size="sm" as-child>
                                    <Link :href="OrganisationController.show.url(org.id)">View</Link>
                                </Button>
                                <Button variant="destructive" size="sm" @click="destroy(org)">
                                    Delete
                                </Button>
                            </div>
                        </TableCell>
                    </TableRow>
                </TableBody>
            </Table>
        </div>

        <div class="flex gap-1">
            <template v-for="link in organisations.links" :key="link.label">
                <Button v-if="link.url" variant="outline" size="sm" :disabled="link.active" as-child>
                    <Link :href="link.url" v-html="link.label" />
                </Button>
                <Button v-else variant="ghost" size="sm" disabled v-html="link.label" />
            </template>
        </div>
    </div>
</template>
