<script setup lang="ts">
import PageContainer from '@/components/core/page/PageContainer.vue';
import HeadingSmall from '@/components/core/util/HeadingSmall.vue';
import { ColorVariant } from '@/enums/colors';
import { IconName } from '@/enums/icons';
import { BreadcrumbItemInterface } from '@/types/ui';
import { Head } from '@inertiajs/vue3';
import { computed } from 'vue';

type User = {
    first_name?: string | null;
    middle_name?: string | null;
    last_name?: string | null;
    full_name?: string | null;
};

type Staff = {
    id: number | string;
    user?: User | null;
};

type Hostel = {
    id: number | string;
    name: string;
    location?: string | null;
    floor_count: number;
    rooms_count: number;
    capacity: number;
    status: 'active' | 'inactive';
    type?: 'male' | 'female' | 'mixed' | null;
    description?: string | null;
    warden?: Staff | null;
};

interface Props {
    hostel: Hostel;
}

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItemInterface[] = [
    { transChoiceKey: 'hms.title', href: route('hostels.index') },
    { title: props.hostel.name },
];

const wardenName = computed(() => {
    const user = props.hostel.warden?.user;
    if (!user) return 'Unassigned';

    if (user.full_name) return user.full_name;

    return [user.first_name, user.middle_name, user.last_name]
        .filter((part) => Boolean(part && String(part).trim()))
        .join(' ') || 'Unassigned';
});
</script>

<template>
    <Head :title="hostel.name" />

    <PageContainer :breadcrumbs="breadcrumbs" :back-url="route('hostels.index')">
        <BaseCard>
            <div class="flex items-start justify-between gap-2">
                <HeadingSmall :title="hostel.name" :description="hostel.location ?? ''" />
                <IconButton
                    :icon="IconName.edit"
                    :variant="ColorVariant.success_outline"
                    @click="$emit('edit')"
                />
            </div>
            <div class="grid grid-cols-1 gap-2 md:grid-cols-4">
                <LabelValue :label="$tChoice('hms.status', 1)" :value="String(hostel.status)" />
                <LabelValue :label="$tChoice('hms.type', 1)" :value="String(hostel.type ?? '---')" />
                <LabelValue :label="$tChoice('hms.warden', 1)" :value="String(wardenName)" />
                <LabelValue :label="$tChoice('hms.capacity', 1)" :value="String(hostel.capacity)" />
                <LabelValue :label="$tChoice('hms.floor_count', 1)" :value="String(hostel.floor_count)" />
                <LabelValue :label="$tChoice('hms.rooms_count', 1)" :value="String(hostel.rooms_count)" />
                <LabelValue :label="$t('hms.description')" :value="hostel.description ?? '---'" />
            </div>
        </BaseCard>
    </PageContainer>
</template>
