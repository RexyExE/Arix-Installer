<?php

namespace Pterodactyl\Http\Controllers\Admin\Arix;

use Illuminate\View\View;
use Prologue\Alerts\AlertsMessageBag;
use Illuminate\View\Factory as ViewFactory;
use Pterodactyl\Http\Controllers\Controller;
use Pterodactyl\Http\Requests\Admin\Arix\ArixStylingRequest;
use Pterodactyl\Contracts\Repository\SettingsRepositoryInterface;

class ArixStylingController extends Controller
{
    public function __construct(
        private AlertsMessageBag $alert,
        private SettingsRepositoryInterface $settings,
        private ViewFactory $view
    ) {}

    public function index(): View
    {
        return $this->view->make('admin.arix.styling', [
            'navbar' => 'styling',
            'sideEditor' => true,
            'pageTitle' => $this->settings->get('settings::arix:pageTitle', true),
            'background' => $this->settings->get('settings::arix:background', true),
            'backgroundImage' => $this->settings->get('settings::arix:backgroundImage', ''),
            'backgroundImageLight' => $this->settings->get('settings::arix:backgroundImageLight', ''),
            'loginBackground' => $this->settings->get('settings::arix:loginBackground', '/arix/background-login.png'),
            'backgroundFaded' => $this->settings->get('settings::arix:backgroundFaded', 'default'),
            'backdrop' => $this->settings->get('settings::arix:backdrop', false),
            'backdropPercentage' => $this->settings->get('settings::arix:backdropPercentage', 100),
            'radiusInput' => $this->settings->get('settings::arix:radiusInput', 7),
            'radiusBox' => $this->settings->get('settings::arix:radiusBox', 10),
            'borderInput' => $this->settings->get('settings::arix:borderInput', true),
            'flashMessage' => $this->settings->get('settings::arix:flashMessage', 1),
            'font' => $this->settings->get('settings::arix:font', 'default'),
            'icon' => $this->settings->get('settings::arix:icon', 'heroicons'),
        ]);
    }

    public function store(ArixStylingRequest $request)
    {
        foreach ($request->normalize() as $key => $value) {
            $this->settings->set('settings::' . $key, $value);
        }
        $this->alert->success('Theme settings have been updated successfully.')->flash();
        return redirect()->route('admin.arix.styling');
    }
}