<?php

namespace App\Support\Examinations;

/**
 * Parses a HEXCO "Individual Statement of Results" worksheet into Exam-Dump rows.
 */
final class ExaminationStatementSheetParser
{
    private const string LABEL_CANDIDATE = 'CANDIDATE NUMBER';

    private const string LABEL_COMMENT = 'COMMENT';

    private const string LABEL_SURNAME = 'SURNAME';

    private const string LABEL_FIRST_NAMES = 'FIRST NAMES';

    private const string LABEL_COURSE_LEVEL = 'COURSE LEVEL';

    private const string LABEL_COURSE_TITLE = 'COURSE TITLE';

    private const string HEADER_PAPER = 'PAPER No.';

    /**
     * @param  list<list<mixed>>  $rows
     * @return list<array<string, string|null>>
     */
    public function parse(array $rows, ?string $onlySession = null): array
    {
        $meta = $this->extractMeta($rows);
        $headerRowIndex = $this->findSubjectHeaderRow($rows);

        if ($headerRowIndex === null || $meta['candidate_number'] === null) {
            return [];
        }

        $normalizedOnlySession = $onlySession !== null
            ? $this->normalizeSessionFilter($onlySession)
            : null;

        $dumpRows = [];

        for ($index = $headerRowIndex + 1, $count = count($rows); $index < $count; $index++) {
            $row = $rows[$index];
            $subjectCode = $this->cellString($row, 3);
            $subject = $this->cellString($row, 4);
            $grade = $this->cellString($row, 7);
            $sessionRaw = $this->cellString($row, 8);

            if ($subjectCode === null || $sessionRaw === null) {
                continue;
            }

            if (strcasecmp($subjectCode, self::HEADER_PAPER) === 0) {
                continue;
            }

            $session = $this->sessionToIsoDate($sessionRaw);

            if ($session === null) {
                continue;
            }

            if ($normalizedOnlySession !== null && $this->normalizeSessionFilter($sessionRaw) !== $normalizedOnlySession) {
                continue;
            }

            $dumpRows[] = [
                ExaminationDumpColumns::DISCIPLINE => $meta['course_title'],
                ExaminationDumpColumns::COURSE_CODE => null,
                ExaminationDumpColumns::CANDIDATE_NUMBER => $meta['candidate_number'],
                ExaminationDumpColumns::SURNAME => $meta['surname'],
                ExaminationDumpColumns::FIRST_NAMES => $meta['first_names'],
                ExaminationDumpColumns::SUBJECT_CODE => $subjectCode,
                ExaminationDumpColumns::SUBJECT => $subject,
                ExaminationDumpColumns::GRADE => $grade,
                ExaminationDumpColumns::SESSION => $session,
                ExaminationDumpColumns::COURSE_COMMENT => $meta['course_comment'],
                ExaminationDumpColumns::COURSE_LEVEL => $meta['course_level'],
            ];
        }

        return $dumpRows;
    }

    /**
     * @param  list<list<mixed>>  $rows
     * @return array{
     *     candidate_number: ?string,
     *     course_comment: ?string,
     *     surname: ?string,
     *     first_names: ?string,
     *     course_level: ?string,
     *     course_title: ?string
     * }
     */
    private function extractMeta(array $rows): array
    {
        $meta = [
            'candidate_number' => null,
            'course_comment' => null,
            'surname' => null,
            'first_names' => null,
            'course_level' => null,
            'course_title' => null,
        ];

        foreach ($rows as $row) {
            $label = $this->cellString($row, 3);
            $value = $this->cellString($row, 6);

            if ($label === null) {
                continue;
            }

            $normalized = strtoupper(preg_replace('/\s+/', ' ', $label) ?? $label);

            match ($normalized) {
                self::LABEL_CANDIDATE => $meta['candidate_number'] = $value,
                self::LABEL_COMMENT => $meta['course_comment'] = $value,
                self::LABEL_SURNAME => $meta['surname'] = $value,
                self::LABEL_FIRST_NAMES => $meta['first_names'] = $value,
                self::LABEL_COURSE_LEVEL => $meta['course_level'] = $value,
                self::LABEL_COURSE_TITLE => $meta['course_title'] = $value,
                default => null,
            };
        }

        return $meta;
    }

    /**
     * @param  list<list<mixed>>  $rows
     */
    private function findSubjectHeaderRow(array $rows): ?int
    {
        foreach ($rows as $index => $row) {
            $paper = $this->cellString($row, 3);

            if ($paper !== null && strcasecmp($paper, self::HEADER_PAPER) === 0) {
                return $index;
            }
        }

        return null;
    }

    /**
     * @param  list<mixed>  $row
     */
    private function cellString(array $row, int $index): ?string
    {
        if (! array_key_exists($index, $row) || $row[$index] === null) {
            return null;
        }

        $value = $row[$index];

        if (is_float($value) || is_int($value)) {
            if (floor((float) $value) == $value) {
                $string = (string) (int) $value;
            } else {
                $string = rtrim(rtrim(sprintf('%.12F', $value), '0'), '.');
            }
        } else {
            $string = trim((string) $value);
        }

        return $string === '' ? null : $string;
    }

    public function sessionToIsoDate(string $session): ?string
    {
        $session = trim($session);

        if (preg_match('/^(\d{1,2})\/(\d{4})$/', $session, $matches) === 1) {
            $month = (int) $matches[1];
            $year = (int) $matches[2];

            if ($month < 1 || $month > 12 || $year < 1900 || $year > 2100) {
                return null;
            }

            return sprintf('%04d-%02d-01', $year, $month);
        }

        if (preg_match('/^\d{4}-\d{2}-\d{2}/', $session) === 1) {
            return substr($session, 0, 10);
        }

        return null;
    }

    private function normalizeSessionFilter(string $session): string
    {
        $session = trim($session);
        $iso = $this->sessionToIsoDate($session);

        if ($iso !== null) {
            return substr($iso, 0, 7);
        }

        return strtoupper($session);
    }
}
