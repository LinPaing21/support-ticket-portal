<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { ArrowDown, ArrowUp, ArrowUpDown } from '@lucide/vue';
import UserController from '@/actions/App/Http/Controllers/UserController';
import Heading from '@/components/Heading.vue';
import { Button } from '@/components/ui/button';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { index } from '@/routes/users';

type Organisation = { id: number; name: string };
type User = { id: number; name: string; email: string; role: string; organisation: Organisation | null };
type PaginationLink = { url: string | null; label: string; active: boolean };
type Filters = { sort: string; direction: 'asc' | 'desc' };
type Props = {
    users: { data: User[]; links: PaginationLink[] };
    sortable: string[];
    filters: Filters;
};

const props = defineProps<Props>();

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Users', href: index() }],
    },
});

function sortBy(column: string) {
    if (!props.sortable.includes(column)) return;
    const direction = props.filters.sort === column && props.filters.direction === 'asc' ? 'desc' : 'asc';
    router.get(index(), { sort: column, direction }, { preserveState: true, preserveScroll: true });
}

function sortIcon(column: string) {
    if (props.filters.sort !== column) return ArrowUpDown;
    return props.filters.direction === 'asc' ? ArrowUp : ArrowDown;
}

function destroy(user: User) {
    if (confirm(`Delete "${user.name}"?`)) {
        router.delete(UserController.destroy.url(user.id));
    }
}

const columns: { key: string; label: string }[] = [
    { key: 'name', label: 'Name' },
    { key: 'email', label: 'Email' },
    { key: 'role', label: 'Role' },
    { key: 'organisation', label: 'Organisation' },
    { key: 'created_at', label: 'Created' },
];
</script>

<template>
    <Head title="Users" />

    <div class="flex h-full flex-1 flex-col gap-4 p-4">
        <div class="flex items-center justify-between">
            <Heading title="Users" description="Manage system users" />
            <Button as-child>
                <Link :href="UserController.create.url()">New user</Link>
            </Button>
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
                    <TableRow v-for="user in users.data" :key="user.id">
                        <TableCell class="font-medium">{{ user.name }}</TableCell>
                        <TableCell class="text-muted-foreground">{{ user.email }}</TableCell>
                        <TableCell class="capitalize">{{ user.role.replace('_', ' ') }}</TableCell>
                        <TableCell class="text-muted-foreground">{{ user.organisation?.name ?? '—' }}</TableCell>
                        <TableCell class="text-sm text-muted-foreground">
                            {{ new Date(user.created_at).toLocaleDateString() }}
                        </TableCell>
                        <TableCell>
                            <div class="flex gap-2">
                                <Button variant="outline" size="sm" as-child>
                                    <Link :href="UserController.edit.url(user.id)">Edit</Link>
                                </Button>
                                <Button variant="destructive" size="sm" @click="destroy(user)">
                                    Delete
                                </Button>
                            </div>
                        </TableCell>
                    </TableRow>
                </TableBody>
            </Table>
        </div>

        <div class="flex gap-1">
            <template v-for="link in users.links" :key="link.label">
                <Button v-if="link.url" variant="outline" size="sm" :disabled="link.active" as-child>
                    <Link :href="link.url" v-html="link.label" />
                </Button>
                <Button v-else variant="ghost" size="sm" disabled v-html="link.label" />
            </template>
        </div>
    </div>
</template>
