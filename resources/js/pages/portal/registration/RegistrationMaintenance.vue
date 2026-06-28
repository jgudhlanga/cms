<script setup lang="ts">
import BaseAlert from '@/components/core/alert/BaseAlert.vue';
import { BaseButton } from '@/components/core/button';
import BaseIcon from '@/components/core/icon/BaseIcon.vue';
import { useUtils } from '@/composables/core/useUtils';
import { ColorVariant } from '@/enums/colors';
import { IconName } from '@/enums/icons';
import { TypeVariant } from '@/enums/type-variants';
import RegistrationBrandHeader from '@/pages/portal/guest/components/RegistrationBrandHeader.vue';
import { useRegistrationAvailability } from '@/composables/students/useRegistrationAvailability';
import { Head, Link } from '@inertiajs/vue3';
import { trans } from 'laravel-vue-i18n';
import { computed, onMounted } from 'vue';

interface Props {
    status?: 'suspended' | 'closed' | null;
    message: string;
    intakeName?: string | null;
}

const props = defineProps<Props>();
const { navigateTo } = useUtils();
const { redirectIfOpen } = useRegistrationAvailability();

onMounted(() => {
    redirectIfOpen();
});

const title = computed(() => {
    if (props.status === 'suspended') {
        return trans('trans.registration_maintenance_title_suspended');
    }

    return trans('trans.registration_maintenance_title_closed');
});

const alertType = computed(() => (props.status === 'closed' ? TypeVariant.info : TypeVariant.warning));

const alertDescription = computed(() => {
    if (props.status === 'closed') {
        return trans('trans.registration_maintenance_title_closed');
    }

    return trans('trans.registration_maintenance_title_suspended');
});
</script>

<template>
    <Head :title="$t('trans.registration_maintenance_page_title')" />
    <div class="flex min-h-svh flex-col bg-background p-4 sm:p-6">
        <div class="mx-auto flex w-full max-w-lg flex-1 flex-col justify-center">
            <RegistrationBrandHeader />

            <div
                role="status"
                aria-live="polite"
                class="rounded-3xl border border-border/50 bg-card/70 p-8 text-card-foreground shadow-xl backdrop-blur-xl"
            >
                <div class="mb-6 flex justify-center">
                    <div
                        class="flex size-16 items-center justify-center rounded-full bg-amber-100 text-amber-700 dark:bg-amber-950/50 dark:text-amber-300"
                    >
                        <BaseIcon :name="IconName.warning" class="size-8" />
                    </div>
                </div>

                <div class="space-y-4 text-center">
                    <h1 class="text-xl font-semibold tracking-tight text-foreground">
                        {{ title }}
                    </h1>

                    <BaseAlert :type="alertType" :description="alertDescription" class="text-left" />

                    <p class="text-left text-sm leading-relaxed text-muted-foreground">
                        {{ message }}
                    </p>

                    <div class="space-y-3 pt-2">
                        <BaseButton
                            type="button"
                            class="w-full"
                            :variant="ColorVariant.primary"
                            @click="navigateTo(route('login'))"
                        >
                            {{ $t('trans.registration_maintenance_return_to_sign_in') }}
                        </BaseButton>

                        <p class="text-center text-xs text-muted-foreground">
                            {{ $t('trans.registration_maintenance_returning_hint') }}
                            <Link :href="route('login')" class="font-medium text-primary underline-offset-4 hover:underline">
                                {{ $t('trans.login') }}
                            </Link>
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
