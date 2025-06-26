<script setup lang="ts">
import PageContainer from '@/components/core/page/PageContainer.vue';
import type { Link } from '@/types/ui';
import { Head } from '@inertiajs/vue3';
import { ref } from 'vue';
import draggable from 'vuedraggable';

const breadcrumbs: Array<Link> = [
    {
        transChoiceKey: 'institution',
        href: route('institution.index'),
    },
    { transKey: 'portal_setup' },
];

const list = ref([
    { id: 1, name: 'Abby', sport: 'basket' },
    { id: 2, name: 'Brooke', sport: 'foot' },
    { id: 3, name: 'Courtenay', sport: 'volley' },
    { id: 4, name: 'David', sport: 'rugby' },
]);
const onChange = (e) => {
    console.log(e)
}
</script>

<template>
    <Head :title="$t('trans.portal_setup')" />
    <PageContainer :breadcrumbs="breadcrumbs">
        <div class="flex flex-col">
            <div class="flex w-full flex-col">
                <h3>Draggable table</h3>

                <table class="hava-table">
                    <thead class="hava-thead">
                        <tr>
                            <th class="hava-th" scope="col">Id</th>
                            <th class="hava-th" scope="col">Name</th>
                            <th class="hava-th" scope="col">Sport</th>
                        </tr>
                    </thead>
                    <draggable class="hava-tbody" v-model="list" tag="tbody" item-key="id" @change="onChange">
                        <template #item="{ element }">
                            <tr class="hava-tr cursor-move">
                                <td class="hava-td" scope="row">{{ element.id }}</td>
                                <td class="hava-td">{{ element.name }}</td>
                                <td class="hava-td">{{ element.sport }}</td>
                            </tr>
                        </template>
                    </draggable>
                </table>
            </div>
        </div>
    </PageContainer>
</template>
<style>
.flip-list-move {
    transition: transform 0.5s;
}

.no-move {
    transition: transform 0s;
}

.ghost {
    opacity: 0.5;
    background: #c8ebfb;
}

.list-group {
    min-height: 20px;
}

.list-group-item {
    cursor: move;
}

.list-group-item i {
    cursor: pointer;
}
</style>
