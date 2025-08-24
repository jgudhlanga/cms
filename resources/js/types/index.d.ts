
import { trans, trans_choice } from 'laravel-vue-i18n';
export interface Auth {
    user: User;
    can: any;
}

export type PageModule = {
    default: {
        layout?: unknown; // Adjust type if you have specific layouts
    };
};

export type PageProps<T extends Record<string, unknown> = Record<string, unknown>> = T & {
    auth: {
        user: User;
        can: any;
    };
    ziggy: Config & { location: string };
};

declare module '@tanstack/table-core' {
    // eslint-disable-next-line @typescript-eslint/no-unused-vars
    interface ColumnMeta<T, K> {
        align?: string;
    }
}


declare module '@vue/runtime-core' {
    interface ComponentCustomProperties {
        route: (name: string, params?: RouteParamsWithQueryOverload | undefined, absolute?: boolean | undefined) => string;
        $t: typeof trans;
        $tChoice: typeof trans_choice;
    }
}

// shims-wangeditor.d.ts (in your src/ or types/ folder)
declare module '@wangeditor/editor-for-vue' {
    import Editor from '@wangeditor/editor-for-vue/dist/src/index'
    export * from '@wangeditor/editor-for-vue/dist/src/index'
    export default Editor
}

// shims-wangeditor.d.ts
declare module '@wangeditor/editor-for-vue';
