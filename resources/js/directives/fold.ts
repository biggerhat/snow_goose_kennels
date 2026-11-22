import type { Directive } from 'vue';
import { reactive } from 'vue';

type FoldOptions = { id: string; label: string; open?: boolean };

/** The sections on the current page, in order. The jump bar reads this. */
export const pageSections = reactive<{ id: string; label: string }[]>([]);

const cleanups = new WeakMap<HTMLElement, () => void>();

/**
 * Turns a card on a long page into a foldable, linkable section:
 *   <section v-fold="{ id: 'papers', label: 'Papers', open: false }">
 * Tapping its heading folds it; the jump bar can scroll to it and open it.
 */
export const vFold: Directive<HTMLElement, FoldOptions> = {
    mounted(el, { value }) {
        el.id = value.id;
        el.classList.add('fold');
        pageSections.push({ id: value.id, label: value.label });

        const h2 = el.querySelector('h2');
        let head: HTMLElement | null = h2;

        while (head && head.parentElement !== el) {
            head = head.parentElement;
        }

        if (!head) {
            return;
        }

        head.classList.add('fold-head');

        // Only the heading itself is the button; the row around it may hold other buttons and links.
        const trigger: HTMLElement = h2 ?? head;
        trigger.setAttribute('role', 'button');
        trigger.tabIndex = 0;

        const set = (open: boolean) => {
            el.toggleAttribute('data-collapsed', !open);
            trigger.setAttribute('aria-expanded', String(open));
        };
        const toggle = () => set(el.hasAttribute('data-collapsed'));
        const onClick = (e: Event) => {
            const t = e.target as HTMLElement;

            // Let links, buttons and the help "?" inside the heading do their own job.
            if (t.closest('a, button, input, select, textarea') && t !== head) {
                return;
            }

            toggle();
        };
        const onKey = (e: KeyboardEvent) => {
            if ((e.key === 'Enter' || e.key === ' ') && e.target === trigger) {
                e.preventDefault();
                toggle();
            }
        };
        const onOpen = (e: Event) => {
            if ((e as CustomEvent<string>).detail === value.id) {
                set(true);
            }
        };

        set(value.open !== false);
        head.addEventListener('click', onClick);
        head.addEventListener('keydown', onKey);
        window.addEventListener('section:open', onOpen);

        cleanups.set(el, () => {
            head?.removeEventListener('click', onClick);
            head?.removeEventListener('keydown', onKey);
            window.removeEventListener('section:open', onOpen);
        });
    },
    unmounted(el, { value }) {
        cleanups.get(el)?.();
        const i = pageSections.findIndex((s) => s.id === value.id);

        if (i >= 0) {
            pageSections.splice(i, 1);
        }
    },
};

/** Scroll to a section, opening it first if it is folded. */
export function jumpTo(id: string): void {
    window.dispatchEvent(new CustomEvent('section:open', { detail: id }));
    document
        .getElementById(id)
        ?.scrollIntoView({ behavior: 'smooth', block: 'start' });
}
