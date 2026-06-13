# CRUD Guide — Laravel + Inertia + Vue

Step-by-step for adding a new resource (example: `Post`).

---

## Step 1 — Create the Controller

```bash
php artisan make:controller PostController --resource --model=Post --no-interaction
```

Creates `app/Http/Controllers/PostController.php` with all 7 resource methods stubbed.

---

## Step 2 — Create the Model & Migration

```bash
php artisan make:model Post -m --no-interaction
```

Edit the migration in `database/migrations/` to add your columns:

```php
$table->string('title');
$table->text('body')->nullable();
$table->timestamps();
```

Add `#[Fillable]` to the model (project convention — no `$fillable` array):

```php
use Illuminate\Database\Eloquent\Attributes\Fillable;

#[Fillable(['title', 'body'])]
class Post extends Model
```

Run the migration:

```bash
php artisan migrate
```

---

## Step 3 — Create the Form Request

```bash
php artisan make:request PostRequest --no-interaction
```

> **Pitfall:** The generated stub has `authorize()` returning `false`. You must change it to `true` (or a policy check), otherwise every request returns 403.

Edit `app/Http/Requests/PostRequest.php`:

```php
public function authorize(): bool
{
    return true; // use policy — see Step 8
}

public function rules(): array
{
    $post = $this->route('post');

    return [
        'title' => ['required', 'string', 'max:255'],
        // for unique rules, exclude the current record on update:
        // 'slug' => ['required', 'unique:posts,slug,' . ($post?->id ?? 'NULL')],
        'body'  => ['nullable', 'string'],
    ];
}
```

---

## Step 4 — Register the Route

In `routes/web.php`, inside the `auth` + `verified` middleware group:

```php
Route::middleware(['auth', 'verified'])->group(function () {
    Route::inertia('dashboard', 'Dashboard')->name('dashboard');

    Route::resource('posts', PostController::class); // add this
});
```

---

## Step 5 — Implement the Controller

> **Pitfall:** Always type-hint your `FormRequest` (e.g. `PostRequest`) on `store()` and `update()`, never plain `Request`. Plain `Request` does not have a `validated()` method — it will throw a runtime error.

```php
use App\Http\Requests\PostRequest;
use App\Models\Post;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

public function index(): Response
{
    return Inertia::render('posts/Index', [
        'posts' => Post::latest()->paginate(15),
    ]);
}

public function create(): Response
{
    return Inertia::render('posts/Create');
}

public function store(PostRequest $request): RedirectResponse
{
    Post::create($request->validated());

    Inertia::flash('toast', ['type' => 'success', 'message' => 'Post created.']);

    return to_route('posts.index');
}

public function show(Request $request, Post $post): Response
{
    return Inertia::render('posts/Show', [
        'post' => $post,
        'can'  => [
            'edit'   => $request->user()->can('update', $post),
            'delete' => $request->user()->can('delete', $post),
        ],
    ]);
}

public function edit(Request $request, Post $post): Response
{
    return Inertia::render('posts/Edit', [
        'post' => $post,
    ]);
}

public function update(PostRequest $request, Post $post): RedirectResponse
{
    $post->update($request->validated());

    Inertia::flash('toast', ['type' => 'success', 'message' => 'Post updated.']);

    return to_route('posts.show', ['post' => $post]);
}

public function destroy(Post $post): RedirectResponse
{
    $post->delete();

    Inertia::flash('toast', ['type' => 'success', 'message' => 'Post deleted.']);

    return to_route('posts.index');
}
```

> **Pitfall:** `Inertia::flash` takes an array with string keys: `['type' => 'success', 'message' => '...']`. Using positional values like `['type', 'success']` silently breaks the toast.

> **Pitfall:** When passing `can` props from the controller, use the policy method name exactly — `can('update', $post)` and `can('delete', $post)`. Do **not** use `can('edit', $post)` — there is no `edit` method on the policy.

---

## Step 6 — Build to Generate Wayfinder Files

Run the build so Wayfinder generates TypeScript route/action helpers:

```bash
npm run build
# or during development:
npm run dev
```

This generates:
- `resources/js/routes/posts/index.ts` — typed URL helpers (`index`, `show`, `create`, `edit`)
- `resources/js/actions/App/Http/Controllers/PostController.ts` — typed form action helpers

> **Note:** Wayfinder runs at the start of the Vite build, so even if compilation fails the generated files are still written. You can fix Vue errors and rebuild without losing the generated types.

---

## Step 7 — Create Vue Pages

Create a `resources/js/pages/posts/` directory with four pages.

### Page content wrapper

Every page must wrap its content with a padding div to match the app layout:

```vue
<div class="flex h-full flex-1 flex-col gap-4 p-4">
    <!-- page content -->
</div>
```

> **Pitfall:** Without `p-4` the content will be flush against the sidebar header — no spacing.

### Breadcrumbs — static vs dynamic

Use `defineOptions` for **static** breadcrumbs (no props reference):

```ts
defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Posts', href: index() }],
    },
});
```

Use `setLayoutProps` for **dynamic** breadcrumbs that reference `props` (e.g. Show/Edit pages):

```ts
import { setLayoutProps } from '@inertiajs/vue3';

const props = defineProps<Props>();

setLayoutProps({
    breadcrumbs: [
        { title: 'Posts', href: index() },
        { title: props.post.title, href: PostController.show.url(props.post.id) },
    ],
});
```

> **Pitfall:** `defineOptions()` is hoisted **outside** of `setup()` by Vue's compiler, so it cannot reference locally declared variables like `props`. Using `props` inside `defineOptions` causes a compile error.

### Table action buttons

> **Pitfall:** Do not put `class="flex gap-2"` directly on `<TableCell>`. Table cells are `display: table-cell` — flex has no effect. Wrap the buttons in a `<div>` inside the cell instead.

```vue
<!-- Wrong -->
<TableCell class="flex gap-2">
    <Button>Edit</Button>
</TableCell>

<!-- Correct -->
<TableCell>
    <div class="flex gap-2">
        <Button>Edit</Button>
    </div>
</TableCell>
```

---

### `pages/posts/Index.vue`

```vue
<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import PostController from '@/actions/App/Http/Controllers/PostController';
import Heading from '@/components/Heading.vue';
import { Button } from '@/components/ui/button';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { index } from '@/routes/posts';

type Post = { id: number; title: string; body: string | null };
type PaginationLink = { url: string | null; label: string; active: boolean };
type Props = {
    posts: { data: Post[]; links: PaginationLink[] };
};

defineProps<Props>();

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Posts', href: index() }],
    },
});

function destroy(post: Post) {
    if (confirm(`Delete "${post.title}"?`)) {
        router.delete(PostController.destroy.url(post.id));
    }
}
</script>

<template>
    <Head title="Posts" />

    <div class="flex h-full flex-1 flex-col gap-4 p-4">
        <div class="flex items-center justify-between">
            <Heading title="Posts" description="Manage your posts" />
            <Button as-child>
                <Link :href="PostController.create.url()">New post</Link>
            </Button>
        </div>

        <div class="rounded-xl border">
            <Table>
                <TableHeader>
                    <TableRow>
                        <TableHead>Title</TableHead>
                        <TableHead>Body</TableHead>
                        <TableHead class="w-36" />
                    </TableRow>
                </TableHeader>
                <TableBody>
                    <TableRow v-for="post in posts.data" :key="post.id">
                        <TableCell class="font-medium">{{ post.title }}</TableCell>
                        <TableCell class="text-muted-foreground">{{ post.body ?? '—' }}</TableCell>
                        <TableCell>
                            <div class="flex gap-2">
                                <Button variant="outline" size="sm" as-child>
                                    <Link :href="PostController.edit.url(post.id)">Edit</Link>
                                </Button>
                                <Button variant="destructive" size="sm" @click="destroy(post)">
                                    Delete
                                </Button>
                            </div>
                        </TableCell>
                    </TableRow>
                </TableBody>
            </Table>
        </div>

        <div class="flex gap-1">
            <template v-for="link in posts.links" :key="link.label">
                <Button v-if="link.url" variant="outline" size="sm" :disabled="link.active" as-child>
                    <Link :href="link.url" v-html="link.label" />
                </Button>
                <Button v-else variant="ghost" size="sm" disabled v-html="link.label" />
            </template>
        </div>
    </div>
</template>
```

### `pages/posts/Show.vue`

```vue
<script setup lang="ts">
import { Head, Link, router, setLayoutProps } from '@inertiajs/vue3';
import PostController from '@/actions/App/Http/Controllers/PostController';
import Heading from '@/components/Heading.vue';
import { Button } from '@/components/ui/button';
import { index } from '@/routes/posts';

type Post = { id: number; title: string; body: string | null; created_at: string };
type Props = {
    post: Post;
    can: { edit: boolean; delete: boolean };
};

const props = defineProps<Props>();

setLayoutProps({
    breadcrumbs: [
        { title: 'Posts', href: index() },
        { title: props.post.title, href: PostController.show.url(props.post.id) },
    ],
});

function destroy() {
    if (confirm(`Delete "${props.post.title}"?`)) {
        router.delete(PostController.destroy.url(props.post.id));
    }
}
</script>

<template>
    <Head :title="post.title" />

    <div class="flex h-full flex-1 flex-col p-4">
        <div class="max-w-xl space-y-6">
            <div class="flex items-start justify-between">
                <Heading :title="post.title" />
                <div class="flex gap-2">
                    <Button v-if="can.edit" variant="outline" as-child>
                        <Link :href="PostController.edit.url(post.id)">Edit</Link>
                    </Button>
                    <Button v-if="can.delete" variant="destructive" @click="destroy">
                        Delete
                    </Button>
                </div>
            </div>

            <p class="text-sm text-muted-foreground">{{ post.body ?? 'No content.' }}</p>
        </div>
    </div>
</template>
```

### `pages/posts/Create.vue`

```vue
<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3';
import PostController from '@/actions/App/Http/Controllers/PostController';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { index } from '@/routes/posts';

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Posts', href: index() },
            { title: 'New post', href: PostController.create.url() },
        ],
    },
});
</script>

<template>
    <Head title="New post" />

    <div class="flex h-full flex-1 flex-col p-4">
        <div class="max-w-xl space-y-6">
            <Heading title="New post" />

            <Form
                v-bind="PostController.store.form()"
                class="space-y-6"
                v-slot="{ errors, processing }"
            >
                <div class="grid gap-2">
                    <Label for="title">Title</Label>
                    <Input id="title" name="title" placeholder="Post title" required />
                    <InputError :message="errors.title" />
                </div>

                <div class="grid gap-2">
                    <Label for="body">Body</Label>
                    <Input id="body" name="body" placeholder="Post body" />
                    <InputError :message="errors.body" />
                </div>

                <Button :disabled="processing">Create post</Button>
            </Form>
        </div>
    </div>
</template>
```

### `pages/posts/Edit.vue`

```vue
<script setup lang="ts">
import { Form, Head, setLayoutProps } from '@inertiajs/vue3';
import PostController from '@/actions/App/Http/Controllers/PostController';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { index } from '@/routes/posts';

type Post = { id: number; title: string; body: string | null };
type Props = { post: Post };

const props = defineProps<Props>();

setLayoutProps({
    breadcrumbs: [
        { title: 'Posts', href: index() },
        { title: props.post.title, href: PostController.show.url(props.post.id) },
        { title: 'Edit', href: PostController.edit.url(props.post.id) },
    ],
});
</script>

<template>
    <Head :title="`Edit ${post.title}`" />

    <div class="flex h-full flex-1 flex-col p-4">
        <div class="max-w-xl space-y-6">
            <Heading :title="`Edit ${post.title}`" />

            <Form
                v-bind="PostController.update.form(post.id)"
                class="space-y-6"
                v-slot="{ errors, processing }"
            >
                <div class="grid gap-2">
                    <Label for="title">Title</Label>
                    <Input id="title" name="title" :default-value="post.title" placeholder="Post title" required />
                    <InputError :message="errors.title" />
                </div>

                <div class="grid gap-2">
                    <Label for="body">Body</Label>
                    <Input id="body" name="body" :default-value="post.body ?? ''" placeholder="Post body" />
                    <InputError :message="errors.body" />
                </div>

                <Button :disabled="processing">Save changes</Button>
            </Form>
        </div>
    </div>
</template>
```

---

## Step 7.5 — Add Table Search & Column Sorting

This step enhances the model, controller, and `Index.vue`. Skip it if the resource doesn't need filtering.

### Create the `HasTableFilters` trait

Create `app/Traits/HasTableFilters.php`:

```php
<?php

namespace App\Traits;

use Illuminate\Database\Eloquent\Builder;

trait HasTableFilters
{
    public function scopeGlobalSearch(Builder $query, string $search): Builder
    {
        if (empty($search)) {
            return $query;
        }

        return $query->where(function (Builder $query) use ($search) {
            foreach ($this->filterable as $column) {
                $query->orWhere($column, 'like', "%{$search}%");
            }
        });
    }

    public function scopeSorting(Builder $query, string $sort, string $direction): Builder
    {
        if (! in_array($sort, $this->sortable ?? []) || ! in_array($direction, ['asc', 'desc'])) {
            return $query;
        }

        return $query->orderBy($sort, $direction);
    }
}
```

`scopeGlobalSearch` wraps all `orWhere` clauses in a single `where()` group so they don't bleed into any other query constraints. `scopeSorting` whitelists against `$sortable` — raw user input never reaches `orderBy()`.

### Add the trait and properties to the model

```php
use App\Traits\HasTableFilters;

class Post extends Model
{
    use HasTableFilters;

    /** @var array<int, string> */
    public array $filterable = ['title', 'body'];

    /** @var array<int, string> */
    public array $sortable = ['title', 'created_at'];
}
```

> **Pitfall:** Declare `$filterable` and `$sortable` as `public`, not `protected`. Repositories access these from outside the model — a `protected` property throws a PHP fatal error when read externally, which silently sends `null` to the Vue prop instead.

### Controller — accept and apply filter params

```php
use Illuminate\Http\Request;

public function index(Request $request): Response
{
    $search  = $request->string('search');
    $sort    = $request->string('sort', 'created_at');
    $sortDir = $request->string('direction', 'desc');

    return Inertia::render('posts/Index', [
        'posts' => Post::globalSearch($search)
            ->sorting($sort, $sortDir)
            ->paginate(15)
            ->withQueryString(),
        'filters' => [
            'search'    => $search,
            'sort'      => $sort,
            'direction' => $sortDir,
        ],
    ]);
}
```

> **Pitfall:** Always call `->withQueryString()` on the paginator. Without it, pagination links drop the `search`, `sort`, and `direction` params — clicking "next page" resets the filters.

> **Pitfall:** The default `$sort` value passed by the controller (`'created_at'`) must be in the model's `$sortable` array, otherwise `scopeSorting` skips it and results come back unordered.

### `pages/posts/Index.vue` — search input + sortable headers

```vue
<script setup lang="ts">
import { ref } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { watchDebounced } from '@vueuse/core';
import { ArrowDown, ArrowUp, ArrowUpDown, Search } from '@lucide/vue';
import PostController from '@/actions/App/Http/Controllers/PostController';
import Heading from '@/components/Heading.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { index } from '@/routes/posts';

type Post = { id: number; title: string; body: string | null; created_at: string };
type PaginationLink = { url: string | null; label: string; active: boolean };
type Filters = { search: string; sort: string; direction: 'asc' | 'desc' };
type Props = {
    posts: { data: Post[]; links: PaginationLink[] };
    filters: Filters;
};

const props = defineProps<Props>();

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Posts', href: index() }],
    },
});

const search = ref(props.filters.search ?? '');

watchDebounced(
    search,
    (value) => {
        router.get(
            index(),
            { search: value || undefined, sort: props.filters.sort, direction: props.filters.direction },
            { preserveState: true, preserveScroll: true, replace: true },
        );
    },
    { debounce: 300 },
);

function sortBy(column: string) {
    const direction =
        props.filters.sort === column && props.filters.direction === 'asc' ? 'desc' : 'asc';
    router.get(
        index(),
        { search: search.value || undefined, sort: column, direction },
        { preserveState: true, preserveScroll: true },
    );
}

function sortIcon(column: string) {
    if (props.filters.sort !== column) return ArrowUpDown;
    return props.filters.direction === 'asc' ? ArrowUp : ArrowDown;
}

function destroy(post: Post) {
    if (confirm(`Delete "${post.title}"?`)) {
        router.delete(PostController.destroy.url(post.id));
    }
}
</script>

<template>
    <Head title="Posts" />

    <div class="flex h-full flex-1 flex-col gap-4 p-4">
        <div class="flex items-center justify-between">
            <Heading title="Posts" description="Manage your posts" />
            <Button as-child>
                <Link :href="PostController.create.url()">New post</Link>
            </Button>
        </div>

        <div class="relative w-64">
            <Search class="absolute left-3 top-1/2 size-4 -translate-y-1/2 text-muted-foreground" />
            <Input v-model="search" placeholder="Search posts…" class="pl-9" />
        </div>

        <div class="rounded-xl border">
            <Table>
                <TableHeader>
                    <TableRow>
                        <TableHead>
                            <button class="flex items-center gap-1 hover:text-foreground" @click="sortBy('title')">
                                Title
                                <component :is="sortIcon('title')" class="size-4" />
                            </button>
                        </TableHead>
                        <TableHead>Body</TableHead>
                        <TableHead>
                            <button class="flex items-center gap-1 hover:text-foreground" @click="sortBy('created_at')">
                                Created
                                <component :is="sortIcon('created_at')" class="size-4" />
                            </button>
                        </TableHead>
                        <TableHead class="w-36" />
                    </TableRow>
                </TableHeader>
                <TableBody>
                    <TableRow v-for="post in posts.data" :key="post.id">
                        <TableCell class="font-medium">{{ post.title }}</TableCell>
                        <TableCell class="text-muted-foreground">{{ post.body ?? '—' }}</TableCell>
                        <TableCell class="text-sm text-muted-foreground">
                            {{ new Date(post.created_at).toLocaleDateString() }}
                        </TableCell>
                        <TableCell>
                            <div class="flex gap-2">
                                <Button variant="outline" size="sm" as-child>
                                    <Link :href="PostController.edit.url(post.id)">Edit</Link>
                                </Button>
                                <Button variant="destructive" size="sm" @click="destroy(post)">
                                    Delete
                                </Button>
                            </div>
                        </TableCell>
                    </TableRow>
                </TableBody>
            </Table>
        </div>

        <div class="flex gap-1">
            <template v-for="link in posts.links" :key="link.label">
                <Button v-if="link.url" variant="outline" size="sm" :disabled="link.active" as-child>
                    <Link :href="link.url" v-html="link.label" />
                </Button>
                <Button v-else variant="ghost" size="sm" disabled v-html="link.label" />
            </template>
        </div>
    </div>
</template>
```

> **Pitfall:** Pass `search: value || undefined` (not `search: value`) when the field is empty. Passing an empty string keeps `?search=` in the URL; passing `undefined` removes the key entirely — cleaner URL and avoids an empty `LIKE '%%'` query on every load.

> **Pitfall:** Use `replace: true` on the search `router.get()` so each keystroke doesn't push a new browser history entry. Sorting calls should **not** use `replace: true` so the user can navigate back to the previous sort.

---

## Step 8 — Create a Policy

```bash
php artisan make:policy PostPolicy --model=Post --no-interaction
```

Laravel auto-discovers policies named `ModelPolicy` — no registration needed.

### Define the rules

```php
public function viewAny(User $user): bool
{
    return true;
}

public function view(User $user, Post $post): bool
{
    return true;
}

public function create(User $user): bool
{
    return true;
}

public function update(User $user, Post $post): bool
{
    return $user->id === $post->user_id;
}

public function delete(User $user, Post $post): bool
{
    return $user->id === $post->user_id;
}
```

### Authorize in the controller

> **Pitfall:** The base `Controller` class in this project does not extend `Illuminate\Routing\Controller`, so `$this->authorize()` is not available. Use `Gate::authorize()` instead — it throws an `AuthorizationException` (403) the same way.

```php
use Illuminate\Support\Facades\Gate;

public function index(): Response
{
    Gate::authorize('viewAny', Post::class);
    // ...
}

public function store(PostRequest $request): RedirectResponse
{
    Gate::authorize('create', Post::class);
    // ...
}

public function update(PostRequest $request, Post $post): RedirectResponse
{
    Gate::authorize('update', $post);
    // ...
}

public function destroy(Post $post): RedirectResponse
{
    Gate::authorize('delete', $post);
    // ...
}
```

Unauthorized requests automatically return a `403` response.

### Authorize in the Form Request (alternative)

Centralizes authorization so the controller stays clean:

```php
public function authorize(): bool
{
    $post = $this->route('post');

    return $post
        ? $this->user()->can('update', $post)
        : $this->user()->can('create', Post::class);
}
```

### Expose permissions to Vue

Pass `can` as a prop so the UI can hide buttons the user can't use:

```php
// show() and edit() in the controller
'can' => [
    'edit'   => $request->user()->can('update', $post), // policy method = 'update'
    'delete' => $request->user()->can('delete', $post), // policy method = 'delete'
],
```

Then in the Vue page:

```vue
<Button v-if="can.edit" variant="outline" as-child>
    <Link :href="PostController.edit.url(post.id)">Edit</Link>
</Button>
<Button v-if="can.delete" variant="destructive" @click="destroy">Delete</Button>
```

---

## Step 9 — Add to Sidebar

### Share permissions globally in `HandleInertiaRequests`

Add a `can` key to the shared `auth` object so every page (including the sidebar) can read it without an extra prop:

```php
// app/Http/Middleware/HandleInertiaRequests.php
use App\Models\Post;

public function share(Request $request): array
{
    return [
        ...parent::share($request),
        'name' => config('app.name'),
        'auth' => [
            'user' => $request->user(),
            'can'  => [
                'posts.viewAny' => $request->user()?->can('viewAny', Post::class) ?? false,
            ],
        ],
        'sidebarOpen' => ! $request->hasCookie('sidebar_state') || $request->cookie('sidebar_state') === 'true',
    ];
}
```

Add each new resource's `viewAny` check here as you create it. The `?? false` default ensures unauthenticated requests never accidentally return `null`.

### Update the `Auth` type

```ts
// resources/js/types/auth.ts
export type Auth = {
    user: User;
    can: Record<string, boolean>;
};
```

### Gate the nav item in `AppSidebar.vue`

Change `mainNavItems` from a plain `const` to a `computed` so it reacts to the shared props:

```ts
import { computed } from 'vue';
import { usePage } from '@inertiajs/vue3';
import { FileText, LayoutGrid } from '@lucide/vue';
import { index as posts } from '@/routes/posts';
import type { Auth } from '@/types';

const page = usePage<{ auth: Auth }>();
const can  = computed(() => page.props.auth.can);

const mainNavItems = computed<NavItem[]>(() =>
    [
        { title: 'Dashboard', href: dashboard(), icon: LayoutGrid },
        can.value['posts.viewAny'] ? { title: 'Posts', href: posts(), icon: FileText } : null,
    ].filter((item): item is NavItem => item !== null),
);
```

> **Note:** Import `index` aliased (e.g. `posts`) to avoid name collisions with local variables.

> **Pitfall:** Do not use a static `const` array when items depend on permissions. `usePage()` props are reactive — a plain array is evaluated once at module load before the user data is available.

---

## Step 10 — Write Tests

```bash
php artisan make:test PostCrudTest --no-interaction
```

```php
public function test_index_requires_auth(): void
{
    $this->get(route('posts.index'))->assertRedirectToRoute('login');
}

public function test_authenticated_user_can_view_posts(): void
{
    $this->actingAs(User::factory()->create())
        ->get(route('posts.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('posts/Index'));
}

public function test_authenticated_user_can_create_a_post(): void
{
    $this->actingAs(User::factory()->create())
        ->post(route('posts.store'), ['title' => 'Hello', 'body' => 'World'])
        ->assertRedirectToRoute('posts.index');

    $this->assertDatabaseHas('posts', ['title' => 'Hello']);
}

public function test_authenticated_user_can_update_a_post(): void
{
    $post = Post::factory()->create();

    $this->actingAs(User::factory()->create())
        ->patch(route('posts.update', $post), ['title' => 'Updated'])
        ->assertRedirect();

    $this->assertDatabaseHas('posts', ['id' => $post->id, 'title' => 'Updated']);
}

public function test_authenticated_user_can_delete_a_post(): void
{
    $post = Post::factory()->create();

    $this->actingAs(User::factory()->create())
        ->delete(route('posts.destroy', $post))
        ->assertRedirectToRoute('posts.index');

    $this->assertDatabaseMissing('posts', ['id' => $post->id]);
}

// Policy tests
public function test_non_owner_cannot_update_post(): void
{
    $post = Post::factory()->for(User::factory()->create())->create();

    $this->actingAs(User::factory()->create())
        ->patch(route('posts.update', $post), ['title' => 'Hacked'])
        ->assertForbidden();
}
```

```bash
php artisan test --compact tests/Feature/PostCrudTest.php
```

---

## Optional — Layer Architecture (Service + Repository)

Add this layer when a resource has non-trivial business logic or needs to be reusable across multiple controllers (e.g. API + web). For simple CRUD the controller calling Eloquent directly (Step 5) is fine.

### Directory layout

```
app/
├── Repositories/
│   └── PostRepository.php
└── Services/
    └── PostService.php
```

### Repository

```php
<?php

// app/Repositories/PostRepository.php

namespace App\Repositories;

use App\Models\Post;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class PostRepository
{
    public function paginate(string $search, string $sort, string $direction, int $perPage = 15): LengthAwarePaginator
    {
        return Post::globalSearch($search)
            ->sorting($sort, $direction)
            ->paginate($perPage)
            ->withQueryString();
    }

    public function create(array $data): Post
    {
        return Post::create($data);
    }

    public function update(Post $post, array $data): Post
    {
        $post->update($data);

        return $post;
    }

    public function delete(Post $post): void
    {
        $post->delete();
    }
}
```

### Service

The service owns business logic. For basic CRUD it delegates to the repository — it will grow as rules are added without touching the controller.

```php
<?php

// app/Services/PostService.php

namespace App\Services;

use App\Models\Post;
use App\Repositories\PostRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class PostService
{
    public function __construct(
        private readonly PostRepository $repository,
    ) {}

    public function list(string $search, string $sort, string $direction): LengthAwarePaginator
    {
        return $this->repository->paginate($search, $sort, $direction);
    }

    public function create(array $data): Post
    {
        return $this->repository->create($data);
    }

    public function update(Post $post, array $data): Post
    {
        return $this->repository->update($post, $data);
    }

    public function delete(Post $post): void
    {
        $this->repository->delete($post);
    }
}
```

Laravel's container auto-resolves concrete classes — no binding in `AppServiceProvider` is needed.

### Controller — inject the service

```php
use App\Services\PostService;

class PostController extends Controller
{
    public function __construct(private readonly PostService $service) {}

    public function index(Request $request): Response
    {
        $search    = $request->string('search');
        $sort      = $request->string('sort', 'created_at');
        $direction = $request->string('direction', 'desc');

        return Inertia::render('posts/Index', [
            'posts'   => $this->service->list($search, $sort, $direction),
            'filters' => [
                'search'    => $search,
                'sort'      => $sort,
                'direction' => $direction,
            ],
        ]);
    }

    public function store(PostRequest $request): RedirectResponse
    {
        $this->service->create($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Post created.']);

        return to_route('posts.index');
    }

    public function update(PostRequest $request, Post $post): RedirectResponse
    {
        $this->service->update($post, $request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Post updated.']);

        return to_route('posts.show', ['post' => $post]);
    }

    public function destroy(Post $post): RedirectResponse
    {
        $this->service->delete($post);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Post deleted.']);

        return to_route('posts.index');
    }
}
```

The `index()`, `create()`, `show()`, and `edit()` read-only methods stay the same as Step 5 — they can call `$this->service->list()` (as above) or stay as inline Eloquent if no business logic is involved.

> **Pitfall:** The repository must call `->withQueryString()` on the paginator, not the controller. If the call moves outside the repository the filter params are dropped from pagination links.

---

## Summary

| Step | What you create |
|------|----------------|
| 1 | `PostController` (resource stub) |
| 2 | `Post` model + migration |
| 3 | `PostRequest` — remember to fix `authorize()` |
| 4 | Route in `web.php` |
| 5 | Controller logic — use `PostRequest`, not `Request` |
| 6 | `npm run build` → Wayfinder types |
| 7 | Vue pages: `Index`, `Show`, `Create`, `Edit` |
| 7.5 | `HasTableFilters` trait + `$filterable`/`$sortable` on model + search & sort in `Index.vue` |
| 8 | `PostPolicy` |
| 9 | Sidebar link |
| 10 | Feature tests |

## Common Pitfalls

| Pitfall | Fix |
|---------|-----|
| `authorize()` returns `false` in generated stub | Change to `true` or a policy check |
| `$this->authorize()` throws "method not found" | Base `Controller` has no parent — use `Gate::authorize()` instead |
| `$request->validated()` on plain `Request` | Type-hint `PostRequest` on `store`/`update` |
| `can('edit', $post)` doesn't match policy | Use `can('update', $post)` — matches `PostPolicy::update()` |
| Content has no padding from edges | Wrap page content in `<div class="flex h-full flex-1 flex-col gap-4 p-4">` |
| `flex gap-2` on `<TableCell>` does nothing | Wrap buttons in `<div class="flex gap-2">` inside the cell |
| `props` referenced inside `defineOptions` | Use `setLayoutProps()` from `@inertiajs/vue3` for dynamic breadcrumbs |
| Pagination links drop search/sort params | Call `->withQueryString()` on the paginator |
| Default sort column not in `$sortable` | `scopeSorting` silently skips `orderBy` — ensure default sort key is in the array |
| Empty search string sent as `?search=` | Pass `search: value \|\| undefined` so the key is omitted when blank |
| Keystrokes pollute browser history | Use `replace: true` on the search `router.get()` call only |
| Sidebar item always visible regardless of permission | Share `can` in `HandleInertiaRequests` and use a `computed` array in `AppSidebar.vue` |
| `can` is `null` for unauthenticated requests | Use `?->can(...) ?? false` — never leave it as `null` |
