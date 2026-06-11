<?php

declare(strict_types=1);

namespace App\Services\Maintenance\Staff;

class ImportLookupMatcher
{
    private const float FUZZY_SIMILARITY_THRESHOLD = 80.0;

    private const int LEVENSHTEIN_MAX_DISTANCE = 2;

    /**
     * @param  list<array{id: int, label: string, slug?: string|null}>  $candidates
     * @return array{id: int, label: string, score: float, matchType: 'exact'|'fuzzy'}|null
     */
    public function match(string $input, array $candidates): ?array
    {
        $normalizedInput = $this->normalize($input);

        if ($normalizedInput === '') {
            return null;
        }

        $bestMatch = null;
        $bestScore = 0.0;

        foreach ($candidates as $candidate) {
            $label = (string) ($candidate['label'] ?? '');
            $slug = isset($candidate['slug']) ? (string) $candidate['slug'] : null;

            if ($this->findExactMatch($normalizedInput, $label, $slug)) {
                return [
                    'id' => (int) $candidate['id'],
                    'label' => $label,
                    'score' => 100.0,
                    'matchType' => 'exact',
                ];
            }

            $fuzzyScore = $this->fuzzyScore($normalizedInput, $label, $slug);
            if ($fuzzyScore > $bestScore) {
                $bestScore = $fuzzyScore;
                $bestMatch = [
                    'id' => (int) $candidate['id'],
                    'label' => $label,
                    'score' => $fuzzyScore,
                    'matchType' => 'fuzzy',
                ];
            }
        }

        if ($bestMatch === null) {
            return null;
        }

        if ($bestScore >= self::FUZZY_SIMILARITY_THRESHOLD || $this->isCloseLevenshteinMatch($normalizedInput, $bestMatch['label'])) {
            return $bestMatch;
        }

        return null;
    }

    private function findExactMatch(string $normalizedInput, string $label, ?string $slug): bool
    {
        if ($this->normalize($label) === $normalizedInput) {
            return true;
        }

        if ($slug !== null && $this->normalize($slug) === $normalizedInput) {
            return true;
        }

        return false;
    }

    private function fuzzyScore(string $normalizedInput, string $label, ?string $slug): float
    {
        $scores = [0.0];

        similar_text($normalizedInput, $this->normalize($label), $percent);
        $scores[] = $percent;

        if ($slug !== null) {
            similar_text($normalizedInput, $this->normalize($slug), $slugPercent);
            $scores[] = $slugPercent;
        }

        return max($scores);
    }

    private function isCloseLevenshteinMatch(string $normalizedInput, string $label): bool
    {
        $normalizedLabel = $this->normalize($label);

        if ($normalizedLabel === '' || strlen($normalizedInput) > 50 || strlen($normalizedLabel) > 50) {
            return false;
        }

        return levenshtein($normalizedInput, $normalizedLabel) <= self::LEVENSHTEIN_MAX_DISTANCE;
    }

    private function normalize(string $value): string
    {
        return strtolower(trim($value));
    }
}
