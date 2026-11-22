<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import { ImagePlus } from '@lucide/vue';
import { ref } from 'vue';
import { toast } from 'vue-sonner';
import ConfirmDelete from '@/components/admin/ConfirmDelete.vue';
import ToggleField from '@/components/admin/ToggleField.vue';
import { Input } from '@/components/ui/input';
import { destroy, store, update } from '@/routes/admin/photos';
import type { Photo } from '@/types';

const props = defineProps<{
    photos: Photo[];
    type?: 'dog' | 'litter' | 'puppy';
    id?: number;
    showGalleryToggle?: boolean;
    /** Upload into this album (photo library only). */
    albumId?: number | null;
    /** Offer a "Show on the website" choice for new uploads, starting off. */
    askPublish?: boolean;
    /** Hide the photo grid; the parent draws its own. */
    uploadOnly?: boolean;
}>();

const uploading = ref(false);
const dragging = ref(false);
const credit = ref('');
const publish = ref(!props.askPublish);

const BATCH = 5;
const progress = ref('');

/** Upload in small batches so big phone photos never exceed the server's limits. */
async function upload(files: FileList | File[] | null) {
    if (!files || !files.length) {
        return;
    }

    const all = Array.from(files);
    uploading.value = true;
    let failed = false;

    for (let i = 0; i < all.length && !failed; i += BATCH) {
        const batch = all.slice(i, i + BATCH);
        progress.value = `${Math.min(i + batch.length, all.length)} of ${all.length}`;

        await new Promise<void>((resolve) => {
            router.post(
                store.url(),
                {
                    photos: batch,
                    photoable_type: props.type,
                    photoable_id: props.id,
                    credit: credit.value || null,
                    album_id: props.albumId ?? null,
                    is_public: publish.value,
                },
                {
                    forceFormData: true,
                    preserveScroll: true,
                    onError: (errors) => {
                        failed = true;
                        toast.error(
                            Object.values(errors)[0] ??
                                'That upload did not work. The photos may be too large for this server.',
                        );
                    },
                    onFinish: () => resolve(),
                },
            );
        });
    }

    uploading.value = false;
    progress.value = '';
}

function save(photo: Photo, patch: Partial<Photo>) {
    router.put(
        update.url(photo.id),
        {
            caption: photo.caption,
            credit: photo.credit,
            is_public: photo.is_public,
            in_gallery: photo.in_gallery,
            ...patch,
        },
        { preserveScroll: true, preserveState: true },
    );
}

function remove(photo: Photo) {
    router.delete(destroy.url(photo.id), { preserveScroll: true });
}
</script>

<template>
    <div class="space-y-4">
        <label
            :class="[
                'flex cursor-pointer flex-col items-center gap-1 rounded-xl border-2 border-dashed px-4 py-8 text-center text-sm transition-colors',
                dragging ? 'border-primary bg-accent' : 'hover:bg-muted/60',
            ]"
            @dragover.prevent="dragging = true"
            @dragleave.prevent="dragging = false"
            @drop.prevent="
                dragging = false;
                upload($event.dataTransfer?.files ?? null);
            "
        >
            <ImagePlus class="size-6 text-muted-foreground" />
            <span class="font-medium">
                {{
                    uploading
                        ? `Uploading ${progress}…`
                        : 'Tap to add photos, or drop them here'
                }}
            </span>
            <span class="text-xs text-muted-foreground">
                Photos from your phone are fine; they are resized automatically.
            </span>
            <input
                type="file"
                class="sr-only"
                accept="image/*"
                multiple
                :disabled="uploading"
                @change="
                    upload(($event.target as HTMLInputElement).files);
                    ($event.target as HTMLInputElement).value = '';
                "
            />
        </label>

        <ToggleField
            v-if="askPublish"
            v-model="publish"
            label="Show these on the website"
            hint="Off by default: they are kept in your library and visitors cannot see them."
        />
        <Input
            v-model="credit"
            placeholder="Photo credit for this upload (optional, e.g. 'Photo: Jane Smith')"
        />

        <ul
            v-if="!uploadOnly && photos.length"
            class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3"
        >
            <li
                v-for="photo in photos"
                :key="photo.id"
                class="overflow-hidden rounded-xl border bg-card"
            >
                <img
                    :src="photo.thumb_url"
                    :alt="photo.caption ?? ''"
                    class="aspect-[4/3] w-full object-cover"
                    loading="lazy"
                />
                <div class="space-y-2 p-3">
                    <Input
                        v-model="photo.caption"
                        placeholder="Caption"
                        @change="save(photo, { caption: photo.caption })"
                    />
                    <Input
                        v-model="photo.credit"
                        placeholder="Credit (other people's photos)"
                        @change="save(photo, { credit: photo.credit })"
                    />
                    <div class="flex items-center justify-between gap-2">
                        <div class="space-y-1.5">
                            <ToggleField
                                :model-value="photo.is_public"
                                label="Show on the website"
                                @update:model-value="
                                    save(photo, { is_public: $event })
                                "
                            />
                            <ToggleField
                                v-if="showGalleryToggle !== false"
                                :model-value="photo.in_gallery"
                                label="In gallery"
                                @update:model-value="
                                    save(photo, { in_gallery: $event })
                                "
                            />
                        </div>
                        <ConfirmDelete
                            icon
                            message="Delete this photo for good?"
                            @confirm="remove(photo)"
                        />
                    </div>
                </div>
            </li>
        </ul>
    </div>
</template>
