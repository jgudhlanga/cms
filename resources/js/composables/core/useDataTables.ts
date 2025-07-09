import BaseButton from '@/components/core/button/BaseButton.vue';
import CountButton from '@/components/core/button/CountButton.vue';
import DropdownButton from '@/components/core/button/DropdownButton.vue';
import BaseIcon from '@/components/core/icon/BaseIcon.vue';
import OrderComponent from '@/components/core/table/OrderComponent.vue';
import BaseTag from '@/components/core/util/BaseTag.vue';
import TextLink from '@/components/core/util/TextLink.vue';
import UserAvatar from '@/components/core/util/UserAvatar.vue';
import { useUtils } from '@/composables/core/useUtils';
import { ColorVariant } from '@/enums/colors';
import { IconName } from '@/enums/icons';
import { dangerDialog, forbiddenAlert, successAlert, warningDialog } from '@/lib/alerts';
import { AvatarParams, ButtonDropdownOption, TableButton } from '@/types/tables';
import { router } from '@inertiajs/vue3';
import {
    getCoreRowModel,
    getFilteredRowModel,
    getPaginationRowModel,
    getSortedRowModel,
    SortingState,
    type Table,
    useVueTable
} from '@tanstack/vue-table';
import { trans } from 'laravel-vue-i18n';
import { debounce } from 'lodash';
import { h, Ref, ref } from 'vue';

/**
 * Provides a set of utilities for managing data tables. This includes
 * functionalities for initializing tables, managing column visibility,
 * searching, pagination, and various actions like delete, restore, and view.
 *
 * The utility leverages the Vue Table library for handling core,
 * pagination, sorting, and filtering row models. It also provides debounced
 * methods for server-side data fetching based on search input and pagination
 * changes.
 *
 * Returns an object with the following methods:
 *
 * - `initialize`: Initializes a data table with provided data and columns.
 * - `toggleColumnVisibility`: Toggles the visibility of a given column.
 * - `tableSearch`: Debounced function to perform a search on the table.
 * - `setPageSize`: Debounced function to set the page size of the table.
 * - `goToPage`: Navigates to a specified page.
 * - `loadTrashed`: Loads trashed items based on filters.
 * - `moreActionButton`: Renders a dropdown button for additional actions.
 * - `countActionButton`: Renders a button displaying a count with an action.
 * - `actionButton`: Renders a generic action button.
 * - `checkStatusIcon`: Renders an icon indicating status.
 * - `textLink`: Renders a clickable text link.
 * - `onView`: Initiates a view action if the user has permissions.
 * - `onDelete`: Initiates a delete action with a confirmation dialog.
 * - `onForceDelete`: Initiates a force delete action with a confirmation dialog.
 * - `onRestore`: Initiates a restore action.
 */

export function useDataTables() {
    const columnVisibility = ref({});
    const sorting = ref<SortingState>([]);
    const { isItTrue } = useUtils();
    /**
     * Initializes a data table with provided data and columns.
     *
     * @param props - A data table initialization object with `data` and `columns` properties.
     * @param props.data - An array of objects representing the data to be used in the data table.
     * @param props.columns - An array of objects representing the columns of the data table.
     * @returns A Vue Table instance.
     */
    const initialize = (props: { data: Array<any>; columns: Array<any> }) =>
        useVueTable({
            get data() {
                return props.data;
            },
            get columns() {
                return props.columns;
            },
            getCoreRowModel: getCoreRowModel(),
            getPaginationRowModel: getPaginationRowModel(),
            getSortedRowModel: getSortedRowModel(),
            getFilteredRowModel: getFilteredRowModel(),
            state: {
                get sorting() {
                    return sorting.value;
                },
                get columnVisibility() {
                    return columnVisibility.value;
                },
            },
            onSortingChange: (updaterOrValue) => {
                sorting.value = typeof updaterOrValue === 'function' ? updaterOrValue(sorting.value) : updaterOrValue;
            },
        });

    /**
     * Toggles the visibility of a column in a data table.
     *
     * @param column - A column object from a Vue Table instance.
     */
    const toggleColumnVisibility = (column: any) => {
        columnVisibility.value = {
            ...columnVisibility.value,
            [column.id]: !column.getIsVisible(),
        };
    };

    const requestOptions = { preserveState: true, replace: true };

    /**
     * Returns a debounced function that performs a search on a data table based on given filters.
     *
     * @param pageSize - A reactive reference to the page size filter.
     * @param currentPage - A reactive reference to the current page filter.
     * @param onlyTrashed - A reactive reference to the trashed filter.
     * @param url - An optional URL to make the request to.
     * @param useApi
     * @param apiFetchAction
     * @returns A debounced function that performs a search on a data table.
     */
    const tableSearch = (
        pageSize: Ref,
        currentPage: Ref,
        onlyTrashed: Ref,
        url?: string,
        useApi?: boolean,
        apiFetchAction?: (url: string) => void,
    ) => {
        return debounce(async function (value) {
            let data = { search: value };
            if (pageSize.value) {
                data = { ...data, ...{ page_size: pageSize.value } };
            }
            if (onlyTrashed.value) {
                data = { ...data, ...{ trashed: onlyTrashed.value } };
            }
            if (url) {
                if (useApi) {
                    const query = new URLSearchParams(data).toString();
                    if (apiFetchAction) {
                        apiFetchAction(`${url}?${query}`);
                    }
                } else {
                    router.get(url, data, requestOptions);
                }
            }
            currentPage.value = 1;
        }, 600);
    };

    /**
     * Returns a debounced function that sets the page size for a data table.
     *
     * @param filter - A reactive reference to the search filter.
     * @param table - The Vue Table instance.
     * @param currentPage - A reactive reference to the current page filter.
     * @param onlyTrashed - A reactive reference to the trashed filter.
     * @param url - An optional URL to make the request to.
     * @param useApi
     * @param apiFetchAction
     * @returns A debounced function that sets the page size for a data table.
     */
    const setPageSize = (
        filter: Ref,
        table: Table<any>,
        currentPage: Ref,
        onlyTrashed: Ref,
        url?: string,
        useApi?: boolean,
        apiFetchAction?: (url: string) => void,
    ) => {
        return debounce(async function (value) {
            let data = { page_size: value };
            if (filter.value) {
                data = { ...data, ...{ search: filter.value } };
            }
            if (onlyTrashed.value) {
                data = { ...data, ...{ trashed: onlyTrashed.value } };
            }
            if (url) {
                if (useApi) {
                    const query = new URLSearchParams(data).toString();
                    if (apiFetchAction) {
                        apiFetchAction(`${url}?${query}`);
                    }
                } else {
                    router.get(url, data, requestOptions);
                }
            }
            table.setPageSize(+value);
            currentPage.value = 1;
        }, 600);
    };

    /**
     * Returns a function that sets the page number for a data table.
     *
     * @param filter - A reactive reference to the search filter.
     * @param pageSize - A reactive reference to the page size filter.
     * @param onlyTrashed - A reactive reference to the trashed filter.
     * @param url - An optional URL to make the request to.
     * @param useApi
     * @param apiFetchAction
     * @returns A function that sets the page number for a data table.
     */
    const goToPage = (filter: Ref, pageSize: Ref, onlyTrashed: Ref, url?: string, useApi?: boolean, apiFetchAction?: (url: string) => void) => {
        return async function (value: any) {
            let data = { page: value };
            if (filter.value) {
                data = { ...data, ...{ search: filter.value } };
            }
            if (pageSize.value) {
                data = { ...data, ...{ page_size: pageSize.value } };
            }
            if (onlyTrashed.value) {
                data = { ...data, ...{ trashed: onlyTrashed.value } };
            }
            if (url) {
                if (useApi) {
                    const query = new URLSearchParams(data).toString();
                    if (apiFetchAction) {
                        apiFetchAction(`${url}?${query}`);
                    }
                } else {
                    router.get(url, data, requestOptions);
                }
            }
        };
    };

    /**
     * Returns a function that sets the trashed filter for a data table.
     *
     * @param filter - A reactive reference to the search filter.
     * @param pageSize - A reactive reference to the page size filter.
     * @param currentPage - A reactive reference to the current page filter.
     * @param url - An optional URL to make the request to.
     * @param useApi
     * @param apiFetchAction
     * @returns A function that sets the trashed filter for a data table.
     */
    const loadTrashed = (filter: Ref, pageSize: Ref, currentPage: Ref, url?: string, useApi?: boolean, apiFetchAction?: (url: string) => void) => {
        return async function (value: any) {
            let data = { trashed: value };

            if (filter.value) {
                data = { ...data, ...{ search: filter.value } };
            }
            if (pageSize.value) {
                data = { ...data, ...{ page_size: pageSize.value } };
            }
            if (url) {
                if (useApi) {
                    const query = new URLSearchParams(data).toString();
                    if (apiFetchAction) {
                        apiFetchAction(`${url}?${query}`);
                    }
                } else {
                    router.get(url, data, requestOptions);
                }
            }
            currentPage.value = 1;
        };
    };

    /**
     * Returns a rendered DropdownButton component with options filtered based on the archive state.
     *
     * @param isArchived - A boolean indicating if the item is archived.
     * @param params - An array of ButtonDropdownOption objects to filter options from.
     * @returns A rendered DropdownButton component with appropriate options.
     */
    const moreActionButton = (isArchived: boolean, params: Array<ButtonDropdownOption>) => {
        let options = [];
        if (isArchived) {
            options = params?.filter((item: ButtonDropdownOption) => item.key === 'restore');
        } else {
            options = params?.filter((item: ButtonDropdownOption) => item.key !== 'restore');
        }
        return h(DropdownButton, {
            options: options,
            onlyIcon: true,
        });
    };

    /**
     * Returns a rendered CountButton component with title, variant and classes.
     * If params.variant is not provided, it will default to ColorVariant.primary_outline.
     * If params.classes is not provided, it will default to 'h-6 p-2 rounded-full'.
     * If params.onClick is not provided, it will be null.
     *
     * @param params - An object with title, variant and classes properties.
     * @returns A rendered CountButton component.
     */
    const countActionButton = (params: TableButton) => {
        return h(CountButton, {
            count: params.title,
            variant: params?.variant ?? ColorVariant.primary_outline,
            classes: params?.classes ?? 'h-6 p-2 rounded-full',
            onClick: params?.onClick,
        });
    };

    /**
     * Returns a rendered BaseButton component with title, variant and classes.
     * If params.variant is not provided, it will default to ColorVariant.fuchsia_outline.
     * If params.classes is not provided, it will default to 'rounded-full h-7 capitalize font-normal'.
     * If params.onClick is not provided, it will be null.
     *
     * @param params - An object with title, variant and classes properties.
     * @returns A rendered BaseButton component.
     */
    const actionButton = (params: TableButton) => {
        return h(BaseButton, {
            title: params.title,
            variant: params?.variant ?? ColorVariant.fuchsia_outline,
            classes: params?.classes ?? 'rounded-full h-7 capitalize font-normal',
            onClick: params?.onClick,
        });
    };

    /**
     * Returns a rendered BaseIcon component with check or close icon based on the given status.
     * If the status is true, it will render a check icon, otherwise it will render a close icon.
     * The color of the icon will be set to ColorVariant.shade.
     *
     * @param status - The status to check. Can be a boolean, string or number.
     * @returns A rendered BaseIcon component.
     */
    const checkStatusIcon = (status: any) => {
        const isTrue = isItTrue(status);
        return h(BaseIcon, {
            color: ColorVariant.shade,
            name: isTrue ? IconName.check : IconName.close,
            size: '18',
        });
    };

    /**
     * Renders a TextLink component with the given href and title.
     *
     * @param href - The href attribute of the link.
     * @param title - The title attribute of the link.
     * @returns A rendered TextLink component.
     */
    const textLink = (href: string, title: string) => {
        return h(TextLink, { href }, () => title);
    };

    /**
     * Open a view route if the user has the necessary permissions.
     * Otherwise, it will show a forbidden alert.
     * @param can - Whether the user can view the resource
     * @param url - The URL of the view route
     */
    const onView = (can: boolean, url: string) => {
        if (!can) {
            return forbiddenAlert();
        }
        return router.get(url);
    };

    /**
     * Opens an edit route if the user has the necessary permissions.
     * Otherwise, it will show a forbidden alert.
     *
     * @param can - A boolean indicating whether the user can edit the resource.
     * @param url - The URL of the edit route.
     */

    const onEdit = (can: boolean, url: string) => {
        if (!can) {
            return forbiddenAlert();
        }
        return router.get(url);
    };

    /**
     * Initiates the deletion process for an item, showing appropriate dialogs and alerts.
     * If the user lacks permission, a forbidden alert is displayed.
     * On confirmation, the item is archived and a success alert is shown.
     *
     * @param can - A boolean indicating if the user has permission to delete the item.
     * @param url - The URL endpoint for deleting the item.
     * @param itemName - The name of the item to be deleted, used in alerts.
     */
    const onDelete = (can: boolean, url: string, itemName: string) => {
        if (!can) {
            return forbiddenAlert();
        }
        warningDialog(() => {
            router.delete(url);
            successAlert(trans('trans.item_archived', { item: itemName }));
            return true;
        });
    };

    /**
     * Permanently deletes an item after showing a confirmation dialog, if the user has the necessary permissions.
     * Displays a forbidden alert if the user lacks permissions.
     * On confirmation, the item is deleted and a success alert is shown.
     *
     * @param can - A boolean indicating if the user has permission to force delete the item.
     * @param url - The URL endpoint for deleting the item.
     * @param itemName - The name of the item to be deleted, used in alerts.
     */
    const onForceDelete = (can: boolean, url: string, itemName: string) => {
        if (!can) {
            return forbiddenAlert();
        }
        dangerDialog(() => {
            router.delete(url);
            successAlert(trans('trans.item_deleted', { item: itemName }));
            return true;
        });
    };

    /**
     * Restores an item if the user has the necessary permissions.
     * Displays a forbidden alert if the user lacks permissions.
     * On successful restoration, a success alert is shown.
     *
     * @param can - A boolean indicating if the user has permission to restore the item.
     * @param url - The URL endpoint for restoring the item.
     * @param itemName - The name of the item to be restored, used in alerts.
     */
    const onRestore = (can: boolean, url: string, itemName: string) => {
        if (!can) {
            return forbiddenAlert();
        }
        router.put(url);
        successAlert(trans('trans.item_restored', { item: itemName }));
    };

    const orderButtons = () => {
        return h(OrderComponent, {});
    };

    const avatar = (params: AvatarParams) => {
        return h(UserAvatar, { href: params.href, src: params.src, classes: params.classes, title: params.title });
    };

    const tag = (title: string, classes?: string, variant?: ColorVariant) => {
        return h(BaseTag, { title, classes, variant });
    };

    return {
        initialize,
        toggleColumnVisibility,
        tableSearch,
        setPageSize,
        goToPage,
        loadTrashed,
        moreActionButton,
        countActionButton,
        onDelete,
        onForceDelete,
        onRestore,
        onView,
        onEdit,
        checkStatusIcon,
        actionButton,
        textLink,
        avatar,
        orderButtons,
        tag,
    };
}
