<?php

namespace Pterodactyl\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Artisan;
use Pterodactyl\Contracts\Repository\SettingsRepositoryInterface;

class ArixFix extends Command
{
    protected $signature = 'arix:fix';
    protected $description = 'Self-healing repair command for Arix Theme: restores default settings, resets widget state, fixes permissions, and clears caches.';

    public function handle(SettingsRepositoryInterface $settings): int
    {
        $this->info('Starting Arix Theme self-healing & repair procedure...');

        // 1. Clear caches
        $this->line(' -> Clearing compiled views, cache, and configuration...');
        Artisan::call('view:clear');
        Artisan::call('config:clear');
        Artisan::call('route:clear');
        Artisan::call('cache:clear');
        Artisan::call('optimize:clear');
        $this->info(' -> Caches cleared successfully.');

        // 2. Check and seed missing default Arix settings
        $this->line(' -> Verifying default Arix theme settings in database...');
        $defaultSettings = [
            'arix:logo' => '/arix/Arix.png',
            'arix:logoLight' => '/arix/Arix.png',
            'arix:fullLogo' => 'false',
            'arix:logoHeight' => '32',
            'arix:discord' => '715281172422197300',
            'arix:support' => 'https://discord.gg/geCjrRbAwC',
            'arix:announcement' => 'false',
            'arix:announcementColor' => '#16aaaa',
            'arix:announcementIcon' => 'megaphone',
            'arix:announcementMessage' => 'We have a brand new game panel design!',
            'arix:announcementCta' => 'false',
            'arix:announcementCtaTitle' => 'Buy now!',
            'arix:announcementCtaLink' => '/',
            'arix:announcementDismissable' => 'false',
            'arix:pageTitle' => 'true',
            'arix:background' => 'true',
            'arix:backgroundImage' => '',
            'arix:backgroundImageLight' => '',
            'arix:loginBackground' => '/arix/background-login.png',
            'arix:backgroundFaded' => 'default',
            'arix:backdrop' => 'false',
            'arix:backdropPercentage' => '100',
            'arix:radiusInput' => '7',
            'arix:radiusBox' => '10',
            'arix:borderInput' => 'true',
            'arix:flashMessage' => '1',
            'arix:font' => 'default',
            'arix:icon' => 'heroicons',
            'arix:layout' => '1',
            'arix:searchComponent' => '1',
            'arix:logoPosition' => '1',
            'arix:socialPosition' => '1',
            'arix:loginLayout' => '1',
            'arix:serverRow' => '1',
            'arix:statsCards' => '2',
            'arix:sideGraphs' => '2',
            'arix:graphs' => '2',
            'arix:dashboardWidgets' => json_encode(['banner', 'statCards', 'graphs', 'infoAdvanced']),
            'arix:primary' => '#4A35CF',
            'arix:successText' => '#E1FFD8',
            'arix:successBorder' => '#56AA2B',
            'arix:successBackground' => '#3D8F1F',
            'arix:dangerText' => '#FFD8D8',
            'arix:dangerBorder' => '#AA2A2A',
            'arix:dangerBackground' => '#8F1F20',
            'arix:secondaryText' => '#B2B2C1',
            'arix:secondaryBorder' => '#42425B',
            'arix:secondaryBackground' => '#2B2B40',
            'arix:gray50' => '#F4F4F4',
            'arix:gray100' => '#D5D5DB',
            'arix:gray200' => '#B2B2C1',
            'arix:gray300' => '#8282A4',
            'arix:gray400' => '#5E5E7F',
            'arix:gray500' => '#42425B',
            'arix:gray600' => '#2B2B40',
            'arix:gray700' => '#1D1D37',
            'arix:gray800' => '#0B0D2A',
            'arix:gray900' => '#040519',
            'arix:lightmode_primary' => '#4A35CF',
            'arix:lightmode_successText' => '#E1FFD8',
            'arix:lightmode_successBorder' => '#56AA2B',
            'arix:lightmode_successBackground' => '#3D8F1F',
            'arix:lightmode_dangerText' => '#FFD8D8',
            'arix:lightmode_dangerBorder' => '#AA2A2A',
            'arix:lightmode_dangerBackground' => '#8F1F20',
            'arix:lightmode_secondaryText' => '#46464D',
            'arix:lightmode_secondaryBorder' => '#C0C0D3',
            'arix:lightmode_secondaryBackground' => '#A6A7BD',
            'arix:lightmode_gray50' => '#141415',
            'arix:lightmode_gray100' => '#27272C',
            'arix:lightmode_gray200' => '#46464D',
            'arix:lightmode_gray300' => '#626272',
            'arix:lightmode_gray400' => '#757689',
            'arix:lightmode_gray500' => '#A6A7BD',
            'arix:lightmode_gray600' => '#C0C0D3',
            'arix:lightmode_gray700' => '#E7E7EF',
            'arix:lightmode_gray800' => '#F0F1F5',
            'arix:lightmode_gray900' => '#FFFFFF',
            'arix:meta_color' => '#4A35CF',
            'arix:meta_title' => 'Pterodactyl Panel',
            'arix:meta_description' => 'Our official Pterodactyl panel',
            'arix:meta_image' => '/arix/meta-tags.png',
            'arix:meta_favicon' => '/arix/Arix.png',
            'arix:mail_color' => '#4A35CF',
            'arix:mail_backgroundColor' => '#F5F5FF',
            'arix:mail_logo' => 'https://arix.gg/arix.png',
            'arix:mail_logoFull' => 'false',
            'arix:mail_mode' => 'light',
            'arix:mail_discord' => 'https://arix.gg/discord',
            'arix:mail_twitter' => 'https://x.com',
            'arix:mail_facebook' => 'https://facebook.com',
            'arix:mail_instagram' => 'https://instagram.com',
            'arix:mail_linkedin' => 'https://linkedin.com',
            'arix:mail_youtube' => 'https://youtube.com',
            'arix:mail_status' => 'https://arix.gg/status',
            'arix:mail_billing' => 'https://arix.gg/billing',
            'arix:mail_support' => 'https://arix.gg/support',
            'arix:profileType' => 'gravatar',
            'arix:modeToggler' => 'true',
            'arix:langSwitch' => 'true',
            'arix:defaultLang' => 'en',
            'arix:languageOptions' => '[{"key":"en","name":"English"}]',
            'arix:ipFlag' => 'true',
            'arix:lowResourcesAlert' => 'false',
            'arix:alertLink' => '',
            'arix:dashboardPage' => 'true',
            'arix:registration' => 'false',
            'arix:defaultMode' => 'darkmode',
            'arix:copyright' => 'Designed by Weijers.one',
            'arix:socials' => '[]',
            'arix:socialButtons' => 'false',
            'arix:discordBox' => 'true',
        ];

        $seededCount = 0;
        foreach ($defaultSettings as $key => $defaultVal) {
            $existing = $settings->get('settings::' . $key);
            if ($existing === null) {
                $settings->set('settings::' . $key, $defaultVal);
                $seededCount++;
            }
        }

        // Validate dashboard widgets format
        $widgets = $settings->get('settings::arix:dashboardWidgets');
        if (!is_string($widgets) || json_decode($widgets, true) === null) {
            $settings->set('settings::arix:dashboardWidgets', json_encode(['banner', 'statCards', 'graphs', 'infoAdvanced']));
            $this->warn(' -> Repaired invalid dashboardWidgets JSON.');
        }

        // Validate socials format
        $socials = $settings->get('settings::arix:socials');
        if (!is_string($socials) || json_decode($socials, true) === null) {
            $settings->set('settings::arix:socials', '[]');
            $this->warn(' -> Repaired invalid socials JSON.');
        }

        $this->info(" -> Settings verification complete. Seeded {$seededCount} missing settings.");

        // 3. Compile translations if ArixLang command is registered
        if ($this->getApplication()->has('language:compile')) {
            $this->line(' -> Compiling Arix translations...');
            Artisan::call('language:compile');
            $this->info(' -> Translations compiled.');
        }

        // 4. Verify public assets
        $publicArix = public_path('arix');
        if (!File::exists($publicArix)) {
            $this->warn(" -> Notice: {$publicArix} directory not found. Ensure public/arix assets are copied.");
        } else {
            $this->info(' -> Public Arix asset directory verified.');
        }

        // 5. Fix permissions for storage and bootstrap/cache
        $this->line(' -> Ensuring correct permissions on storage and bootstrap/cache...');
        @chmod(storage_path(), 0775);
        @chmod(base_path('bootstrap/cache'), 0775);

        $this->info("\n✅ Arix Theme self-healing & repair finished successfully!");
        return Command::SUCCESS;
    }
}
