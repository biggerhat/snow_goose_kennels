import type { ComputedRef, InjectionKey } from 'vue';
import { inject } from 'vue';

/** A form field gives its label and its control the same id, so the two are linked for screen readers. */
export const fieldIdKey: InjectionKey<ComputedRef<string>> = Symbol('fieldId');

export function useFieldId() {
    return inject(fieldIdKey, null);
}
