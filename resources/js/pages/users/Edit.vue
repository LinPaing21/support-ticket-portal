<script setup lang="ts">
import { computed, ref } from 'vue';
import { Form, Head, setLayoutProps } from '@inertiajs/vue3';
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
type User = { id: number; name: string; email: string; role: string; organisation_id: number | null };
type Props = {
    user: User;
    organisations: Organisation[];
    roles: Role[];
};

const props = defineProps<Props>();

setLayoutProps({
    breadcrumbs: [
        { title: 'Users', href: index() },
        { title: props.user.name, href: UserController.show.url(props.user.id) },
        { title: 'Edit', href: UserController.edit.url(props.user.id) },
    ],
});

const organisationOptions = computed(() =>
    props.organisations.map((o) => ({ value: o.id, label: o.name })),
);

const selectedRole = ref(props.user.role);
const requiresOrganisation = computed(() => ['client', 'organisation_owner'].includes(selectedRole.value));

const selectedOrgId = ref<number | null>(props.user.organisation_id);

function onRoleChange(e: Event) {
    selectedRole.value = (e.target as HTMLSelectElement).value;
    if (!requiresOrganisation.value) {
        selectedOrgId.value = null;
    }
}
</script>

<template>
    <Head :title="`Edit ${user.name}`" />

    <div class="flex h-full flex-1 flex-col p-4">
        <div class="max-w-xl space-y-6">
            <Heading :title="`Edit ${user.name}`" />

            <Form
                v-bind="UserController.update.form(user.id)"
                class="space-y-6"
                v-slot="{ errors, processing }"
            >
                <div class="grid gap-2">
                    <Label for="name">Name</Label>
                    <Input id="name" name="name" :default-value="user.name" placeholder="Full name" />
                    <InputError :message="errors.name" />
                </div>

                <div class="grid gap-2">
                    <Label for="email">Email</Label>
                    <Input id="email" name="email" type="email" :default-value="user.email" placeholder="email@example.com" />
                    <InputError :message="errors.email" />
                </div>

                <div class="grid gap-2">
                    <Label for="role">Role</Label>
                    <select
                        id="role"
                        name="role"
                        class="border-input bg-background ring-offset-background focus-visible:ring-ring flex h-9 w-full rounded-md border px-3 py-1 text-sm shadow-sm focus-visible:outline-none focus-visible:ring-1"
                        @change="onRoleChange"
                    >
                        <option v-for="role in roles" :key="role.value" :value="role.value" :selected="role.value === user.role">
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

                <Button :disabled="processing">Save changes</Button>
            </Form>
        </div>
    </div>
</template>
