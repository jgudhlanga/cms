<script setup lang="ts">
import { BaseButton } from '@/components/core/button';
import { BaseInput } from '@/components/core/form';
import DocumentTypeComboSelect from '@/components/core/form/combobox/DocumentTypeComboSelect.vue';
import WangEditor from '@/components/core/form/editor/WangEditor.vue';
import Name from '@/components/core/form/text/Name.vue';
import CustomSeparator from '@/components/core/util/CustomSeparator.vue';
import { Label } from '@/components/ui/label';
import { useUtils } from '@/composables/core/useUtils';
import { useDocumentTemplates } from '@/composables/institution/useDocumentTemplates';
import { ButtonSize } from '@/enums/buttons';
import { ColorVariant } from '@/enums/colors';
import { TextFieldType } from '@/enums/inputs';
import { DocumentTemplate, DocumentTemplateParams } from '@/types/institution';
import { SelectOption } from '@/types/utils';
import { useForm } from '@inertiajs/vue3';
import { onMounted, ref } from 'vue';

interface Props {
    documentTemplate?: DocumentTemplate;
}

const props = defineProps<Props>();
const { documentTemplate } = props;

const documentType = ref<SelectOption | null>(null);

const { navigateTo } = useUtils();

const { saveDocumentTemplate } = useDocumentTemplates();

const body = ref<string>('');

const form = useForm<DocumentTemplateParams>({
    document_type_id: null,
    body: '',
    header_address_line_1: '',
    header_address_line_2: '',
    header_email: '',
    header_line_1: '',
    header_line_2: '',
    header_logo_1: '',
    header_logo_2: '',
    header_telephone: '',
    header_website: '',
    name: '',
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
    if (upload.type.startsWith('image/') || upload.type === 'application/pdf') {
        logon1Preview.value = URL.createObjectURL(upload);
    } else {
        logon1Preview.value = null;
    }
};

const handleLogo2FileChange = (event: any) => {
    const upload = event.target.files[0];
    if (!upload) return;
    form.header_logo_2 = upload;
    if (logon2Preview.value) {
        URL.revokeObjectURL(logon2Preview.value);
    }
    logo2FileType.value = upload.type;
    if (upload.type.startsWith('image/') || upload.type === 'application/pdf') {
        logon2Preview.value = URL.createObjectURL(upload);
    } else {
        logon2Preview.value = null;
    }
};

onMounted(() => {
    if (typeof documentTemplate !== 'undefined') {
        form.document_type_id = documentTemplate?.attributes?.documentTypeId ?? null;
        body.value = documentTemplate?.attributes?.body ?? '';
        form.header_address_line_1 = documentTemplate?.attributes?.headerAddressLine1 ?? '';
        form.header_address_line_2 = documentTemplate?.attributes?.headerAddressLine2 ?? '';
        form.header_email = documentTemplate?.attributes?.headerEmail ?? '';
        form.header_line_1 = documentTemplate?.attributes?.headerLine1 ?? '';
        form.header_line_2 = documentTemplate?.attributes?.headerLine2 ?? '';
        form.header_telephone = documentTemplate?.attributes?.headerTelephone ?? '';
        form.header_website = documentTemplate?.attributes?.headerWebsite ?? '';
        form.name = documentTemplate?.attributes?.name ?? '';
        if (documentTemplate?.attributes?.documentType) {
            documentType.value = {
                label: documentTemplate.attributes?.documentType,
                value: Number(documentTemplate?.attributes?.documentTypeId ?? null),
            };
        }
        /*if (documentTemplate.header_logo_1_url) {
            logon1Preview.value = documentTemplate.header_logo_1_url;
            logo1FileType.value = 'image/!*';
        }
        if (documentTemplate.header_logo_2_url) {
            logon2Preview.value = documentTemplate.header_logo_2_url;
            logo2FileType.value = 'image/!*';
        }*/
    }
});

const saveForm = () => {
    form.document_type_id = documentType.value?.value ?? null;
    form.body = body.value;
    saveDocumentTemplate(form, documentTemplate);
};
</script>

<template>
    <form @submit.prevent="saveForm" class="flex flex-col">
        <div class="grid grid-cols-1 gap-5 md:grid-cols-3">
            <DocumentTypeComboSelect :form="form" v-model="documentType" :is-required="true" :label-uppercase="true" />
            <Name v-model="form.name" :label-uppercase="true" :is-required="true" />
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
            <WangEditor v-model="body" />
        </div>
        <div class="mt-6 flex w-full justify-center space-x-3 border-t-[1px] px-6 py-5">
            <BaseButton type="button" :variant="ColorVariant.shade" @click="() => navigateTo(route('document-templates.index'))" :size="ButtonSize.lg"
                >{{ $t('trans.back') }}
            </BaseButton>
            <BaseButton :processing="form.processing" :size="ButtonSize.lg">{{ $t('trans.save') }}</BaseButton>
        </div>
    </form>
</template>
