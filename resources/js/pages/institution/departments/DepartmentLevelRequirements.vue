<script setup lang="ts">
import { BaseCheckbox } from '@/components/core/form';
import PageContainer from '@/components/core/page/PageContainer.vue';
import Empty from '@/components/core/util/Empty.vue';
import HeadingSmall from '@/components/core/util/HeadingSmall.vue';
import { useUtils } from '@/composables/core/useUtils';
import { useLevels } from '@/composables/institution/useLevels';
import { useSubjects } from '@/composables/institution/useSubjects';
import { ColorVariant } from '@/enums/colors';
import { getIdParams } from '@/lib/utils';
import { AuthObject } from '@/types/data-pagination';
import { DepartmentLevel } from '@/types/department-meta-data';
import { InstitutionDepartment } from '@/types/institution';
import type { Link } from '@/types/ui';
import { Head, useForm } from '@inertiajs/vue3';
import { onMounted, ref } from 'vue';
import BaseButton from '../../../components/core/button/BaseButton.vue';

interface Props {
    institutionDepartment: InstitutionDepartment;
    departmentLevel: DepartmentLevel;
    auth: AuthObject;
    errors: object;
}

const props = defineProps<Props>();
const { departmentLevel, institutionDepartment } = props;
const breadcrumbs: Array<Link> = [
    { transChoiceKey: 'institution', transChoiceKeyIndex: 1, href: route('institution.index') },
    { transChoiceKey: 'department', href: route('institution-departments.index') },
    {
        title: institutionDepartment.attributes.department,
        href: route('institution-departments.show', getIdParams(institutionDepartment.id?.toString() ?? '')),
    },
    { title: departmentLevel.attributes.level },
    { transKey: 'level_requirements' },
];

const isOLevelRequired = ref(false);
const onlyReadWriteRequired = ref(false);
const isPreviousLevelRequired = ref(false);
const { listSubjects, subjects } = useSubjects();
const { listLevels, levels } = useLevels();

onMounted(async () => {
    await listSubjects();
    await listLevels();
});

const { navigateTo } = useUtils();
const form = useForm<any>({
    is_o_level_required: isOLevelRequired.value,
    subject_ids: null,
    only_read_write_required: onlyReadWriteRequired.value,
    is_previous_level_required: isPreviousLevelRequired.value,
});

const updateLevel = () => {};
</script>

<template>
    <Head :title="$t('trans.level_requirements')" />
    <PageContainer :breadcrumbs="breadcrumbs">
        <form @submit.prevent="() => updateLevel()" class="flex flex-col">
            <div class="flex flex-col">
                <div class="flex flex-col space-y-3">
                    <HeadingSmall :title="$t('trans.o_level_subjects')" :description="$t('trans.select_o_level_subjects')" />
                    <BaseCheckbox input-id="is_o_level_required" v-model="isOLevelRequired" :label="`${$t('trans.is_o_level_required')}`" />
                </div>
                <div v-if="isOLevelRequired">
                    <template v-if="subjects && subjects.length > 0">
                        <div class="flex flex-col">
                            <div class="grid grid-cols-1 gap-x-3 md:grid-cols-3">
                                <template v-for="subject in subjects" :key="`subject_key_${subject['id']}`">
                                    <BaseCheckbox
                                        :input-id="`subject_id_${subject['id']}`"
                                        :value="subject['id']"
                                        v-model="form.subject_ids"
                                        :label="subject['attributes']['name']"
                                    />
                                </template>
                            </div>
                        </div>
                    </template>
                    <template v-else>
                        <Empty />
                    </template>
                </div>
                <div class="flex flex-col">
                    <HeadingSmall class="mt-5" :title="$t('trans.spd_requirements')" :description="$t('trans.spd_requirements_description')" />
                    <BaseCheckbox
                        input-id="only_read_write_required"
                        v-model="onlyReadWriteRequired"
                        :label="`${$t('trans.only_read_write_required')}`"
                    />
                </div>
                <div class="mt-5 flex flex-col space-y-3">
                    <HeadingSmall :title="$t('trans.requires_previous_level')" :description="$t('trans.requires_previous_level_description')" />
                    <BaseCheckbox
                        input-id="is_previous_level_required"
                        v-model="isPreviousLevelRequired"
                        :label="`${$t('trans.requires_previous_level')}`"
                    />
                    <div v-if="isPreviousLevelRequired">
                        <template v-if="levels && levels.length > 0">
                            <div class="flex flex-col">
                                {{ levels }}
                            </div>
                        </template>
                        <template v-else>
                            <Empty />
                        </template>
                    </div>
                </div>
            </div>
            <div class="flex items-center justify-center space-x-3 p-6">
                <BaseButton
                    type="button"
                    :variant="ColorVariant.shade"
                    @click="
                        () =>
                            navigateTo(
                                route('institution-departments.show', getIdParams(institutionDepartment?.attributes?.departmentId.toString() ?? '')),
                            )
                    "
                    >{{ $t('trans.back') }}
                </BaseButton>
                <BaseButton :processing="form.processing" :disabled="form.processing"> {{ $t('trans.save') }} </BaseButton>
            </div>
        </form>
    </PageContainer>
</template>
