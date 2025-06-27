<script setup lang="ts">
import Empty from '@/components/core/util/Empty.vue';
import { FlexRender, type Table } from '@tanstack/vue-table';
import { ref, watchEffect } from 'vue';
import draggable from 'vuedraggable';
import { useShared } from '@/composables/shared/useShared';

interface Props {
    table: Table<any>;
    dragItems?: boolean;
    draggableUpdateUrl?: string;
}

const props = defineProps<Props>();
const { movePosition } = useShared();
// Local list of row data to support dragging
const draggableRows = ref<any[]>([]);

watchEffect(() => {
    if (props.dragItems) {
        draggableRows.value = props.table.getRowModel().rows.map((row) => row.original);
    }
});

// Helper to map original data back to full row
const getRowByOriginal = (original: any) => {
    return props.table.getRowModel().rows.find((r) => r.original.id === original.id);
};

function onChange(evt: any) {
    console.log(props.draggableUpdateUrl);
    const moved = evt?.moved;
    const {element} = moved;
    console.log('New order:', evt);
}
</script>

<template>
    <tr v-if="table.getRowModel().rows.length == 0">
        <td :colspan="table.getFlatHeaders().length" class="whitespace-nowrap">
            <div class="flex w-full flex-col items-center px-3 pt-6 italic">
                <Empty v-if="table.getRowModel().rows.length == 0" />
            </div>
        </td>
    </tr>
    <draggable v-if="dragItems" class="hava-tbody" tag="tbody" v-model="draggableRows" item-key="id" @change="onChange">
        <template #item="{ element }">
            <tr class="hava-tr cursor-move">
                <td
                    v-for="cell in getRowByOriginal(element)?.getVisibleCells() ?? []"
                    :key="cell.id"
                    :align="cell.column.columnDef.meta?.align ?? 'left'"
                    class="hava-td"
                >
                    <FlexRender :render="cell.column.columnDef.cell" :props="cell.getContext()" />
                </td>
            </tr>
        </template>
    </draggable>
    <tbody class="hava-tbody" v-else>
        <tr
            v-for="row in table.getRowModel().rows"
            :key="row.id"
            :class="`${row.original.attributes.deletedAt ? 'hava-tr hava-tr-highlight-archived' : 'hava-tr'}`"
        >
            <td v-for="cell in row.getVisibleCells()" :key="cell.id" :align="cell.column.columnDef.meta?.align ?? 'left'" class="hava-td">
                <FlexRender :render="cell.column.columnDef.cell" :props="cell.getContext()" />
            </td>
        </tr>
    </tbody>
</template>
