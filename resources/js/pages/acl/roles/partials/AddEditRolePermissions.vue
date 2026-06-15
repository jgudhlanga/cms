<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import { ref } from 'vue';
import { BaseCheckbox } from '@/components/core/form';
import BaseModal from '@/components/core/modal/BaseModal.vue';
import { useRolePermissions } from '@/composables/acl/useRolePermissions';
import { SizeVariant } from '@/enums/sizes';
import { APP_MODULE_KEYS } from '@/lib/constants';
import { Permission, Role, RoleParams } from '@/types/acl';

const props = defineProps<{ role: Role; permissions: Array<Permission> }>();

const { groupPermissionsByModule, saveRolePermissions } = useRolePermissions();
const roleValue = props?.role;
const allPermissions = props?.permissions ?? null;
const form = useForm<RoleParams>({
	name: roleValue?.attributes?.name ?? '',
	description: roleValue?.attributes?.description ?? '',
	permissions: roleValue?.relationships?.permissions?.map((perm: any) => perm.id) ?? null,
});

const allSelected = ref(roleValue?.relationships?.permissions?.length === allPermissions?.length);
const assignRolePermissions = (value: string) => {
	if (form.permissions?.length == allPermissions?.length) {
		allSelected.value = true;
	} else {
		allSelected.value = false;
	}
};

const selectAll = () => {
	if (allSelected.value) {
		form.permissions = [];
		allSelected.value = false;
	} else {
		form.permissions = allPermissions?.map((perm: any) => perm.id);
		allSelected.value = true;
	}
};

const groupedPermissions = allPermissions ? groupPermissionsByModule(allPermissions!) : [];

</script>

<template>
	<BaseModal
		:name="APP_MODULE_KEYS.role_permissions"
		:title="`${$t('trans.edit_role_permissions')} - (${role.attributes.name})`"
		:on-form-action="() => saveRolePermissions(role, form)"
		:form="form"
		:size="SizeVariant.full">
		<template #body>
			<div class="flex flex-col">
				<div class="mb-2 flex">
					<BaseCheckbox
						input-id="select_all"
						:checked="allSelected"
						:label="`${$t('trans.select_all')} ${$tChoice('trans.permission', 2)}`"
						@click="selectAll()"
					/>
				</div>
				<div class="flex flex-col pb-3" v-for="(group, index) in groupedPermissions" :key="index">
					<div class="mb-1 mt-3">
						<div class="text-xs font-semibold uppercase">{{ index.toString() }}</div>
					</div>
					<div class="grid grid-cols-1 md:grid-cols-4 gap-x-3">
						<template v-for="(permission, index) in group" :key="permission['id']">
							<BaseCheckbox
								:input-id="`${permission['id']}_${index}`"
								:value="permission['id']"
								v-model="form.permissions"
								:label="permission['attributes']['name']"
								@change="assignRolePermissions($event.target.value)"
							/>
						</template>
					</div>
				</div>
			</div>
		</template>
	</BaseModal>
</template>
