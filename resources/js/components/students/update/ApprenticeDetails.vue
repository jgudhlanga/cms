<script setup lang="ts">
import InputError from '@/components/core/form/InputError.vue';
import BaseInput from '@/components/core/form/text/BaseInput.vue';
import { clearFormErrors } from '@/lib/forms';
import { InertiaForm } from '@inertiajs/vue3';
import { computed } from 'vue';

interface Props {
    form: InertiaForm<{ employer?: string; apprentice_number?: string }>;
}

const props = defineProps<Props>();

const employer = computed({
    get: () => props.form.employer ?? '',
    set: (value: string) => {
        props.form.employer = value;
    },
});

const apprenticeNumber = computed({
    get: () => props.form.apprentice_number ?? '',
    set: (value: string) => {
        props.form.apprentice_number = value;
    },
});
</script>

<template>
    <div class="mt-4 space-y-4 rounded-xl border border-border/60 bg-muted/20 p-4">
        <div>
            <h3 class="text-sm font-semibold text-foreground">
                {{ $t('trans.apprentice_details_title') }}
            </h3>
            <p class="mt-1 text-xs text-muted-foreground">
                {{ $t('trans.apprentice_details_description') }}
            </p>
        </div>

        <BaseInput
            input-id="employer"
            :label="$t('trans.employer')"
            v-model="employer"
            :is-required="true"
            :error="form.errors.employer"
            @input="clearFormErrors(form, 'employer')"
        />
        <InputError v-if="form.errors.employer" :message="form.errors.employer" />

        <BaseInput
            input-id="apprentice_number"
            :label="$t('trans.apprentice_number')"
            v-model="apprenticeNumber"
            :is-required="true"
            :error="form.errors.apprentice_number"
            @input="clearFormErrors(form, 'apprentice_number')"
        />
        <InputError v-if="form.errors.apprentice_number" :message="form.errors.apprentice_number" />
    </div>
</template>
