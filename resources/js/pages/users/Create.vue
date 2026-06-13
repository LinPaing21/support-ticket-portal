<script setup lang="ts">
import { computed, ref } from 'vue';
import { Form, Head } from '@inertiajs/vue3';
import UserController from '@/actions/App/Http/Controllers/UserController';
import ComboboxSelect from '@/components/ComboboxSelect.vue';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { index } from '@/routes/users';

type Organisation = { id: number; name: string };
type Role = { value: string; label: string };
type Props = {
    organisations: Organisation[];
    roles: Role[];
};

const props = defineProps<Props>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Users', href: index() },
            { title: 'New user', href: UserController.create.url() },
        ],
    },
});

const organisationOptions = computed(() =>
    props.organisations.map((o) => ({ value: o.id, label: o.name })),
);

const selectedRole = ref('');
const requiresOrganisation = computed(() => ['client', 'organisation_owner'].includes(selectedRole.value));

const selectedOrgId = ref<number | null>(null);

function onRoleChange(e: Event) {
    selectedRole.value = (e.target as HTMLSelectElement).value;
    if (!requiresOrganisation.value) {
        selectedOrgId.value = null;
    }
}
</script>

<template>
    <Head title="New user" />

    <div class="flex h-full flex-1 flex-col p-4">
        <div class="max-w-xl space-y-6">
            <Heading title="New user" />

            <Form
                v-bind="UserController.store.form()"
                class="space-y-6"
                v-slot="{ errors, processing }"
            >
                <div class="grid gap-2">
                    <Label for="name">Name</Label>
                    <Input id="name" name="name" placeholder="Full name" required />
                    <InputError :message="errors.name" />
                </div>

                <div class="grid gap-2">
                    <Label for="email">Email</Label>
                    <Input id="email" name="email" type="email" placeholder="email@example.com" required />
                    <InputError :message="errors.email" />
                </div>

                <div class="grid gap-2">
                    <Label for="password">Password</Label>
                    <Input id="password" name="password" type="password" required />
                    <InputError :message="errors.password" />
                </div>

                <div class="grid gap-2">
                    <Label for="role">Role</Label>
                    <select
                        id="role"
                        name="role"
                        class="border-input bg-background ring-offset-background focus-visible:ring-ring flex h-9 w-full rounded-md border px-3 py-1 text-sm shadow-sm focus-visible:outline-none focus-visible:ring-1"
                        required
                        @change="onRoleChange"
                    >
                        <option value="" disabled selected>Select a role</option>
                        <option v-for="role in roles" :key="role.value" :value="role.value">
                            {{ role.label }}
                        </option>
                    </select>
                    <InputError :message="errors.role" />
                </div>

                <div v-if="requiresOrganisation" class="grid gap-2">
                    <Label>Organisation</Label>
                    <ComboboxSelect
                        v-model="selectedOrgId"
                        :options="organisationOptions"
                        name="organisation_id"
                        placeholder="Select organisation…"
                        search-placeholder="Search organisation…"
                        empty-label="No organisation found."
                    />
                    <InputError :message="errors.organisation_id" />
                </div>

                <Button :disabled="processing">Create user</Button>
            </Form>
        </div>
    </div>
</template>
