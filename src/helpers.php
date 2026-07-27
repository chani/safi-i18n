<?php

/**
 * Safi Microframework - safi-i18n
 * @author Jean Bruenn
 * @copyright 2026 All Rights Reserved
 * @see https://github.com/chani/safi-i18n
 */

declare(strict_types=1);

namespace Safi\Extensions\I18n;

if (!function_exists('__')) {
    /**
     * Global translation helper.
     *
     * @param array<string, mixed>|mixed $replacements
     */
    function __(string|\UnitEnum $key, mixed ...$replacements): string
    {
        $realKey = $key instanceof \UnitEnum
            ? ($key instanceof \BackedEnum ? (string) $key->value : $key->name)
            : $key;

        /** @var array<string, mixed> $args */
        $args = (isset($replacements[0]) && is_array($replacements[0]))
            ? $replacements[0]
            : $replacements;

        if ($args === []) {
            return $realKey;
        }

        $map = [];
        foreach ($args as $k => $v) {
            $strVal = (is_scalar($v) || $v instanceof \Stringable) ? (string) $v : '';
            $map["{{$k}}"] = $strVal;
            $map["%{$k}%"] = $strVal;
            $map[":{$k}"] = $strVal;
        }

        return strtr($realKey, $map);
    }
}
