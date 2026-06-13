<script setup lang="ts">
import { ref } from 'vue';
import { Form, Link, router, usePage } from '@inertiajs/vue3';
import CommentController from '@/actions/App/Http/Controllers/CommentController';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';

type CommentUser = { id: number; name: string; role: string };
type Comment = {
    id: number;
    body: string;
    is_internal: boolean;
    created_at: string;
    user: CommentUser;
    can: { update: boolean; delete: boolean };
};
type PaginationLink = { url: string | null; label: string; active: boolean };

const props = defineProps<{
    ticketId: number;
    ticketUserId: number;
    comments: { data: Comment[]; links: PaginationLink[] };
    isStaff: boolean;
    canComment: boolean;
}>();

const page = usePage<{ auth: { user: { id: number; name: string } } }>();

const editingId = ref<number | null>(null);
const editBody = ref('');

function startEdit(comment: Comment) {
    editingId.value = comment.id;
    editBody.value = comment.body;
}

function cancelEdit() {
    editingId.value = null;
    editBody.value = '';
}

function saveEdit(comment: Comment) {
    router.patch(
        CommentController.update.url(comment.id),
        { body: editBody.value },
        { preserveScroll: true, onSuccess: () => cancelEdit() },
    );
}

function deleteComment(comment: Comment) {
    if (confirm('Delete this comment?')) {
        router.delete(CommentController.destroy.url(comment.id), { preserveScroll: true });
    }
}

function initials(name: string): string {
    return name
        .split(' ')
        .slice(0, 2)
        .map((n) => n[0])
        .join('')
        .toUpperCase();
}

const avatarColors = [
    'bg-blue-500',
    'bg-purple-500',
    'bg-green-500',
    'bg-orange-500',
    'bg-pink-500',
    'bg-teal-500',
    'bg-indigo-500',
    'bg-rose-500',
];

function avatarColor(id: number): string {
    return avatarColors[id % avatarColors.length];
}

function formatDate(dateStr: string): string {
    return new Date(dateStr).toLocaleString(undefined, {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    });
}
</script>

<template>
    <div class="space-y-4 pt-4">
        <h2 class="text-sm font-semibold uppercase tracking-wide text-muted-foreground">Comments</h2>

        <div v-for="comment in comments.data" :key="comment.id" class="flex gap-3">
            <div
                :class="['size-9 shrink-0 rounded-full flex items-center justify-center text-xs font-semibold text-white', avatarColor(comment.user.id)]"
            >
                {{ initials(comment.user.name) }}
            </div>

            <div class="flex-1 overflow-hidden rounded-lg border">
                <div class="flex items-center justify-between border-b bg-muted/40 px-4 py-2">
                    <div class="flex items-center gap-2 text-sm">
                        <span class="font-semibold">{{ comment.user.name }}</span>
                        <span
                            v-if="comment.user.id === ticketUserId"
                            class="rounded border px-1.5 py-0.5 text-xs text-muted-foreground"
                        >Author</span>
                        <span
                            v-else-if="comment.user.role === 'admin' || comment.user.role === 'agent'"
                            class="rounded border px-1.5 py-0.5 text-xs text-muted-foreground"
                        >Staff</span>
                        <span
                            v-if="comment.is_internal"
                            class="rounded bg-yellow-100 px-1.5 py-0.5 text-xs font-medium text-yellow-700"
                        >Internal note</span>
                        <span class="text-muted-foreground">{{ formatDate(comment.created_at) }}</span>
                    </div>
                    <div v-if="comment.can.update || comment.can.delete" class="flex gap-1">
                        <button
                            v-if="comment.can.update && editingId !== comment.id"
                            class="text-xs text-muted-foreground hover:text-foreground"
                            @click="startEdit(comment)"
                        >Edit</button>
                        <button
                            v-if="comment.can.delete"
                            class="text-xs text-muted-foreground hover:text-destructive"
                            @click="deleteComment(comment)"
                        >Delete</button>
                    </div>
                </div>

                <div class="px-4 py-3">
                    <textarea
                        v-if="editingId === comment.id"
                        v-model="editBody"
                        rows="3"
                        class="border-input bg-background ring-offset-background focus-visible:ring-ring w-full rounded-md border px-3 py-2 text-sm shadow-sm focus-visible:outline-none focus-visible:ring-1"
                    />
                    <p v-else class="whitespace-pre-wrap text-sm">{{ comment.body }}</p>
                </div>

                <div v-if="editingId === comment.id" class="flex gap-2 border-t bg-muted/40 px-4 py-2">
                    <Button size="sm" @click="saveEdit(comment)">Save</Button>
                    <Button size="sm" variant="ghost" @click="cancelEdit">Cancel</Button>
                </div>
            </div>
        </div>

        <div v-if="comments.links.length > 3" class="flex gap-1">
            <template v-for="link in comments.links" :key="link.label">
                <Button v-if="link.url" variant="outline" size="sm" :disabled="link.active" as-child>
                    <Link :href="link.url" :only="['comments']" preserve-scroll v-html="link.label" />
                </Button>
                <Button v-else variant="ghost" size="sm" disabled v-html="link.label" />
            </template>
        </div>

        <div v-if="canComment" class="flex gap-3">
            <div
                :class="['size-9 shrink-0 rounded-full flex items-center justify-center text-xs font-semibold text-white', avatarColor(page.props.auth.user.id)]"
            >
                {{ initials(page.props.auth.user.name) }}
            </div>

            <Form
                v-bind="CommentController.store.form(ticketId)"
                class="flex-1 overflow-hidden rounded-lg border"
                reset-on-success
                v-slot="{ errors }"
            >
                <textarea
                    name="body"
                    rows="4"
                    placeholder="Leave a comment…"
                    class="border-input bg-background w-full resize-none px-4 py-3 text-sm focus:outline-none"
                />
                <InputError :message="errors.body" class="px-4 pb-2" />
                <div class="flex items-center justify-between border-t bg-muted/40 px-4 py-2">
                    <label v-if="isStaff" class="flex cursor-pointer items-center gap-2 text-sm text-muted-foreground">
                        <input type="checkbox" name="is_internal" value="1" class="rounded" />
                        Internal note
                    </label>
                    <span v-else />
                    <Button type="submit" size="sm">Comment</Button>
                </div>
            </Form>
        </div>
    </div>
</template>
