<?php

/**
 * Safi Microframework - safi-i18n
 * @author Jean Bruenn
 * @copyright 2026 All Rights Reserved
 * @see https://github.com/chani/safi-i18n
 */

declare(strict_types=1);

namespace {

    use Psr\Container\ContainerInterface;
    use Safi\Extensions\I18n\Translator;

    if (!function_exists('__')) {
        /**
         * Translates a given key with optional placeholder replacements.
         */
        function __(string|\UnitEnum $key, mixed ...$replacements): string
        {
            global $safiContainer;

            $realKey = $key instanceof \UnitEnum
                ? ($key instanceof \BackedEnum ? (string) $key->value : $key->name)
                : $key;

            /** @var array<string, mixed> $args */
            $args = (isset($replacements[0]) && is_array($replacements[0]))
                ? $replacements[0]
                : $replacements;

            if (isset($safiContainer) && $safiContainer instanceof ContainerInterface && $safiContainer->has(Translator::class)) {
                /** @var Translator $translator */
                $translator = $safiContainer->get(Translator::class);

                return $translator->translate($realKey, $args);
            }

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
}
