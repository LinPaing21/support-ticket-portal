<script setup lang="ts">
import { Head, Link, router, setLayoutProps } from '@inertiajs/vue3';
import OrganisationController from '@/actions/App/Http/Controllers/OrganisationController';
import Heading from '@/components/Heading.vue';
import { Button } from '@/components/ui/button';
import { index } from '@/routes/organisations';

type Organisation = { id: number; name: string; short_code: string; joined_at: string; created_at: string };
type Props = {
    organisation: Organisation;
    can: { edit: boolean; delete: boolean };
};

const props = defineProps<Props>();

setLayoutProps({
    breadcrumbs: [
        { title: 'Organisations', href: index() },
        { title: props.organisation.name, href: OrganisationController.show.url(props.organisation.id) },
    ],
});

function destroy() {
    if (confirm(`Delete "${props.organisation.name}"?`)) {
        router.delete(OrganisationController.destroy.url(props.organisation.id));
    }
}
</script>

<template>
    <Head :title="organisation.name" />

    <div class="flex h-full flex-1 flex-col p-4">
        <div class="max-w-xl space-y-6">
            <div class="flex items-start justify-between">
                <Heading :title="organisation.name" />
                <div class="flex gap-2">
                    <Button v-if="can.edit" variant="outline" as-child>
                        <Link :href="OrganisationController.edit.url(organisation.id)">Edit</Link>
                    </Button>
                    <Button v-if="can.delete" variant="destructive" @click="destroy">Delete</Button>
                </div>
            </div>

            <dl class="space-y-3 text-sm">
                <div class="flex gap-4">
                    <dt class="w-32 shrink-0 text-muted-foreground">Short Code</dt>
                    <dd>
                        <span class="bg-muted rounded px-1.5 py-0.5 font-mono text-xs">{{ organisation.short_code }}</span>
                    </dd>
                </div>
                <div class="flex gap-4">
                    <dt class="w-32 shrink-0 text-muted-foreground">Joined</dt>
                    <dd>{{ new Date(organisation.joined_at).toLocaleDateString() }}</dd>
                </div>
                <div class="flex gap-4">
                    <dt class="w-32 shrink-0 text-muted-foreground">Created</dt>
                    <dd>{{ new Date(organisation.created_at).toLocaleDateString() }}</dd>
                </div>
            </dl>
        </div>
    </div>
</template>
