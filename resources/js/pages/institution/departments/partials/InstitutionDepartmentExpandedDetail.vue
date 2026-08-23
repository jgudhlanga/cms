<script setup lang="ts">
import BaseTag from '@/components/core/util/BaseTag.vue';
import LevelCodeBadge from '@/components/core/util/LevelCodeBadge.vue';
import { ColorVariant } from '@/enums/colors';
import { formatLevelBadge } from '@/lib/levelBadge';
import { InstitutionDepartment } from '@/types/institution';
import { CircleUser } from 'lucide-vue-next';

interface Props {
    department: InstitutionDepartment;
    isAcademic?: boolean;
    canView?: boolean;
    canEdit?: boolean;
}

defineProps<Props>();

const emit = defineEmits<{
    view: [];
    edit: [];
}>();

const hasStaffName = (name?: string | null): boolean => Boolean(name?.trim());
</script>

<template>
    <div class="grid gap-2 border-t border-border/60 bg-muted/20 p-2 md:grid-cols-3">
        <div class="rounded-md border border-border/50 bg-card p-2 shadow-none">
            <div class="mb-1 text-[10px] font-semibold tracking-wide text-muted-foreground uppercase">
                {{ $t('trans.ui_leadership') }}
            </div>
            <dl class="space-y-0.5 text-xs">
                <div class="flex items-center justify-between gap-2">
                    <dt class="text-muted-foreground">{{ $t('trans.head_of_department') }}</dt>
                    <dd class="min-w-0">
                        <span
                            v-if="hasStaffName(department.attributes?.headOfDepartment)"
                            class="inline-flex max-w-full items-center gap-1.5 font-medium text-foreground"
                        >
                            <span class="inline-flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-primary/10 text-primary">
                                <CircleUser class="h-3.5 w-3.5" aria-hidden="true" />
                            </span>
                            <span class="truncate">{{ department.attributes.headOfDepartment }}</span>
                        </span>
                        <span v-else class="font-medium text-foreground">—</span>
                    </dd>
                </div>
                <div v-if="department.attributes?.division" class="flex items-center justify-between gap-2">
                    <dt class="text-muted-foreground">{{ $tChoice('trans.division', 1) }}</dt>
                    <dd class="font-medium text-foreground">{{ department.attributes.division }}</dd>
                </div>
                <div v-if="department.attributes?.headOfDivision" class="flex items-center justify-between gap-2">
                    <dt class="text-muted-foreground">{{ $t('trans.head_of_division') }}</dt>
                    <dd class="min-w-0">
                        <span class="inline-flex max-w-full items-center gap-1.5 font-medium text-foreground">
                            <span class="inline-flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-primary/10 text-primary">
                                <CircleUser class="h-3.5 w-3.5" aria-hidden="true" />
                            </span>
                            <span class="truncate">{{ department.attributes.headOfDivision }}</span>
                        </span>
                    </dd>
                </div>
            </dl>
        </div>

        <div class="rounded-md border border-border/50 bg-card p-2 shadow-none">
            <div class="mb-1 text-[10px] font-semibold tracking-wide text-muted-foreground uppercase">
                {{ $t('trans.ui_offerings') }}
            </div>
            <dl class="space-y-1 text-xs">
                <div class="flex items-center justify-between gap-2">
                    <dt class="text-muted-foreground">{{ $t('trans.ui_courses_offered') }}</dt>
                    <dd class="font-medium text-foreground">{{ department.attributes?.coursesOfferedCount ?? 0 }}</dd>
                </div>
                <div class="space-y-1">
                    <dt class="text-muted-foreground">{{ $t('trans.ui_levels_offered') }}</dt>
                    <dd class="flex flex-wrap gap-1">
                        <template v-if="(department.attributes?.levelsOffered?.length ?? 0) > 0">
                            <LevelCodeBadge
                                v-for="level in department.attributes?.levelsOffered ?? []"
                                :key="level"
                                :label="formatLevelBadge(level)"
                                :title="level"
                            />
                        </template>
                        <span v-else class="font-medium text-foreground">—</span>
                    </dd>
                </div>
                <div v-if="isAcademic" class="flex items-center justify-between gap-2">
                    <dt class="text-muted-foreground">{{ $t('trans.apprentice_course') }}</dt>
                    <dd>
                        <BaseTag
                            v-if="department.attributes?.hasApprenticeCourses"
                            :title="$t('trans.yes')"
                            :variant="ColorVariant.success_outline"
                        />
                        <span v-else class="font-medium text-foreground">—</span>
                    </dd>
                </div>
            </dl>
        </div>

        <div class="flex flex-col rounded-md border border-border/50 bg-card p-2 shadow-none">
            <div class="mb-1 text-[10px] font-semibold tracking-wide text-muted-foreground uppercase">
                {{ $t('trans.ui_department_overview') }}
            </div>
            <dl class="space-y-0.5 text-xs">
                <div class="flex items-center justify-between gap-2">
                    <dt class="text-muted-foreground">{{ $t('trans.ui_staff_count') }}</dt>
                    <dd>
                        <span
                            class="inline-flex h-6 min-w-6 items-center justify-center rounded-full bg-primary px-1.5 text-[10px] font-bold tabular-nums text-primary-foreground"
                        >
                            {{ department.attributes?.staffCount ?? 0 }}
                        </span>
                    </dd>
                </div>
                <div v-if="department.attributes?.description" class="space-y-0.5 pt-1">
                    <dt class="text-muted-foreground">{{ $tChoice('trans.description', 1) }}</dt>
                    <dd class="text-foreground">{{ department.attributes.description }}</dd>
                </div>
            </dl>

            <div v-if="canView || canEdit" class="mt-auto flex items-center justify-end gap-2 pt-3">
                <button
                    v-if="canView"
                    type="button"
                    class="rounded-full border-2 border-primary bg-transparent px-5 py-1 text-xs font-semibold text-primary transition-colors hover:bg-primary/5"
                    @click.stop="emit('view')"
                >
                    {{ $t('trans.view') }}
                </button>
                <button
                    v-if="canEdit"
                    type="button"
                    class="rounded-full border border-primary/30 bg-transparent px-5 py-1 text-xs font-semibold text-primary transition-colors hover:bg-primary/5"
                    @click.stop="emit('edit')"
                >
                    {{ $t('trans.edit') }}
                </button>
            </div>
        </div>
    </div>
</template>
