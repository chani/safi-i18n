<?php

/**
 * Safi Microframework - safi-i18n
 * @author Jean Bruenn
 * @copyright 2026 All Rights Reserved
 * @see https://github.com/chani/safi-i18n
 */

declare(strict_types=1);

namespace Safi\Extensions\I18n\Twig;

use Safi\Extensions\I18n\Translator;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;
use Twig\TwigFunction;

final class I18nTwigExtension extends AbstractExtension
{
    public function __construct(
        private readonly Translator $translator,
    ) {}

    /**
     * @return list<TwigFilter>
     */
    #[\Override]
    public function getFilters(): array
    {
        return [
            new TwigFilter('__', function (string $key, array $replacements = []): string {
                /** @var array<string, mixed> $replacements */
                return $this->translator->translate($key, $replacements);
            }),
        ];
    }

    /**
     * @return list<TwigFunction>
     */
    #[\Override]
    public function getFunctions(): array
    {
        return [
            new TwigFunction('__', function (string $key, array $replacements = []): string {
                /** @var array<string, mixed> $replacements */
                return $this->translator->translate($key, $replacements);
            }),
            new TwigFunction('i18n_js_slice', fn(bool $isPublic = false): string => $this->translator->exportJsSlice($isPublic)),
        ];
    }
}
