<script setup lang="ts">
import { GenericButton } from '@/components/core/button';
import PageContainer from '@/components/core/page/PageContainer.vue';
import DataTable from '@/components/core/table/DataTable.vue';
import { useStudentPortal } from '@/composables/portal/useStudentPortal';
import { useUtils } from '@/composables/core/useUtils';
import { ColorVariant } from '@/enums/colors';
import { IconName } from '@/enums/icons';
import { getIdParams } from '@/lib/utils';
import { AuthObject, DataFilters, DataListProps } from '@/types/data-pagination';
import { BreadcrumbItemInterface } from '@/types/ui';
import { User } from '@/types/users';
import { Head } from '@inertiajs/vue3';

interface Props {
    user: User;
    applications: DataListProps;
    trashedCount: any;
    filters: DataFilters;
    auth: AuthObject;
    errors: object;
}

const props = defineProps<Props>();
const { applicationsTable } = useStudentPortal();
const { navigateTo } = useUtils();
const { user } = props;

const breadcrumbs: BreadcrumbItemInterface[] = [{ title: user.attributes?.name }, { transChoiceKey: 'application' }];
</script>
<template>
    <Head :title="$tChoice('trans.application', 2)" />
    <PageContainer :breadcrumbs="breadcrumbs">
        <DataTable
            :data="[]"
            :show-archived-filter="false"
            :filters="filters"
            :search-url="route('portal.index', getIdParams(user.id.toString() ?? ''))"
            :pagination="{ ...applications?.links, ...applications?.meta }"
            :columns="applicationsTable()"
            :disable-create="false"
        >
            <template #head-right>
                <GenericButton
                    :icon="IconName.add"
                    class="rounded-full"
                    :icon-variant="ColorVariant.white"
                    :variant="ColorVariant.primary"
                    @click="() => navigateTo(route('portal.edit', getIdParams(user.id.toString() ?? '')))"
                    :title="$t('trans.create_new_application')"
                />
            </template>
        </DataTable>
    </PageContainer>
</template>
