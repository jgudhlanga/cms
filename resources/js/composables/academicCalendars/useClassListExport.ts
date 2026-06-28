import { APP_MODULE_KEYS } from '@/lib/constants';
import { useModalStore } from '@/store/core/useModalStore';

export function openClassListExportModal(): void {
    const { openModal } = useModalStore();
    openModal(APP_MODULE_KEYS.class_list_export);
}
