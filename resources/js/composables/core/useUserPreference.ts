import { usePreferencesStore } from '@/store/core/preferences.store';
import HttpService from '@/services/http.service';
import ToastService from '@/services/toast.service';

type UserPreferencePayload = {
    id?: number | string;
    attributes?: {
        sideBarState?: boolean;
    };
    data?: {
        id?: number | string;
        attributes?: {
            sideBarState?: boolean;
        };
    };
};

export function useUserPreference() {
    const preferencesStore = usePreferencesStore();

    const parsePreferenceResponse = (response: UserPreferencePayload): { id: number | null; sideBarState: boolean | null } => {
        const preferenceData = response?.data ?? response;
        const sideBarState = preferenceData?.attributes?.sideBarState;

        return {
            id: preferenceData?.id ? Number(preferenceData.id) : null,
            sideBarState: typeof sideBarState === 'boolean' ? sideBarState : null,
        };
    };

    const hydratePreferenceOnce = async (): Promise<void> => {
        if (preferencesStore.hydratedFromServer) {
            return;
        }

        try {
            const response = await HttpService.get('api/v1/preferences');
            const parsedResponse = parsePreferenceResponse(response);

            if (parsedResponse.sideBarState === null) {
                preferencesStore.markHydrated();

                return;
            }

            preferencesStore.hydrateSidebarPreference(parsedResponse.sideBarState, parsedResponse.id);
        } catch {
            preferencesStore.markHydrated();
        }
    };

    const persistSidebarState = async (sideBarState: boolean): Promise<void> => {
        try {
            if (preferencesStore.preferenceId) {
                await HttpService.put(`api/v1/preferences/${preferencesStore.preferenceId}`, { side_bar_state: sideBarState });

                return;
            }

            const response = await HttpService.post('api/v1/preferences', { side_bar_state: sideBarState });
            const parsedResponse = parsePreferenceResponse(response);
            preferencesStore.preferenceId = parsedResponse.id;
        } catch {
            ToastService.error('Failed to save sidebar preference.');
        }
    };

    return {
        hydratePreferenceOnce,
        persistSidebarState,
    };
}
