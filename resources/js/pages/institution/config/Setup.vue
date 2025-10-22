<script setup lang="ts">
import { hasAbility } from '@/lib/permissions';
import type { Link } from '@/types/ui';
import { Head } from '@inertiajs/vue3';
import { ref } from 'vue';
import { ColorVariant } from '@/enums/colors';

const breadcrumbs: Array<Link> = [
    {
        transChoiceKey: 'institution',
        href: route('institution.index'),
    },
    { transKey: 'institution_setup' },
];

const tabs: Array<Link> = [
    {
        transChoiceKey: 'intake_period',
        url: route('intake-periods.index'),
    },
    {
        transChoiceKey: 'document_template',
        url: route('document-templates.index'),
    },
    {
        transChoiceKey: 'fee_levy_structure',
        url: route('fee-structures.index'),
    },
];
const allowed = hasAbility('view:institution-settings');
const edit = ref<any>(null);
const openModel = ref<boolean>(false);
const editModal = (editItem?: any) => {
    edit.value = editItem;
    openModel.value = true;
};

function closeModal() {
    openModel.value = false;
}
</script>

<template>
    <Head :title="$t('trans.institution_setup')" />
    <PageContainer :breadcrumbs="breadcrumbs">
        <template v-if="allowed">
            <AvatarTitleList :tabs="tabs" />
        </template>
        <BaseAlert v-if="!allowed" :title="$t('trans.forbidden')" :description="$t('trans.forbidden_message')" />
        <BaseButton title="Show Modal" @click="() => (openModel = true)" classes="w-[200px] my-3" />
        <CustomModal :show="openModel" title="Hello World" @close="closeModal" size="full" >
            <div>
                Lorem ipsum dolor sit amet, consectetur adipisicing elit. Aliquid dolores dolorum, eius enim eum eveniet explicabo hic inventore iure,
                minus nam nobis nostrum quia sint vel vero voluptatibus? Ab accusantium alias aliquam asperiores blanditiis commodi consequuntur culpa
                debitis distinctio doloremque
                ullam veritatis.
            </div>
            <div class="mt-6 flex items-center justify-center space-x-3 border-t-[1px] pt-4">
                <BaseButton
                    type="button"
                    classes="rounded-full w-full md:w-[200px]"
                    title="Close"
                    @click="closeModal"
                    :variant="ColorVariant.shade"
                />
                <BaseButton classes="rounded-full w-full md:w-[200px]" title="Save" @click="closeModal" />
            </div>
        </CustomModal>
    </PageContainer>
</template>
