<script setup lang="ts">
import Empty from '@/components/core/util/Empty.vue';
import { useShared } from '@/composables/shared/useShared';
import { getIdParams } from '@/lib/utils';
import { FlexRender, type Table } from '@tanstack/vue-table';
import { ref, watchEffect } from 'vue';
import draggable from 'vuedraggable';

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

const onMove = (evt: any) => {
    const draggedElement = evt.draggedContext?.element;
    const isDeleted = !!draggedElement?.attributes?.deletedAt;
    return !isDeleted; // allow move only if not deleted
};
const onChange = (evt: any) => {
    const moved = evt?.moved;
    if (!moved) return;
    const { element, newIndex } = moved;
    const isDeleted = !!element.attributes?.deletedAt;
    if (isDeleted) return;
    // get the position of the new index
    movePosition(route(props.draggableUpdateUrl ?? '', getIdParams(element.id.toString())), newIndex + 1);
};
</script>

<template>
    <tr v-if="table.getRowModel().rows.length == 0">
        <td :colspan="table.getFlatHeaders().length" class="whitespace-nowrap">
            <div class="flex w-full flex-col items-center px-3 pt-6 italic">
                <Empty v-if="table.getRowModel().rows.length == 0" />
            </div>
        </td>
    </tr>
    <draggable v-if="dragItems" class="hava-tbody" tag="tbody" v-model="draggableRows" item-key="id" @change="onChange" :move="onMove">
        <template #item="{ element }">
            <tr
                class="hava-tr cursor-move"
                :class="`hava-tr cursor-move ${element.attributes?.deletedAt && 'hava-tr-highlight-archived'}`"
            >
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
            :class="`hava-tr ${row.original?.attributes?.deletedAt && 'hava-tr-highlight-archived'}`"
        >
            <td v-for="cell in row.getVisibleCells()" :key="cell.id" :align="cell.column.columnDef.meta?.align ?? 'left'" class="hava-td">
                <FlexRender :render="cell.column.columnDef.cell" :props="cell.getContext()" />
            </td>
        </tr>
    </tbody>
</template>
