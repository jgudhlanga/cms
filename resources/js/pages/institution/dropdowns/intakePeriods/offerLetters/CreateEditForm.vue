<script setup lang="ts">
import { BaseButton } from '@/components/core/button';
import { BaseInput } from '@/components/core/form';
import CourseComboSelect from '@/components/core/form/combobox/CourseComboSelect.vue';
import InstitutionDepartmentComboSelect from '@/components/core/form/combobox/InstitutionDepartmentComboSelect.vue';
import LevelComboSelect from '@/components/core/form/combobox/LevelComboSelect.vue';
import ModeOfStudyComboSelect from '@/components/core/form/combobox/ModeOfStudyComboSelect.vue';
import WangEditor from '@/components/core/form/editor/WangEditor.vue';
import Name from '@/components/core/form/text/Name.vue';
import CustomSeparator from '@/components/core/util/CustomSeparator.vue';
import { Label } from '@/components/ui/label';
import { useUtils } from '@/composables/core/useUtils';
import { useOfferLetterTemplates } from '@/composables/institution/useOfferLetterTemplates';
import { ButtonSize } from '@/enums/buttons';
import { ColorVariant } from '@/enums/colors';
import { TextFieldType } from '@/enums/inputs';
import { getIdParams } from '@/lib/utils';
import { IntakePeriod, OfferLetterTemplate, OfferLetterTemplateParams } from '@/types/institution';
import { SelectOption } from '@/types/utils';
import { useForm } from '@inertiajs/vue3';
import { onMounted, ref } from 'vue';

interface Props {
    intakePeriod: IntakePeriod;
    offerLetterTemplate?: OfferLetterTemplate;
}

const props = defineProps<Props>();
const { navigateTo } = useUtils();
const { saveOfferLetterTemplate } = useOfferLetterTemplates();
const intakeId = getIdParams(props.intakePeriod.id?.toString() ?? '');

const departments = ref<SelectOption[]>([]);
const levels = ref<SelectOption[]>([]);
const course = ref<SelectOption | null>(null);
const modeOfStudy = ref<SelectOption | null>(null);
const body = ref<string>('');

const form = useForm<OfferLetterTemplateParams>({
    name: '',
    helper_description: '',
    institution_department_ids: [],
    level_ids: [],
    course_id: null,
    mode_of_study_id: null,
    tuition_override: null,
    header_address_line_1: '',
    header_address_line_2: '',
    header_email: '',
    header_line_1: '',
    header_line_2: '',
    header_logo_1: '',
    header_logo_2: '',
    header_telephone: '',
    header_website: '',
    body: '',
});

const logon1Preview = ref<string | null>(null);
const logon2Preview = ref<string | null>(null);
const logo1FileType = ref<string | null>(null);
const logo2FileType = ref<string | null>(null);

const handleLogo1FileChange = (event: any) => {
    const upload = event.target.files[0];
    if (!upload) return;
    form.header_logo_1 = upload;
    if (logon1Preview.value) {
        URL.revokeObjectURL(logon1Preview.value);
    }
    logo1FileType.value = upload.type;
    logon1Preview.value = upload.type.startsWith('image/') ? URL.createObjectURL(upload) : null;
};

const handleLogo2FileChange = (event: any) => {
    const upload = event.target.files[0];
    if (!upload) return;
    form.header_logo_2 = upload;
    if (logon2Preview.value) {
        URL.revokeObjectURL(logon2Preview.value);
    }
    logo2FileType.value = upload.type;
    logon2Preview.value = upload.type.startsWith('image/') ? URL.createObjectURL(upload) : null;
};

onMounted(() => {
    const template = props.offerLetterTemplate;
    if (!template) {
        return;
    }
    form.name = template.attributes?.name ?? '';
    form.helper_description = template.attributes?.helperDescription ?? '';
    form.tuition_override = template.attributes?.tuitionOverride ?? null;
    form.header_address_line_1 = template.attributes?.headerAddressLine1 ?? '';
    form.header_address_line_2 = template.attributes?.headerAddressLine2 ?? '';
    form.header_email = template.attributes?.headerEmail ?? '';
    form.header_line_1 = template.attributes?.headerLine1 ?? '';
    form.header_line_2 = template.attributes?.headerLine2 ?? '';
    form.header_telephone = template.attributes?.headerTelephone ?? '';
    form.header_website = template.attributes?.headerWebsite ?? '';
    form.course_id = template.attributes?.courseId ?? null;
    form.mode_of_study_id = template.attributes?.modeOfStudyId ?? null;
    body.value = template.attributes?.body ?? '';
    departments.value = (template.attributes?.departmentOptions ?? []).map((item) => ({
        value: Number(item.id),
        label: item.name,
    }));
    levels.value = (template.attributes?.levelOptions ?? []).map((item) => ({
        value: Number(item.id),
        label: item.name,
    }));
    course.value =
        template.attributes?.courseId && Number(template.attributes.courseId) > 0
            ? { value: Number(template.attributes.courseId), label: template.attributes.course ?? '' }
            : null;
    modeOfStudy.value =
        template.attributes?.modeOfStudyId && Number(template.attributes.modeOfStudyId) > 0
            ? { value: Number(template.attributes.modeOfStudyId), label: template.attributes.modeOfStudy ?? '' }
            : null;
});

const saveForm = () => {
    form.institution_department_ids = departments.value.map((item) => Number(item.value));
    form.level_ids = levels.value.map((item) => Number(item.value));
    form.course_id = course.value?.value ?? null;
    form.mode_of_study_id = modeOfStudy.value?.value ?? null;
    form.body = body.value;
    saveOfferLetterTemplate(form, props.intakePeriod, props.offerLetterTemplate);
};
</script>

<template>
    <form @submit.prevent="saveForm" class="flex flex-col">
        <p class="mb-4 text-sm text-muted-foreground">{{ $t('trans.offer_letter_template_scope_help') }}</p>
        <div class="grid grid-cols-1 gap-5 md:grid-cols-3">
            <Name v-model="form.name" :label-uppercase="true" :is-required="true" />
            <InstitutionDepartmentComboSelect :form="form" multiple v-model="departments" :label-uppercase="true" />
            <LevelComboSelect :form="form" multiple v-model="levels" :label-uppercase="true" />
            <CourseComboSelect :form="form" v-model="course" :label-uppercase="true" />
            <ModeOfStudyComboSelect :form="form" v-model="modeOfStudy" :include-catalogue-modes="true" :label-uppercase="true" />
            <BaseInput
                input-id="tuition_override"
                v-model="form.tuition_override"
                :label-uppercase="true"
                :label="$t('trans.offer_letter_template_tuition_override')"
                :type="TextFieldType.number"
            />
            <BaseInput input-id="header_line_1" v-model="form.header_line_1" :label-uppercase="true" :label="$t('trans.header_line_1')" />
            <BaseInput input-id="header_line_2" v-model="form.header_line_2" :label-uppercase="true" :label="$t('trans.header_line_2')" />
            <BaseInput
                input-id="header_address_line_1"
                v-model="form.header_address_line_1"
                :label-uppercase="true"
                :label="$t('trans.header_address_line_1')"
            />
            <BaseInput
                input-id="header_address_line_2"
                v-model="form.header_address_line_2"
                :label-uppercase="true"
                :label="$t('trans.header_address_line_2')"
            />
            <BaseInput input-id="header_telephone" v-model="form.header_telephone" :label-uppercase="true" :label="$t('trans.header_telephone')" />
            <BaseInput input-id="header_email" v-model="form.header_email" :label-uppercase="true" :label="$t('trans.header_email')" />
            <BaseInput input-id="header_website" v-model="form.header_website" :label-uppercase="true" :label="$t('trans.header_website')" />
        </div>
        <div class="mt-5">
            <BaseInput
                input-id="helper_description"
                v-model="form.helper_description"
                :label-uppercase="true"
                :label="$t('trans.helper_description')"
            />
        </div>
        <div class="mt-5 flex space-x-5">
            <div class="flex w-full flex-col space-y-2">
                <BaseInput
                    input-id="header_logo_1"
                    :label-uppercase="true"
                    :error="form.errors.header_logo_1"
                    :label="`${$t('trans.logo')} 1`"
                    :type="TextFieldType.file"
                    @change="handleLogo1FileChange"
                />
                <img
                    v-if="logon1Preview && logo1FileType?.startsWith('image/')"
                    class="h-30 w-30 rounded-full"
                    :src="logon1Preview"
                    :alt="$t('trans.ui_image_preview')"
                />
            </div>
            <div class="flex w-full flex-col space-y-2">
                <BaseInput
                    input-id="header_logo_2"
                    :label-uppercase="true"
                    :error="form.errors.header_logo_2"
                    :label="`${$t('trans.logo')} 2`"
                    :type="TextFieldType.file"
                    @change="handleLogo2FileChange"
                />
                <img
                    v-if="logon2Preview && logo2FileType?.startsWith('image/')"
                    class="h-30 w-30 rounded-full"
                    :src="logon2Preview"
                    :alt="$t('trans.ui_image_preview')"
                />
            </div>
        </div>
        <CustomSeparator classes="mt-6 h-1" />
        <div class="mt-2 flex w-full flex-col">
            <Label class="my-2 uppercase">{{ $t('trans.body') }}</Label>
            <p class="mb-2 text-xs text-muted-foreground">{{ $t('trans.document_template_placeholders') }}</p>
            <WangEditor v-model="body" />
        </div>
        <div class="mt-6 flex w-full justify-center space-x-3 border-t-[1px] px-6 py-5">
            <BaseButton
                type="button"
                :variant="ColorVariant.shade"
                @click="() => navigateTo(route('intake-periods.offer-letter-templates.index', intakeId))"
                :size="ButtonSize.lg"
                >{{ $t('trans.back') }}
            </BaseButton>
            <BaseButton :processing="form.processing" :size="ButtonSize.lg">{{ $t('trans.save') }}</BaseButton>
        </div>
    </form>
</template>
