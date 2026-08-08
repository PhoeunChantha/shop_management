<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class GenerateMissingTranslations extends Command
{
    protected $signature = 'translations:generate';

    protected $description = 'Generate missing translation keys from Blade files';

    protected $languages = ['en', 'km'];

    public function handle()
    {
        $translationKeys = $this->collectTranslationKeys();

        foreach ($this->languages as $language) {
            $this->updateLangFile($language, $translationKeys);
        }

        $this->info('Missing translations have been added to the language files.');
    }

    protected function collectTranslationKeys(): array
    {
        // Scan Blade views AND PHP under app/ (controllers, services, requests) so
        // flash messages and other __('…') strings outside views are collected too.
        $files = array_merge(
            File::allFiles(resource_path('views')),
            File::allFiles(app_path()),
        );

        $translationKeys = [];

        foreach ($files as $file) {
            if (! in_array($file->getExtension(), ['php', 'blade.php'], true)
                && ! str_ends_with($file->getFilename(), '.blade.php')) {
                continue;
            }

            // Skip this generator itself (its placeholder string is not a key).
            if ($file->getFilename() === 'GenerateMissingTranslations.php') {
                continue;
            }

            preg_match_all('/__\((["\'])(.*?)\1/', File::get($file), $matches);
            $translationKeys = array_merge($translationKeys, $matches[2]);
        }

        return array_unique($translationKeys);
    }

    protected function updateLangFile(string $language, array $keys)
    {
        $langPath = lang_path("{$language}.json");
        $translations = File::exists($langPath) ? json_decode(File::get($langPath), true) : [];

        $newKeys = array_filter($keys, function ($key) use ($translations) {
            return ! array_key_exists($key, $translations);
        });

        foreach ($newKeys as $key) {
            $translations[$key] = $language === 'km' ? 'សូមបញ្ចូលការបកប្រែ' : $key;
        }

        if (! empty($newKeys)) {
            File::put($langPath, json_encode($translations, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        }
    }

    // command run
    // php artisan translations:generate
}
