<script setup lang="ts">
import { BaseButton } from '@/components/core/button';
import { ColorVariant } from '@/enums/colors';
import { IconName, icons } from '@/lib/icons';
import { cn } from '@/lib/utils';
import type { FaultyStudentMergeResult } from '@/types/faulty-student-ids';
import { trans, trans_choice } from 'laravel-vue-i18n';
import { computed } from 'vue';

const props = defineProps<{
    result: FaultyStudentMergeResult;
}>();

const emit = defineEmits<{
    closed: [];
    viewProfile: [];
}>();

const detailFields = computed(() => [
    { label: trans('trans.email_address'), value: props.result.email ?? '---' },
    { label: trans('trans.phone_number'), value: props.result.phoneNumber ?? '---' },
    { label: trans_choice('trans.student_number', 1), value: props.result.studentNumber ?? '---' },
    { label: trans('trans.id_number'), value: props.result.idNumber ?? '---' },
    { label: trans('trans.maintenance_faulty_data_merge_programmes'), value: String(props.result.programmesCount) },
    { label: trans('trans.maintenance_faulty_data_merge_enrolments'), value: String(props.result.enrolmentsCount) },
]);
</script>

<template>
    <div class="fixed inset-0 z-50 flex items-center justify-center">
        <div class="absolute inset-0 z-0 bg-black opacity-50"></div>
        <div class="relative z-10 m-2 w-[640px] rounded-2xl bg-white text-green-600 shadow-lg">
            <div class="flex items-center justify-between px-6 pt-6">
                <div class="flex items-center space-x-3">
                    <component :is="icons[IconName.check_box]" :size="22" />
                    <h2 class="text-md font-semibold uppercase">
                        {{ trans('trans.maintenance_faulty_data_merge_success_title') }}
                    </h2>
                </div>
                <button
                    class="rounded-full p-2 hover:bg-green-200"
                    type="button"
                    @click="emit('closed')"
                >
                    <component :is="icons[IconName.close]" :size="26" />
                </button>
            </div>

            <div class="px-6 pt-3">
                <div class="rounded-md border-l-4 border-green-600 bg-gray-50 p-4 shadow-sm">
                    <p class="text-sm text-green-700">
                        {{ trans('trans.maintenance_faulty_data_merge_success_description') }}
                    </p>

                    <div class="mt-4">
                        <p class="text-xs font-semibold uppercase tracking-wide text-green-800">
                            {{ trans('trans.maintenance_faulty_data_merge_survivor_details') }}
                        </p>
                        <p class="mt-1 text-sm font-medium text-green-900">{{ result.name ?? '---' }}</p>
                        <dl class="mt-3 grid grid-cols-1 gap-2 text-sm text-green-700 sm:grid-cols-2">
                            <div
                                v-for="field in detailFields"
                                :key="field.label"
                                class="flex min-w-0 justify-between gap-2 rounded-md border border-green-100 bg-white px-3 py-2"
                            >
                                <dt class="truncate text-green-600/80">{{ field.label }}</dt>
                                <dd class="truncate text-right font-medium">{{ field.value }}</dd>
                            </div>
                        </dl>
                    </div>
                </div>
            </div>

            <div :class="cn('mt-6 flex w-full items-center justify-center space-x-3 px-6 py-5')">
                <BaseButton
                    classes="rounded-full"
                    :variant="ColorVariant.shade"
                    type="button"
                    @click="emit('closed')"
                >
                    {{ trans('trans.close') }}
                </BaseButton>
                <BaseButton
                    classes="rounded-full"
                    :variant="ColorVariant.success"
                    type="button"
                    @click="emit('viewProfile')"
                >
                    {{ trans('trans.maintenance_faulty_data_merge_view_profile') }}
                </BaseButton>
            </div>
        </div>
    </div>
</template>
