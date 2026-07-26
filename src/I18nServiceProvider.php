<?php

/**
 * Safi Microframework - safi-i18n
 * @author Jean Bruenn
 * @copyright 2026 All Rights Reserved
 * @see https://github.com/chani/safi-i18n
 */

declare(strict_types=1);

namespace Safi\Extensions\I18n;

use Psr\Container\ContainerInterface;
use Safi\Core\Cli\CommandKernel;
use Safi\Core\Contracts\ContainerRegistrarInterface;
use Safi\Core\Contracts\ServiceProviderInterface;
use Safi\Core\Contracts\ViewEngineInterface;
use Safi\Extensions\I18n\Cli\Commands\I18nExtractCommand;
use Safi\Extensions\I18n\Twig\I18nTwigExtension;
use Safi\Extensions\ViewTwig\TwigViewAdapter;

final class I18nServiceProvider implements ServiceProviderInterface
{
    public function __construct(
        private readonly string $langDir,
        private readonly string $defaultLocale = 'de',
        private readonly bool $debug = false,
    ) {}

    #[\Override]
    public function register(ContainerRegistrarInterface $registrar): void
    {
        $registrar->set(Translator::class, fn(): Translator => new Translator(
            langDir: $this->langDir,
            currentLocale: $this->defaultLocale,
            debug: $this->debug,
        ));
    }

    #[\Override]
    public function boot(ContainerInterface $container): void
    {
        if ($container->has(ViewEngineInterface::class)) {
            $view = $container->get(ViewEngineInterface::class);
            if ($view instanceof TwigViewAdapter) {
                /** @var Translator $translator */
                $translator = $container->get(Translator::class);

                $reflection = new \ReflectionClass($view);
                if ($reflection->hasProperty('twig')) {
                    $prop = $reflection->getProperty('twig');
                    /** @var \Twig\Environment $twigEnvironment */
                    $twigEnvironment = $prop->getValue($view);
                    $twigEnvironment->addExtension(new I18nTwigExtension($translator));
                }
            }
        }

        if ($container->has(CommandKernel::class)) {
            /** @var CommandKernel $kernel */
            $kernel = $container->get(CommandKernel::class);
            $kernel->registerCommand(new I18nExtractCommand(
                projectRoot: dirname($this->langDir),
                langDir: $this->langDir,
            ));
        }
    }
}
