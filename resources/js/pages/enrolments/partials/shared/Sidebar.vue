<script setup lang="ts">
import { useUtils } from '@/composables/core/useUtils';
import { enrolmentStatusFromQuery } from '@/lib/enrolmentStatusOrigin';
import { ClassListTopNext, ClassListType, OtherApplication } from '@/types/enrolments';
import { trans } from 'laravel-vue-i18n';

interface Props {
    nextTop: ClassListTopNext[];
    otherApplications?: OtherApplication[];
    type: ClassListType;
    compact?: boolean;
}

defineProps<Props>();
const { getQueryParams } = useUtils();
const originQuery = enrolmentStatusFromQuery(getQueryParams());

const getDescription = (type: ClassListType) => {
    switch (type) {
        case 'provisional':
            return trans('enrolments.up_next_description_verify');
        case 'verified':
            return trans('enrolments.up_next_description_confirm');
        default:
            return '';
    }
};

const getRouteName = (type: ClassListType, applicationId: string) => {
    switch (type) {
        case 'provisional':
            return route('enrolments.verify', { student_application: applicationId, type: 'provisional', ...originQuery });
        case 'verified':
            return route('enrolments.confirm', { student_application: applicationId, type: 'verified', ...originQuery });
        default:
            return '';
    }
};
</script>

<template>
    <div class="flex flex-col gap-4">
        <div v-if="otherApplications && otherApplications.length > 0" class="flex flex-col gap-2">
            <div>
                <h3 class="text-[0.65rem] font-semibold tracking-[0.12em] text-muted-foreground uppercase">
                    {{ $t('trans.ui_other_applications') }}
                </h3>
                <p class="text-xs text-muted-foreground">{{ $t('enrolments.other_applications_subtitle') }}</p>
            </div>
            <div class="flex flex-col gap-2">
                <div
                    v-for="application in otherApplications"
                    :key="application.applicationId"
                    class="rounded-lg border border-border bg-card px-3 py-2"
                >
                    <div class="flex items-start justify-between gap-2">
                        <span class="text-sm font-semibold leading-tight">{{ application.course }}</span>
                        <span class="shrink-0 rounded-full bg-primary/15 px-2 py-0.5 text-[10px] font-semibold uppercase text-primary">
                            {{ application.level }}
                        </span>
                    </div>
                    <p class="mt-0.5 text-xs text-muted-foreground">
                        {{ application.department }} · {{ application.modeOfStudy }}
                    </p>
                    <p class="mt-1 text-[10px] font-medium uppercase tracking-wide text-muted-foreground">
                        {{
                            application.inClassList
                                ? $t('enrolments.in_class_list')
                                : $t('enrolments.not_in_class_list')
                        }}
                    </p>
                </div>
            </div>
        </div>

        <div v-if="nextTop && nextTop.length > 0" class="flex flex-col gap-2">
            <div>
                <h3 class="text-[0.65rem] font-semibold tracking-[0.12em] text-muted-foreground uppercase">
                    {{ $t('enrolments.up_next_title') }}
                </h3>
                <p class="text-xs text-muted-foreground">{{ getDescription(type as ClassListType) }}</p>
            </div>
            <div class="flex flex-col gap-1.5">
                <TextLink
                    v-for="application in nextTop"
                    :key="application.applicationId"
                    classes="rounded-lg border border-border bg-card px-3 py-2 text-sm font-medium text-foreground hover:bg-muted"
                    :title="application.name"
                    :href="getRouteName(type as ClassListType, String(application.applicationId))"
                />
            </div>
        </div>
    </div>
</template>
