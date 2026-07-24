import { computed, inject, provide, ref, type ComputedRef, type InjectionKey, type Ref } from 'vue';

const SIDEBAR_ACCORDION_KEY: InjectionKey<Ref<string | null>> = Symbol('sidebarAccordionOpenKey');

export function provideSidebarAccordion(): Ref<string | null> {
    const openKey = ref<string | null>(null);
    provide(SIDEBAR_ACCORDION_KEY, openKey);

    return openKey;
}

export function useSidebarAccordion(itemKey: ComputedRef<string> | string): {
    isOpen: ComputedRef<boolean>;
    setOpen: (value: boolean) => void;
} {
    const openKey = inject(SIDEBAR_ACCORDION_KEY, null);

    const resolvedKey = computed(() => (typeof itemKey === 'string' ? itemKey : itemKey.value));

    const isOpen = computed(() => openKey?.value === resolvedKey.value);

    function setOpen(value: boolean) {
        if (!openKey) {
            return;
        }

        if (value) {
            openKey.value = resolvedKey.value;
            return;
        }

        if (openKey.value === resolvedKey.value) {
            openKey.value = null;
        }
    }

    return { isOpen, setOpen };
}
