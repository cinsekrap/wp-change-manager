<?php

namespace App\Support;

use Illuminate\Support\HtmlString;

/**
 * Renders a word-level inline diff between two pieces of text as safe HTML:
 * unchanged runs are shown plain, removed runs wrapped in <del> (red,
 * strike-through) and added runs in <ins> (green). Used to show admins (and,
 * via the wizard, requesters) exactly how a "change" differs from the current
 * content. A matching JS implementation (wordDiffHtml) powers the live preview.
 */
class WordDiff
{
    /**
     * @return HtmlString diff markup; every token is escaped before wrapping.
     */
    public static function toHtml(?string $old, ?string $new): HtmlString
    {
        $oldTokens = self::tokenize(self::normalize((string) $old));
        $newTokens = self::tokenize(self::normalize((string) $new));

        $html = '';
        foreach (self::diff($oldTokens, $newTokens) as [$op, $token]) {
            $escaped = e($token);

            $html .= match ($op) {
                'del' => '<del class="bg-red-100 text-red-700 line-through rounded px-0.5">' . $escaped . '</del>',
                'ins' => '<ins class="bg-green-100 text-green-800 no-underline rounded px-0.5">' . $escaped . '</ins>',
                default => $escaped,
            };
        }

        return new HtmlString($html);
    }

    /**
     * Normalise characters that Word and rich editors silently substitute, so
     * visually-identical text compares as identical (and the JS live preview,
     * which applies the same rules, stays in agreement). Must match the
     * normalize() in the wizard's wordDiffHtml(). Only used for comparison and
     * display of the diff — the raw text is stored unchanged.
     */
    public static function normalize(string $text): string
    {
        return strtr($text, [
            "\r\n" => "\n",
            "\r" => "\n",
            "\u{00A0}" => ' ', // non-breaking space
            "\u{2018}" => "'",  // left single quote
            "\u{2019}" => "'",  // right single quote / apostrophe
            "\u{201C}" => '"',  // left double quote
            "\u{201D}" => '"',  // right double quote
        ]);
    }

    /**
     * Split into words while keeping the whitespace runs as their own tokens,
     * so spacing and line breaks survive reconstruction.
     *
     * @return string[]
     */
    private static function tokenize(string $text): array
    {
        if ($text === '') {
            return [];
        }

        return preg_split('/(\s+)/u', $text, -1, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY);
    }

    /**
     * Classic LCS diff over tokens. Returns a list of [op, token] where op is
     * one of 'eq', 'del', 'ins'.
     *
     * @param  string[]  $a
     * @param  string[]  $b
     * @return array<int, array{0: string, 1: string}>
     */
    private static function diff(array $a, array $b): array
    {
        $n = count($a);
        $m = count($b);

        // lcs[i][j] = length of the longest common subsequence of a[i:] and b[j:]
        $lcs = array_fill(0, $n + 1, array_fill(0, $m + 1, 0));
        for ($i = $n - 1; $i >= 0; $i--) {
            for ($j = $m - 1; $j >= 0; $j--) {
                $lcs[$i][$j] = $a[$i] === $b[$j]
                    ? $lcs[$i + 1][$j + 1] + 1
                    : max($lcs[$i + 1][$j], $lcs[$i][$j + 1]);
            }
        }

        $result = [];
        $i = 0;
        $j = 0;
        while ($i < $n && $j < $m) {
            if ($a[$i] === $b[$j]) {
                $result[] = ['eq', $a[$i]];
                $i++;
                $j++;
            } elseif ($lcs[$i + 1][$j] >= $lcs[$i][$j + 1]) {
                $result[] = ['del', $a[$i]];
                $i++;
            } else {
                $result[] = ['ins', $b[$j]];
                $j++;
            }
        }
        while ($i < $n) {
            $result[] = ['del', $a[$i]];
            $i++;
        }
        while ($j < $m) {
            $result[] = ['ins', $b[$j]];
            $j++;
        }

        return $result;
    }
}
