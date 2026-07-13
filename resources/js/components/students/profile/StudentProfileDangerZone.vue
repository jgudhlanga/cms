<script setup lang="ts">
import { BaseButton } from '@/components/core/button';
import {
    openAccountPurgeDialog,
    studentAccountPurgeItems,
} from '@/composables/account-purge/useAccountPurgeDialog';
import { ColorVariant } from '@/enums/colors';
import { errorAlert } from '@/lib/alerts';
import { hasAbility } from '@/lib/permissions';
import type { Student } from '@/types/students';
import { router, usePage } from '@inertiajs/vue3';
import { trans } from 'laravel-vue-i18n';
import { computed, ref } from 'vue';

interface Props {
    student: Student;
}

const props = defineProps<Props>();

const isPurging = ref(false);
const page = usePage();

const purgeArchiveRetentionDays = computed(
    () => (page.props.purgeArchiveRetentionDays as number | undefined) ?? 30,
);

const canPurge = computed(() => hasAbility('root:manage'));

const studentName = computed(
    () => props.student.relationships?.user?.attributes?.name ?? props.student.attributes?.studentNumber ?? 'Student',
);

const studentEmail = computed(() => props.student.relationships?.user?.attributes?.email ?? '');

const handlePurge = (): void => {
    const fromParam = new URL(window.location.href).searchParams.get('from');
    const purgeUrl = fromParam
        ? `${route('students.purge', props.student.id)}?from=${encodeURIComponent(fromParam)}`
        : route('students.purge', props.student.id);

    openAccountPurgeDialog({
        users: [
            {
                name: studentName.value,
                email: studentEmail.value,
            },
        ],
        purgeItems: [...studentAccountPurgeItems],
        introTranslationKey: 'trans.student_account_purge_confirm_intro',
        onConfirm: (reason) => {
            isPurging.value = true;

            router.delete(purgeUrl, {
                data: { reason },
                onFinish: () => {
                    isPurging.value = false;
                },
                onError: () => {
                    errorAlert(trans('trans.student_account_purge_failure'));
                },
            });
        },
    });
};
</script>

<template>
    <section
        v-if="canPurge"
        class="mx-2 mb-3 mt-6 rounded-xl border border-red-200 bg-red-50/50 p-4 md:mx-3"
    >
        <h3 class="text-sm font-semibold uppercase tracking-wide text-red-700">
            {{ $t('trans.student_profile_danger_zone_heading') }}
        </h3>
        <p class="mt-2 text-sm text-red-700">
            {{ $t('trans.student_profile_danger_zone_description', { days: purgeArchiveRetentionDays }) }}
        </p>
        <div class="mt-4">
            <BaseButton
                classes="rounded-full"
                :variant="ColorVariant.danger"
                type="button"
                :processing="isPurging"
                @click="handlePurge"
            >
                {{ $t('trans.student_profile_purge_account') }}
            </BaseButton>
        </div>
    </section>
</template>
