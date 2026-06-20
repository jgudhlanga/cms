<script setup lang="ts">
import IconButton from '@/Components/Core/Button/IconButton.vue';
import Heading from '@/Components/Core/Utils/Heading.vue';
import { ColorVariant } from '@/enums/colors';
import { IconName } from '@/enums/icons';
import type { IconButtonParams } from '@/types/utils';

const props = defineProps({
    title: { type: String, required: true },
    otherClasses: { type: String, required: false },
    showEditButton: { type: Boolean, required: false, default: false },
    editAction: { type: Function, required: false },
    disableActionBtn: { type: Boolean, required: false, default: false },
});
const editParams: IconButtonParams = {
    action: () =>
        props.editAction
            ? props.editAction()
            : () => {
                  console.log('Nothing to do');
              },
    disable: props.disableActionBtn,
    icon: IconName.edit,
    type: ColorVariant.shade,
};
</script>
<template>
    <Heading :other-classes="`${otherClasses} uppercase font-semibold text-sm`" :title="title">
        <div v-if="showEditButton">
            <IconButton :params="editParams" />
        </div>
    </Heading>
</template>
