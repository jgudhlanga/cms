<script setup lang="ts">
import { BaseInputWithIcon } from '@/components/core/form';
import BaseButton from '@/components/core/button/BaseButton.vue';
import BaseIcon from '@/components/core/icon/BaseIcon.vue';
import PageContainer from '@/components/core/page/PageContainer.vue';
import HeadingSmall from '@/components/core/util/HeadingSmall.vue';
import HostelCard from '@/components/hms/HostelCard.vue';
import CreateEdit from '@/pages/hms/partials/CreateEdit.vue';
import { BreadcrumbItemInterface } from '@/types/ui';
import { ColorVariant } from '@/enums/colors';
import { IconName } from '@/enums/icons';
import { dangerDialog, openModal, successAlert } from '@/lib/alerts';
import { APP_MODULE_KEYS } from '@/lib/constants';
import { Head, router } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

type User = { full_name?: string | null };
type Staff = { id: number | string; user?: User | null };

type Hostel = {
    id: number | string;
    name: string;
    location?: string | null;
    floor_count: number;
    rooms_count: number;
    capacity: number;
    status: 'active' | 'inactive';
    type?: 'male' | 'female' | 'mixed' | null;
    warden?: Staff | null;
};

type PaginationMeta = { total?: number | null };
type Paginator<T> = { data: T[]; meta?: PaginationMeta };

interface Props {
    hostels: Paginator<Hostel>;
    wardens: Array<{ id: number | string; name: string | null }>;
    filters: { search?: string | null; with_trashed?: boolean | null };
    trashedCount: number;
}

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItemInterface[] = [{ transChoiceKey: 'dashboard', href: route('dashboard') }, { transChoiceKey: 'hms.title' }];

const search = ref<string>('');

const filteredHostels = computed(() => {
    const term = search.value.trim().toLowerCase();
    if (!term) return props.hostels.data ?? [];

    return (props.hostels.data ?? []).filter((h) => {
        return (
            String(h.name ?? '')
                .toLowerCase()
                .includes(term) ||
            String(h.location ?? '')
                .toLowerCase()
                .includes(term)
        );
    });
});

const openCreate = () => openModal({ name: APP_MODULE_KEYS.hostels });
const openEdit = (hostel: Hostel) => openModal({ name: APP_MODULE_KEYS.hostels, edit: hostel });

const onDelete = (hostel: Hostel) => {
    dangerDialog(() => {
        router.delete(route('hostels.destroy', String(hostel.id)), {
            preserveScroll: true,
            onSuccess: () => successAlert('Hostel deleted'),
        });
        return true;
    });
};
</script>

<template>
    <Head :title="$tChoice('hms.title', 2)" />
    <PageContainer :breadcrumbs="breadcrumbs">
        <div class="my-6 flex items-center justify-between">
            <HeadingSmall title="Hostel Management" :description="`${hostels?.meta?.total ?? hostels?.data?.length ?? 0} hostels registered`" />

            <BaseButton classes="rounded-full" :variant="ColorVariant.primary" @click="openCreate">
                <BaseIcon :name="IconName.add" />
                <span>Add Hostel</span>
            </BaseButton>
        </div>

        <div class="mb-6 flex w-full items-center gap-3">
            <BaseInputWithIcon v-model="search" :icon="IconName.search" placeholder="Search hostels..." class="w-full rounded-full" />
        </div>

        <div class="grid grid-cols-1 gap-5 md:grid-cols-2 xl:grid-cols-3">
            <HostelCard v-for="hostel in filteredHostels" :key="hostel.id" :hostel="hostel" @edit="openEdit" @delete="onDelete" />
        </div>

        <CreateEdit :wardens="wardens" @saved="() => router.reload({ only: ['hostels', 'trashedCount'] })" />
    </PageContainer>
</template>
