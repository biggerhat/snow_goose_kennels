<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { computed } from 'vue';
import { edit } from '@/routes/admin/dogs';

export type PedigreeNode = {
    name: string;
    registered_name: string | null;
    titles: string | null;
    color: string | null;
    sex: string;
    slug: string | null;
    admin_slug?: string;
    registry_number?: string | null;
    is_ours?: boolean;
    sire?: PedigreeNode | null;
    dam?: PedigreeNode | null;
};

const props = withDefaults(
    defineProps<{
        tree: PedigreeNode | null;
        generations?: number;
        /** Link names to the admin dog page instead of the public one. */
        admin?: boolean;
    }>(),
    { generations: 3, admin: false },
);

type Cell = {
    node: PedigreeNode | null;
    col: number;
    row: number;
    span: number;
    label: string;
};

const rows = computed(() => 2 ** props.generations);

const cells = computed<Cell[]>(() => {
    const out: Cell[] = [];

    const walk = (
        node: PedigreeNode | null | undefined,
        gen: number,
        index: number,
        label: string,
    ) => {
        const span = rows.value / 2 ** gen;
        out.push({
            node: node ?? null,
            col: gen + 1,
            row: index * span + 1,
            span,
            label,
        });

        if (gen < props.generations) {
            walk(node?.sire, gen + 1, index * 2, 'Sire');
            walk(node?.dam, gen + 1, index * 2 + 1, 'Dam');
        }
    };

    walk(props.tree, 0, 0, 'Dog');

    return out;
});

function href(n: PedigreeNode): string | null {
    if (props.admin) {
        return n.admin_slug ? edit.url(n.admin_slug) : null;
    }

    return n.slug ? `/our-dogs/${n.slug}` : null;
}
</script>

<template>
    <div class="overflow-x-auto pb-2">
        <div
            class="grid min-w-[44rem] gap-1.5"
            :style="{
                gridTemplateColumns: `repeat(${generations + 1}, minmax(0, 1fr))`,
                gridTemplateRows: `repeat(${rows}, minmax(2.75rem, auto))`,
            }"
            role="table"
            aria-label="Family tree"
        >
            <div
                v-for="(c, i) in cells"
                :key="i"
                role="cell"
                class="flex flex-col justify-center rounded-md border px-2.5 py-1 text-xs leading-tight"
                :class="
                    c.node
                        ? 'bg-card'
                        : 'border-dashed text-muted-foreground/60'
                "
                :style="{
                    gridColumn: c.col,
                    gridRow: `${c.row} / span ${c.span}`,
                }"
            >
                <template v-if="c.node">
                    <component
                        :is="href(c.node) ? Link : 'span'"
                        :href="href(c.node) ?? undefined"
                        class="text-sm font-semibold"
                        :class="
                            href(c.node) && 'underline-offset-2 hover:underline'
                        "
                    >
                        {{ c.node.name }}
                    </component>
                    <span
                        v-if="c.node.registered_name"
                        class="truncate text-muted-foreground italic"
                        >{{ c.node.registered_name }}</span
                    >
                    <span v-if="c.node.titles" class="text-muted-foreground">{{
                        c.node.titles
                    }}</span>
                    <span
                        v-if="admin && c.node.registry_number"
                        class="text-muted-foreground"
                        >{{ c.node.registry_number }}</span
                    >
                    <span
                        v-if="admin && c.node.is_ours === false"
                        class="text-[10px] tracking-wide text-muted-foreground uppercase"
                        >Another kennel</span
                    >
                </template>
                <span v-else>{{
                    c.label === 'Dog' ? '' : `${c.label} unknown`
                }}</span>
            </div>
        </div>
    </div>
</template>
