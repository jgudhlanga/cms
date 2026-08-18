<script setup lang="ts">
import BaseButton from '@/components/core/button/BaseButton.vue';
import BaseInput from '@/components/core/form/text/BaseInput.vue';
import HeadingSmall from '@/components/core/util/HeadingSmall.vue';
import { ButtonSize } from '@/enums/buttons';
import { TextFieldType } from '@/enums/inputs';
import { hasAbility } from '@/lib/permissions';
import type { Student } from '@/types/students';
import { useForm } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

const props = defineProps<{
    student: Student;
}>();

const preview = ref<string | null>(null);
const form = useForm({
    photo: null as File | null,
});

const canUpload = computed(() =>
    hasAbility(['uploadIdPhoto:students', 'update:students', 'manageOwnStudentPersonalDetails:students']),
);

const photoUrl = computed(
    () => preview.value ?? props.student.attributes?.idPhotoUrl ?? props.student.attributes?.idPhotoThumbUrl ?? null,
);

const hasIdentity = computed(() => {
    const attributes = props.student.attributes;
    const idNumber = attributes?.idNumber?.trim() ?? '';
    const passport = attributes?.passportNumber?.trim() ?? '';

    return idNumber !== '' || passport !== '';
});

const studentId = computed(() => Number(props.student.id));

const handleChange = (event: Event) => {
    const file = (event.target as HTMLInputElement).files?.[0];
    if (!file) {
        return;
    }

    form.photo = file;
    if (preview.value) {
        URL.revokeObjectURL(preview.value);
    }
    preview.value = URL.createObjectURL(file);
};

const upload = () => {
    if (!studentId.value) {
        return;
    }

    form.post(route('students.id-photo.store', studentId.value), {
        forceFormData: true,
        preserveScroll: true,
        onSuccess: () => {
            form.reset();
        },
    });
};
</script>

<template>
    <section v-if="canUpload" class="rounded-2xl border border-border bg-card p-5 shadow-sm">
        <HeadingSmall :title="$t('trans.student_id_card_photo')" />
        <p class="mt-1 text-sm text-muted-foreground">
            {{ $t('trans.student_id_card_staff_photo_help') }}
        </p>
        <div class="mt-4 grid gap-4 sm:grid-cols-[8rem_minmax(0,1fr)]">
            <div class="aspect-35/45 overflow-hidden rounded-lg border border-border bg-muted">
                <img
                    v-if="photoUrl"
                    :src="photoUrl"
                    alt=""
                    class="h-full w-full object-cover"
                >
            </div>
            <div class="space-y-3">
                <BaseInput
                    input-id="staff-id-photo"
                    :label="$t('trans.student_id_card_upload_photo')"
                    :type="TextFieldType.file"
                    :error="form.errors.photo"
                    accept="image/jpeg,image/png"
                    @change="handleChange"
                />
                <BaseButton
                    :title="student.attributes?.idPhotoUrl ? $t('trans.student_id_card_replace_photo') : $t('trans.student_id_card_upload_photo')"
                    :processing="form.processing"
                    :disabled="!form.photo || !hasIdentity"
                    :size="ButtonSize.sm"
                    @click="upload"
                />
                <p v-if="!hasIdentity" class="text-sm text-muted-foreground">
                    {{ $t('trans.student_id_card_gallery_no_identity') }}
                </p>
            </div>
        </div>
    </section>
</template>
