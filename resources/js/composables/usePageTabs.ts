import { onMounted, ref } from 'vue';

/** The current tab, starting from the page address (#health) when it names one. */
export function usePageTabs<K extends string>(keys: readonly K[], first: K) {
    const tab = ref<K>(first);

    onMounted(() => {
        const h = location.hash.slice(1) as K;

        if (keys.includes(h)) {
            tab.value = h;
        }
    });

    return tab;
}
