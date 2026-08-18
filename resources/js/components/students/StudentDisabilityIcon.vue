<script setup lang="ts">
import BaseIcon from '@/components/core/icon/BaseIcon.vue';
import { IconName } from '@/enums/icons';
import { computed } from 'vue';

interface Props {
    status?: string | null;
    show?: boolean | null;
}

const props = withDefaults(defineProps<Props>(), {
    status: null,
    show: null,
});

const visible = computed(() => {
    if (props.show !== null && props.show !== undefined) {
        return props.show;
    }

    return props.status === 'yes';
});
</script>

<template>
    <BaseIcon
        v-if="visible"
        :name="IconName.accessibility"
        class="h-3.5 w-3.5 shrink-0 text-sky-600"
        :aria-label="$t('hms.student_has_disability')"
        :title="$t('hms.student_has_disability')"
    />
</template>
