<?php

namespace App\Rules;

use App\Support\SafeUrl;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * A value that is safe to render as a link.
 *
 * Not `url`: the wizard legitimately submits values that are not absolute URLs —
 * a relative path, or the placeholder a content request carries before it has a
 * page. This rejects only what a browser would treat as something other than a
 * web address.
 */
class WebUrl implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! SafeUrl::isSafe(is_string($value) ? $value : null)) {
            $fail('The :attribute must be a web address.');
        }
    }
}
