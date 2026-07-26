<?php

/**
 * Safi Microframework - safi-i18n
 * @author Jean Bruenn
 * @copyright 2026 All Rights Reserved
 * @see https://github.com/chani/safi-i18n
 */

declare(strict_types=1);

namespace Safi\Extensions\I18n\Cli\Commands;

use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use RegexIterator;
use Safi\Core\Cli\CommandInterface;
use SplFileInfo;

final readonly class I18nExtractCommand implements CommandInterface
{
    public function __construct(
        private string $projectRoot,
        private string $langDir,
    ) {}

    #[\Override]
    public function getName(): string
    {
        return 'i18n:extract';
    }

    #[\Override]
    public function getDescription(): string
    {
        return 'Scans PHP and Twig files for __() translation keys and updates JSON language files.';
    }

    #[\Override]
    public function getCategory(): string
    {
        return 'i18n';
    }

    #[\Override]
    public function execute(array $args): int
    {
        $targetLocale = $args[0] ?? 'de';
        echo "Scanning codebase for translation keys (Target: {$targetLocale})...\n";

        $extractedKeys = [];
        $files = $this->scanDirectory($this->projectRoot);

        foreach ($files as $file) {
            $content = file_get_contents($file);
            if ($content === false) {
                continue;
            }

            if (str_ends_with($file, '.php')) {
                $keys = $this->extractFromPhpTokens($content);
                $extractedKeys = array_merge($extractedKeys, $keys);
            } elseif (str_ends_with($file, '.twig')) {
                $keys = $this->extractFromTwigContent($content);
                $extractedKeys = array_merge($extractedKeys, $keys);
            }
        }

        $extractedKeys = array_unique($extractedKeys);
        sort($extractedKeys);

        echo "Found " . count($extractedKeys) . " unique source keys.\n";

        $targetFile = "{$this->langDir}/{$targetLocale}.json";
        $existingContent = file_exists($targetFile) ? (string) file_get_contents($targetFile) : '{}';
        $decoded = json_decode($existingContent, true);
        /** @var array<string, string> $existing */
        $existing = is_array($decoded) ? $decoded : [];

        $added = 0;
        foreach ($extractedKeys as $key) {
            if (!isset($existing[$key])) {
                $existing[$key] = $key;
                $added++;
            }
        }

        if (!is_dir($this->langDir)) {
            @mkdir($this->langDir, 0750, true);
        }

        $encoded = json_encode($existing, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        if (is_string($encoded)) {
            file_put_contents($targetFile, $encoded);
        }

        echo "Updated '{$targetFile}'. Added {$added} new keys.\n";

        return 0;
    }

    /**
     * @return list<string>
     */
    private function scanDirectory(string $dir): array
    {
        if (!is_dir($dir)) {
            return [];
        }

        $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir));
        $regex = new RegexIterator($iterator, '/\.(php|twig)$/i');
        $files = [];

        /** @var SplFileInfo $file */
        foreach ($regex as $file) {
            if (str_contains($file->getPathname(), '/vendor/')) {
                continue;
            }
            $files[] = $file->getPathname();
        }

        return $files;
    }

    /**
     * Uses PHP token_get_all to extract __('string') robustly.
     * @return list<string>
     */
    private function extractFromPhpTokens(string $content): array
    {
        $tokens = token_get_all($content);
        $keys = [];
        $count = count($tokens);

        for ($i = 0; $i < $count; $i++) {
            if (is_array($tokens[$i]) && $tokens[$i][0] === T_STRING && $tokens[$i][1] === '__') {
                for ($j = $i + 1; $j < $i + 5 && $j < $count; $j++) {
                    if (is_array($tokens[$j]) && $tokens[$j][0] === T_CONSTANT_ENCAPSED_STRING) {
                        $rawString = $tokens[$j][1];
                        $keys[] = trim($rawString, "\"'");
                        break;
                    }
                }
            }
        }

        return $keys;
    }

    /**
     * Extract keys from Twig 'string'|__ or __('string')
     * @return list<string>
     */
    private function extractFromTwigContent(string $content): array
    {
        $keys = [];
        preg_match_all("/'([^']+)'\s*\|\s*__/", $content, $m1);
        preg_match_all('/"([^"]+)"\s*\|\s*__/', $content, $m2);
        preg_match_all("/__\(\s*'([^']+)'\s*\)/", $content, $m3);
        preg_match_all('/__\(\s*"([^"]+)"\s*\)/', $content, $m4);

        return array_merge($keys, $m1[1], $m2[1], $m3[1], $m4[1]);
    }
}
