import { closeModal } from '@/lib/alerts';
import { APP_MODULE_KEYS } from '@/lib/constants';
import { hostelApplicationBlockerMessage } from '@/lib/hms/applicationBlockers';
import { useHms } from '@/composables/hms/useHms';
import { useHmsStore } from '@/store/hms/useHmsStore';
import { useModalStore } from '@/store/core/useModalStore';
import type {
    HostelApplicationEligibilityRule,
    HostelApplicationLookupRoomAvailability,
    HostelApplicationLookupSemester,
    HostelApplicationStudentLookup,
} from '@/types/hms';
import type { RadioGroupOption } from '@/types/forms';
import type { SelectOption } from '@/types/utils';
import { useForm } from '@inertiajs/vue3';
import { trans } from 'laravel-vue-i18n';
import { computed, ref, watch } from 'vue';

export function useHostelApplicationForm() {
    const { fetchApplicationStudentLookup, fetchHmsSettings, saveApplication } = useHms();
    const { modals } = useModalStore();
    const hmsStore = useHmsStore();

    const applicationType = ref<'student' | 'guest'>('student');
    const studentSearch = ref('');
    const isSearching = ref(false);
    const lookup = ref<HostelApplicationStudentLookup | null>(null);
    const eligibility = ref<HostelApplicationEligibilityRule[]>([]);
    const eligibilityPassed = ref(true);
    const gender = ref<SelectOption | null>(null);
    const saveValidationError = ref<string | null>(null);
    const canSubmitFromLookup = ref(true);
    const lookupBlockers = ref<string[]>([]);
    const semesterInfo = ref<HostelApplicationLookupSemester | null>(null);
    const roomAvailabilityInfo = ref<HostelApplicationLookupRoomAvailability | null>(null);
    const allowGuests = ref(false);

    const form = useForm({
        applicationType: 'student' as 'student' | 'guest',
        studentId: null as number | null,
        studentEnrolmentId: null as number | null,
        name: '',
        genderId: null as number | null,
        phoneNumber: '',
        emailAddress: '',
        nextOfKinName: '',
        nextOfKinContact: '',
        checkIn: '',
        checkOut: '',
    });

    const applicationTypeOptions = computed<RadioGroupOption[]>(() => {
        const options: RadioGroupOption[] = [
            {
                value: 'student',
                label: trans('hms.application_type_student'),
                inputId: 'application-type-student',
            },
        ];

        if (allowGuests.value) {
            options.push({
                value: 'guest',
                label: trans('hms.application_type_guest'),
                inputId: 'application-type-guest',
            });
        }

        return options;
    });

    const isStudent = computed(() => applicationType.value === 'student');
    const isGuest = computed(() => applicationType.value === 'guest');
    const showStudentLookupGrid = computed(() => isStudent.value && !!lookup.value);
    const showStudentSearchHelper = computed(() => isStudent.value && !lookup.value);
    const showSaveButton = computed(() => {
        if (!isStudent.value) {
            return true;
        }

        return !!lookup.value && !isSearching.value && canSubmitFromLookup.value;
    });

    const resetStudentFields = (): void => {
        lookup.value = null;
        eligibility.value = [];
        eligibilityPassed.value = true;
        form.studentId = null;
        form.studentEnrolmentId = null;
        form.name = '';
        form.phoneNumber = '';
        form.emailAddress = '';
        form.genderId = null;
        form.nextOfKinName = '';
        form.nextOfKinContact = '';
        form.checkIn = '';
        form.checkOut = '';
        canSubmitFromLookup.value = true;
        lookupBlockers.value = [];
        semesterInfo.value = null;
        roomAvailabilityInfo.value = null;
    };

    const loadAllowGuestsSetting = async (): Promise<void> => {
        const settings = await fetchHmsSettings();
        allowGuests.value = settings?.attributes.allowGuests ?? false;
        if (!allowGuests.value && applicationType.value === 'guest') {
            applicationType.value = 'student';
            form.applicationType = 'student';
        }
    };

    watch(modals!, () => {
        applicationType.value = 'student';
        studentSearch.value = '';
        saveValidationError.value = null;
        gender.value = null;
        resetStudentFields();
        form.reset();
        form.clearErrors();
        form.applicationType = 'student';
        void loadAllowGuestsSetting();
        form.defaults();
    });

    watch(applicationType, (type) => {
        form.applicationType = type;
        saveValidationError.value = null;
        resetStudentFields();
        if (type === 'guest') {
            gender.value = null;
        }
    });

    const isValidEmail = (value: string): boolean => /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(value);

    const validateForm = (): boolean => {
        form.clearErrors();
        saveValidationError.value = null;

        if (applicationType.value === 'student') {
            if (!lookup.value) {
                saveValidationError.value = trans('hms.student_search_required_before_save');
                return false;
            }

            if (!canSubmitFromLookup.value) {
                saveValidationError.value =
                    lookupBlockers.value.map((key) => hostelApplicationBlockerMessage(key)).join(' ') ||
                    trans('hms.student_search_required_before_save');
                return false;
            }

            if (!form.studentId) {
                form.setError('studentId', trans('hms.student_required'));
            }

            if (lookupBlockers.value.includes('pending_application_exists')) {
                form.setError('studentId', trans('hms.student_pending_application_exists'));
            }

            if (lookupBlockers.value.includes('student_already_allocated')) {
                form.setError('studentId', trans('hms.student_already_allocated'));
            }

        }

        if (applicationType.value === 'guest') {
            form.genderId = (gender.value?.value as number) ?? null;

            if (!form.name?.trim()) {
                form.setError('name', trans('hms.guest_name_required'));
            }

            if (!form.genderId) {
                form.setError('genderId', trans('hms.gender_required'));
            }

            if (!form.checkIn) {
                form.setError(
                    'checkIn',
                    trans('trans.required_field', { field: trans('hms.check_in') }),
                );
            }

            if (!form.checkOut) {
                form.setError(
                    'checkOut',
                    trans('trans.required_field', { field: trans('hms.check_out') }),
                );
            }

            if (form.checkIn && form.checkOut && form.checkOut <= form.checkIn) {
                form.setError('checkOut', trans('hms.check_out_after_check_in'));
            }
        }

        if (!form.nextOfKinName?.trim()) {
            form.setError('nextOfKinName', trans('hms.next_of_kin_required'));
        }

        if (!form.nextOfKinContact?.trim()) {
            form.setError('nextOfKinContact', trans('hms.next_of_kin_required'));
        }

        const email = form.emailAddress?.trim() ?? '';
        if (email && !isValidEmail(email)) {
            form.setError('emailAddress', trans('trans.enter_valid_email_address'));
        }

        return Object.keys(form.errors).length === 0 && !saveValidationError.value;
    };

    const searchStudent = async (): Promise<void> => {
        saveValidationError.value = null;
        if (!studentSearch.value.trim()) {
            return;
        }
        isSearching.value = true;
        lookup.value = null;
        try {
            const res = await fetchApplicationStudentLookup(studentSearch.value.trim());
            if (res?.found && res.student) {
                lookup.value = res.student;
                eligibility.value = res.eligibility ?? [];
                eligibilityPassed.value = res.eligibilityPassed ?? true;
                canSubmitFromLookup.value = res.canSubmit ?? false;
                lookupBlockers.value = res.blockers ?? [];
                semesterInfo.value = res.semester ?? null;
                roomAvailabilityInfo.value = res.roomAvailability ?? null;
                form.studentId = res.student.id;
                form.studentEnrolmentId = res.student.studentEnrolmentId ?? null;
                form.name = res.student.name ?? '';
                form.phoneNumber = res.student.phoneNumber ?? '';
                form.emailAddress = res.student.emailAddress ?? '';
                form.genderId = res.student.genderId ?? null;
                form.nextOfKinName = res.student.nextOfKinName ?? '';
                form.nextOfKinContact = res.student.nextOfKinContact ?? '';
                form.checkIn = res.semester?.checkIn ?? '';
                form.checkOut = res.semester?.checkOut ?? '';
                if (res.student.genderId) {
                    gender.value = { value: res.student.genderId, label: res.student.gender ?? '' };
                }
            }
        } finally {
            isSearching.value = false;
        }
    };

    const save = async (): Promise<void> => {
        form.applicationType = applicationType.value;

        if (!validateForm()) {
            return;
        }

        if (applicationType.value === 'guest') {
            form.genderId = (gender.value?.value as number) ?? null;
        }

        const attributes: Record<string, unknown> = {
            applicationType: form.applicationType,
            nextOfKinName: form.nextOfKinName,
            nextOfKinContact: form.nextOfKinContact,
            checkIn: form.checkIn,
            checkOut: form.checkOut,
        };

        if (applicationType.value === 'student') {
            attributes.studentId = form.studentId;
            attributes.studentEnrolmentId = form.studentEnrolmentId;
            attributes.genderId = form.genderId;
            attributes.phoneNumber = form.phoneNumber || null;
            attributes.emailAddress = form.emailAddress || null;
        } else {
            attributes.name = form.name;
            attributes.genderId = form.genderId;
            attributes.phoneNumber = form.phoneNumber || null;
            attributes.emailAddress = form.emailAddress || null;
        }

        const ok = await saveApplication(attributes);
        if (ok) {
            hmsStore.refreshApplications();
            closeModal(APP_MODULE_KEYS.hostel_applications);
        }
    };

    return {
        form,
        applicationType,
        applicationTypeOptions,
        isStudent,
        isGuest,
        showStudentLookupGrid,
        showStudentSearchHelper,
        showSaveButton,
        studentSearch,
        isSearching,
        lookup,
        eligibility,
        eligibilityPassed,
        canSubmitFromLookup,
        lookupBlockers,
        semesterInfo,
        roomAvailabilityInfo,
        gender,
        saveValidationError,
        searchStudent,
        save,
    };
}
