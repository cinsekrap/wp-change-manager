<?php

namespace App\Support;

/**
 * A URL that is safe to put in an href.
 *
 * Blade escapes the quotes around an attribute but does nothing about the scheme,
 * so a stored value can still name one the browser will execute. Anything that is
 * not plainly a web address is refused rather than rewritten — a link that does
 * nothing is better than one that does something unexpected.
 */
class SafeUrl
{
    private const ALLOWED_SCHEMES = ['http', 'https'];

    /** The URL if it is safe to link to, otherwise null. */
    public static function for(?string $url): ?string
    {
        $url = trim((string) $url);
        if ($url === '') {
            return null;
        }

        // Control characters are stripped by browsers before the scheme is read,
        // so "java\nscript:" would run. Reject rather than clean.
        if (preg_match('/[\x00-\x1F\x7F]/', $url)) {
            return null;
        }

        $scheme = parse_url($url, PHP_URL_SCHEME);

        // A relative path with no scheme stays on this site, which is fine.
        if ($scheme === null || $scheme === false) {
            return str_starts_with($url, '//') ? null : $url;
        }

        return in_array(strtolower($scheme), self::ALLOWED_SCHEMES, true) ? $url : null;
    }

    public static function isSafe(?string $url): bool
    {
        return self::for($url) !== null;
    }
}
