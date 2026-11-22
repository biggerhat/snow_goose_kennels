import { router } from '@inertiajs/vue3';
import { toast } from 'vue-sonner';
import type { FlashToast } from '@/types/ui';

export function initializeFlashToast(): void {
    router.on('flash', (event) => {
        const flash = (event as CustomEvent).detail?.flash;
        const data = flash?.toast as FlashToast | undefined;

        if (!data) {
            return;
        }

        toast[data.type](data.message, {
            // Errors stay until dismissed; a phone user who looked away should not miss them.
            duration:
                data.type === 'error' ? Infinity : data.action ? 12000 : 4000,
            closeButton: data.type === 'error',
            action: data.action
                ? {
                      label: data.action.label,
                      onClick: () =>
                          router.post(
                              data.action!.url,
                              {},
                              { preserveScroll: true },
                          ),
                  }
                : undefined,
        });
    });
}
