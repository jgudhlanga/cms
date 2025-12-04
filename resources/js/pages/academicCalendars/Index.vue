<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import PageContainer from '@/components/core/page/PageContainer.vue';
import DataTable from '@/components/core/table/DataTable.vue';
import { AuthObject, DataFilters, DataListProps } from '@/types/data-pagination';
import CreateEdit from '@/pages/academicCalendars/partials/CreateEdit.vue';
import { AcademicCalendar } from '@/types/academic-calendar';
import { useAcademicCalendars } from '@/composables/academicCalendars/useAcademicCalendars';

const { createTableColumns, breadcrumbs, onOpenModal } = useAcademicCalendars();
const props = defineProps<{
    academicCalendars: DataListProps<AcademicCalendar>;
	trashedCount: any;
	filters: DataFilters;
	auth: AuthObject;
	errors: object;
}>();
const can = props?.auth?.can;
</script>

<template>
	<Head :title="$tChoice('trans.academic_calendar', 2)" />
	<PageContainer :breadcrumbs="breadcrumbs">
		<DataTable
			:data="academicCalendars.data"
			:trashed-count="trashedCount"
			:filters="filters"
			:search-url="route('academic-calendars.index')"
			:pagination="{ ...academicCalendars.links, ...academicCalendars.meta }"
			:columns="createTableColumns()"
			:on-create="() => onOpenModal(can['create:academic-calendars'])"
			:disable-create="!can['create:academic-calendars']"
		/>
		<CreateEdit />
	</PageContainer>
</template>
