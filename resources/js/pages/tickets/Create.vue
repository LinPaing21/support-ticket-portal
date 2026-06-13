<script setup lang="ts">
import { computed, ref } from 'vue';
import { Form, Head } from '@inertiajs/vue3';
import TicketController from '@/actions/App/Http/Controllers/TicketController';
import ComboboxSelect from '@/components/ComboboxSelect.vue';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { index } from '@/routes/tickets';

type Option = { value: string | number; label: string };
type Organisation = { id: number; name: string };
type Agent = { id: number; name: string };
type Props = {
    organisations: Organisation[];
    agents: Agent[];
    priorities: Option[];
    isAdmin: boolean;
    isAgent: boolean;
};

const props = defineProps<Props>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Tickets', href: index() },
            { title: 'New ticket', href: TicketController.create.url() },
        ],
    },
});

const organisationOptions = computed(() => props.organisations.map((o) => ({ value: o.id, label: o.name })));
const agentOptions = computed(() => props.agents.map((a) => ({ value: a.id, label: a.name })));

const selectedOrgId = ref<number | null>(null);
const selectedAgentId = ref<number | null>(null);
const selectedPriority = ref<string | null>(null);
</script>

<template>
    <Head title="New ticket" />

    <div class="flex h-full flex-1 flex-col p-4">
        <div class="max-w-xl space-y-6">
            <Heading title="New ticket" />

            <Form
                v-bind="TicketController.store.form()"
                class="space-y-6"
                v-slot="{ errors, processing }"
            >
                <div class="grid gap-2">
                    <Label for="title">Title</Label>
                    <Input id="title" name="title" placeholder="Brief summary of the issue" required />
                    <InputError :message="errors.title" />
                </div>

                <div class="grid gap-2">
                    <Label for="description">Description</Label>
                    <textarea
                        id="description"
                        name="description"
                        rows="4"
                        placeholder="Describe the issue in detail…"
                        required
                        class="border-input bg-background ring-offset-background focus-visible:ring-ring flex w-full rounded-md border px-3 py-2 text-sm shadow-sm focus-visible:outline-none focus-visible:ring-1"
                    />
                    <InputError :message="errors.description" />
                </div>

                <div class="grid gap-2">
                    <Label>Priority</Label>
                    <ComboboxSelect
                        v-model="selectedPriority"
                        :options="priorities"
                        name="priority"
                        placeholder="Select priority…"
                    />
                    <InputError :message="errors.priority" />
                </div>

                <div v-if="isAdmin || isAgent" class="grid gap-2">
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

                <div v-if="isAdmin || isAgent" class="grid gap-2">
                    <Label>Assigned agent <span class="text-muted-foreground">(optional)</span></Label>
                    <ComboboxSelect
                        v-model="selectedAgentId"
                        :options="agentOptions"
                        name="assigned_agent_id"
                        placeholder="Select agent…"
                        search-placeholder="Search agent…"
                        empty-label="No agent found."
                    />
                    <InputError :message="errors.assigned_agent_id" />
                </div>

                <Button :disabled="processing">Create ticket</Button>
            </Form>
        </div>
    </div>
</template>
