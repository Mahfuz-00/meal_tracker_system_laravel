<?php

namespace App\Support;

use Illuminate\Support\Facades\File;

/**
 * CENTRAL LOCALE RESOLUTION.
 *
 * One class owns every question about languages:
 *   - which locales exist and their metadata,
 *   - whether a code is valid,
 *   - the flattened message catalogue the React layer renders from.
 *
 * The frontend never reads `lang/` directly; HandleInertiaRequests ships the
 * catalogue for the ACTIVE locale as a shared prop, and LocaleProvider renders
 * from it. That keeps a single path for translations on both sides of the wire.
 */
class LocaleManager
{
    /** All configured locales, keyed by code. */
    public static function supported(): array
    {
        return config('locales.supported', ['en' => ['label' => 'English', 'english' => 'English', 'rtl' => false]]);
    }

    /** The default locale (used when nothing else applies). */
    public static function default(): string
    {
        return config('locales.default', config('app.locale', 'en'));
    }

    /** The fallback locale, merged UNDER the active one so no key is ever blank. */
    public static function fallback(): string
    {
        return config('locales.fallback', 'en');
    }

    public static function isSupported(?string $code): bool
    {
        return $code !== null && array_key_exists($code, static::supported());
    }

    /** Normalise a code to a supported locale (falls back to the default). */
    public static function normalise(?string $code): string
    {
        return static::isSupported($code) ? $code : static::default();
    }

    /**
     * The list the language switcher renders - each language named in its own
     * script, plus whether it reads right-to-left.
     */
    public static function catalogue(): array
    {
        return collect(static::supported())
            ->map(fn (array $meta, string $code) => [
                'code' => $code,
                'label' => $meta['label'] ?? $code,
                'english' => $meta['english'] ?? $code,
                'rtl' => (bool) ($meta['rtl'] ?? false),
            ])
            ->values()
            ->all();
    }

    /** Metadata for a single locale (normalised). */
    public static function meta(?string $code): array
    {
        $code = static::normalise($code);

        return array_merge(['code' => $code], static::supported()[$code] ?? []);
    }

    /**
     * The flattened message catalogue for a locale: every key from every
     * `lang/<code>/*.php` file, dot-joined (e.g. `nav.dashboard`), with the
     * fallback locale merged underneath so missing keys degrade to English.
     *
     * @return array<string,string>
     */
    public static function messages(?string $code = null): array
    {
        $code = static::normalise($code);
        $fallback = static::fallback();

        $base = static::loadGroup($fallback);

        return $code === $fallback
            ? $base
            : array_merge($base, static::loadGroup($code));
    }

    /**
     * The phrase book for a locale: an English source string => translation map,
     * used by `t('Some English label')` for page-body literals that are not
     * worth a dotted key. English has no phrase book (identity).
     *
     * @return array<string,string>
     */
    public static function phrases(?string $code = null): array
    {
        $code = static::normalise($code);
        $file = lang_path("{$code}/phrases.php");

        if (! File::exists($file)) {
            return [];
        }

        $phrases = require $file;

        return is_array($phrases) ? $phrases : [];
    }

    /**
     * Load and flatten every PHP file in `lang/<locale>/`, prefixing keys with
     * the file name so `nav.php` becomes `nav.<key>`. `phrases.php` is excluded
     * (it is a flat English=>translation map, served by phrases()).
     *
     * @return array<string,string>
     */
    protected static function loadGroup(string $locale): array
    {
        $dir = lang_path($locale);

        if (! File::isDirectory($dir)) {
            return [];
        }

        $messages = [];

        foreach (File::files($dir) as $file) {
            if ($file->getExtension() !== 'php') {
                continue;
            }

            $group = $file->getFilenameWithoutExtension();

            if ($group === 'phrases') {
                continue;
            }

            $lines = require $file->getPathname();

            if (is_array($lines)) {
                $messages = array_merge($messages, static::flatten($lines, $group));
            }
        }

        return $messages;
    }

    /**
     * Flatten a nested translation array into dot notation.
     *
     * @param  array<string,mixed>  $lines
     * @return array<string,string>
     */
    protected static function flatten(array $lines, string $prefix): array
    {
        $out = [];

        foreach ($lines as $key => $value) {
            $full = $prefix === '' ? (string) $key : "{$prefix}.{$key}";

            if (is_array($value)) {
                $out = array_merge($out, static::flatten($value, $full));
            } else {
                $out[$full] = $value;
            }
        }

        return $out;
    }
}
