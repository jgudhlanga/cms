<script setup lang="ts">
import BaseAlert from '@/components/core/alert/BaseAlert.vue';
import { BaseButton } from '@/components/core/button';
import BaseSwitch from '@/components/core/form/radio/BaseSwitch.vue';
import { TypeVariant } from '@/enums/type-variants';
import { useModules } from '@/composables/acl/useModules';
import { hasAbility } from '@/lib/permissions';
import { Module } from '@/types/acl';
import { useForm } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps<{
    module: Module;
}>();

const { saveModuleSettings, dashboardTabOptions } = useModules();

const isDashboardModule = computed(() => props.module.attributes.slug === 'dashboards');

const form = useForm({
    status: props.module.attributes.status ?? true,
    settings: {
        tabs: {
            overview: props.module.attributes.settings?.tabs?.overview ?? true,
            academic: props.module.attributes.settings?.tabs?.academic ?? true,
            enrolments: props.module.attributes.settings?.tabs?.enrolments ?? true,
            attendance: props.module.attributes.settings?.tabs?.attendance ?? true,
            staff: props.module.attributes.settings?.tabs?.staff ?? true,
            finance: props.module.attributes.settings?.tabs?.finance ?? true,
            hostel: props.module.attributes.settings?.tabs?.hostel ?? true,
        },
    },
});

const save = () => {
    saveModuleSettings(form, props.module);
};
</script>

<template>
    <div class="space-y-6">
        <p class="text-sm text-muted-foreground">{{ $t('trans.module_settings_description') }}</p>

        <BaseAlert
            v-if="!hasAbility('update:modules')"
            :title="$t('trans.forbidden')"
            :description="$t('trans.forbidden_message')"
            :type="TypeVariant.danger"
        />

        <form v-else class="space-y-6" @submit.prevent="save">
            <div class="space-y-4 rounded-lg border border-border p-4">
                <BaseSwitch
                    input-id="module_status"
                    v-model="form.status"
                    :label="$t('trans.module_enabled')"
                    :on-update="(value) => (form.status = value)"
                />
            </div>

            <div v-if="isDashboardModule" class="space-y-4 rounded-lg border border-border p-4">
                <div>
                    <h2 class="text-sm font-medium">{{ $t('trans.dashboard_tabs') }}</h2>
                    <p class="text-sm text-muted-foreground">{{ $t('trans.dashboard_tabs_description') }}</p>
                </div>

                <BaseSwitch
                    v-for="tab in dashboardTabOptions"
                    :key="tab.key"
                    :input-id="`dashboard_tab_${tab.key}`"
                    v-model="form.settings.tabs[tab.key]"
                    :label="tab.choice ? $tChoice(tab.labelKey, tab.choice) : $t(tab.labelKey)"
                    :on-update="(value) => (form.settings.tabs[tab.key] = value)"
                />
            </div>

            <BaseButton type="submit" :disabled="form.processing">
                {{ $t('trans.save') }}
            </BaseButton>
        </form>
    </div>
</template>
