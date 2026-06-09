<?php

namespace App\Support;

/**
 * Flesch-Kincaid reading-age estimate. A direct port of the wizard's JS
 * (calculateReadingAge / countSyllables in wizard-scripts.blade.php) so the
 * server-rendered approval view agrees with what requesters saw while typing.
 * Needs ~30 words to be meaningful; returns null below that (as the JS does).
 */
class ReadingAge
{
    public const MIN_WORDS = 30;

    // Flesch-Kincaid is unreliable on very short text, so the badge / absolute
    // "too high" check require 30 words. The increase comparison is a softer
    // nudge, so it scores from a lower floor to catch edits to shorter passages.
    public const MIN_WORDS_FOR_COMPARISON = 10;

    /** Estimated reading age, or null when there isn't enough text to score. */
    public static function grade(?string $text, ?int $minWords = null): ?int
    {
        $minWords ??= self::MIN_WORDS;
        $text = trim((string) $text);
        if ($text === '') {
            return null;
        }

        $words = preg_split('/\s+/', $text, -1, PREG_SPLIT_NO_EMPTY);
        $wordCount = count($words);
        if ($wordCount < $minWords) {
            return null;
        }

        $sentences = array_filter(
            preg_split('/[.!?]+/', $text, -1, PREG_SPLIT_NO_EMPTY),
            fn ($s) => trim($s) !== ''
        );
        $sentenceCount = max(count($sentences), 1);

        $totalSyllables = 0;
        foreach ($words as $w) {
            $totalSyllables += self::countSyllables($w);
        }

        $gradeLevel = 0.39 * ($wordCount / $sentenceCount)
            + 11.8 * ($totalSyllables / $wordCount)
            - 15.59;

        return max((int) round($gradeLevel + 5), 5);
    }

    /**
     * If both texts score and the updated text reads older, return [from, to];
     * otherwise null. Used to flag stylistic edits that raise the reading age.
     */
    public static function increase(?string $current, ?string $updated): ?array
    {
        $from = self::grade($current, self::MIN_WORDS_FOR_COMPARISON);
        $to = self::grade($updated, self::MIN_WORDS_FOR_COMPARISON);

        if ($from !== null && $to !== null && $to > $from) {
            return ['from' => $from, 'to' => $to];
        }

        return null;
    }

    private static function countSyllables(string $word): int
    {
        $word = preg_replace('/[^a-z]/', '', strtolower($word));
        if ($word === '' || $word === null) {
            return 1;
        }
        if (strlen($word) <= 3) {
            return 1;
        }
        $word = preg_replace('/(?:[^laeiouy]es|ed|[^laeiouy]e)$/', '', $word);
        $word = preg_replace('/^y/', '', $word);
        preg_match_all('/[aeiouy]{1,2}/', $word, $m);

        return max(count($m[0]), 1);
    }
}
