import { router } from '@inertiajs/vue3';
import { onBeforeUnmount } from 'vue';

/** Ask before leaving a page with edits that were not saved. */
export function useUnsavedWarning(isDirty: () => boolean): void {
    const stop = router.on('before', (event) => {
        const visit = event.detail.visit;
        const leaving =
            visit.method === 'get' &&
            !visit.only.length &&
            visit.url.pathname !== window.location.pathname;

        if (
            leaving &&
            isDirty() &&
            !window.confirm(
                'You have changes that are not saved yet. Leave this page without saving?',
            )
        ) {
            event.preventDefault();
        }
    });

    const onUnload = (e: BeforeUnloadEvent) => {
        if (isDirty()) {
            e.preventDefault();
        }
    };

    window.addEventListener('beforeunload', onUnload);

    onBeforeUnmount(() => {
        stop();
        window.removeEventListener('beforeunload', onUnload);
    });
}
