<script lang="ts" setup>
import BaseCombobox from '@/components/core/form/combobox/BaseCombobox.vue';
import { useDocumentTypes } from '@/composables/shared/useDocumentTypes';
import { clearFormErrors } from '@/lib/forms';
import { DocumentType } from '@/types/settings';
import { SelectOption } from '@/types/utils';
import { InertiaForm } from '@inertiajs/vue3';
import { debounce } from 'lodash';
import { computed, onMounted } from 'vue';

interface Props {
    form: InertiaForm<any>;
    labelUppercase?: boolean;
    isRequired?: boolean;
}
const { isLoading, documentTypes, listDocumentTypes } = useDocumentTypes();
onMounted(async () => {
    await listDocumentTypes();
});
const props = defineProps<Props>();
const options = computed(() => {
    return documentTypes.value.map(
        (documentType: DocumentType) =>
            <SelectOption>{
                value: Number(documentType.id),
                label: documentType?.attributes?.name,
            },
    );
});

const whenSearch = debounce(async (search: string) => {
    clearFormErrors(props.form, 'documentType');
    await listDocumentTypes(search);
}, 600);
</script>

<template>
    <BaseCombobox
        :label="$tChoice('trans.document_type', 1)"
        :options="options"
        :on-search="async (search: string) => await whenSearch(search)"
        :is-loading="isLoading"
        :label-uppercase="labelUppercase"
        v-bind="$attrs"
        :is-required="isRequired"
    />
</template>
