import { ref } from 'vue';

const G_PER_OZ = 28.3495;

function stored(): 'oz' | 'g' {
    try {
        return localStorage.getItem('weight_unit') === 'g' ? 'g' : 'oz';
    } catch {
        return 'oz';
    }
}

// One shared choice for the whole admin: weights are stored in grams, shown in ounces by default.
const unit = ref<'oz' | 'g'>(stored());

export function useWeightUnit() {
    function setUnit(u: 'oz' | 'g'): void {
        unit.value = u;

        try {
            localStorage.setItem('weight_unit', u);
        } catch {
            // ignore: the choice just will not be remembered
        }
    }

    /** Grams (as stored) to the number shown in the chosen unit. */
    const toDisplay = (g: number | null | undefined): string =>
        g == null
            ? ''
            : unit.value === 'oz'
              ? (g / G_PER_OZ).toFixed(1)
              : String(g);

    /** What was typed, in the chosen unit, back to whole grams. */
    const toGrams = (
        value: string | number | null | undefined,
    ): number | null => {
        const n = parseFloat(String(value ?? ''));

        return Number.isNaN(n) || n <= 0
            ? null
            : Math.round(unit.value === 'oz' ? n * G_PER_OZ : n);
    };

    return { unit, setUnit, toDisplay, toGrams };
}
