<?php

/**
 * Safi Microframework - safi-i18n
 * @author Jean Bruenn
 * @copyright 2026 All Rights Reserved
 * @see https://github.com/chani/safi-i18n
 */

declare(strict_types=1);

namespace Safi\Extensions\I18n;

use BackedEnum;
use UnitEnum;

final class Translator
{
    private static ?Translator $globalInstance = null;

    /** @var array<string, array<string, string>> */
    private array $loadedTranslations = [];

    public function __construct(
        private readonly string $langDir,
        private string $currentLocale = 'de',
        private readonly bool $debug = false,
    ) {
        $this->loadLocale($this->currentLocale);
        self::$globalInstance = $this;
    }

    public static function setGlobalInstance(Translator $translator): void
    {
        self::$globalInstance = $translator;
    }

    public static function getGlobalInstance(): ?Translator
    {
        return self::$globalInstance;
    }

    public function setLocale(string $locale): void
    {
        $this->currentLocale = $locale;
        $this->loadLocale($locale);
    }

    public function getLocale(): string
    {
        return $this->currentLocale;
    }

    /**
     * Translates a string using source-text as key.
     *
     * @param array<string, mixed> $replacements
     */
    public function translate(string|UnitEnum $key, array $replacements = [], ?string $component = null): string
    {
        $realKey = $key instanceof UnitEnum
            ? ($key instanceof BackedEnum ? (string) $key->value : $key->name)
            : $key;

        $translation = $this->lookup($realKey, $component) ?? $realKey;

        if ($replacements === []) {
            return $translation;
        }

        return $this->interpolate($translation, $replacements);
    }

    /**
     * Exports a security-gated JSON slice for JavaScript consumption.
     *
     * @param array<int, string> $activeComponents
     */
    public function exportJsSlice(bool $isPublicOnly, array $activeComponents = []): string
    {
        /** @var array<string, string> $slice */
        $slice = [];

        $publicFile = "{$this->langDir}/{$this->currentLocale}.public.json";
        if (file_exists($publicFile)) {
            $content = file_get_contents($publicFile);
            if ($content !== false) {
                /** @var array<string, string> $decoded */
                $decoded = json_decode($content, true) ?? [];
                $slice = array_merge($slice, $decoded);
            }
        }

        if ($isPublicOnly) {
            return json_encode($slice, JSON_THROW_ON_ERROR);
        }

        $privateFile = "{$this->langDir}/{$this->currentLocale}.json";
        if (file_exists($privateFile)) {
            $content = file_get_contents($privateFile);
            if ($content !== false) {
                /** @var array<string, string> $decoded */
                $decoded = json_decode($content, true) ?? [];
                $slice = array_merge($slice, $decoded);
            }
        }

        foreach ($activeComponents as $comp) {
            $compFile = "{$this->langDir}/components/{$comp}/{$this->currentLocale}.json";
            if (file_exists($compFile)) {
                $content = file_get_contents($compFile);
                if ($content !== false) {
                    /** @var array<string, string> $decoded */
                    $decoded = json_decode($content, true) ?? [];
                    $slice = array_merge($slice, $decoded);
                }
            }
        }

        return json_encode($slice, JSON_THROW_ON_ERROR);
    }

    private function lookup(string $key, ?string $component): ?string
    {
        if ($component !== null && isset($this->loadedTranslations[$component][$key])) {
            return $this->loadedTranslations[$component][$key];
        }

        return $this->loadedTranslations['global'][$key] ?? null;
    }

    private function loadLocale(string $locale): void
    {
        $cacheFile = "{$this->langDir}/.cache_{$locale}.php";

        if (!$this->debug && file_exists($cacheFile)) {
            /** @var array<string, array<string, string>> $cached */
            $cached = require $cacheFile;
            $this->loadedTranslations = $cached;
            return;
        }

        $globalFile = "{$this->langDir}/{$locale}.json";
        if (file_exists($globalFile)) {
            $content = file_get_contents($globalFile);
            if ($content !== false) {
                $decoded = json_decode($content, true);
                if (is_array($decoded)) {
                    /** @var array<string, string> $global */
                    $global = [];
                    foreach ($decoded as $k => $v) {
                        if (is_string($k) && (is_string($v) || is_numeric($v))) {
                            $global[$k] = (string) $v;
                        }
                    }
                    $this->loadedTranslations['global'] = $global;
                }
            }
        }
    }

    private function interpolate(string $message, array $replacements): string
    {
        $map = [];
        foreach ($replacements as $key => $value) {
            if ($value instanceof UnitEnum) {
                $strVal = $value instanceof BackedEnum ? (string) $value->value : $value->name;
            } elseif (is_scalar($value) || $value instanceof \Stringable) {
                $strVal = (string) $value;
            } else {
                $strVal = '';
            }

            $map["{{$key}}"] = $strVal;
            $map["%{$key}%"] = $strVal;
            $map[":{$key}"] = $strVal;
        }

        return strtr($message, $map);
    }
}
