<?php

namespace App\Support;

use Illuminate\Support\Facades\File;

/**
 * CENTRAL LANGUAGE HUB.
 *
 * One class owns every question about languages:
 *   - which locales exist and their metadata,
 *   - whether a code is valid,
 *   - the flat message catalogue the React layer renders from.
 *
 * TRANSLATIONS LIVE IN JSON. Each locale is a single flat key/value file -
 * `lang/en.json`, `lang/bn.json` - and BOTH sides of the wire read it:
 *   - Laravel's own `__('settings.language_saved')` resolves from the JSON file
 *     for the active locale (Laravel checks JSON translations first),
 *   - the React layer renders from the same keys, shipped via the shared
 *     `locale.messages` Inertia prop.
 *
 * Keys may be dotted namespaces (`nav.dashboard`) OR the English source string
 * itself (`"Dashboard": "ড্যাশবোর্ড"`) so page-body copy can be translated
 * without inventing a key for every sentence. Adding a language is one file.
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
     * The flat message catalogue for a locale, with the fallback locale merged
     * underneath so a key missing from the active language degrades to English.
     *
     * @return array<string,string>
     */
    public static function messages(?string $code = null): array
    {
        $code = static::normalise($code);
        $fallback = static::fallback();

        $base = static::load($fallback);

        return $code === $fallback
            ? $base
            : array_merge($base, static::load($code));
    }

    /**
     * Load a locale's flat JSON catalogue.
     *
     * @return array<string,string>
     */
    protected static function load(string $locale): array
    {
        $file = lang_path("{$locale}.json");

        if (! File::exists($file)) {
            return [];
        }

        $decoded = json_decode(File::get($file), true);

        return is_array($decoded) ? $decoded : [];
    }
}
