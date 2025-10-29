<script lang="ts" setup>
import { BaseButton } from '@/components/core/button';
import BaseIcon from '@/components/core/icon/BaseIcon.vue';
import CustomSeparator from '@/components/core/util/CustomSeparator.vue';
import { useUtils } from '@/composables/core/useUtils';
import { ColorVariant } from '@/enums/colors';
import { IconName } from '@/lib/icons';
import type { EnrolmentLookup } from '@/types/enrolments';
import { computed } from 'vue';

interface Props {
    enrolmentLookup: EnrolmentLookup;
}

const props = defineProps<Props>();
const { enrolmentLookup } = props;

/**
 * ─── Computed Properties ────────────────────────────────────────────────
 */
const programCount = computed(() => Number(enrolmentLookup?.currentProgramCount) || 0);

const levelBadgeClass = computed(() => {
    const level = enrolmentLookup.currentLevel;
    const isEmpty = !level || level === 'null' || level.toLowerCase() === 'none';
    return isEmpty ? 'bg-gray-100 text-gray-600' : 'bg-blue-100 text-blue-700';
});

const profileFound = computed(() => String(enrolmentLookup?.statusCode) === '200');
const eligibleToNavigate = computed(() => !enrolmentLookup.hasAdminRole);

/**
 * ─── Helpers For Status Items ───────────────────────────────────────────
 */
interface StatusItem {
    icon: IconName;
    label: string;
    positiveText: string;
    negativeText: string;
    isActive: boolean;
}

const statusItems = computed<StatusItem[]>(() => [
    {
        icon: IconName.check,
        label: 'Eligible to apply',
        positiveText: 'Yes',
        negativeText: 'No',
        isActive: enrolmentLookup.eligibleForEnrolment && !enrolmentLookup.hasAdminRole,
    },
    {
        icon: IconName.user,
        label: 'User Account',
        positiveText: 'Exists',
        negativeText: 'None',
        isActive: !!enrolmentLookup.user,
    },
    {
        icon: IconName.money,
        label: 'Application Fee',
        positiveText: 'Paid',
        negativeText: 'None',
        isActive: enrolmentLookup.hasPaidApplicationFee,
    },
    {
        icon: IconName.finger_print,
        label: 'Has Admin Role',
        positiveText: 'Yes',
        negativeText: 'No',
        isActive: enrolmentLookup.hasAdminRole,
    },
]);

const { navigateTo } = useUtils();
</script>

<template>
    <div class="card p-3">
        <div class="space-y-4">
            <!-- ─── Current Level ────────────────────────────────────────────── -->
            <div class="flex items-center justify-between border-b border-gray-100 py-3">
                <div class="flex items-center space-x-3">
                    <BaseIcon :name="IconName.graduation_cape" class="text-primary" />
                    <span class="text-accent-foreground font-medium">Current Level</span>
                </div>
                <span class="status-badge" :class="levelBadgeClass">
                    {{ enrolmentLookup.currentLevel || 'None' }}
                </span>
            </div>

            <!-- ─── Current Applications Count ───────────────────────────────── -->
            <div class="flex items-center justify-between border-b border-gray-100 py-3">
                <div class="flex items-center space-x-3">
                    <BaseIcon :name="IconName.file" class="text-purple-600" />
                    <span class="text-accent-foreground font-medium">Current Applications</span>
                </div>
                <div class="flex items-center space-x-2">
                    <div class="flex -space-x-2">
                        <div
                            v-for="n in Math.min(programCount, 3)"
                            :key="n"
                            class="flex h-6 w-6 items-center justify-center rounded-full border-2 border-white bg-purple-500"
                        >
                            <span class="text-xs font-bold text-white">{{ n }}</span>
                        </div>
                        <div v-if="programCount > 3" class="flex h-6 w-6 items-center justify-center rounded-full border-2 border-white bg-gray-300">
                            <span class="text-xs font-bold text-gray-600">+{{ programCount - 3 }}</span>
                        </div>
                    </div>
                    <span class="text-accent-foreground text-lg font-semibold">{{ programCount }}</span>
                </div>
            </div>

            <!-- ─── Status Items Grid ────────────────────────────────────────── -->
            <div class="grid grid-cols-1 gap-4 pt-2 md:grid-cols-4">
                <div v-for="(item, idx) in statusItems" :key="idx" class="flex items-center space-x-3 rounded-lg bg-gray-50 p-3">
                    <div class="flex-shrink-0">
                        <div class="flex h-10 w-10 items-center justify-center rounded-full" :class="item.isActive ? 'bg-green-100' : 'bg-red-100'">
                            <BaseIcon :name="item.icon" :class="item.isActive ? 'text-green-600' : 'text-red-600'" />
                        </div>
                    </div>
                    <div>
                        <p class="text-accent-foreground text-sm font-medium">{{ item.label }}</p>
                        <p class="text-sm" :class="item.isActive ? 'text-green-600' : 'text-red-600'">
                            {{ item.isActive ? item.positiveText : item.negativeText }}
                        </p>
                    </div>
                </div>
            </div>
        </div>
        <div class="flex flex-col items-center justify-center" v-if="eligibleToNavigate">
            <CustomSeparator classes="h-[1px] my-7" />
            <BaseButton
                @click="navigateTo(route('enrolments.show-profile', { student: enrolmentLookup?.studentId }))"
                v-if="profileFound"
                :variant="ColorVariant.success_outline"
                title="View Full Profile"
                classes="rounded-full"
            >
                <BaseIcon :name="IconName.user" />
            </BaseButton>
            <BaseButton
                @click="navigateTo(route('enrolments.create-profile', { payment_mode: 'cash' }))"
                v-else
                :variant="ColorVariant.danger_outline"
                title="Create Enrolment"
                classes="rounded-full"
            >
                <BaseIcon :name="IconName.user_add" />
            </BaseButton>
        </div>
    </div>
</template>

<style scoped>
.status-badge {
    padding: 4px 12px;
    border-radius: 20px;
    font-size: 0.875rem;
    font-weight: 500;
}
</style>
