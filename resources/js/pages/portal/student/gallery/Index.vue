<script setup lang="ts">
import BaseAlert from '@/components/core/alert/BaseAlert.vue';
import { BaseButton } from '@/components/core/button';
import BaseInput from '@/components/core/form/text/BaseInput.vue';
import PageContainer from '@/components/core/page/PageContainer.vue';
import OfferLettersPanel from '@/components/students/profile/OfferLettersPanel.vue';
import HeadingSmall from '@/components/core/util/HeadingSmall.vue';
import { ButtonSize } from '@/enums/buttons';
import { TextFieldType } from '@/enums/inputs';
import { TypeVariant } from '@/enums/type-variants';
import { isModuleEnabled } from '@/lib/permissions';
import type { AuthObject } from '@/types/data-pagination';
import type { Student } from '@/types/students';
import type { BreadcrumbItemInterface } from '@/types/ui';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

interface Props {
    student: Student;
    hasIdentity: boolean;
    idPhotoUrl: string | null;
    idPhotoThumbUrl: string | null;
    photoMinWidth: number;
    photoMinHeight: number;
    photoMaxKilobytes: number;
    offerLetterIntakePeriodIds?: Array<string | number>;
    auth: AuthObject;
    errors: Record<string, string>;
}

const props = defineProps<Props>();
const idPhotoPreview = ref<string | null>(null);

const breadcrumbs: BreadcrumbItemInterface[] = [
    { transChoiceKey: 'dashboard', href: route('portal.dashboard') },
    { transKey: 'trans.student_id_card_gallery' },
];

const idPhotoForm = useForm({
    photo: null as File | null,
});

const previewIdPhotoUrl = computed(() => idPhotoPreview.value ?? props.idPhotoUrl ?? props.idPhotoThumbUrl);
const maxMb = computed(() => Math.round(props.photoMaxKilobytes / 1024));

const assignPreview = (current: string | null, file: File): string => {
    if (current) {
        URL.revokeObjectURL(current);
    }

    return URL.createObjectURL(file);
};

const handleIdPhotoChange = (event: Event) => {
    const file = (event.target as HTMLInputElement).files?.[0];
    if (!file) {
        return;
    }

    idPhotoForm.photo = file;
    idPhotoPreview.value = assignPreview(idPhotoPreview.value, file);
};

const uploadIdPhoto = () => {
    idPhotoForm.post(route('portal.gallery.id-photo'), {
        forceFormData: true,
        preserveScroll: true,
        onSuccess: () => {
            idPhotoForm.reset();
        },
    });
};
</script>

<template>
    <Head :title="$t('trans.student_id_card_gallery')" />

    <PageContainer :breadcrumbs="breadcrumbs">
        <div class="flex w-full flex-col gap-6 px-4 py-4">
            <div>
                <h1 class="text-xl font-semibold text-foreground">
                    {{ $t('trans.student_id_card_gallery') }}
                </h1>
                <p class="mt-1 text-sm text-muted-foreground">
                    {{ $t('trans.student_id_card_gallery_help') }}
                </p>
            </div>

            <BaseAlert
                :type="TypeVariant.info"
                :description="$t('trans.student_id_card_photo_requirements', {
                    width: photoMinWidth,
                    height: photoMinHeight,
                    size: maxMb,
                })"
            />
            <BaseAlert
                v-if="!hasIdentity"
                :type="TypeVariant.warning"
                :description="$t('trans.student_id_card_gallery_no_identity')"
            />

            <section class="rounded-2xl border border-border bg-card p-5 shadow-sm">
                <HeadingSmall :title="$t('trans.student_id_card_photo')" />
                <p class="mt-1 text-sm text-muted-foreground">
                    {{ $t('trans.student_id_card_gallery_id_photo_note') }}
                </p>
                <div class="mt-4 grid gap-4 sm:grid-cols-[8rem_minmax(0,1fr)]">
                    <div class="aspect-35/45 overflow-hidden rounded-lg border border-border bg-muted">
                        <img
                            v-if="previewIdPhotoUrl"
                            :src="previewIdPhotoUrl"
                            alt=""
                            class="h-full w-full object-cover"
                        >
                    </div>
                    <div class="space-y-3">
                        <BaseInput
                            input-id="gallery-id-photo"
                            :label="$t('trans.student_id_card_upload_photo')"
                            :type="TextFieldType.file"
                            :error="idPhotoForm.errors.photo || errors.photo"
                            accept="image/jpeg,image/png"
                            @change="handleIdPhotoChange"
                        />
                        <BaseButton
                            :title="idPhotoUrl ? $t('trans.student_id_card_replace_photo') : $t('trans.student_id_card_upload_photo')"
                            :processing="idPhotoForm.processing"
                            :disabled="!idPhotoForm.photo || !hasIdentity"
                            :size="ButtonSize.sm"
                            @click="uploadIdPhoto"
                        />
                    </div>
                </div>
            </section>

            <OfferLettersPanel
                :student="student"
                :offer-letter-intake-period-ids="offerLetterIntakePeriodIds"
            />

            <p v-if="isModuleEnabled('student-ids')" class="text-sm text-muted-foreground">
                <Link :href="route('portal.id-card.index')" class="font-medium text-primary underline-offset-4 hover:underline">
                    {{ $t('trans.student_id_card') }}
                </Link>
            </p>
        </div>
    </PageContainer>
</template>
