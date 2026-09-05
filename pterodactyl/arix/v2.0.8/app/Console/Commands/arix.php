<?php

namespace Pterodactyl\Console\Commands;

use Illuminate\Console\Command;
use Symfony\Component\Console\Formatter\OutputFormatterStyle;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Artisan;

class Arix extends Command
{
    protected $signature = "arix {action?}";
    protected $description = "Management commands for Arix Theme for Pterodactyl.";

    public function handle(): int
    {
        $action = $this->argument("action");
        $title = new OutputFormatterStyle("#fff", null, ["bold"]);
        $this->output->getFormatter()->setStyle("title", $title);
        $b = new OutputFormatterStyle(null, null, ["bold"]);
        $this->output->getFormatter()->setStyle("b", $b);

        if ($action === null) {
            $this->line("\r\n            <title>\r\n            ░█████╗░██████╗░██╗██╗░░██╗\r\n            ██╔══██╗██╔══██╗██║╚██╗██╔╝\r\n            ███████║██████╔╝██║░╚███╔╝░\r\n            ██╔══██║██╔══██╗██║░██╔██╗░\r\n            ██║░░██║██║░░██║██║██╔╝╚██╗\r\n            ╚═╝░░╚═╝╚═╝░░╚═╝╚═╝╚═╝░░╚═╝\r\n\r\n           Arix Theme Management Console</title>\r\n\r\n           > php artisan arix (this window)\r\n           > php artisan arix install\r\n           > php artisan arix update\r\n           > php artisan arix repair\r\n           > php artisan arix uninstall\r\n            ");
            return Command::SUCCESS;
        }

        $this->info("\n    Arix Theme Manager\n");
        if ($action === "install") {
            $this->install();
        } elseif ($action === "update") {
            $this->update();
        } elseif ($action === "repair" || $action === "fix") {
            return Artisan::call('arix:fix', [], $this->output);
        } elseif ($action === "uninstall") {
            $this->uninstall();
        } else {
            $this->error("Invalid action. Supported actions: install, update, repair, fix, uninstall");
            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }

    public function installOrUpdate($isUpdate = false)
    {
        if ($isUpdate) {
            $this->info("\n    Theme update mode.\n   Skips frequently customized addon files to preserve modifications.\n");
        }

        $versions = File::directories("./arix");
        if (empty($versions)) {
            $this->warn("No version directories found in ./arix. Using current root files...");
            $version = "v2.0.8";
        } else {
            $version = basename($this->choice("Select a version to install:", $versions, 0));
        }

        $this->info("Installing Arix Theme {$version}...");

        $sourceDir = base_path("arix/{$version}/");
        if (File::exists($sourceDir)) {
            $excludeOption = $isUpdate ? "--exclude='routes.ts' --exclude='getServer.ts' --exclude='admin.blade.php' --exclude='admin.php' --exclude='ServerTransformer.php'" : '';
            exec("rsync -a {$excludeOption} " . escapeshellarg($sourceDir) . " ./");
        }

        $directoryPath = app_path("Http/Controllers/Admin/Arix");
        File::makeDirectory($directoryPath, 0755, true, true);

        $this->info("Migrating database...");
        exec("php artisan migrate --force");

        $this->info("Installing required frontend packages (yarn)...");
        exec("yarn install --ignore-engines");
        exec("yarn add cronstrue jszip react-turnstile @dnd-kit/core @dnd-kit/sortable @dnd-kit/utilities @types/md5 md5 react-icons@5.4.0 markdown-to-jsx@7.7.10 i18next-browser-languagedetector@7.2.1 --ignore-engines");

        $this->info("Compiling translations...");
        if ($this->getApplication()->has('language:compile')) {
            Artisan::call('language:compile');
        }

        $this->info("Building panel production assets...");
        $nodeVersion = (int) ltrim(shell_exec("node -v") ?? '0', "v");
        if ($nodeVersion >= 17) {
            $this->info("Node.js version is v{$nodeVersion} (>= 17), enabling OpenSSL legacy provider.");
            putenv("NODE_OPTIONS=--openssl-legacy-provider --max-old-space-size=4096");
        } else {
            putenv("NODE_OPTIONS=--max-old-space-size=4096");
        }
        exec("yarn --ignore-engines run build:production");

        $this->info("Setting file permissions...");
        $this->fixWebPermissions();

        $this->info("Optimizing application...");
        Artisan::call('optimize:clear');
        Artisan::call('arix:fix', [], $this->output);

        $message = $isUpdate ? "│    Theme updated successfully   │" : "│   Theme installed successfully  │";
        $this->line("\n     ┌──────────────────────────────────────┐\n     │                                      │\n     {$message}\n     │                                      │\n     └──────────────────────────────────────┘\n     ");
    }

    public function install()
    {
        $this->installOrUpdate(false);
    }

    public function update()
    {
        $this->installOrUpdate(true);
    }

    private function uninstall()
    {
        if (!$this->confirm("Are you sure you want to remove Arix Theme and restore official Pterodactyl files?", false)) {
            $this->info("Uninstall aborted.");
            return;
        }

        $this->line("Uninstalling Arix Theme and restoring clean Pterodactyl core files...");
        exec("php artisan down");
        exec("curl -L https://github.com/pterodactyl/panel/releases/latest/download/panel.tar.gz | tar -xzv");
        exec("chmod -R 755 storage/* bootstrap/cache");
        exec("composer install --no-dev --optimize-autoloader");
        exec("php artisan view:clear");
        exec("php artisan config:clear");
        exec("php artisan migrate --force");
        $this->fixWebPermissions();
        exec("php artisan queue:restart");
        exec("php artisan up");

        $this->info("Pterodactyl core restored successfully.");
    }

    private function fixWebPermissions()
    {
        $webUser = 'www-data';
        if (posix_getpwnam('nginx')) {
            $webUser = 'nginx';
        } elseif (posix_getpwnam('www-data')) {
            $webUser = 'www-data';
        } elseif (posix_getpwnam('apache')) {
            $webUser = 'apache';
        }

        $base = base_path();
        exec("chown -R {$webUser}:{$webUser} " . escapeshellarg($base) . "/*");
        exec("chmod -R 755 " . escapeshellarg("{$base}/storage") . " " . escapeshellarg("{$base}/bootstrap/cache"));
    }
}
