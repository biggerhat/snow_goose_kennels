<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { FolderPlus, Globe, Lock, Pencil } from '@lucide/vue';
import { computed, reactive, ref, watch } from 'vue';
import ConfirmDelete from '@/components/admin/ConfirmDelete.vue';
import ConfirmDialog from '@/components/admin/ConfirmDialog.vue';
import EmptyState from '@/components/admin/EmptyState.vue';
import NativeSelect from '@/components/admin/NativeSelect.vue';
import PageHeader from '@/components/admin/PageHeader.vue';
import PhotoManager from '@/components/admin/PhotoManager.vue';
import ToggleField from '@/components/admin/ToggleField.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import {
    store as storeAlbum,
    update as updateAlbum,
    destroy as destroyAlbum,
} from '@/routes/admin/albums';
import { bulk, index, update } from '@/routes/admin/photos';
import type { Album, Photo } from '@/types';

defineOptions({
    layout: { breadcrumbs: [{ title: 'Photos', href: index() }] },
});

type Page = {
    data: Photo[];
    current_page: number;
    last_page: number;
    total: number;
    prev_page_url: string | null;
    next_page_url: string | null;
};

const props = defineProps<{
    photos: Page;
    albums: Album[];
    filters: {
        album: string | null;
        show: string | null;
        subject: string | null;
        q: string;
    };
    totals: { all: number; unsorted: number; public: number; private: number };
}>();

// ---- filters -------------------------------------------------------------

const query = reactive<{
    q: string;
    show: string | null;
    subject: string | null;
}>({
    q: props.filters.q,
    show: props.filters.show,
    subject: props.filters.subject,
});

function go(extra: Record<string, string | null> = {}) {
    const params: Record<string, string> = {};
    const merged = {
        album: props.filters.album,
        q: query.q,
        show: query.show,
        subject: query.subject,
        ...extra,
    };

    for (const [k, v] of Object.entries(merged)) {
        if (v) {
            params[k] = v;
        }
    }

    router.get(index.url(), params, { preserveState: true, replace: true });
}

let timer: ReturnType<typeof setTimeout> | undefined;
watch(
    () => query.q,
    () => {
        clearTimeout(timer);
        timer = setTimeout(() => go(), 350);
    },
);

const currentAlbum = computed(() =>
    props.albums.find((a) => String(a.id) === props.filters.album),
);

const albumOptions = computed(() =>
    props.albums.map((a) => ({ value: String(a.id), label: a.name })),
);

const hasFilters = computed(() => !!(query.q || query.show || query.subject));

function clearFilters() {
    query.q = '';
    query.show = null;
    query.subject = null;
    go({ q: null, show: null, subject: null });
}

// ---- albums --------------------------------------------------------------

const newAlbum = ref('');
const creating = ref(false);

function createAlbum() {
    if (!newAlbum.value.trim()) {
        return;
    }

    router.post(
        storeAlbum.url(),
        { name: newAlbum.value.trim() },
        {
            preserveScroll: true,
            onSuccess: () => {
                newAlbum.value = '';
                creating.value = false;
            },
        },
    );
}

const editing = ref(false);
const albumForm = reactive({ name: '', description: '' });

function startEdit() {
    albumForm.name = currentAlbum.value?.name ?? '';
    albumForm.description = currentAlbum.value?.description ?? '';
    editing.value = true;
}

function saveAlbum() {
    if (!currentAlbum.value) {
        return;
    }

    router.put(
        updateAlbum.url(currentAlbum.value.id),
        { ...albumForm },
        {
            preserveScroll: true,
            onSuccess: () => (editing.value = false),
        },
    );
}

function deleteAlbum() {
    if (currentAlbum.value) {
        router.delete(destroyAlbum.url(currentAlbum.value.id));
    }
}

// ---- selection and bulk actions -------------------------------------------

const selected = ref<number[]>([]);
const moveTo = ref<string | null>(null);

watch(
    () => props.photos.data,
    () => (selected.value = []),
);

const allSelected = computed(
    () =>
        props.photos.data.length > 0 &&
        selected.value.length === props.photos.data.length,
);

function toggleAll() {
    selected.value = allSelected.value
        ? []
        : props.photos.data.map((p) => p.id);
}

function bulkDo(action: 'move' | 'show' | 'hide' | 'delete') {
    router.post(
        bulk.url(),
        {
            ids: selected.value,
            action,
            album_id:
                action === 'move' && moveTo.value ? Number(moveTo.value) : null,
        },
        { preserveScroll: true, onSuccess: () => (selected.value = []) },
    );
}

const confirmDelete = ref(false);
function removeSelected() {
    confirmDelete.value = true;
}

// ---- single photo edits ------------------------------------------------------

function save(photo: Photo, patch: Partial<Photo>) {
    router.put(
        update.url(photo.id),
        {
            caption: photo.caption,
            credit: photo.credit,
            taken_on: photo.taken_on,
            album_id: photo.album_id ?? null,
            is_public: photo.is_public,
            in_gallery: photo.in_gallery,
            ...patch,
        },
        { preserveScroll: true, preserveState: true },
    );
}

function albumName(id?: number | null) {
    return props.albums.find((a) => a.id === id)?.name ?? null;
}

const subjectLabel: Record<string, string> = {
    dog: 'Dog',
    litter: 'Litter',
    puppy: 'Puppy',
};
</script>

<template>
    <Head title="Photos" />

    <div class="max-w-6xl space-y-6 p-4 md:p-6">
        <PageHeader
            title="Photo library"
            description="Your private photo box. Keep everything here, sort it into albums, and choose which photos visitors can see."
            :help="[
                'This is where all your photos live. A photo is private until you choose Show on the website, so you can keep vet photos, records and family pictures here safely.',
                'Tap the box to add photos from your phone or computer. You can add many at once. They are shrunk automatically so the website stays fast.',
                'Albums are folders for you. Make one like Vet visits, then pick it when you add photos or move photos into it. Visitors never see albums.',
                'Tick the little box on several photos to move them, show them on the website, make them private or delete them all at once.',
                'To show a photo on a particular dog or litter page, add it from that dog’s or litter’s own page.',
            ]"
            help-title="The photo library"
            help-topic="Add photos"
        />

        <!-- Totals -->
        <dl class="grid grid-cols-2 gap-3 sm:grid-cols-4">
            <div class="rounded-xl border bg-card p-3">
                <dt class="text-xs text-muted-foreground">All photos</dt>
                <dd class="font-serif text-2xl font-semibold">
                    {{ totals.all }}
                </dd>
            </div>
            <div class="rounded-xl border bg-card p-3">
                <dt class="text-xs text-muted-foreground">On the website</dt>
                <dd class="font-serif text-2xl font-semibold">
                    {{ totals.public }}
                </dd>
            </div>
            <div class="rounded-xl border bg-card p-3">
                <dt class="text-xs text-muted-foreground">Private</dt>
                <dd class="font-serif text-2xl font-semibold">
                    {{ totals.private }}
                </dd>
            </div>
            <div class="rounded-xl border bg-card p-3">
                <dt class="text-xs text-muted-foreground">Not in an album</dt>
                <dd class="font-serif text-2xl font-semibold">
                    {{ totals.unsorted }}
                </dd>
            </div>
        </dl>

        <!-- Albums -->
        <nav aria-label="Albums" class="flex flex-wrap items-center gap-2">
            <Link
                :href="index.url()"
                preserve-scroll
                :class="[
                    'rounded-full border px-3.5 py-1.5 text-sm transition-colors',
                    !filters.album
                        ? 'border-foreground bg-card font-medium'
                        : 'hover:bg-muted',
                ]"
                >All photos
                <span class="text-muted-foreground">{{
                    totals.all
                }}</span></Link
            >
            <Link
                :href="index.url({ query: { album: 'none' } })"
                preserve-scroll
                :class="[
                    'rounded-full border px-3.5 py-1.5 text-sm transition-colors',
                    filters.album === 'none'
                        ? 'border-foreground bg-card font-medium'
                        : 'hover:bg-muted',
                ]"
                >Not in an album
                <span class="text-muted-foreground">{{
                    totals.unsorted
                }}</span></Link
            >
            <Link
                v-for="a in albums"
                :key="a.id"
                :href="index.url({ query: { album: String(a.id) } })"
                preserve-scroll
                :class="[
                    'rounded-full border px-3.5 py-1.5 text-sm transition-colors',
                    filters.album === String(a.id)
                        ? 'border-foreground bg-card font-medium'
                        : 'hover:bg-muted',
                ]"
                >{{ a.name }}
                <span class="text-muted-foreground">{{
                    a.photos_count
                }}</span></Link
            >

            <form
                v-if="creating"
                class="flex items-center gap-2"
                @submit.prevent="createAlbum"
            >
                <Input
                    v-model="newAlbum"
                    class="h-8 w-48"
                    placeholder="Album name"
                    autofocus
                />
                <Button type="submit" size="sm">Add</Button>
                <Button
                    type="button"
                    size="sm"
                    variant="ghost"
                    @click="creating = false"
                    >Cancel</Button
                >
            </form>
            <Button
                v-else
                type="button"
                size="sm"
                variant="outline"
                @click="creating = true"
            >
                <FolderPlus class="size-4" /> New album
            </Button>
        </nav>

        <!-- Current album header -->
        <section
            v-if="currentAlbum"
            class="space-y-3 rounded-xl border bg-card p-4"
        >
            <template v-if="!editing">
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div>
                        <h2 class="font-serif text-xl font-semibold">
                            {{ currentAlbum.name }}
                        </h2>
                        <p
                            v-if="currentAlbum.description"
                            class="text-sm text-muted-foreground"
                        >
                            {{ currentAlbum.description }}
                        </p>
                        <p class="mt-1 text-xs text-muted-foreground">
                            Albums are private. They only help you stay
                            organised.
                        </p>
                    </div>
                    <div class="flex gap-1">
                        <Button
                            type="button"
                            size="sm"
                            variant="outline"
                            @click="startEdit"
                        >
                            <Pencil class="size-4" /> Rename
                        </Button>
                        <ConfirmDelete
                            label="Delete album"
                            message="Delete this album? The photos stay in your library."
                            @confirm="deleteAlbum"
                        />
                    </div>
                </div>
            </template>
            <form v-else class="space-y-2" @submit.prevent="saveAlbum">
                <Input
                    v-model="albumForm.name"
                    placeholder="Album name"
                    aria-label="Album name"
                />
                <Input
                    v-model="albumForm.description"
                    placeholder="Notes (optional)"
                />
                <div class="flex gap-2">
                    <Button type="submit" size="sm">Save</Button>
                    <Button
                        type="button"
                        size="sm"
                        variant="ghost"
                        @click="editing = false"
                        >Cancel</Button
                    >
                </div>
            </form>
        </section>

        <!-- Upload -->
        <PhotoManager
            :photos="[]"
            upload-only
            ask-publish
            :album-id="currentAlbum?.id ?? null"
        />

        <!-- Filters -->
        <div class="flex flex-wrap items-center gap-2">
            <Input
                v-model="query.q"
                class="w-full sm:w-64"
                placeholder="Search captions and credits"
                aria-label="Search captions and credits"
            />
            <div class="w-full sm:w-44">
                <NativeSelect
                    v-model="query.show"
                    :options="[
                        { value: 'public', label: 'On the website' },
                        { value: 'private', label: 'Private' },
                    ]"
                    placeholder="Website: any"
                    aria-label="Filter by website visibility"
                    @update:model-value="go()"
                />
            </div>
            <div class="w-full sm:w-44">
                <NativeSelect
                    v-model="query.subject"
                    :options="[
                        { value: 'dog', label: 'A dog' },
                        { value: 'litter', label: 'A litter' },
                        { value: 'puppy', label: 'A puppy' },
                        { value: 'none', label: 'Nothing (loose)' },
                    ]"
                    placeholder="Linked to: anything"
                    aria-label="Filter by what the photo is linked to"
                    @update:model-value="go()"
                />
            </div>
            <Button
                v-if="hasFilters"
                type="button"
                size="sm"
                variant="ghost"
                @click="clearFilters"
            >
                Clear filters
            </Button>
        </div>

        <!-- Bulk bar -->
        <div
            v-if="photos.data.length"
            class="sticky top-2 z-10 flex flex-wrap items-center gap-2 rounded-xl border bg-card/95 p-2.5 shadow-sm backdrop-blur"
        >
            <label class="flex items-center gap-2 px-1 text-sm">
                <input
                    type="checkbox"
                    class="size-4 accent-primary"
                    :checked="allSelected"
                    @change="toggleAll"
                />
                {{
                    selected.length
                        ? `${selected.length} selected`
                        : 'Select all on this page'
                }}
            </label>
            <template v-if="selected.length">
                <div class="w-44">
                    <NativeSelect
                        v-model="moveTo"
                        :options="albumOptions"
                        placeholder="No album"
                        aria-label="Move selected photos to album"
                    />
                </div>
                <Button
                    type="button"
                    size="sm"
                    variant="outline"
                    @click="bulkDo('move')"
                    >Move</Button
                >
                <Button
                    type="button"
                    size="sm"
                    variant="outline"
                    @click="bulkDo('show')"
                >
                    <Globe class="size-4" /> Show on the website
                </Button>
                <Button
                    type="button"
                    size="sm"
                    variant="outline"
                    @click="bulkDo('hide')"
                >
                    <Lock class="size-4" /> Make private
                </Button>
                <Button
                    type="button"
                    size="sm"
                    variant="ghost"
                    class="text-destructive"
                    @click="removeSelected"
                >
                    Delete
                </Button>
            </template>
        </div>

        <ConfirmDialog
            v-model:open="confirmDelete"
            :message="`Delete ${selected.length} photo(s) for good? This cannot be undone.`"
            confirm-label="Delete photos"
            @confirm="bulkDo('delete')"
        />

        <!-- Grid -->
        <ul
            v-if="photos.data.length"
            class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3"
        >
            <li
                v-for="photo in photos.data"
                :key="photo.id"
                :class="[
                    'overflow-hidden rounded-xl border bg-card',
                    selected.includes(photo.id) && 'ring-2 ring-primary',
                ]"
            >
                <div class="relative">
                    <img
                        :src="photo.thumb_url"
                        :alt="photo.caption ?? ''"
                        class="aspect-[4/3] w-full object-cover"
                        loading="lazy"
                    />
                    <input
                        v-model="selected"
                        type="checkbox"
                        :value="photo.id"
                        class="absolute top-2 left-2 size-5 accent-primary"
                        :aria-label="`Select photo ${photo.caption ?? photo.id}`"
                    />
                    <span
                        :class="[
                            'absolute top-2 right-2 flex items-center gap-1 rounded-full px-2 py-0.5 text-xs font-medium',
                            photo.is_public
                                ? 'bg-primary text-primary-foreground'
                                : 'bg-background/90 text-muted-foreground',
                        ]"
                    >
                        <component
                            :is="photo.is_public ? Globe : Lock"
                            class="size-3"
                        />
                        {{ photo.is_public ? 'On website' : 'Private' }}
                    </span>
                </div>
                <div class="space-y-2 p-3">
                    <p
                        v-if="photo.subject || photo.album_id"
                        class="flex flex-wrap gap-1.5 text-xs text-muted-foreground"
                    >
                        <span
                            v-if="photo.subject"
                            class="rounded bg-muted px-1.5 py-0.5"
                        >
                            {{
                                subjectLabel[photo.photoable_type ?? ''] ?? ''
                            }}: {{ photo.subject }}
                        </span>
                        <span
                            v-if="albumName(photo.album_id)"
                            class="rounded bg-muted px-1.5 py-0.5"
                        >
                            {{ albumName(photo.album_id) }}
                        </span>
                    </p>
                    <Input
                        v-model="photo.caption"
                        placeholder="Caption"
                        @change="save(photo, { caption: photo.caption })"
                    />
                    <details class="group text-sm">
                        <summary
                            class="cursor-pointer text-muted-foreground select-none hover:text-foreground"
                        >
                            More details
                        </summary>
                        <div class="mt-2 space-y-2">
                            <Input
                                v-model="photo.credit"
                                placeholder="Credit (other people's photos)"
                                @change="save(photo, { credit: photo.credit })"
                            />
                            <label class="block text-xs text-muted-foreground">
                                Date taken
                                <Input
                                    v-model="photo.taken_on"
                                    type="date"
                                    @change="
                                        save(photo, {
                                            taken_on: photo.taken_on || null,
                                        })
                                    "
                                />
                            </label>
                            <label class="block text-xs text-muted-foreground">
                                Album
                                <NativeSelect
                                    :model-value="
                                        photo.album_id
                                            ? String(photo.album_id)
                                            : null
                                    "
                                    :options="albumOptions"
                                    placeholder="No album"
                                    aria-label="Album"
                                    @update:model-value="
                                        (v) => {
                                            photo.album_id = v
                                                ? Number(v)
                                                : null;
                                            save(photo, {
                                                album_id: photo.album_id,
                                            });
                                        }
                                    "
                                />
                            </label>
                            <ToggleField
                                :model-value="photo.is_public"
                                label="Show on the website"
                                @update:model-value="
                                    save(photo, { is_public: $event })
                                "
                            />
                            <ToggleField
                                :model-value="photo.in_gallery"
                                label="In the public gallery"
                                hint="Only matters when the photo is shown on the website."
                                @update:model-value="
                                    save(photo, { in_gallery: $event })
                                "
                            />
                        </div>
                    </details>
                </div>
            </li>
        </ul>
        <EmptyState
            v-else
            :title="
                hasFilters || filters.album
                    ? 'No photos match'
                    : 'No photos yet'
            "
            :description="
                hasFilters || filters.album
                    ? 'Try clearing the filters, or pick another album.'
                    : 'Add your first photos above. They stay private until you choose to show them.'
            "
        />

        <div
            v-if="photos.last_page > 1"
            class="flex items-center justify-between text-sm"
        >
            <Button
                v-if="photos.prev_page_url"
                as-child
                variant="outline"
                size="sm"
            >
                <Link :href="photos.prev_page_url" preserve-scroll
                    >← Newer</Link
                >
            </Button>
            <span v-else />
            <span class="text-muted-foreground"
                >Page {{ photos.current_page }} of {{ photos.last_page }}</span
            >
            <Button
                v-if="photos.next_page_url"
                as-child
                variant="outline"
                size="sm"
            >
                <Link :href="photos.next_page_url" preserve-scroll
                    >Older →</Link
                >
            </Button>
            <span v-else />
        </div>
    </div>
</template>
