<script setup lang="ts">
import { ColorVariant } from '@/enums/colors';
import { IconName } from '@/enums/icons';
import { cn } from '@/lib/utils';
import { computed } from 'vue';

type IconButtonTone = 'default' | 'header' | 'header-danger' | 'header-primary';

interface Props {
    icon: IconName;
    iconColor?: ColorVariant;
    iconSize?: string;
    variant?: ColorVariant;
    tone?: IconButtonTone;
}

const props = withDefaults(defineProps<Props>(), {
    tone: 'default',
    variant: ColorVariant.primary,
});

const toneClasses: Record<IconButtonTone, string> = {
    default: '',
    header: 'border-0 bg-transparent shadow-none text-muted-foreground hover:scale-[1.02] transition-colors',
    'header-danger':
        'border-0 bg-transparent shadow-none text-muted-foreground hover:scale-[1.02] transition-colors hover:bg-destructive/10 hover:text-destructive',
    'header-primary':
        'border-0 bg-transparent shadow-none text-muted-foreground hover:scale-[1.02] transition-colors hover:bg-primary/10 hover:text-primary',
};

const isHeaderTone = computed((): boolean => props.tone !== 'default');

const resolvedIconColor = computed((): ColorVariant | undefined => {
    if (props.iconColor !== undefined) {
        return props.iconColor;
    }

    if (isHeaderTone.value) {
        return undefined;
    }

    return ColorVariant.white;
});

const resolvedVariant = computed((): ColorVariant => {
    if (isHeaderTone.value) {
        return ColorVariant.transparent;
    }

    return props.variant;
});
</script>

<template>
    <BaseButton
        type="button"
        :variant="resolvedVariant"
        :classes="cn('w-8 h-8 p-0 rounded-full flex items-center justify-center flex-shrink-0', toneClasses[tone])"
    >
        <BaseIcon :name="icon" :color="resolvedIconColor" :size="iconSize" />
    </BaseButton>
</template>
