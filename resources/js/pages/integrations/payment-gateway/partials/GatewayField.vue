<script setup lang="ts">
import { PasswordInputWithToggle } from '@/components/core/form';
import BaseInput from '@/components/core/form/text/BaseInput.vue';
import type { PaymentGatewayFieldSource } from '@/types/integrations';
import { trans } from 'laravel-vue-i18n';
import { computed } from 'vue';
import SourceChip from './SourceChip.vue';

const props = withDefaults(
    defineProps<{
        inputId: string;
        label: string;
        envKey: string;
        source: PaymentGatewayFieldSource;
        error?: string;
        secret?: boolean;
        /** Secrets only: a stored value exists, so an empty box means "keep it". */
        isSet?: boolean;
    }>(),
    {
        secret: false,
        isSet: false,
    },
);

const model = defineModel<string>({ default: '' });

const placeholder = computed(() => {
    if (!props.secret) {
        return '';
    }

    return props.isSet ? trans('integrations.keep_secret_short') : trans('integrations.secret_placeholder');
});

// Matches PasswordInputWithToggle so text and secret boxes line up in the same grid.
const textInputClasses = 'min-h-11 rounded-xl px-3 py-2';
</script>

<template>
    <div class="relative min-w-0">
        <!-- Meta rides the label line: the env key names the field, the chip says where its value comes from. -->
        <div class="absolute top-0 right-0 flex h-3.5 max-w-[60%] items-center gap-1.5">
            <code class="text-muted-foreground/80 truncate font-mono text-[10px]" :title="envKey">{{ envKey }}</code>
            <SourceChip :source="source" />
        </div>

        <PasswordInputWithToggle
            v-if="secret"
            v-model="model"
            :input-id="inputId"
            :label="label"
            :error="error"
            :placeholder="placeholder"
            autocomplete="new-password"
        />
        <BaseInput
            v-else
            v-model="model"
            :input-id="inputId"
            :label="label"
            :error="error"
            :classes="textInputClasses"
            autocomplete="off"
            spellcheck="false"
        />
    </div>
</template>
