import { APP_MODULE_KEYS } from '@/lib/constants';
import customAxios from '@/services/http-init';
import { useModalStore } from '@/store/core/useModalStore';
import type { RadioGroupOption } from '@/types/forms';
import type { StudyPositionItem, StudyPositionStatus, StudyPositionSummary } from '@/types/study-position';
import { useForm, usePage } from '@inertiajs/vue3';
import { trans } from 'laravel-vue-i18n';
import { computed, reactive, ref, watch } from 'vue';

const MODAL_KEY = APP_MODULE_KEYS.student_study_position_prompt;
const FOLLOW_UP_ANSWERS = ['not_sure', 'wrong_programme'] as const;

type FollowUpAnswer = (typeof FOLLOW_UP_ANSWERS)[number];

const isFollowUpAnswer = (value: string): value is FollowUpAnswer => (FOLLOW_UP_ANSWERS as readonly string[]).includes(value);

/**
 * The per-period "which phase are you studying?" prompt on student pages. It opens itself (and cannot
 * be dismissed) while any programme is unconfirmed; the banner can also open it on demand.
 */
export function useStudyPositionPrompt() {
    const page = usePage();
    const modalStore = useModalStore();

    const summary = computed<StudyPositionSummary | null>(() => (page.props.studyPosition as StudyPositionSummary | null | undefined) ?? null);
    const required = computed(() => Boolean(summary.value?.required));
    const isOpen = computed(() => Boolean(modalStore.isOpen(MODAL_KEY)));

    const status = ref<StudyPositionStatus | null>(null);
    const isLoading = ref(false);
    const loadError = ref(false);
    // Keyed by enrolment id: a programme_semester id as a string, or a follow-up answer.
    const answers = reactive<Record<number, string | null>>({});

    const form = useForm({
        answers: [] as Array<{
            student_enrolment_id: number;
            answer: string;
            programme_semester_id: number | null;
        }>,
    });

    const answerableItems = computed<StudyPositionItem[]>(() => status.value?.items.filter((item) => item.studentCanAnswer) ?? []);
    const answeredItems = computed<StudyPositionItem[]>(
        () => status.value?.items.filter((item) => !item.studentCanAnswer && item.confirmation !== null) ?? [],
    );
    const periodLabel = computed(() => status.value?.periodLabel ?? summary.value?.periodLabel ?? '');

    const optionsFor = (item: StudyPositionItem): RadioGroupOption[] => [
        ...item.options.map((phase) => ({
            inputId: `study-position-${item.enrolmentId}-${phase.id}`,
            label: phase.label,
            value: String(phase.id),
        })),
        ...FOLLOW_UP_ANSWERS.map((answer) => ({
            inputId: `study-position-${item.enrolmentId}-${answer}`,
            label: trans(`students.study_position_answer_${answer}`),
            value: answer,
        })),
    ];

    const errorFor = (index: number): string | undefined => {
        const errors = form.errors as Record<string, string | undefined>;

        return (
            errors[`answers.${index}.programme_semester_id`] ?? errors[`answers.${index}.student_enrolment_id`] ?? errors[`answers.${index}.answer`]
        );
    };

    const load = async (): Promise<void> => {
        isLoading.value = true;
        loadError.value = false;

        try {
            const response = await customAxios('').get<StudyPositionStatus>(route('portal.study-position.show'));
            status.value = response.data;

            for (const key of Object.keys(answers)) {
                delete answers[Number(key)];
            }

            // Nothing is preselected: the student has to make a deliberate choice.
            for (const item of response.data.items) {
                if (item.studentCanAnswer) {
                    answers[item.enrolmentId] = null;
                }
            }
        } catch {
            loadError.value = true;
        } finally {
            isLoading.value = false;
        }
    };

    const openPrompt = (): void => {
        modalStore.openModal(MODAL_KEY);
    };

    const onClose = (): void => {
        form.clearErrors();
    };

    const submit = (): void => {
        form.clearErrors();

        const missing = answerableItems.value.some((item) => !answers[item.enrolmentId]);

        if (missing || answerableItems.value.length === 0) {
            form.setError('answers', trans('students.study_position_answer_required'));

            return;
        }

        form.transform(() => ({
            answers: answerableItems.value.map((item) => {
                const value = String(answers[item.enrolmentId]);
                const followUp = isFollowUpAnswer(value);

                return {
                    student_enrolment_id: item.enrolmentId,
                    answer: followUp ? value : 'phase',
                    programme_semester_id: followUp ? null : Number(value),
                };
            }),
        })).post(route('portal.study-position.store'), {
            preserveScroll: true,
            onSuccess: () => {
                modalStore.closeModal(MODAL_KEY);
                status.value = null;
            },
        });
    };

    // Registered before the auto-open below so the first forced open still loads the programmes.
    watch(
        isOpen,
        (opened) => {
            if (opened) {
                void load();
            }
        },
        { immediate: true },
    );

    watch(
        required,
        (isRequired) => {
            if (isRequired && !isOpen.value) {
                openPrompt();
            }
        },
        { immediate: true },
    );

    return {
        MODAL_KEY,
        summary,
        required,
        status,
        isLoading,
        loadError,
        answers,
        form,
        answerableItems,
        answeredItems,
        periodLabel,
        optionsFor,
        errorFor,
        load,
        openPrompt,
        onClose,
        submit,
    };
}
