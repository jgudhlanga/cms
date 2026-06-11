<script setup lang="ts">
import {
    formatStaffImportDateOfBirth,
    isValidStaffImportDateOfBirth,
    isValidStaffImportEmail,
    isValidStaffImportPhone,
} from '@/composables/maintenance/staff-import/staffImportRowHelpers';
import StaffImportLookupCell from '@/pages/maintenance/partials/staff/StaffImportLookupCell.vue';
import StaffImportRolesCell from '@/pages/maintenance/partials/staff/StaffImportRolesCell.vue';
import { Check, Trash2, X } from 'lucide-vue-next';
import type {
    StaffImportFieldKey,
    StaffImportLookupOption,
    StaffImportPreviewLookups,
    StaffImportPreviewRow,
    StaffImportRowCorrection,
} from '@/types/staff-import';
import { computed } from 'vue';

const COLUMN_COUNT = 13;

const props = defineProps<{
    row: StaffImportPreviewRow;
    lookups: StaffImportPreviewLookups;
    correction: StaffImportRowCorrection;
    effectiveAction: StaffImportPreviewRow['action'];
    actionLabel: string;
    activeErrors: string[];
    createdFields: Set<StaffImportFieldKey>;
    createdRoleNames: Set<string>;
}>();

const emit = defineEmits<{
    'update:correction': [StaffImportRowCorrection];
    'lookup-created': [fieldKey: StaffImportFieldKey, option: StaffImportLookupOption];
    remove: [];
}>();

const effectiveEmail = computed((): string => {
    if (props.correction.email !== undefined && props.correction.email.trim() !== '') {
        return props.correction.email.trim();
    }

    return props.row.email?.trim() ?? '';
});

const effectivePhone = computed((): string => {
    if (props.correction.phoneNumber !== undefined && props.correction.phoneNumber.trim() !== '') {
        return props.correction.phoneNumber.trim();
    }

    return props.row.phoneNumber?.trim() ?? '';
});

const effectiveDateOfBirth = computed((): string => {
    if (props.correction.dateOfBirth !== undefined && props.correction.dateOfBirth.trim() !== '') {
        return formatStaffImportDateOfBirth(props.correction.dateOfBirth);
    }

    const rowDateOfBirth = props.row.dateOfBirth?.trim() ?? '';

    return rowDateOfBirth !== '' ? formatStaffImportDateOfBirth(rowDateOfBirth) : '';
});

const fieldInputClass = (isValid: boolean, hasCorrection: boolean): string => {
    if (hasCorrection && isValid) {
        return 'border-green-500 bg-green-50';
    }

    if (!isValid) {
        return 'border-destructive bg-destructive/5';
    }

    return 'border-border';
};

const emailInputClass = computed((): string => {
    return fieldInputClass(
        isValidStaffImportEmail(effectiveEmail.value),
        props.correction.email !== undefined,
    );
});

const phoneInputClass = computed((): string => {
    return fieldInputClass(
        isValidStaffImportPhone(effectivePhone.value),
        props.correction.phoneNumber !== undefined,
    );
});

const dateOfBirthInputClass = computed((): string => {
    return fieldInputClass(
        isValidStaffImportDateOfBirth(effectiveDateOfBirth.value),
        props.correction.dateOfBirth !== undefined,
    );
});

const onEmailInput = (event: Event): void => {
    const value = (event.target as HTMLInputElement).value;

    updateCorrection({ email: value });
};

const onPhoneInput = (event: Event): void => {
    const value = (event.target as HTMLInputElement).value;

    updateCorrection({ phoneNumber: value });
};

const onDateOfBirthInput = (event: Event): void => {
    const value = (event.target as HTMLInputElement).value;

    updateCorrection({ dateOfBirth: value });
};

const effectiveId = (
    correctionKey: keyof StaffImportRowCorrection,
    resolvedId: number | null,
): number | null => {
    const correctionValue = props.correction[correctionKey];

    if (typeof correctionValue === 'number') {
        return correctionValue;
    }

    return resolvedId;
};

const updateCorrection = (patch: Partial<StaffImportRowCorrection>): void => {
    emit('update:correction', {
        ...props.correction,
        ...patch,
    });
};

const selectedRoleIds = computed((): number[] => {
    if (props.correction.roleIds !== undefined) {
        return props.correction.roleIds;
    }

    return props.row.fields.roles
        .map((role) => role.resolvedId)
        .filter((id): id is number => id !== null);
});

const onLookupCreated = (fieldKey: StaffImportFieldKey, option: StaffImportLookupOption): void => {
    emit('lookup-created', fieldKey, option);
};
</script>

<template>
    <tr class="j-tr">
        <td class="j-td w-8">{{ row.rowNumber }}</td>
        <td class="j-td font-mono text-[10px]">{{ row.employeeNumber ?? '—' }}</td>
        <td class="j-td max-w-[8rem] truncate" :title="row.fullName ?? undefined">{{ row.fullName ?? '—' }}</td>
        <td class="j-td min-w-[8rem]">
            <input
                type="email"
                class="h-7 w-full rounded border bg-background px-1.5 text-[10px]"
                :class="emailInputClass"
                :value="effectiveEmail"
                :placeholder="$t('trans.email')"
                @input="onEmailInput"
            />
        </td>
        <td class="j-td min-w-[7rem]">
            <input
                type="tel"
                class="h-7 w-full rounded border bg-background px-1.5 text-[10px]"
                :class="phoneInputClass"
                :value="effectivePhone"
                :placeholder="$t('trans.phone_number')"
                @input="onPhoneInput"
            />
        </td>
        <td class="j-td min-w-[7rem]">
            <input
                type="date"
                class="h-7 w-full rounded border bg-background px-1.5 text-[10px]"
                :class="dateOfBirthInputClass"
                :value="effectiveDateOfBirth"
                @input="onDateOfBirthInput"
            />
        </td>
        <td class="j-td">
            <StaffImportLookupCell
                :field="row.fields.department"
                :options="lookups.departments"
                lookup-type="department"
                :creatable="true"
                :is-created="createdFields.has('department')"
                :model-value="effectiveId('institutionDepartmentId', row.fields.department.resolvedId)"
                @update:model-value="updateCorrection({ institutionDepartmentId: $event ?? undefined })"
                @created="onLookupCreated('department', $event)"
            />
        </td>
        <td class="j-td">
            <StaffImportLookupCell
                :field="row.fields.title"
                :options="lookups.titles"
                lookup-type="title"
                :creatable="true"
                :is-created="createdFields.has('title')"
                :model-value="effectiveId('titleId', row.fields.title.resolvedId)"
                @update:model-value="updateCorrection({ titleId: $event ?? undefined })"
                @created="onLookupCreated('title', $event)"
            />
        </td>
        <td class="j-td">
            <StaffImportLookupCell
                :field="row.fields.gender"
                :options="lookups.genders"
                lookup-type="gender"
                :creatable="true"
                :is-created="createdFields.has('gender')"
                :model-value="effectiveId('genderId', row.fields.gender.resolvedId)"
                @update:model-value="updateCorrection({ genderId: $event ?? undefined })"
                @created="onLookupCreated('gender', $event)"
            />
        </td>
        <td class="j-td">
            <StaffImportLookupCell
                :field="row.fields.maritalStatus"
                :options="lookups.maritalStatuses"
                lookup-type="marital_status"
                :creatable="true"
                :is-created="createdFields.has('maritalStatus')"
                :model-value="effectiveId('maritalStatusId', row.fields.maritalStatus.resolvedId)"
                @update:model-value="updateCorrection({ maritalStatusId: $event ?? undefined })"
                @created="onLookupCreated('maritalStatus', $event)"
            />
        </td>
        <td class="j-td">
            <StaffImportLookupCell
                :field="row.fields.employmentType"
                :options="lookups.employmentTypes"
                lookup-type="employment_type"
                :creatable="true"
                :is-created="createdFields.has('employmentType')"
                :model-value="effectiveId('employmentTypeId', row.fields.employmentType.resolvedId)"
                @update:model-value="updateCorrection({ employmentTypeId: $event ?? undefined })"
                @created="onLookupCreated('employmentType', $event)"
            />
        </td>
        <td class="j-td min-w-36">
            <StaffImportRolesCell
                :roles="row.fields.roles"
                :options="lookups.roles"
                :model-value="selectedRoleIds"
                :created-role-names="createdRoleNames"
                @update:model-value="updateCorrection({ roleIds: $event })"
                @created="onLookupCreated('roles', $event)"
            />
        </td>
        <td class="j-td w-12 text-center">
            <div class="flex flex-col items-center gap-0.5">
                <span
                    v-if="effectiveAction === 'create' || effectiveAction === 'update'"
                    class="inline-flex h-6 w-6 items-center justify-center rounded-full border border-green-200 bg-green-50"
                    :title="actionLabel"
                >
                    <Check class="h-3.5 w-3.5 text-green-700" />
                </span>
                <span
                    v-else-if="effectiveAction === 'fail'"
                    class="inline-flex h-6 w-6 items-center justify-center rounded-full border border-destructive/30 bg-destructive/10"
                    :title="actionLabel"
                >
                    <X class="h-3.5 w-3.5 text-destructive" />
                </span>
                <button
                    type="button"
                    class="inline-flex h-6 w-6 items-center justify-center rounded-full border border-border text-muted-foreground hover:border-destructive/30 hover:bg-destructive/10 hover:text-destructive"
                    :title="$t('trans.maintenance_staff_import_remove_row')"
                    @click="emit('remove')"
                >
                    <Trash2 class="h-3 w-3" />
                </button>
            </div>
            <p v-if="row.needsReview" class="mt-0.5 text-[10px] text-amber-700">
                {{ $t('trans.maintenance_staff_import_needs_review') }}
            </p>
        </td>
    </tr>
    <tr v-if="activeErrors.length > 0" class="j-tr bg-destructive/5">
        <td :colspan="COLUMN_COUNT" class="j-td whitespace-normal px-3 py-1.5 text-[10px] text-destructive">
            {{ activeErrors.join(' ') }}
        </td>
    </tr>
</template>
