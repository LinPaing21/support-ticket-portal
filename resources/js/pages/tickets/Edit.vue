<script setup lang="ts">
import { computed, ref } from 'vue';
import { Form, Head, setLayoutProps } from '@inertiajs/vue3';
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
type Ticket = {
    id: number;
    title: string;
    description: string;
    status: string;
    priority: string;
    organisation_id: number | null;
    assigned_agent_id: number | null;
};
type Props = {
    ticket: Ticket;
    organisations: Organisation[];
    agents: Agent[];
    priorities: Option[];
    statuses: Option[];
    isAdmin: boolean;
    isAgent: boolean;
    isAgentUpdate: boolean;
};

const props = defineProps<Props>();

setLayoutProps({
    breadcrumbs: [
        { title: 'Tickets', href: index() },
        { title: props.ticket.title, href: TicketController.show.url(props.ticket.id) },
        { title: 'Edit', href: TicketController.edit.url(props.ticket.id) },
    ],
});

const organisationOptions = computed(() => props.organisations.map((o) => ({ value: o.id, label: o.name })));
const agentOptions = computed(() => props.agents.map((a) => ({ value: a.id, label: a.name })));

const selectedStatus = ref<string | null>(props.ticket.status);
const selectedPriority = ref<string | null>(props.ticket.priority);
const selectedOrgId = ref<number | null>(props.ticket.organisation_id);
const selectedAgentId = ref<number | null>(props.ticket.assigned_agent_id);
</script>

<template>
    <Head :title="`Edit: ${ticket.title}`" />

    <div class="flex h-full flex-1 flex-col p-4">
        <div class="max-w-xl space-y-6">
            <Heading :title="`Edit: ${ticket.title}`" />

            <Form
                v-bind="TicketController.update.form(ticket.id)"
                class="space-y-6"
                v-slot="{ errors, processing }"
            >
                <div class="grid gap-2">
                    <Label for="title">Title</Label>
                    <Input id="title" name="title" :default-value="ticket.title" placeholder="Brief summary of the issue" :disabled="isAgentUpdate" />
                    <InputError :message="errors.title" />
                </div>

                <div class="grid gap-2">
                    <Label for="description">Description</Label>
                    <textarea
                        id="description"
                        name="description"
                        rows="4"
                        :value="ticket.description"
                        :disabled="isAgentUpdate"
                        class="border-input bg-background ring-offset-background focus-visible:ring-ring disabled:cursor-not-allowed disabled:opacity-50 flex w-full rounded-md border px-3 py-2 text-sm shadow-sm focus-visible:outline-none focus-visible:ring-1"
                    />
                    <InputError :message="errors.description" />
                </div>

                <div class="grid gap-2">
                    <Label>Status</Label>
                    <ComboboxSelect
                        v-model="selectedStatus"
                        :options="statuses"
                        name="status"
                        placeholder="Select status…"
                    />
                    <InputError :message="errors.status" />
                </div>

                <div class="grid gap-2">
                    <Label>Priority</Label>
                    <ComboboxSelect
                        v-model="selectedPriority"
                        :options="priorities"
                        name="priority"
                        placeholder="Select priority…"
                    />
                    <p class="text-muted-foreground text-xs">Changing priority will recalculate the SLA deadline.</p>
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
                        :disabled="isAgentUpdate"
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

                <Button :disabled="processing">Save changes</Button>
            </Form>
        </div>
    </div>
</template>
