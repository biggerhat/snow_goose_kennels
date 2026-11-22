import { usePage } from '@inertiajs/vue3';
import { nextTick, watch } from 'vue';
import { toast } from 'vue-sonner';

/**
 * When a form comes back with problems, say so out loud and scroll to the first one,
 * so nobody has to hunt for the red text on a long page.
 */
export function useErrorFocus(): void {
    const page = usePage();

    watch(
        () => page.props.errors as Record<string, string> | undefined,
        (errors) => {
            // Photo uploads report their own problems.
            const keys = Object.keys(errors ?? {}).filter(
                (k) => !k.startsWith('photos'),
            );

            if (!keys.length) {
                return;
            }

            toast.error(
                keys.length === 1
                    ? 'One thing needs fixing. It is marked in red.'
                    : `${keys.length} things need fixing. They are marked in red.`,
            );

            void nextTick(() => {
                document
                    .querySelector('[data-field-error]')
                    ?.scrollIntoView({ block: 'center', behavior: 'smooth' });
            });
        },
    );
}
