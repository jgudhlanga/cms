import AppLogo from '@/components/core/image/AppLogo.vue';
import { TenantInterface } from '@/types/tenants';
import { markRaw } from 'vue';

export const tenants: Array<TenantInterface> = [
    {
        id: '1',
        type: 'tenant',
        attributes: {
            name: 'Harare Poly',
            isDefault: true,
            logo: markRaw(AppLogo),
            bio: 'Software',
        },
    },
];
