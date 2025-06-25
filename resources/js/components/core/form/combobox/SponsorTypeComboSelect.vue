<script lang="ts" setup>
import { computed, onMounted } from 'vue';
import { SelectOption } from '@/types/utils';
import BaseCombobox from '@/components/core/form/combobox/BaseCombobox.vue';
import { clearFormErrors } from '@/lib/forms';
import { InertiaForm } from '@inertiajs/vue3';
import { debounce } from 'lodash';
import { SponsorType } from '@/types/settings';
import { useSponsorTypes } from '@/composables/shared/useSponsorTypes';

interface Props {
    form: InertiaForm<any>;
    labelUppercase?: boolean;
    isRequired?: boolean;
}
const { isLoading, sponsorTypes, listSponsorTypes } = useSponsorTypes();
onMounted(async () => {
    await listSponsorTypes();
});
const props = defineProps<Props>();
const options = computed(() => {
    return sponsorTypes.value.map(
        (sponsorTye: SponsorType) =>
            <SelectOption>{
                value: Number(sponsorTye.id),
                label: sponsorTye?.attributes?.name,
            },
    );
});

const whenSearch = debounce(async (search: string) => {
    clearFormErrors(props.form, 'sponsorType');
    await listSponsorTypes(search);
}, 600);
</script>

<template>
    <BaseCombobox
        :label="$tChoice('trans.sponsor_type', 1)"
        :options="options"
        :on-search="async (search: string) => await whenSearch(search)"
        :is-loading="isLoading"
        :label-uppercase="labelUppercase"
        v-bind="$attrs"
        :is-required="isRequired"
    />
</template>
