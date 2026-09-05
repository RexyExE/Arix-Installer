<?php

namespace Pterodactyl\Http\Controllers\Admin\Arix;

use Illuminate\View\View;
use Prologue\Alerts\AlertsMessageBag;
use Illuminate\View\Factory as ViewFactory;
use Pterodactyl\Http\Controllers\Controller;
use Pterodactyl\Http\Requests\Admin\Arix\ArixMailRequest;
use Pterodactyl\Contracts\Repository\SettingsRepositoryInterface;

class ArixMailController extends Controller
{
    public function __construct(
        private AlertsMessageBag $alert,
        private SettingsRepositoryInterface $settings,
        private ViewFactory $view
    ) {}

    public function index(): View
    {
        return $this->view->make('admin.arix.mail', [
            'navbar' => 'mail',
            'sideEditor' => false,
            'mail_color' => $this->settings->get('settings::arix:mail_color', '#4a35cf'),
            'mail_backgroundColor' => $this->settings->get('settings::arix:mail_backgroundColor', '#F5F5FF'),
            'mail_logo' => $this->settings->get('settings::arix:mail_logo', 'https://arix.gg/arix.png'),
            'mail_logoFull' => $this->settings->get('settings::arix:mail_logoFull', false),
            'mail_mode' => $this->settings->get('settings::arix:mail_mode', 'light'),
            'mail_discord' => $this->settings->get('settings::arix:mail_discord', 'https://arix.gg/discord'),
            'mail_twitter' => $this->settings->get('settings::arix:mail_twitter', 'https://x.com'),
            'mail_facebook' => $this->settings->get('settings::arix:mail_facebook', 'https://facebook.com'),
            'mail_instagram' => $this->settings->get('settings::arix:mail_instagram', 'https://instagram.com'),
            'mail_linkedin' => $this->settings->get('settings::arix:mail_linkedin', 'https://linkedin.com'),
            'mail_youtube' => $this->settings->get('settings::arix:mail_youtube', 'https://youtube.com'),
            'mail_status' => $this->settings->get('settings::arix:mail_status', 'https://arix.gg/status'),
            'mail_billing' => $this->settings->get('settings::arix:mail_billing', 'https://arix.gg/billing'),
            'mail_support' => $this->settings->get('settings::arix:mail_support', 'https://arix.gg/support'),
        ]);
    }

    public function store(ArixMailRequest $request)
    {
        foreach ($request->normalize() as $key => $value) {
            $this->settings->set('settings::' . $key, $value);
        }
        $this->alert->success('Theme settings have been updated successfully.')->flash();
        return redirect()->route('admin.arix.mail');
    }
}