<script setup lang="ts">
import { Head, Link, router, setLayoutProps } from '@inertiajs/vue3';
import UserController from '@/actions/App/Http/Controllers/UserController';
import Heading from '@/components/Heading.vue';
import { Button } from '@/components/ui/button';
import { index } from '@/routes/users';

type Organisation = { id: number; name: string };
type User = { id: number; name: string; email: string; role: string; organisation: Organisation | null; created_at: string };
type Props = {
    user: User;
    can: { edit: boolean; delete: boolean };
};

const props = defineProps<Props>();

setLayoutProps({
    breadcrumbs: [
        { title: 'Users', href: index() },
        { title: props.user.name, href: UserController.show.url(props.user.id) },
    ],
});

function destroy() {
    if (confirm(`Delete "${props.user.name}"?`)) {
        router.delete(UserController.destroy.url(props.user.id));
    }
}
</script>

<template>
    <Head :title="user.name" />

    <div class="flex h-full flex-1 flex-col p-4">
        <div class="max-w-xl space-y-6">
            <div class="flex items-start justify-between">
                <Heading :title="user.name" />
                <div class="flex gap-2">
                    <Button v-if="can.edit" variant="outline" as-child>
                        <Link :href="UserController.edit.url(user.id)">Edit</Link>
                    </Button>
                    <Button v-if="can.delete" variant="destructive" @click="destroy">Delete</Button>
                </div>
            </div>

            <dl class="space-y-3 text-sm">
                <div class="flex gap-4">
                    <dt class="w-32 shrink-0 text-muted-foreground">Email</dt>
                    <dd>{{ user.email }}</dd>
                </div>
                <div class="flex gap-4">
                    <dt class="w-32 shrink-0 text-muted-foreground">Role</dt>
                    <dd class="capitalize">{{ user.role.replace('_', ' ') }}</dd>
                </div>
                <div class="flex gap-4">
                    <dt class="w-32 shrink-0 text-muted-foreground">Organisation</dt>
                    <dd>{{ user.organisation?.name ?? '—' }}</dd>
                </div>
                <div class="flex gap-4">
                    <dt class="w-32 shrink-0 text-muted-foreground">Joined</dt>
                    <dd>{{ new Date(user.created_at).toLocaleDateString() }}</dd>
                </div>
            </dl>
        </div>
    </div>
</template>
