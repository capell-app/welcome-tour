<?php

declare(strict_types=1);

namespace Capell\WelcomeTour\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

final class WelcomeTourSelector implements ValidationRule
{
    public static function accepts(mixed $value): bool
    {
        if ($value === null || $value === '') {
            return true;
        }

        // Stable tour anchors use tags, IDs, classes and attributes, with optional combinators.
        $identifier = '[a-zA-Z_][a-zA-Z0-9_-]*';
        $attribute = '\\[' . $identifier . '(?:=(?:"[^"\\[\\]<>]*"|\'[^\'\\[\\]<>]*\'|' . $identifier . '))?\\]';
        $compound = '(?:' . $identifier . '|\\*)?(?:[.#]' . $identifier . '|' . $attribute . ')*';

        return is_string($value)
            && trim($value) !== ''
            && preg_match('!^(?=\\S)' . $compound . '(?:(?:\\s*[>+~]\\s*|\\s+)(?=\\S)' . $compound . ')*$!D', trim($value)) === 1;
    }

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! self::accepts($value)) {
            $fail(__('capell-welcome-tour::welcome_tour.invalid_selector'));
        }
    }
}
