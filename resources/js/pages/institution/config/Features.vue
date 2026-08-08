<script setup lang="ts">
import BaseAlert from '@/components/core/alert/BaseAlert.vue';
import { BaseButton } from '@/components/core/button';
import BaseSwitch from '@/components/core/form/radio/BaseSwitch.vue';
import PageContainer from '@/components/core/page/PageContainer.vue';
import HeadingSmall from '@/components/core/util/HeadingSmall.vue';
import { TypeVariant } from '@/enums/type-variants';
import { hasAbility } from '@/lib/permissions';
import type { Link } from '@/types/ui';
import { Head, useForm, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps<{
    features: {
        allow_online_clearance: boolean;
    };
}>();

const breadcrumbs: Array<Link> = [
    {
        transChoiceKey: 'institution',
        href: route('institution.index'),
    },
    {
        transKey: 'institution_setup',
        href: route('institution.setup'),
    },
    { transKey: 'institution_features' },
];

const form = useForm({
    allow_online_clearance: props.features.allow_online_clearance ?? false,
});

const flashSuccess = computed(() => (usePage().props.flash as { success?: string } | undefined)?.success);

const save = () => {
    form.put(route('institution-features.update'), {
        preserveScroll: true,
    });
};
</script>

<template>
    <Head :title="$t('trans.institution_features')" />
    <PageContainer :breadcrumbs="breadcrumbs">
        <HeadingSmall
            :title="$t('trans.institution_features')"
            :description="$t('trans.allow_online_clearance_description')"
        />

        <BaseAlert
            v-if="!hasAbility('manage:institution-features')"
            :title="$t('trans.forbidden')"
            :description="$t('trans.forbidden_message')"
            :type="TypeVariant.danger"
        />

        <BaseAlert
            v-if="flashSuccess"
            :type="TypeVariant.success"
            :description="flashSuccess"
            class="mb-4"
        />

        <form
            v-if="hasAbility('manage:institution-features')"
            class="mt-4 max-w-xl space-y-6 rounded-lg border border-border p-4"
            @submit.prevent="save"
        >
            <div class="space-y-2">
                <BaseSwitch
                    input-id="allow_online_clearance"
                    v-model="form.allow_online_clearance"
                    :label="$t('trans.allow_online_clearance')"
                    :on-update="(value) => (form.allow_online_clearance = value)"
                />
                <p class="text-sm text-muted-foreground">
                    {{ $t('trans.allow_online_clearance_description') }}
                </p>
            </div>

            <BaseButton type="submit" :disabled="form.processing" :processing="form.processing">
                {{ $t('trans.save') }}
            </BaseButton>
        </form>
    </PageContainer>
</template>
