<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3';
import OrganisationController from '@/actions/App/Http/Controllers/OrganisationController';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { index } from '@/routes/organisations';

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Organisations', href: index() },
            { title: 'New organisation', href: OrganisationController.create.url() },
        ],
    },
});
</script>

<template>
    <Head title="New organisation" />

    <div class="flex h-full flex-1 flex-col p-4">
        <div class="max-w-xl space-y-6">
            <Heading title="New organisation" />

            <Form
                v-bind="OrganisationController.store.form()"
                class="space-y-6"
                v-slot="{ errors, processing }"
            >
                <div class="grid gap-2">
                    <Label for="name">Name</Label>
                    <Input id="name" name="name" placeholder="Organisation name" required />
                    <InputError :message="errors.name" />
                </div>

                <div class="grid gap-2">
                    <Label for="joined_at">Joined date</Label>
                    <Input id="joined_at" name="joined_at" type="date" required />
                    <InputError :message="errors.joined_at" />
                </div>

                <p class="text-muted-foreground text-sm">A unique 7-character short code will be generated automatically.</p>

                <Button :disabled="processing">Create organisation</Button>
            </Form>
        </div>
    </div>
</template>
