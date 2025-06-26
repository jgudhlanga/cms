<script setup lang="ts">
import PageContainer from '@/components/core/page/PageContainer.vue';
import type { Link } from '@/types/ui';
import { Head } from '@inertiajs/vue3';
import draggable from 'vuedraggable';
import {ref} from "vue"

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
</script>

<template>
    <Head :title="$t('trans.portal_setup')" />
    <PageContainer :breadcrumbs="breadcrumbs">
        <div class="row">
            <div class="col-8">
                <h3>Draggable table</h3>

                <table class="table-striped table">
                    <thead class="thead-dark">
                    <tr>
                        <th scope="col">Id</th>
                        <th scope="col">Name</th>
                        <th scope="col">Sport</th>
                    </tr>
                    </thead>
                    <draggable v-model="list" tag="tbody" item-key="id">
                        <template #item="{ element }">
                            <tr>
                                <td scope="row">{{ element.id }}</td>
                                <td>{{ element.name }}</td>
                                <td>{{ element.sport }}</td>
                            </tr>
                        </template>
                    </draggable>
                </table>
            </div>
        </div>
    </PageContainer>
</template>
