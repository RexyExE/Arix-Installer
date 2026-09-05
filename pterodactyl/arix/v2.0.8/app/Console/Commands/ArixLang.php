<?php

namespace Pterodactyl\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class ArixLang extends Command
{
    protected $signature = 'language:compile';
    protected $description = 'Compile all translations under resources/lang/[lang]/ into a dist directory with flattened filenames';

    public function handle(): int
    {
        $langPath = resource_path('lang');

        if (!File::exists($langPath)) {
            $this->warn("Language directory {$langPath} does not exist.");
            return Command::SUCCESS;
        }

        foreach (File::directories($langPath) as $langDir) {
            $lang = basename($langDir);
            $distPath = $langDir . '/dist';

            File::ensureDirectoryExists($distPath);
            File::cleanDirectory($distPath);

            foreach (File::allFiles($langDir) as $file) {
                $normalizedPath = str_replace('\\', '/', $file->getPath());
                if (str_contains($normalizedPath, '/dist')) {
                    continue;
                }

                $relativePath = str_replace($langDir . DIRECTORY_SEPARATOR, '', $file->getPathname());
                $relativePath = str_replace(['.php', '\\'], ['', '/'], $relativePath);
                $flatName = str_replace('/', '-', $relativePath) . '.php';

                File::copy($file->getPathname(), $distPath . '/' . $flatName);
            }

            $this->info("Compiled files for '{$lang}' into dist/");
        }

        return Command::SUCCESS;
    }
}
