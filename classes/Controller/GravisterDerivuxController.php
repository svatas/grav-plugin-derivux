<?php

declare(strict_types=1);

namespace Grav\Plugin\GravisterDerivux\Controller;

use Grav\Common\Grav;
use Grav\Plugin\Api\Controllers\AbstractApiController;
use Grav\Plugin\Api\Exceptions\ConflictException;
use Grav\Plugin\Api\Exceptions\ValidationException;
use Grav\Plugin\Api\Response\ApiResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final class GravisterDerivuxController extends AbstractApiController
{
    private const THEMES_DIR = 'user://themes';
    private const VERSION = '0.6.2';

    public function themes(ServerRequestInterface $request): ResponseInterface
    {
        $this->requirePermission($request, 'api.derivux.read');

        $base = self::themesPath();
        $themes = [];

        foreach (scandir($base) ?: [] as $dir) {
            if ($dir === '.' || $dir === '..') {
                continue;
            }

            $path = $base . DIRECTORY_SEPARATOR . $dir;
            if (!is_dir($path) || !self::validSlug($dir)) {
                continue;
            }

            $class = self::discoverThemeClass($path, $dir);

            $themes[] = [
                'slug' => $dir,
                'class' => $class,
                'compatible' => $class !== null,
            ];
        }

        usort($themes, static fn(array $a, array $b): int => strcmp($a['slug'], $b['slug']));

        return ApiResponse::create(['themes' => $themes]);
    }

    public function validate(ServerRequestInterface $request): ResponseInterface
    {
        $this->requirePermission($request, 'api.derivux.create');

        $input = self::input((array) ($request->getParsedBody() ?? []));
        $plan = self::plan($input);

        return ApiResponse::create([
            'dry_run' => true,
            'operations' => self::stripWriteContent($plan['operations']),
            'report' => $plan['report'],
            'build_hints' => $plan['build_hints'],
            'source_class' => $input['source_class'],
            'target_class' => $input['target_class'],
        ]);
    }

    public function create(ServerRequestInterface $request): ResponseInterface
    {
        $this->requirePermission($request, 'api.derivux.create');

        $body = (array) ($request->getParsedBody() ?? []);
        $input = self::input($body);
        $plan = self::plan($input);

        if (filter_var($body['dry_run'] ?? false, FILTER_VALIDATE_BOOLEAN)) {
            return ApiResponse::create([
                'ok' => true,
                'dry_run' => true,
                'operations' => self::stripWriteContent($plan['operations']),
                'report' => $plan['report'],
                'build_hints' => $plan['build_hints'],
            ]);
        }

        $result = self::execute($plan['operations'], $input['target_path']);

        return ApiResponse::create([
            'ok' => $result['ok'],
            'dry_run' => false,
            'report' => $result['report'],
            'errors' => $result['errors'],
            'target_path' => $input['target_path_rel'],
        ], $result['ok'] ? 201 : 500);
    }

    private static function input(array $body): array
    {
        $source = trim((string) ($body['source_theme'] ?? ''));
        $target = trim((string) ($body['target_slug'] ?? ''));

        if (!self::validSlug($source)) {
            throw new ValidationException('Invalid source_theme.', ['source_theme' => 'PLUGIN_GRAVISTER_DERIVUX.INVALID_SOURCE_THEME']);
        }

        if (!self::validSlug($target)) {
            throw new ValidationException('Invalid target_slug.', ['target_slug' => 'PLUGIN_GRAVISTER_DERIVUX.INVALID_TARGET_SLUG']);
        }

        if ($source === $target) {
            throw new ValidationException('Target theme must be different from source theme.', ['target_slug' => 'PLUGIN_GRAVISTER_DERIVUX.TARGET_EQUALS_SOURCE']);
        }

        $base = self::themesPath();
        $sourcePath = $base . DIRECTORY_SEPARATOR . $source;
        $targetPath = $base . DIRECTORY_SEPARATOR . $target;

        if (!is_dir($sourcePath)) {
            throw new ValidationException('Source theme not found.', ['source_theme' => 'PLUGIN_GRAVISTER_DERIVUX.SOURCE_THEME_NOT_FOUND']);
        }

        if (file_exists($targetPath)) {
            throw new ConflictException('Target theme already exists.');
        }

        $sourceClass = self::discoverThemeClass($sourcePath, $source);
        if ($sourceClass === null) {
            throw new ValidationException('Parent theme PHP class could not be resolved safely.', ['source_theme' => 'PLUGIN_GRAVISTER_DERIVUX.PARENT_CLASS_UNRESOLVED']);
        }

        return [
            'source_theme' => $source,
            'target_slug' => $target,
            'source_name' => self::human($source),
            'target_name' => self::human($target),
            'source_class' => $sourceClass,
            'target_class' => self::classFromSlug($target),
            'target_version' => self::VERSION,
            'source_path' => $sourcePath,
            'target_path' => $targetPath,
            'target_path_rel' => 'user/themes/' . $target,
        ];
    }

    private static function plan(array $input): array
    {
        $target = $input['target_path'];
        $buildHints = self::buildHints($input['source_path']);

        $operations = [
            ['op' => 'mkdir', 'path' => $target],
            ['op' => 'mkdir', 'path' => $target . '/templates'],
            ['op' => 'mkdir', 'path' => $target . '/css'],
            ['op' => 'mkdir', 'path' => $target . '/css/custom'],
            ['op' => 'write', 'path' => $target . '/blueprints.yaml', 'content' => self::blueprints($input)],
            ['op' => 'write', 'path' => $target . '/' . $input['target_slug'] . '.yaml', 'content' => self::themeYaml($input)],
            ['op' => 'write', 'path' => $target . '/' . $input['target_slug'] . '.php', 'content' => self::themePhp($input)],
            ['op' => 'write', 'path' => $target . '/README.md', 'content' => self::readme($input)],
            ['op' => 'write', 'path' => $target . '/screenshot.svg', 'content' => self::svg($input)],
            ['op' => 'write', 'path' => $target . '/thumbnail.svg', 'content' => self::svg($input)],
            ['op' => 'write', 'path' => $target . '/preview.svg', 'content' => self::svg($input)],
            ['op' => 'write', 'path' => $target . '/screenshot.png', 'content' => self::purePng($input)],
            ['op' => 'write', 'path' => $target . '/thumbnail.png', 'content' => self::purePng($input)],
            ['op' => 'write', 'path' => $target . '/preview.png', 'content' => self::purePng($input)],
        ];

        foreach (self::jpgFallbacks() as $name => $source) {
            $operations[] = ['op' => 'copy', 'source' => $source, 'path' => $target . '/' . $name];
        }

        return [
            'operations' => $operations,
            'build_hints' => $buildHints,
            'report' => [
                ['level' => 'info', 'message' => 'Parent theme class resolved as ' . $input['source_class'] . '.'],
                ['level' => 'info', 'message' => 'Derived theme blueprint will extend the parent theme blueprint.'],
                ['level' => 'info', 'message' => 'Dynamic high-contrast SVG previews will be generated.'],
                ['level' => 'info', 'message' => 'Dynamic pure PHP PNG previews will be generated without GD.'],
                ['level' => 'info', 'message' => 'Static JPG fallback previews will be copied.'],
                ['level' => 'info', 'message' => 'Parent templates and assets are not copied.'],
                ['level' => 'info', 'message' => 'Generated theme is not activated automatically.'],
            ],
        ];
    }

    private static function blueprints(array $input): string
    {
        return <<<YAML
name: {$input['target_name']}
slug: {$input['target_slug']}
type: theme
version: {$input['target_version']}
description: Custom inherited theme based on {$input['source_name']}. Generated by Derivux by Gravister.
icon: code-branch

extends@:
  type: blueprints
  context: themes://{$input['source_theme']}

dependencies:
  - { name: grav, version: '>=2.0.0' }
  - { name: {$input['source_theme']}, version: '*' }

YAML;
    }

    private static function themeYaml(array $input): string
    {
        $parent = '';

        foreach ([
            $input['source_path'] . '/' . $input['source_theme'] . '.yaml',
            $input['source_path'] . '/theme.yaml',
        ] as $file) {
            if (is_file($file) && is_readable($file)) {
                $parent = (string) file_get_contents($file);
                break;
            }
        }

        if (trim($parent) === '') {
            $parent = "enabled: true\n";
        }

        $parent = self::removeTopLevelBlock($parent, 'streams');

        return rtrim($parent) . "\n\nstreams:\n"
            . "  schemes:\n"
            . "    theme:\n"
            . "      type: ReadOnlyStream\n"
            . "      prefixes:\n"
            . "        '':\n"
            . "          - user/themes/{$input['target_slug']}\n"
            . "          - user/themes/{$input['source_theme']}\n";
    }

    private static function themePhp(array $input): string
    {
        return <<<PHP
<?php

declare(strict_types=1);

namespace Grav\\Theme;

class {$input['target_class']} extends {$input['source_class']}
{
}

PHP;
    }

    private static function readme(array $input): string
    {
        return <<<MD
# {$input['target_name']}

Minimal inherited theme generated by **Derivux by Gravister**.

Parent theme: `{$input['source_theme']}`  
Derived theme: `{$input['target_slug']}`

## Safe usage

The parent theme is not modified and its templates or assets are not copied.

Configuration-level changes are the intended default workflow. If the parent uses compiled CSS and you later add child templates with new utility classes, a separate CSS build may be required.

Derivux does not run Node, Tailwind, Vite, npm, PostCSS or shell commands.

MD;
    }

    private static function discoverThemeClass(string $sourcePath, string $slug): ?string
    {
        $candidates = [
            $sourcePath . '/' . $slug . '.php',
            $sourcePath . '/theme.php',
        ];

        foreach (glob($sourcePath . '/*.php') ?: [] as $file) {
            if (!in_array($file, $candidates, true)) {
                $candidates[] = $file;
            }
        }

        foreach ($candidates as $file) {
            if (!is_file($file) || !is_readable($file)) {
                continue;
            }

            $source = (string) file_get_contents($file);

            if (!preg_match('/namespace\s+Grav\\\\Theme\s*;/', $source)) {
                continue;
            }

            if (preg_match('/\bclass\s+([A-Za-z_][A-Za-z0-9_]*)\b/', $source, $match)) {
                return $match[1];
            }
        }

        return null;
    }

    private static function buildHints(string $sourcePath): array
    {
        $indicators = [];

        foreach ([
            'build/css/site.css',
            'package.json',
            'tailwind.config.js',
            'tailwind.config.mjs',
            'postcss.config.js',
            'vite.config.js',
            'vite.config.mjs',
        ] as $relative) {
            if (is_file($sourcePath . '/' . $relative)) {
                $indicators[] = $relative;
            }
        }

        return [
            'compiled_css_possible' => $indicators !== [],
            'indicators' => $indicators,
        ];
    }

    private static function execute(array $operations, string $targetRoot): array
    {
        $report = [];
        $errors = [];
        $createdTarget = false;

        foreach ($operations as $operation) {
            try {
                $type = $operation['op'] ?? '';

                if ($type === 'mkdir') {
                    $path = (string) $operation['path'];

                    if ($path === $targetRoot && !is_dir($path)) {
                        $createdTarget = true;
                    }

                    if (!is_dir($path) && !@mkdir($path, 0755, true) && !is_dir($path)) {
                        throw new \RuntimeException('mkdir failed: ' . $path);
                    }

                    $report[] = ['level' => 'info', 'message' => 'mkdir: ' . $path];
                    continue;
                }

                if ($type === 'write') {
                    $path = (string) $operation['path'];

                    if (!self::isInsideTarget($path, $targetRoot)) {
                        throw new \RuntimeException('Refusing write outside target theme.');
                    }

                    if (@file_put_contents($path, (string) $operation['content'], LOCK_EX) === false) {
                        throw new \RuntimeException('write failed: ' . $path);
                    }

                    $report[] = ['level' => 'info', 'message' => 'write: ' . $path];
                    continue;
                }

                if ($type === 'copy') {
                    $path = (string) $operation['path'];

                    if (!self::isInsideTarget($path, $targetRoot)) {
                        throw new \RuntimeException('Refusing copy outside target theme.');
                    }

                    if (!@copy((string) $operation['source'], $path)) {
                        throw new \RuntimeException('copy failed: ' . $path);
                    }

                    $report[] = ['level' => 'info', 'message' => 'copy: ' . $path];
                    continue;
                }

                throw new \RuntimeException('Unknown operation: ' . $type);
            } catch (\Throwable $error) {
                $errors[] = ['message' => $error->getMessage(), 'op' => $operation];
                $report[] = ['level' => 'error', 'message' => $error->getMessage()];

                if ($createdTarget && is_dir($targetRoot)) {
                    try {
                        self::removeTree($targetRoot);
                        $report[] = ['level' => 'info', 'message' => 'rollback: removed newly created target theme'];
                    } catch (\Throwable $rollbackError) {
                        $errors[] = ['message' => 'Rollback failed: ' . $rollbackError->getMessage()];
                        $report[] = ['level' => 'error', 'message' => 'Rollback failed: ' . $rollbackError->getMessage()];
                    }
                }

                break;
            }
        }

        return [
            'ok' => $errors === [],
            'report' => $report,
            'errors' => $errors,
        ];
    }

    private static function removeTree(string $path): void
    {
        if (!is_dir($path)) {
            return;
        }

        $items = scandir($path);
        if ($items === false) {
            throw new \RuntimeException('Unable to read rollback directory.');
        }

        foreach ($items as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }

            $child = $path . DIRECTORY_SEPARATOR . $item;

            if (is_link($child) || is_file($child)) {
                if (!@unlink($child)) {
                    throw new \RuntimeException('Unable to remove rollback file: ' . $child);
                }
                continue;
            }

            if (is_dir($child)) {
                self::removeTree($child);
            }
        }

        if (!@rmdir($path)) {
            throw new \RuntimeException('Unable to remove rollback directory: ' . $path);
        }
    }

    private static function isInsideTarget(string $path, string $targetRoot): bool
    {
        $normalizedRoot = rtrim(str_replace('\\', '/', $targetRoot), '/') . '/';
        $normalizedPath = str_replace('\\', '/', $path);

        return str_starts_with($normalizedPath, $normalizedRoot);
    }

    private static function removeTopLevelBlock(string $yaml, string $key): string
    {
        $lines = preg_split('/\R/', $yaml);
        $output = [];
        $skipping = false;

        foreach ($lines ?: [] as $line) {
            if (!$skipping && preg_match('/^' . preg_quote($key, '/') . '\s*:/', $line)) {
                $skipping = true;
                continue;
            }

            if ($skipping) {
                if (preg_match('/^\S/', $line)) {
                    $skipping = false;
                    $output[] = $line;
                }
                continue;
            }

            $output[] = $line;
        }

        return trim(implode("\n", $output)) . "\n";
    }

    private static function jpgFallbacks(): array
    {
        $base = dirname(__DIR__, 2) . '/resources/preview';
        $fallback = $base . '/fallback.jpg';

        if (!is_file($fallback)) {
            return [];
        }

        return [
            'screenshot.jpg' => $fallback,
            'thumbnail.jpg' => $fallback,
            'preview.jpg' => $fallback,
        ];
    }

    private static function svg(array $input): string
    {
        $accent = self::accent($input['target_slug']);

        return '<svg xmlns="http://www.w3.org/2000/svg" width="1200" height="800">'
            . '<rect width="1200" height="800" fill="#0f172a"/>'
            . '<rect y="0" width="1200" height="120" fill="' . $accent . '"/>'
            . '<rect x="60" y="155" width="1080" height="585" rx="34" fill="#f8fafc"/>'
            . '<text x="90" y="78" font-family="Arial" font-size="46" font-weight="900" fill="#fff">Derivux by Gravister</text>'
            . '<text x="100" y="360" font-family="Arial" font-size="104" font-weight="900" fill="#0f172a">' . self::xml($input['target_slug']) . '</text>'
            . '<text x="105" y="545" font-family="Arial" font-size="44" font-weight="900" fill="' . $accent . '">Based on: ' . self::xml($input['source_theme']) . '</text>'
            . '</svg>';
    }

    private static function purePng(array $input): string
    {
        $width = 360;
        $height = 220;
        $background = [15, 23, 42];
        $accent = self::hexToRgb(self::accent($input['target_slug']));
        $card = [248, 250, 252];
        $text = [15, 23, 42];
        $white = [255, 255, 255];

        $image = array_fill(0, $height, array_fill(0, $width, $background));

        self::rect($image, 0, 0, $width, 32, $accent);
        self::rect($image, 18, 48, $width - 18, $height - 18, $card);
        self::text($image, 28, 10, 'DERIVUX BY GRAVISTER', 2, $white);

        $y = 72;
        foreach (self::splitSlug(strtoupper($input['target_slug'])) as $line) {
            self::text($image, 28, $y, $line, 5, $text);
            $y += 42;
        }

        self::text($image, 28, $y + 6, 'BASE: ' . strtoupper($input['source_theme']), 3, $accent);

        return self::pngEncode($image, $width, $height);
    }

    private static function splitSlug(string $slug): array
    {
        if (strlen($slug) <= 13) {
            return [$slug];
        }

        $parts = explode('-', $slug);
        $lines = [];
        $current = '';

        foreach ($parts as $part) {
            $candidate = $current === '' ? $part : $current . '-' . $part;

            if (strlen($candidate) <= 13) {
                $current = $candidate;
            } else {
                if ($current !== '') {
                    $lines[] = $current;
                }
                $current = $part;
            }
        }

        if ($current !== '') {
            $lines[] = $current;
        }

        return array_slice($lines, 0, 2);
    }

    private static function text(array &$image, int $x, int $y, string $text, int $scale, array $color): void
    {
        $cursor = $x;

        foreach (str_split(strtoupper($text)) as $character) {
            if ($character === ' ') {
                $cursor += 4 * $scale;
                continue;
            }

            $glyph = self::glyph($character);

            for ($glyphY = 0; $glyphY < count($glyph); $glyphY++) {
                for ($glyphX = 0; $glyphX < strlen($glyph[$glyphY]); $glyphX++) {
                    if ($glyph[$glyphY][$glyphX] === '1') {
                        self::rect(
                            $image,
                            $cursor + $glyphX * $scale,
                            $y + $glyphY * $scale,
                            $cursor + ($glyphX + 1) * $scale,
                            $y + ($glyphY + 1) * $scale,
                            $color
                        );
                    }
                }
            }

            $cursor += 6 * $scale;
        }
    }

    private static function glyph(string $character): array
    {
        $font = [
            'A' => ['01110','10001','10001','11111','10001','10001','10001'],
            'B' => ['11110','10001','10001','11110','10001','10001','11110'],
            'C' => ['01111','10000','10000','10000','10000','10000','01111'],
            'D' => ['11110','10001','10001','10001','10001','10001','11110'],
            'E' => ['11111','10000','10000','11110','10000','10000','11111'],
            'F' => ['11111','10000','10000','11110','10000','10000','10000'],
            'G' => ['01111','10000','10000','10011','10001','10001','01111'],
            'H' => ['10001','10001','10001','11111','10001','10001','10001'],
            'I' => ['11111','00100','00100','00100','00100','00100','11111'],
            'J' => ['00111','00010','00010','00010','10010','10010','01100'],
            'K' => ['10001','10010','10100','11000','10100','10010','10001'],
            'L' => ['10000','10000','10000','10000','10000','10000','11111'],
            'M' => ['10001','11011','10101','10101','10001','10001','10001'],
            'N' => ['10001','11001','10101','10011','10001','10001','10001'],
            'O' => ['01110','10001','10001','10001','10001','10001','01110'],
            'P' => ['11110','10001','10001','11110','10000','10000','10000'],
            'Q' => ['01110','10001','10001','10001','10101','10010','01101'],
            'R' => ['11110','10001','10001','11110','10100','10010','10001'],
            'S' => ['01111','10000','10000','01110','00001','00001','11110'],
            'T' => ['11111','00100','00100','00100','00100','00100','00100'],
            'U' => ['10001','10001','10001','10001','10001','10001','01110'],
            'V' => ['10001','10001','10001','10001','10001','01010','00100'],
            'W' => ['10001','10001','10001','10101','10101','10101','01010'],
            'X' => ['10001','10001','01010','00100','01010','10001','10001'],
            'Y' => ['10001','10001','01010','00100','00100','00100','00100'],
            'Z' => ['11111','00001','00010','00100','01000','10000','11111'],
            '0' => ['01110','10001','10011','10101','11001','10001','01110'],
            '1' => ['00100','01100','00100','00100','00100','00100','01110'],
            '2' => ['01110','10001','00001','00010','00100','01000','11111'],
            '3' => ['11110','00001','00001','01110','00001','00001','11110'],
            '4' => ['00010','00110','01010','10010','11111','00010','00010'],
            '5' => ['11111','10000','10000','11110','00001','00001','11110'],
            '6' => ['01110','10000','10000','11110','10001','10001','01110'],
            '7' => ['11111','00001','00010','00100','01000','01000','01000'],
            '8' => ['01110','10001','10001','01110','10001','10001','01110'],
            '9' => ['01110','10001','10001','01111','00001','00001','01110'],
            '-' => ['00000','00000','00000','11111','00000','00000','00000'],
            ':' => ['00000','00100','00100','00000','00100','00100','00000'],
        ];

        return $font[$character] ?? ['11111','10001','00010','00100','00000','00100','00100'];
    }

    private static function rect(array &$image, int $x1, int $y1, int $x2, int $y2, array $color): void
    {
        $height = count($image);
        $width = count($image[0]);
        $x1 = max(0, $x1);
        $y1 = max(0, $y1);
        $x2 = min($width, $x2);
        $y2 = min($height, $y2);

        for ($y = $y1; $y < $y2; $y++) {
            for ($x = $x1; $x < $x2; $x++) {
                $image[$y][$x] = $color;
            }
        }
    }

    private static function pngEncode(array $image, int $width, int $height): string
    {
        $raw = '';

        for ($y = 0; $y < $height; $y++) {
            $raw .= chr(0);

            for ($x = 0; $x < $width; $x++) {
                [$red, $green, $blue] = $image[$y][$x];
                $raw .= chr($red) . chr($green) . chr($blue);
            }
        }

        return "\x89PNG\r\n\x1a\n"
            . self::chunk('IHDR', pack('NNCCCCC', $width, $height, 8, 2, 0, 0, 0))
            . self::chunk('IDAT', gzcompress($raw, 9))
            . self::chunk('IEND', '');
    }

    private static function chunk(string $type, string $data): string
    {
        return pack('N', strlen($data)) . $type . $data . pack('N', crc32($type . $data));
    }

    private static function hexToRgb(string $hex): array
    {
        $hex = ltrim($hex, '#');

        return [
            hexdec(substr($hex, 0, 2)),
            hexdec(substr($hex, 2, 2)),
            hexdec(substr($hex, 4, 2)),
        ];
    }

    private static function accent(string $slug): string
    {
        $palette = [
            '#2563eb',
            '#7c3aed',
            '#059669',
            '#ea580c',
            '#e11d48',
            '#0891b2',
            '#d97706',
            '#4f46e5',
            '#16a34a',
            '#c026d3',
        ];

        return $palette[(int) (abs(crc32($slug)) % count($palette))];
    }

    private static function xml(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES | ENT_XML1, 'UTF-8');
    }

    private static function stripWriteContent(array $operations): array
    {
        foreach ($operations as &$operation) {
            if (($operation['op'] ?? '') === 'write') {
                unset($operation['content']);
            }
        }

        return $operations;
    }

    private static function themesPath(): string
    {
        $path = (string) (Grav::instance()['locator']->findResource(self::THEMES_DIR, true) ?: '');

        if ($path === '' || !is_dir($path)) {
            throw new \RuntimeException('Themes directory not found.');
        }

        return $path;
    }

    private static function validSlug(string $slug): bool
    {
        return (bool) preg_match('/^[a-z0-9][a-z0-9-]{0,62}$/', $slug);
    }

    private static function human(string $slug): string
    {
        return implode(' ', array_map(
            static fn(string $part): string => ucfirst($part),
            explode('-', $slug)
        ));
    }

    private static function classFromSlug(string $slug): string
    {
        return preg_replace('/[^A-Za-z0-9]/', '', self::human($slug)) ?: 'GeneratedTheme';
    }
}
