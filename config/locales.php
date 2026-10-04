<?php

/*
|--------------------------------------------------------------------------
| Supported locales (single source of truth)
|--------------------------------------------------------------------------
|
| Every place that needs to know which languages the platform speaks reads
| THIS file - the locale middleware, the shared Inertia `locale` prop and the
| Settings → Language screen. Adding a language is a two-step change:
|
|   1. add an entry here,
|   2. create lang/<code>/ with the translated PHP files.
|
| `label` is the language named in its OWN script (never translated), because a
| language picker that says "Bengali" to a Bengali speaker is the wrong way
| round. `english` is the English name, used for logs / fallback UI.
|
*/

return [

    // The locale used when the user (or session) has not chosen one.
    'default' => env('APP_LOCALE', 'en'),

    // Used to fill any key a translation file is missing.
    'fallback' => env('APP_FALLBACK_LOCALE', 'en'),

    'supported' => [
        'en' => [
            'label' => 'English',
            'english' => 'English',
            'rtl' => false,
        ],
        'bn' => [
            'label' => 'বাংলা',
            'english' => 'Bengali',
            'rtl' => false,
        ],
    ],

];
