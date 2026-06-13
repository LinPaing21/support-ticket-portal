<script setup lang="ts">
import { Form, Head, setLayoutProps } from '@inertiajs/vue3';
import OrganisationController from '@/actions/App/Http/Controllers/OrganisationController';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { index } from '@/routes/organisations';

type Organisation = { id: number; name: string; short_code: string; joined_at: string };
type Props = { organisation: Organisation };

const props = defineProps<Props>();

setLayoutProps({
    breadcrumbs: [
        { title: 'Organisations', href: index() },
        { title: props.organisation.name, href: OrganisationController.show.url(props.organisation.id) },
        { title: 'Edit', href: OrganisationController.edit.url(props.organisation.id) },
    ],
});

const joinedAtDate = props.organisation.joined_at.split('T')[0];
</script>

<template>
    <Head :title="`Edit ${organisation.name}`" />

    <div class="flex h-full flex-1 flex-col p-4">
        <div class="max-w-xl space-y-6">
            <Heading :title="`Edit ${organisation.name}`" />

            <Form
                v-bind="OrganisationController.update.form(organisation.id)"
                class="space-y-6"
                v-slot="{ errors, processing }"
            >
                <div class="grid gap-2">
                    <Label for="name">Name</Label>
                    <Input id="name" name="name" :default-value="organisation.name" placeholder="Organisation name" />
                    <InputError :message="errors.name" />
                </div>

                <div class="grid gap-2">
                    <Label for="joined_at">Joined date</Label>
                    <Input id="joined_at" name="joined_at" type="date" :default-value="joinedAtDate" />
                    <InputError :message="errors.joined_at" />
                </div>

                <div class="grid gap-2">
                    <Label>Short code</Label>
                    <div class="flex items-center gap-2">
                        <span class="bg-muted rounded px-2 py-1 font-mono text-sm">{{ organisation.short_code }}</span>
                        <span class="text-muted-foreground text-xs">Auto-generated, cannot be changed</span>
                    </div>
                </div>

                <Button :disabled="processing">Save changes</Button>
            </Form>
        </div>
    </div>
</template>
