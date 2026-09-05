<?php

namespace Pterodactyl\Http\Controllers\Admin\Arix;

use Illuminate\View\View;
use Prologue\Alerts\AlertsMessageBag;
use Illuminate\View\Factory as ViewFactory;
use Pterodactyl\Http\Controllers\Controller;
use Pterodactyl\Http\Requests\Admin\Arix\ArixRequest;
use Pterodactyl\Contracts\Repository\SettingsRepositoryInterface;

class ArixController extends Controller
{
    public function __construct(
        private AlertsMessageBag $alert,
        private SettingsRepositoryInterface $settings,
        private ViewFactory $view
    ) {}

    public function index(): View
    {
        return $this->view->make('admin.arix.index', [
            'navbar' => 'index',
            'sideEditor' => false,
            'logo' => $this->settings->get('settings::arix:logo', '/arix/Arix.png'),
            'logoLight' => $this->settings->get('settings::arix:logoLight', '/arix/Arix.png'),
            'fullLogo' => $this->settings->get('settings::arix:fullLogo', false),
            'logoHeight' => $this->settings->get('settings::arix:logoHeight', '32'),
            'discord' => $this->settings->get('settings::arix:discord', '715281172422197300'),
            'support' => $this->settings->get('settings::arix:support', 'https://discord.gg/geCjrRbAwC'),
        ]);
    }

    public function store(ArixRequest $request)
    {
        foreach ($request->normalize() as $key => $value) {
            $this->settings->set('settings::' . $key, $value);
        }
        $this->alert->success('Theme settings have been updated successfully.')->flash();
        return redirect()->route('admin.arix');
    }
}